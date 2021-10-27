<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/7/11
 * Time: 9:52 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_LogisticsatuanController extends Zend_Controller_Action
{
    private $satuan;
    private $session;


    public function init()
    {
        $this->satuan = new Logistic_Model_LogisticSatuan();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function satuanAction()
    {

    }

    public function allsatuanAction ()
    {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

    public function getsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $search = $this->getRequest()->getParam('search');
        if (!$search)
            $search = null;
        else
            $search = "sat_nama like '%$search%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sat_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = "is_old = 'N'";
//        else
//            $where = "is_old = 'Y'";

        if ($search)
            $search .= " AND " . $where;
        else
            $search = $where;

        $satuandata = $this->satuan->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
        $return['data'] = $satuandata;
        $return['total'] = $this->satuan->fetchAll($search)->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addsatuanAction ()
    {
        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;
    }

    public function insertsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();
        
        $sat_kode = $this->_getParam('sat_kode');
        $sat_name = $this->_getParam('sat_name');
        $sat_desc = $this->_getParam('sat_desc');

        $new_project = ($this->_getParam("new_project") == 'true') ? true : false;
//        if ($new_project)
            $where = " AND is_old = 'N'";
//        else
//            $where = " AND is_old = 'Y'";

        $search = "(sat_kode = '$sat_kode' OR sat_nama = '$sat_name')";

        $cekSatuan = $this->satuan->fetchRow($search . $where);

        if ($cekSatuan)
        {
            $return = array("success" => false, "pesan" => "Sorry, UOM Exist");
        }else
        {
            $insertSatuan = array(
                "sat_kode" => $sat_kode,
                "sat_nama" => $sat_name,
                "ket" => $sat_desc,
//                "is_old" => ($new_project) ? 'N' : 'Y'
                "is_old" => 'N' 
            );

            $this->satuan->insert($insertSatuan);
            $activityHead['master_satuan'][0]=$insertSatuan;
            
            $return = array("success" => true);

        }

        $json = Zend_Json::encode($return);
        
        
        if ($new_project)
        {
             $activityLog = array(
            "menu_name" => "Create Master Unit of Measurement(New Project)",
            "trano" => $sat_kode,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        }else
        {
             $activityLog = array(
            "menu_name" => "Create Master Unit of Measurement",
            "trano" => $sat_kode,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);

        }
        
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);



    }

    public function editsatuanAction ()
    {
  
        $this->_helper->layout->disableLayout();

        $sat_kode = $this->_getParam('satkode');

        $where = "sat_kode = '$sat_kode'";

        $datasatuan = $this->satuan->fetchRow($where);
        $this->view->tampil = $datasatuan;

        $this->view->new_project = ($this->_getParam("new_project") == 'true') ? true : false;


    }

    public function geteditsatuanAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $sat_kode = $this->_getParam('sat_kode');
        $sat_name = $this->_getParam('sat_name');
        $sat_desc = $this->_getParam('sat_desc');
        $sat_id = $this->_getParam('id');

        $old = $this->satuan->fetchAll("id = '$sat_id'")->toArray();
        $log['satuan-detail-before'] = $old;
        
        $UpdateSatuan = array(
            "sat_kode" => $sat_kode,
            "sat_nama" => $sat_name,
            "ket" => $sat_desc
        );

        $this->satuan->update($UpdateSatuan,"id = '$sat_id'");
        
        $new = $this->satuan->fetchAll("id = '$sat_id'")->toArray();
        $log2['satuan-detail-after'] = $new;

        $return = array("success" => true);

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $sat_id,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);
        
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }


}