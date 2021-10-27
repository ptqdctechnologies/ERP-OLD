<?php

class ArfhController extends Zend_Controller_Action
{
    private $db;
    private $DEFAULT;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');

        $models = array(
            "AdvanceRequestFormH"
        );

        $this->DEFAULT = QDC_Model_Default::init($models);
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
//        $arfh = new Default_Models_AdvanceRequestFormH();
//
//        $return['posts'] = $arfh->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
//        $return['count'] = $arfh->fetchAll()->count();
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

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh where tipe = '$columnName' ORDER BY $sort $dir LIMIT $offset,$limit";

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

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh WHERE $columnName LIKE '%$columnValue%' AND tipe = '$tipe' ORDER BY $sort $dir LIMIT $offset,$limit";

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
    
    public function listrequestorAction()
    {
    	$this->_helper->viewRenderer->setNoRender();
    	$offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'petugas';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS a.request AS id,COALESCE((SELECT uid FROM master_login WHERE id=a.request),\'NO NAME\') as petugas FROM procurement_arfh a GROUP BY a.request ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        
        $hasil['posts'] = array();
        $hasil['posts'][0]['id'] = 'NODATA';
        $hasil['posts'][0]['petugas'] = '-------------';
        
        foreach ($return['posts'] as $key => $val)
        {
        	if ($val['petugas'] == '')
        		$return['posts'][$key]['petugas'] = 'NO NAME';
        		
        	$hasil['posts'][] = $return['posts'][$key];	
        }
        
        $hasil['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentlistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh  ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function getpaymentlistbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

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