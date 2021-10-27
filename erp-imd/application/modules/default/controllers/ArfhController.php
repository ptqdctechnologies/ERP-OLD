<?php

class ArfhController extends Zend_Controller_Action {

    private $db;
    private $DEFAULT;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');

        $models = array(
            "AdvanceRequestFormH"
        );

        $this->DEFAULT = QDC_Model_Default::init($models);
    }

    public function indexAction() {
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

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        if ($columnName == 'BRF') {
            $sql = "SELECT trano, DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,
                    prj_kode,prj_nama,sit_kode,sit_nama,trano_ref 
                    from procurement_arfh where trano LIKE 'BRFP%' 
                    UNION
                    SELECT trano_ref as trano, DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,
                    prj_kode,prj_nama,sit_kode,sit_nama, trano as trano_ref from 
                    procurement_brfh where trano LIKE 'BRF%' and totalsequence = 1
                    ORDER BY tgl DESC, trano DESC LIMIT $offset,$limit;";
        } else
            $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh where tipe = '$columnName' AND trano LIKE 'ARF%' ORDER BY tgl DESC,trano DESC LIMIT $offset,$limit";

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
    
    public function rejectedAction(){
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $trano = $request->getParam('trano');
        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $type = $request->getParam('type');
        
        if($trano !='' || $trano !=null){
            $trano = " AND trano LIKE '%$trano%'";
        }
        
        if($type !='' || $type !=null){
            $type = " AND tipe ='$type'";
        }
        
        if($prj_kode !='' || $prj_kode !=null){
            $prj_kode = " AND prj_kode LIKE '%$prj_kode%'";
        }
        
        if($sit_kode !='' || $sit_kode !=null){
            $sit_kode = " AND sit_kode LIKE '%$sit_kode%'";
        }
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,prj_kode,prj_nama,sit_kode,sit_nama 
                FROM procurement_arfh 
                WHERE approve=300 AND trano NOT LIKE '%P%' $trano $type $prj_kode $sit_kode ORDER BY id DESC LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function listbyparamsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $tipe = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        if ($tipe == 'BRFP')
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh WHERE trano_ref  LIKE '%$columnValue%' AND  trano LIKE 'BRFP%' ORDER BY $sort $dir LIMIT $offset,$limit";
        else
        if ($tipe == 'BRF')
            //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh WHERE $columnName LIKE '%$columnValue%' AND  trano LIKE 'BRFP%' ORDER BY $sort $dir LIMIT $offset,$limit";
            $sql = "SELECT trano, DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,
                    prj_kode,prj_nama,sit_kode,sit_nama,trano_ref 
                    from procurement_arfh where $columnName LIKE '%$columnValue%' 
                    AND trano LIKE 'BRFP%' 
                    UNION
                    SELECT trano_ref as trano, DATE_FORMAT(tgl,'%m/%d/%Y') as tgl,
                    prj_kode,prj_nama,sit_kode,sit_nama, trano as trano_ref from 
                    procurement_brfh where $columnName LIKE '%$columnValue%' 
                    AND trano LIKE 'BRF%' and totalsequence = 1
                    ORDER BY $sort $dir LIMIT $offset,$limit;";
        else
            $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfh WHERE $columnName LIKE '%$columnValue%' AND trano LIKE 'ARF%' AND tipe = '$tipe' ORDER BY $sort $dir LIMIT $offset,$limit";

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

    public function listrequestorAction() {
        $this->_helper->viewRenderer->setNoRender();
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'petugas';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS a.request AS id,COALESCE((SELECT uid FROM master_login WHERE id=a.request),\'NO NAME\') as petugas FROM procurement_arfh a GROUP BY a.request ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();

        $hasil['posts'] = array();
        $hasil['posts'][0]['id'] = 'NODATA';
        $hasil['posts'][0]['petugas'] = '-------------';

        foreach ($return['posts'] as $key => $val) {
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

    public function getpaymentlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function getpaymentlistbyparamsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,DATE_FORMAT(tgl,\'%m/%d/%Y\') as tgl,prj_kode,prj_nama,sit_kode,sit_nama FROM procurement_arfh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll()->toArray();
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