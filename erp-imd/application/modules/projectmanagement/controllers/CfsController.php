<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/16/11
 * Time: 2:50 PM
 * To change this template use File | Settings | File Templates.
 */
 
class projectmanagement_CfsController extends Zend_Controller_Action
{

    private $engineerwork;
    private $db;

    public function init()
    {
        $this->cfs = new ProjectManagement_Models_MasterCFS();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
    }

    public function cfsAction ()
    {
        
    }

    public function viewAction ()
    {
        
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'cfs_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
         
        $return['posts'] = $this->cfs->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $this->cfs->fetchAll()->count();
 
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);    

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'cfs_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS cfs_kode,cfs_nama FROM master_cfs_kode WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
   
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
 
    public function getviewcfsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $text = $this->_getParam('search');
        $option = $this->_getParam('option');
        $search = null;

        if ($text == "" && $option == 1)
        {
            $search = null;
        }else if ($text != null && $option == 1)
        {
            $search = "cfs_kode like '%$text%' ";
        }else if ($text != null && $option == 2)
        {
            $search = "cfs_nama like '%$text%' ";
        }else
        {
            $search = null;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $cfsdata = $this->cfs->fetchAll ($search,array($sort . " " . $dir),$limit,$offset)->toArray ();
        $return['data'] = $cfsdata;
        $return['total'] = $this->cfs->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createAction ()
    {
        
    }

    public function getaddcfsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $activitylog2 = new Admin_Models_Activitylog();
        $activityHead=array();

        $cfs_kode = $this->_getParam('cfs_kode');
        $cfs_nama = $this->_getParam('cfs_nama');
        $username = QDC_User_Session::factory()->getCurrentName();

        $where = "cfs_kode = '$cfs_kode' or cfs_nama = '$cfs_nama' ";

        $cekcfs = $this->cfs->fetchRow($where);

        if ($cekcfs)
        {
            $return = array("success" => false, "pesan" => "Sorry, CFS already Exists");
        }else
        {
            $insertCFS = array(
                "cfs_kode" => $cfs_kode,
                "cfs_nama" => $cfs_nama,
                "created_by" => $username       
            );

            $this->cfs->insert($insertCFS);
            $activityHead['master_cfs_kode'][0]=$insertCFS;
            $return = array("success" => true);
            
        }

        $json = Zend_Json::encode($return);
        
        $activityLog = array(
            "menu_name" => "Create CFS Code",
            "trano" => $cfs_kode,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $username,
            "header" => Zend_Json::encode($activityHead),
            "detail" => '',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);
        
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function editAction ()
    {
        
    }
    
    public function deletecfsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        
        $id = $this->_getParam('id');
       
        if ($id == null || $id == "")
        {
            $return = array("success" => false, "pesan" => "Sorry, you must select from grid ");
        }else
        {

            $update = $this->cfs->delete("id=$id");
            
            $return = array("success" =>true);
            
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function geteditcfsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $cfs_kode = $this->_getParam('cfs_kode');
        $cfs_nama = $this->_getParam('cfs_nama');
        $id = $this->_getParam('id');
        $username = QDC_User_Session::factory()->getCurrentName();
        
         

        if ($id == null || $id == "")
        {
            $return = array("success" => false, "pesan" => "Sorry, you must select from grid ");
        }else
        {
//            // fetch data before
        $old = $this->cfs->fetchAll("id = '$id'")->toArray();
        $log['cfs-detail-before'] = $old;
        
            $updatecfs = array(
            "cfs_kode" => $cfs_kode,
            "cfs_nama" => $cfs_nama,
            "updated_by" => $username,
            "updated_on" => date("Y-m-d h:i:sa")    
            );

            $update = $this->cfs->update($updatecfs,"id = '$id' ");
            
            
//        // fetch data after
        $new = $this->cfs->fetchAll("id = '$id'")->toArray();
        $log2['cfs-detail-after'] = $new;
            
            $return = array("success" =>true);
            
        }

//            //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $cfs_kode,
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