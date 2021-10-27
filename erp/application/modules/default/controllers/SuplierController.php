<?php

class SuplierController extends Zend_Controller_Action {

    private $db;
    private $supplier;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
        $this->supplier = new Default_Models_MasterSuplier();
    }

    public function indexAction() {
        // action body
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
//        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sup_nama';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $suplier = new Default_Models_MasterSuplier();

        $return['posts'] = $suplier->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $suplier->fetchAll()->count();

        foreach ($return['posts'] as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $tmp = str_replace("\"", "", $val2);
                $tmp = str_replace("'", "", $tmp);
                $tmp = str_replace("\r", "", $tmp);
                $tmp = str_replace("\n", "", $tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listallAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sup_nama';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

//        $suplier = new Default_Models_MasterSuplier();
//
//        $return['posts'] = $suplier->fetchAll(null, array($sort . ' ' . $dir))->toArray();
//        $return['count'] = $suplier->fetchAll()->count();

        $sql = "SELECT * FROM master_suplier WHERE aktif = 'Y' Order By sup_nama ASC";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        foreach ($return['posts'] as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $tmp = str_replace("\"", "", $val2);
                $tmp = str_replace("'", "", $tmp);
                $tmp = str_replace("\r", "", $tmp);
                $tmp = str_replace("\n", "", $tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listfilterAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');
//        $suplier = new Default_Models_MasterSuplier();
//
//        $return['posts'] = $suplier->fetchAll(null, array($sort . ' ' . $dir))->toArray();
//        $return['count'] = $suplier->fetchAll()->count();
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sup_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM master_suplier WHERE aktif = 'Y' Order By $sort $dir LIMIT $offset,$limit";

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        foreach ($return['posts'] as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $tmp = str_replace("\"", "", $val2);
                $tmp = str_replace("'", "", $tmp);
                $tmp = str_replace("\r", "", $tmp);
                $tmp = str_replace("\n", "", $tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }

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
        $filter = $request->getParam('filter');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sup_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($filter == true) {
            $sql = "SELECT SQL_CALC_FOUND_ROWS sup_kode,sup_nama FROM master_suplier WHERE aktif = 'Y' AND $columnName LIKE '%$columnValue%' ORDER BY $sort $dir LIMIT $offset,$limit";
        } else {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sup_kode,sup_nama FROM master_suplier WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;
        }

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        foreach ($return['posts'] as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $tmp = str_replace("\"", "", $val2);
                $tmp = str_replace("'", "", $tmp);
                $tmp = str_replace("\r", "", $tmp);
                $tmp = str_replace("\n", "", $tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsnewAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $filter = $request->getParam('filter');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'sup_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($filter == true) {
            $sql = "SELECT SQL_CALC_FOUND_ROWS sup_kode,sup_nama FROM master_suplier WHERE aktif = 'Y' AND $columnName LIKE '%$columnValue%' ORDER BY $sort $dir LIMIT $offset,$limit";
        } else {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS sup_kode,sup_nama FROM master_suplier WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;
        }

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        foreach ($return['posts'] as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $tmp = str_replace("\"", "", $val2);
                $tmp = str_replace("'", "", $tmp);
                $tmp = str_replace("\r", "", $tmp);
                $tmp = str_replace("\n", "", $tmp);
                $return['posts'][$key][$key2] = $tmp;
            }
        }
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function listjenisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $sql = "SELECT jenissupliernama FROM master_jenissupler";
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = count($return['posts']);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listspecAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $sql = "SELECT subjenissupliernama FROM master_jenissupliersub";
        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = count($return['posts']);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsupplierAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch != null || $textsearch != '') {
            $search = "$option LIKE '%$textsearch%'";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'date';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $data['data'] = $this->supplier->fetchAll($search, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->supplier->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getSupplierDetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $supKode = $this->_getParam("sup_kode");
        $bpv = $this->_getParam("bpv_trano");

        $select = $this->db->select()
                ->from(array($this->supplier->__name()), array(
            "bank_name" => new Zend_Db_Expr("REPLACE(namabank,'\"\"','')"),
            "bank_no" => new Zend_Db_Expr("REPLACE(rekbank,'\"\"','')"),
            "bank_account_name" => new Zend_Db_Expr("REPLACE(reknamabank,'\"\"','')"),
            "supplier_name" => "sup_nama",
            "sup_kode",
            "bi_code",
            "bank_city",
            "bank_branch",
            "id"
        ));

        if ($supKode)
            $select = $select->where("sup_kode=?", $supKode);
        if ($bpv) {
            $b = new Finance_Models_BankPaymentVoucher();
            $cek = $b->fetchRow("trano = '$bpv'");
            if ($cek) {
                $cek = $cek->toArray();
                $rpi = $cek['ref_number'];
                $r = new Default_Models_RequestPaymentInvoiceH();
                $rpis = $r->fetchRow("trano = '$rpi'");
                if ($rpis) {
                    $rpis = $rpis->toArray();
                    $supKode = $rpis['sup_kode'];
                    $select = $select->where("sup_kode=?", $supKode);
                }
            }
        }

        $cek = $this->db->fetchRow($select);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($cek));
    }

    public function updateSupplierDetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->_getParam("id");
        $supKode = $this->_getParam("sup_kode");

        $success = false;
        if ($this->supplier->isExist($supKode)) {
            $this->supplier->update(
                    array(
                "namabank" => $this->_getParam("bank_name"),
                "rekbank" => $this->_getParam("bank_no"),
                "reknamabank" => $this->_getParam("bank_account_name"),
                "bi_code" => $this->_getParam("bank_bi_code"),
                "bank_branch" => $this->_getParam("bank_branch"),
                "bank_city" => $this->_getParam("bank_city"),
                    ), "id=$id"
            );
            $success = !$success;
        } else
            $msg = "Supplier not found";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => $success,
                    "msg" => $msg
        )));
    }

}

?>