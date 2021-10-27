<?php

class PohController extends Zend_Controller_Action
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
//        $poh = new Default_Models_ProcurementPoh();
//
//        $return['posts'] = $poh->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//        $return['count'] = $poh->fetchAll()->count();
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
        $totalpo = $request->getParam('totalpo');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if($columnName)
            $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,pr_no,prj_kode,prj_nama,sit_kode,sit_nama,sup_kode,sup_nama FROM procurement_poh where tipe = '$columnName' ORDER BY $sort $dir LIMIT $offset, $limit";
        else
            $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,pr_no,prj_kode,prj_nama,sit_kode,sit_nama,sup_kode,sup_nama FROM procurement_poh ORDER BY $sort $dir LIMIT $offset, $limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        if ($totalpo)
        {
            foreach($return['posts'] as $k => $v)
            {
                $trano = $v['trano'];
                $sql = "SELECT val_kode, SUM(qty*harga) as total FROM procurement_pod WHERE trano = '$trano' GROUP BY trano";
                $fetch2 = $this->db->query($sql);
                $fetch2 = $fetch2->fetch();
                $return['posts'][$k]['totalpo'] = number_format($fetch2['total'],2);
            }
        }
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
        $totalpo = $request->getParam('totalpo');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $groupby = $request->getParam('groupby');
        $site = $request->getParam('site');
        if ($site)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode, sit_kode ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        elseif ($groupby)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        elseif($tipe)
        	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE $columnName LIKE '%$columnValue%' AND tipe = '$tipe' ORDER BY $sort $dir LIMIT $offset,$limit";
        else
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE $columnName LIKE '%$columnValue%' ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        if ($totalpo)
        {
            foreach($return['posts'] as $k => $v)
            {
                $trano = $v['trano'];
                $sql = "SELECT val_kode, SUM(qty*harga) as total FROM procurement_pod WHERE trano = '$trano' GROUP BY trano";
                $fetch2 = $this->db->query($sql);
                $fetch2 = $fetch2->fetch();
                $return['posts'][$k]['totalpo'] = number_format($fetch2['total'],2);
            }
        }
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function listbyparamsnewAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $tipe = $request->getParam('type');
        $totalpo = $request->getParam('totalpo');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $groupby = $request->getParam('groupby');
        $site = $request->getParam('site');
        if ($site)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode, sit_kode ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        elseif ($groupby)
        	$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' GROUP BY trano,prj_kode ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;
        elseif($tipe)
        	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE $columnName LIKE '%$columnValue%' AND tipe = '$tipe' ORDER BY $sort $dir LIMIT $offset,$limit";
        else
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE $columnName LIKE '%$columnValue%' ORDER BY $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        if ($totalpo)
        {
            foreach($return['posts'] as $k => $v)
            {
                $trano = $v['trano'];
                $sql = "SELECT val_kode, SUM(qty*harga) as total FROM procurement_pod WHERE trano = '$trano' GROUP BY trano";
                $fetch2 = $this->db->query($sql);
                $fetch2 = $fetch2->fetch();
                $return['posts'][$k]['totalpo'] = number_format($fetch2['total'],2);
            }
        }
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}

?>