<?php

class RpihController extends Zend_Controller_Action
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
//        $rpih = new Default_Models_RequestPaymentInvoiceH();
//
//        $return['posts'] = $rpih->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//        $return['count'] = $rpih->fetchAll()->count();
//
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
        if ($columnName != '')
            $where = "WHERE tipe = '$columnName'";
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%d/%m/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama,po_no,sup_kode,sup_nama,total,totalpo, invoice_no FROM procurement_rpih $where  ORDER BY $sort $dir LIMIT $offset , $limit";
        
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

        if ($tipe == '')
            $tipe = 'P';
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%d/%m/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama,po_no,sup_kode,sup_nama,total,totalpo, invoice_no FROM procurement_rpih WHERE $columnName LIKE '%$columnValue%' AND tipe = '$tipe' ORDER BY $sort $dir LIMIT $offset,$limit";

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
    
	public function listrpidAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $po_no = $request->getParam('po_no');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_rpid WHERE po_no = '$po_no' ORDER BY " . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function getpaymentlistAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->getRequest()->getParam('trano');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $prj_nama = $this->getRequest()->getParam('prj_nama');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $sit_nama = $this->getRequest()->getParam('sit_nama');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $where = '';

        if ($trano != '')
            $where = "trano LIKE '%$trano%'";
        if ($prj_kode != '')
            $where = "prj_kode LIKE '%$prj_kode%'";
        if ($sit_kode != '')
            $where = "sit_kode LIKE '%$sit_kode%'";
        if ($prj_nama != '')
            $where = "prj_nama LIKE '%$prj_nama%'";
        if ($sit_nama != '')
            $where = "sit_nama LIKE '%$sit_nama%'";

        if ($where != '')
            $where = "WHERE " . $where;

        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\"%m/%d/%Y\") as tgl,prj_kode,prj_nama,sit_kode,sit_nama,po_no,sup_kode,sup_nama,total,totalpo FROM procurement_rpih $where ORDER BY " . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
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