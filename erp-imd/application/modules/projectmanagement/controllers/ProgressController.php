<?php
class ProjectManagement_ProgressController extends Zend_Controller_Action
{
    private $db;
    private $session;
    private $project;
    private $site;
    private $progress;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->project = new Default_Models_MasterProject();
        $this->site = new Default_Models_MasterSite();
        $this->progress = new ProjectManagement_Models_ProjectProgress();
    }

    public function progressAction()
    {
        
    }

    public function addprogressAction()
    {
        
    }

    public function editprogressAction()
    {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getrequest()->getParam("sit_kode");

   		$prog = $this->progress->getLastForEdit($prjKode,$sitKode);

//        $json = Zend_Json::encode($prog);
//
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        $this->getResponse()->setBody($json);

        $this->view->prjKode = $prog['prj_kode'];
        $this->view->prjNama = $prog['prj_nama'];
        $this->view->sitKode = $prog['sit_kode'];
        $this->view->sitNama = $prog['sit_nama'];
        $this->view->prjKode = $prog['prj_kode'];
        $this->view->progress = $prog['progress'];
        $this->view->ket = $prog['ket'];
        $this->view->date = $prog['date'];

    }

    public function getprojectprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');

        $result = $this->progress->getSumProjectProgress($prj_kode);

        $result = number_format($result,2);

        $return = array('success' => true,'progress' => $result);
		$json = Zend_Json::encode($return);
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function getallsiteprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');

        $result = $this->progress->getAllSiteProgress($prj_kode);

        $return = array('success' => true,'posts' => $result);
		$json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsiteprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $date = $this->getRequest()->getParam('date');

        if($date)
        {
            $result = $this->progress->getSumSiteProgressForEdit($prj_kode,$sit_kode,$date);
        }
        else
        {
            $progress = $this->progress->getSumSiteProgress($prj_kode,$sit_kode);
            $result = $progress['last_progress'];
        }

        $return = array('success' => true,'progress' => $result);
		$json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdetailsiteprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $date = $this->getRequest()->getParam('date');

        if($date)
        {
            $result = $this->progress->getSiteProgressForEdit($prj_kode,$sit_kode,$date);
        }
        else
        {
            $result = $this->progress->getSiteProgress($prj_kode,$sit_kode);
        }
        $return = array('success' => true,'posts' => $result);
		$json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $prjKode = $jsonData2[0]['prj_kode'];

        $success = false;
        $msg = '';
        if ($this->progress->isAllowed($prjKode))
        {
            $arrayInsert = array (
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "progress" => $jsonData2[0]['cur_progress'],
                "tgl_progress" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
                "date" => date('Y-m-d H:i:s'),
                "uid" => QDC_User_Session::factory()->getCurrentUID()

            );

            $this->progress->insert($arrayInsert);
            $activityHead['projectmanagement_project_progress'][0]=$arrayInsert;
            $success = true;
        }
        else
        {
            $msg = "You are not allowed to submit Project Progress.";
        }

         $activityLog = array(
            "menu_name" => "Create Project Progress",
            "trano" => $prjKode,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData2[0]['prj_kode'],
            "sit_kode" => $jsonData2[0]['sit_kode'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode(array("success" => $success, "msg" => $msg)));
    }

    public function updateprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $id = $jsonData2[0]['id'];
        $prjKode = $jsonData2[0]['prj_kode'];
        $sitKode = $jsonData2[0]['prj_kode'];

        $success = false;
        $msg = '';
        if ($this->progress->isAllowed($prjKode))
        {
            $arrayInsert = array (
                "progress" => $jsonData2[0]['progress'],
                "tgl_progress" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
                "date" => date('Y-m-d H:i:s'),
                "uid" => $this->session->userName
            );

            $this->progress->update($arrayInsert, "id = '$id' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
            $success = true;
        }
        else
        {
            $msg = "You are not allowed to submit Project Progress.";
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode(array("success" => $success, "msg" => $msg)));
    }

    public function getLastProgressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $progress = $this->progress->getSumSiteProgress($prj_kode,$sit_kode);
        $result = $progress['last_progress'];
        $date = $progress['date'];

        $totalDays = 0;
        if ($date != '')
        {
            $now = new DateTime();
            $last = new DateTime($date);
            $diff = $now->diff($last);
            $totalDays = intval($diff->format('%a'));
            $date = date("d M Y",$date);
        }

        $return = array('success' => true,'progress' => $result, 'diff' => $totalDays, 'last_date' => $date);
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
?>