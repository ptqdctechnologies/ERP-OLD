<?php

class Finance_ApController extends Zend_Controller_Action {

    private $FINANCE;
    private $ADMIN;
    private $db;

    public function init() {
        $this->db = Zend_Registry::get("db");
        $array = array(
            "AccountingCloseAP",
            "AccountingJurnalBank"
        );
        $this->FINANCE = QDC_Model_Finance::init($array);

        $array = array(
            "Logtransaction"
        );
        $this->ADMIN = QDC_Model_Admin::init($array);
    }

    public function indexAction() {
        
    }

    public function editApNumberAction() {
        
    }

    public function getApAction() {
        $this->_helper->viewRenderer->setNoRender();
        $ap = $this->_getParam("ap_number");

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                ->where("ref_number_accounting = ?", $ap);

        $data = $this->db->fetchAll($select);
        $result['success'] = false;
        if ($data) {
            $result['success'] = true;
            $result['data'] = $data;
            $result['total'] = count($data);
        } else {
            $result['msg'] = "Journal Not Found, Reason : no AP Number match.";
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function updateApNumberAction() {
        $this->_helper->viewRenderer->setNoRender();
        $ap = $this->_getParam("ap_number");
        $newAp = $this->_getParam("ap_number_new");

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                ->where("ref_number_accounting = ?", $ap);

        $data = $this->db->fetchAll($select);
        $result['success'] = false;
        if ($data) {
            $select = $this->db->select()
                    ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                    ->where("ref_number_accounting = ?", $newAp);

            $cek = $this->db->fetchAll($select);
            if (!$cek) {
                $result['success'] = true;
                $arrayInsert = array(
                    "ref_number_accounting" => $newAp
                );

                $log['apnumber-detail-before'] = $data;

                $this->FINANCE->AccountingCloseAP->update($arrayInsert, "ref_number_accounting='$ap'");
                $select = $this->db->select()
                        ->from(array($this->FINANCE->AccountingCloseAP->__name()))
                        ->where("ref_number_accounting = ?", $newAp);
                $data = $this->db->fetchAll($select);

                $log2['apnumber-detail-after'] = $data;

                $logs = new Admin_Models_Logtransaction();
                $jsonLog = Zend_Json::encode($log);
                $jsonLog2 = Zend_Json::encode($log2);
                $arrayLog = array(
                    "trano" => $ap,
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "tgl" => date('Y-m-d H:i:s'),
                    "prj_kode" => '',
                    "action" => "UPDATE",
                    "data_before" => $jsonLog,
                    "data_after" => $jsonLog2,
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
                );
                $logs->insert($arrayLog);
            } else
                $result['msg'] = "AP Number already <b>Exist</b> on Database, Choose another one.";
        }
        else {
            $result['msg'] = "Journal Not Found, Reason : no AP Number match.";
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function editApJurnalAction() {
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $this->view->rateidr = $rate['rateidr'];
    }

    public function doUpdateApJurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = Zend_Json::decode($this->getRequest()->getParam('data'));
        $trano = $this->_getParam("trano");
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $ap_number = $this->_getParam("ap_number");
        $uid = QDC_User_Session::factory()->getCurrentID();
        $before = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $before = $before->toArray();
            $log['bankout-detail-before'] = $before;
            $this->FINANCE->AccountingCloseAP->delete("trano = '$trano'");
            foreach ($data as $key => $val) {
                $rateidr = $val['rateidr'];
                $insert = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "rateidr" => floatval($rateidr),
                    "ref_number_accounting" => $ap_number,
                    "tgl_ref_number_accounting" => $val['tgl_ref_number_accounting'],
                    "stspost" => $val['stspost'],
                    "tglpost" => $val['tglpost'],
                    "uidpost" => $val['uidpost'],
                    "stsclose" => $val['stsclose'],
                    "tglclose" => $val['tglclose'],
                    "uidclose" => $val['uidclose'],
                    "memo" => $val['memo'],
                    "memo_id" => $val['memo_id'],
                );

                $this->FINANCE->AccountingCloseAP->insert($insert);
            }
            $log2 = $this->FINANCE->AccountingCloseAP->fetchAll("trano = '$trano'")->toArray();

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
            $this->getResponse()->setBody("{success: true}");
        }
    }

    public function editBankJurnalAction() {
        
    }

    public function doUpdateApBankJurnalAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = Zend_Json::decode($this->getRequest()->getParam('data'));
        $trano = $this->_getParam("trano");
        $newrate = $this->_getParam("rateidr");
        $tranoType = $this->_getParam("tranoType");
        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d H:i:s", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');

        $uid = QDC_User_Session::factory()->getCurrentID();
        $before = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $before = $before->toArray();
            $log['bankout-detail-before'] = $before;
            $this->FINANCE->AccountingJurnalBank->delete("trano = '$trano'");
            foreach ($data as $key => $val) {
                $rateidr = $val['rateidr'];
                $insert = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val['val_kode'],
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "rateidr" => floatval($rateidr),
                    "stspost" => $val['stspost'],
                    "tglpost" => $val['tglpost'],
                    "uidpost" => $val['uidpost'],
                    "stsclose" => $val['stsclose'],
                    "tglclose" => $val['tglclose'],
                    "uidclose" => $val['uidclose'],
                    "memo" => $val['memo'],
                    "memo_id" => $val['memo_id'],
                );

                $this->FINANCE->AccountingJurnalBank->insert($insert);
            }
            $log2 = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano'")->toArray();


            /*
             * update payment rpi rate
             */
            if ($tranoType == 'RPI') {
                $paymentModel = new Finance_Models_PaymentRPI();
                $paymentModel->updateRatePayment(array(
                    "trano" => $trano,
                    "rate" => $newrate,
                ));
            }


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
            $this->getResponse()->setBody("{success: true}");
        }
    }

}

?>