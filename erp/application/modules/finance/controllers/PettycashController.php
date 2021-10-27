<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/16/11
 * Time: 3:05 PM
 * To change this template use File | Settings | File Templates.
 */
class Finance_PettycashController extends Zend_Controller_Action {

    private $pettycashin;
    private $pettycashout;
    private $counter;
    private $db;
    public $FINANCE;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->pettycashin = new Finance_Models_PettyCashIn();
        $this->pettycashout = new Finance_Models_PettyCashOut();
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');

        $this->FINANCE = QDC_Model_Finance::init(array(
                    "PettyCashIn",
                    "PettyCashOut",
        ));
    }

    public function menuAction() {
        
    }

    public function insertpettycashinAction() {
        
    }

    public function doinsertpettycashinAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pettycashindata = Zend_Json::decode($this->getRequest()->getParam('pettycashindata'));

//        $trano = $this->counter->setNewTrans('PRM');
        $trano = $this->counter->setNewTrans('PC');
        $uid = $this->session->userName;
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));


        foreach ($pettycashindata as $key => $val) {
            $insertpettycashin = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode']
            );

            $this->pettycashin->insert($insertpettycashin);
        }

        $this->getResponse()->setBody("{success: true, number: '$trano'}");
    }

    public function insertpettycashoutAction() {
        
    }

    public function doinsertpettycashoutAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $pettycashoutdata = Zend_Json::decode($this->getRequest()->getParam('pettycashoutdata'));

//        var_dump($pettycashoutdata);die;
//        $trano = $this->counter->setNewTrans('PSM');
        $trano = $this->counter->setNewTrans('PC');
        $uid = $this->session->userName;
        $tgl = ($this->_getParam("tgl") == '') ? date('Y-m-d H:i:s') : date("Y-m-d", strtotime($this->_getParam("tgl")));


        foreach ($pettycashoutdata as $key => $val) {
            $insertpettycashout = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode']
            );

            $this->pettycashout->insert($insertpettycashout);
        }

        $this->getResponse()->setBody("{success: true, number: '$trano'}");
    }

    public function editPettycashInAction() {
        
    }

    public function editPettycashOutAction() {
        
    }

    public function getJurnalTranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $trano = $this->_getParam("trano");
        $ref_number = $this->_getParam("ref_number");
        $type = $this->_getParam("jurnal_type");
        $order = ($this->_getParam("order_by") == '') ? "tgl DESC" : $this->_getParam("order_by");

        if ($type == "PCIN")
            $name = $this->FINANCE->PettyCashIn->__name();
        else
            $name = $this->FINANCE->PettyCashOut->__name();

        $select = $this->db->select()
                ->from(array($name), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                    "tgl",
                    "uid",
                    "debit" => "SUM(debit)",
                    "credit" => "SUM(credit)",
                    "ref_number"
                ))
                ->order(array($order))
                ->group(array("trano"))
                ->limit($limit, $offset);

        if ($trano) {
            $select = $select->where("trano LIKE '%$trano%'");
        }

        if ($ref_number) {
            $select = $select->where("ref_number LIKE '%$ref_number%'");
        }

        $data = $this->db->fetchAll($select);
        $counter = new Default_Models_MasterCounter();
        foreach ($data as $k => $v) {
            $tranoData = $v['trano'];
            $type = $counter->getTransTypeFlip($tranoData);
            $data[$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
        }

        $result['data'] = $data;
        $result['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        $result['count'] = $result['total'];

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getDetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $type = $this->_getParam("jurnal_type");

        if ($type == "PCIN")
            $name = $this->FINANCE->PettyCashIn->__name();
        elseif ($type == "PCOUT")
            $name = $this->FINANCE->PettyCashOut->__name();

        $select = $this->db->select()
                ->from(array($name))
                ->where("trano=?", $trano);

        $data = $this->db->fetchAll($select);
        $tgl = $data[0]['tgl'];

        $json = Zend_Json::encode(array("data" => $data, "success" => true, "tgl" => date("d M Y", strtotime($tgl))));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doUpdatePettycashInAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $pettycashindata = Zend_Json::decode($this->getRequest()->getParam('pettycashindata'));
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');


        $cek = $this->FINANCE->PettyCashIn->fetchAll("trano='$trano'");
        if (!$cek) {
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
            die;
        }
        $log['pettycash-in-detail-before'] = $cek->toArray();

        $cek = $this->FINANCE->PettyCashIn->delete("trano='$trano'");

        foreach ($pettycashindata as $key => $val) {
            $insertpettycashin = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode']
            );

            $this->FINANCE->PettyCashIn->insert($insertpettycashin);
        }

        $cek = $this->FINANCE->PettyCashIn->fetchAll("trano='$trano'");
        $log2['pettycash-in-detail-after'] = $cek->toArray();
        $arrayLog = array(
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "action" => "UPDATE",
            "data_before" => Zend_Json::encode($log),
            "data_after" => Zend_Json::encode($log2),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $log = new Admin_Models_Logtransaction();
        $log->insert($arrayLog);

        $this->getResponse()->setBody("{success: true, number: '$trano'}");
    }

    public function doUpdatePettycashOutAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $pettycashindata = Zend_Json::decode($this->getRequest()->getParam('pettycashoutdata'));
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $cek = $this->FINANCE->PettyCashOut->fetchAll("trano='$trano'");
        if (!$cek) {
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
            die;
        }
        $log['pettycash-out-detail-before'] = $cek->toArray();

        $cek = $this->FINANCE->PettyCashOut->delete("trano='$trano'");

        foreach ($pettycashindata as $key => $val) {
            $insertpettycashin = array(
                "trano" => $trano,
                "ref_number" => $val['ref_number'],
                "tgl" => $tgl,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "coa_kode" => $val['coa_kode'],
                "coa_nama" => $val['coa_nama'],
                "val_kode" => $val['val_kode'],
                "debit" => floatval($val['debit']),
                "credit" => floatval($val['credit']),
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode']
            );

            $this->FINANCE->PettyCashOut->insert($insertpettycashin);
        }

        $cek = $this->FINANCE->PettyCashOut->fetchAll("trano='$trano'");
        $log2['pettycash-out-detail-after'] = $cek->toArray();
        $arrayLog = array(
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "action" => "UPDATE",
            "data_before" => Zend_Json::encode($log),
            "data_after" => Zend_Json::encode($log2),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $log = new Admin_Models_Logtransaction();
        $log->insert($arrayLog);

        $this->getResponse()->setBody("{success: true, number: '$trano'}");
    }

}
