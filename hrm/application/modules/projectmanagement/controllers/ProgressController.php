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
            $result = $this->progress->getSumSiteProgress($prj_kode,$sit_kode);
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
       Zend_Loader::loadClass('Zend_Json');
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        	$arrayInsert = array (
                "prj_kode" => $jsonData2[0]['prj_kode'],
                "prj_nama" => $jsonData2[0]['prj_nama'],
                "sit_kode" => $jsonData2[0]['sit_kode'],
                "sit_nama" => $jsonData2[0]['sit_nama'],
                "progress" => $jsonData2[0]['cur_progress'],
                "tgl_progress" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
                "date" => date('Y-m-d H:i:s'),
                "uid" => $this->session->userName

            );

//            var_dump($arrayInsert);die();
            $this->progress->insert($arrayInsert);
//	        $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
    }

    public function updateprogressAction()
    {
        $this->_helper->viewRenderer->setNoRender();
       Zend_Loader::loadClass('Zend_Json');
       $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $id = $jsonData2[0]['id'];
        $prjKode = $jsonData2[0]['prj_kode'];
        $sitKode = $jsonData2[0]['prj_kode'];

        	$arrayInsert = array (
//                "prj_kode" => $jsonData2[0]['prj_kode'],
//                "prj_nama" => $jsonData2[0]['prj_nama'],
//                "sit_kode" => $jsonData2[0]['sit_kode'],
//                "sit_nama" => $jsonData2[0]['sit_nama'],
                "progress" => $jsonData2[0]['progress'],
                "tgl_progress" => date('Y-m-d'),
                "ket" => $jsonData2[0]['ket'],
                "date" => date('Y-m-d H:i:s'),
                "uid" => $this->session->userName

            );

//            var_dump($arrayInsert);die();
            $this->progress->update($arrayInsert, "id = '$id' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
//	        $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
       	    $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody("{success: true}");
    }

}
?>