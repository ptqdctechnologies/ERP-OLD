<?php

class PrhController extends Zend_Controller_Action
{
    private $db;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
    }

    public function indexAction()
    {
        // action body
    }

//    public function listAction()
//    {
//        $this->_helper->viewRenderer->setNoRender();
//        $request = $this->getRequest();
//
//        $listType = $request->getParam('type');
//
//        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
//        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
//        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
//
//        $prh = new Default_Models_ProcurementRequestH();
//
//        $return['posts'] = $prh->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//        $return['count'] = $prh->fetchAll()->count();
//        //the posts
//        Zend_Loader::loadClass('Zend_Json');
//        $json = Zend_Json::encode($return);
//
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        $this->getResponse()->setBody($json);
//    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');
        
        $cekWorkflow = $request->getParam("cekworkflow");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($columnName != '')
            $where = " where tipe = '$columnName'";
        if ($cekWorkflow)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS a.trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,a.prj_kode,a.prj_nama,a.sit_kode,a.sit_nama,a.budgettype FROM procurement_prh a LEFT JOIN workflow_trans b ON a.trano = b.item_id WHERE b.approve IN (300,400) OR b.approve is null GROUP BY a.trano ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        else
        	$sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama,budgettype FROM procurement_prh $where  ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $tipe = $request->getParam('type');

        if($tipe != '')
            $where = " AND tipe = '$tipe'";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prh WHERE $columnName LIKE '%$columnValue%' $where ORDER BY $sort $dir LIMIT $offset,$limit";
        
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>