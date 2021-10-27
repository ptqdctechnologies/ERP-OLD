<?php

class Finance_PaymentController extends Zend_Controller_Action {

    private $db;
//    private $COA;
    private $session;
    private $paymentRPI;
    private $paymentRPId;
    private $paymentARF;
    private $paymentARFd;
    private $paymentREM;
    private $paymentREMd;
    private $paymentPPNREM;
    private $paymentPPNREMd;
    private $settled;
    private $settledDetail;
    private $settledCancel;
    private $settledCancelDetail;
//    private $accBankPayment;
    private $voucher;
    private $coa_bank;
    private $jurnal_bank;
    private $coa;
    private $FINANCE;
    private $procurementpo;
    private $paymentPO;
    private $paymentPOD;
    private $DEFAULT;
    private $paymentPBOQ3;
    private $paymentPBOQ3d;
    
    
    public function init() {
        $this->db = Zend_Registry::get('db');
//        $this->COA = Zend_Registry::get('const_coa');
        $this->session = new Zend_Session_Namespace('login');
        $this->paymentRPI = new Finance_Models_PaymentRPI();
        $this->paymentRPId = new Finance_Models_PaymentRPID();
        $this->paymentARF = new Finance_Models_PaymentARF();
        $this->paymentARFd = new Finance_Models_PaymentARFD();
        $this->paymentREM = new Finance_Models_PaymentReimbursH();
        $this->paymentREMd = new Finance_Models_PaymentReimbursD();
        $this->paymentPPNREM = new Finance_Models_PaymentPpnReimbursementH();
        $this->paymentPPNREMd = new Finance_Models_PaymentPpnReimbursementD();
        $this->settled = new Finance_Models_Settled();
        $this->settledDetail = new Finance_Models_SettledDetail();
        $this->settledCancel = new Finance_Models_SettledCancel();
        $this->settledCancelDetail = new Finance_Models_SettledCancelDetail();
        $this->voucher = new Finance_Models_BankPaymentVoucher();
        $this->voucherD = new Finance_Models_BankPaymentVoucherD();
        $this->coa_bank = new Finance_Models_MasterCoaBank();
        $this->jurnal_bank = new Finance_Models_AccountingJurnalBank();
        $this->coa = new Finance_Models_MasterCoa();
        $this->procurementpo = new Default_Models_ProcurementPoh();
        $this->paymentPO = new Finance_Models_PaymentPO();
        $this->paymentPOD = new Finance_Models_PaymentPOD();
        $this->paymentPBOQ3 = new Finance_Models_PaymentPBOQ3();
        $this->paymentPBOQ3d = new Finance_Models_PaymentPBOQ3d();

        $models = array(
            "ArfAging"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
        
        $models = array(
            "Files"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);


//        $this->accBankPayment = new Finance_Models_AccountingBankPayment();
    }

    public function paymentpoAction() {
        
    }
    public function paymentrpiAction() {
        
    }
    
    public function payrpiAction() {
        $this->_helper->viewRenderer->setNoRender();

        $rpiH = new Default_Models_RequestPaymentInvoiceH();

//    	$type = $this->getRequest()->getParam("type");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);


        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $rpi = $rpiH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }


        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'invoice_no' => $jsonEtc[0]['invoice_no'],
                'total' => $total,
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'total_bayar2' => $jsonEtc[0]['total_bayar2'],
                'jenis_document' => 'RPI',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->paymentRPId->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $rpi['prj_kode'],
            'prj_nama' => $rpi['prj_nama'],
            'sit_kode' => $rpi['sit_kode'],
            'sit_nama' => $rpi['sit_nama'],
            'uid' => $uid,
            'invoice_no' => $jsonEtc[0]['invoice_no'],
            'total' => $rpi['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'total_bayar2' => $jsonEtc[0]['total_bayar2'],
            'jenis_document' => 'RPI',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $jsonEtc[0]['doc_trano'],
            'stspayment' => 'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );
        $this->paymentRPI->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepayrpiAction() {
        $this->_helper->viewRenderer->setNoRender();

        $rpiH = new Default_Models_RequestPaymentInvoiceH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;
        $rpi = $rpiH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->paymentRPId->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'invoice_no' => $jsonEtc[0]['invoice_no'],
                'total' => $val['total'],
                'total_bayar2' => $jsonEtc[0]['total_bayar2'],
                'jenis_document' => 'RPI',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->paymentRPId->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $rpi['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'total_bayar2' => $jsonEtc[0]['total_bayar2'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );


        $fetch = $this->paymentRPI->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepayarfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $arfH = new Default_Models_AdvanceRequestFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $arf = $arfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->paymentARFd->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }

            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $val['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ARF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'pic' => $jsonEtc[0]['pic'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
            );
            $urut++;

            $this->paymentARFd->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $arf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'pic' => $jsonEtc[0]['pic'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
//            'coa_kode' => $coa_kode,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal']
        );


        $fetch = $this->paymentARF->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function paymentarfAction() {
        
    }

    public function payarfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $arfH = new Default_Models_AdvanceRequestFormH();

//    	$type = $this->getRequest()->getParam("type");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] == 'USD')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;
        $arf = $arfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }

            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $total,
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ARF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'pic' => $jsonEtc[0]['pic'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $jsonEtc[0]['doc_trano'],
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
            );
            $urut++;

            $this->paymentARFd->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $arf['prj_kode'],
            'prj_nama' => $arf['prj_nama'],
            'sit_kode' => $arf['sit_kode'],
            'sit_nama' => $arf['sit_nama'],
            'uid' => $uid,
            'total' => $arf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ARF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'pic' => $jsonEtc[0]['pic'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => 'Y',
//            'coa_kode' => $coa_kode,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal']
        );

        $fetch = $this->paymentARF->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function settledasfAction() {
        
    }

    public function settledcancelasfAction() {
        
    }

    public function setasfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$doc_trano = $this->getRequest()->getParam("doc_trano");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//        $arf_no = $this->getRequest()->getParam("arf_no");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ASF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => 'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settled->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatesetasfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );


        $fetch = $this->settled->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cancelasfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$doc_trano = $this->getRequest()->getParam("doc_trano");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $requester = $this->getRequest()->getParam("requester");
//        $pic = $this->getRequest()->getParam("pic");
//        $arf_no = $this->getRequest()->getParam("arf_no");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ASF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => 'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settledCancel->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatecancelasfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

//    	$trano = $this->getRequest()->getParam("trano");
//        $doc_trano = $this->getRequest()->getParam("doc_trano");
//        $invoice_no = $this->getRequest()->getParam("invoice_no");
//    	$ket = $this->getRequest()->getParam("ket");
//    	$total_bayar = $this->getRequest()->getParam("total_bayar");
//    	$val_kode = $this->getRequest()->getParam("val_kode");
//        $status = $this->getRequest()->getParam("status");
//        $coa_kode = $this->getRequest()->getParam("coa_kode");
//        $vatradio = $this->getRequest()->getParam("vatradio");
//        $pajak = $this->getRequest()->getParam("pajak");
//        $gtotal = $this->getRequest()->getParam("gtotal");

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ASF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );


        $fetch = $this->settledCancel->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentrpiAction() {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $rpid = $this->paymentRPId->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $id = 1;
        foreach ($rpid as $key => $val) {
            $rpid[$key]['id'] = $id;
            $id++;

            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $rpid[$key][$key2] = '';
            }

            $rpid[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($rpid);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_payment_rpi WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];
    }

    public function editpaymentarfAction() {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $arfd = $this->paymentARFd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $id = 1;
        foreach ($arfd as $key => $val) {
            $arfd[$key]['id'] = $id;
            $id++;
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $arfd[$key][$key2] = '';
            }

            $arfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_payment_arf WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
    }

    public function editsettledasfAction() {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $asfd = $this->settledDetail->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $id = 1;
        foreach ($asfd as $key => $val) {
            $asfd[$key]['id'] = $id;
            $id++;
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $asfd[$key][$key2] = '';
            }

            $asfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_settled WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];
    }

    public function editsettledcancelasfAction() {
        $request = $this->getRequest();

        $trano = $request->getParam('trano');
        $asfd = $this->settledCancelDetail->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $id = 1;
        foreach ($asfd as $key => $val) {
            $asfd[$key]['id'] = $id;
            $id++;
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $asfd[$key][$key2] = '';
            }

            $asfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_settledcancel WHERE trano ='$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];
    }

    public function paymentrpilistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_payment_rpi  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentrpilistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = str_replace('_', "/", $request->getParam('nilai'));

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_rpi  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentarflistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_payment_arf  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentarflistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_arf  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasflistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settled  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasflistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_settled  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasfcancellistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settledcancel  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledasfcancellistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_settledcancel  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function paymentvoucherrpiAction() {
        $this->view->type = 'RPI';
    }

    public function paymentvoucherarfAction() {
        $this->view->type = 'ARF';
    }

    public function paymentvoucherbrfAction() {
        $this->view->type = 'BRF';
    }
    
    public function paymentvoucherbrfpAction() {
        $this->view->type = 'BRFP';
    }
    
    public function getvoucherAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $optionType = $this->getRequest()->getParam('option_type');
        $textsearch = $this->getRequest()->getParam('search');
        $type = $this->getRequest()->getParam('type');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else {
            $search = "$option like '%" . $textsearch . "%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

//        $data = $this->voucher->viewpaymentvoucher($offset,$limit,$dir,$sort,$type,$search,$trano);

        $sql = $this->db->select()
                ->from(array($this->voucher->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->order(array($sort . ' ' . $dir))
                ->where("item_type=?", $type)
                ->group(array("trano"))
                ->limit($limit, $offset);
        
        if ($search)
            $sql = $sql->where($search);

        if ($optionType) {
            switch ($optionType) {
                case 'PPN':
                    $sql = $sql->where("status_bpv_ppn=1");
                    break;
                case 'WHT':
                    $sql = $sql->where("status_bpv_wht=1");
                    break;
                case 'RPI':
                case 'AP':
                    $sql = $sql->where("status_bpv_wht=0 AND status_bpv_ppn=0");
                    break;
            }
        }
//
//        if ($search == '')
//            $sql->reset(Zend_Db_Select::WHERE);
//
        $data['data'] = $this->db->fetchAll($sql);

        foreach ($data['data'] as $k => $v) {
            if ($v['status_bpv_ppn'] == 1) {
                $data['data'][$k]['bpv_type'] = 'VAT';
            }
            if ($v['status_bpv_wht'] == 1) {
                $data['data'][$k]['bpv_type'] = 'With Holding Tax';
            }
        }
//
        $data['total'] = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getvoucherlistdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = str_replace('_', "/", $this->getRequest()->getParam("trano"));
        $tranoedit = str_replace('_', "/", $this->getRequest()->getParam("tranoedit"));
        $type = $this->getRequest()->getParam('type');

        $data['data'] = $this->voucher->fetchAll("trano = '$trano'")->toArray();

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        if ($data['data'] != '') {
            foreach ($data['data'] as $k => $v) {
                $total = $v['total_bayar'];
                $tranoAsli = $v['trano'];
                $trano = $v['ref_number'];
                if ($tranoedit != '') {
                    $where = "trano = '$tranoedit' AND doc_trano = '$trano'";
                } else {
                    $where = "doc_trano = '$trano'";
                }
                if ($type == 'ARF' || $type == 'BRFP' || $type == 'BRF') {
                    $fetch = $this->paymentARF->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                }
                 if ($type == 'PBOQ3') {
                    $fetch = $this->paymentPBOQ3->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                    $fetch2 = $this->paymentPBOQ3d->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                }
                if ($type == 'RPI') {
                    $fetch = $this->paymentRPI->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                    $fetch2 = $this->paymentRPId->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                    $detailBPV = $this->voucherD->fetchAll("trano = '$tranoAsli'")->toArray();
                    if ($detailBPV) {

                        foreach ($detailBPV as $key2 => $val2) {
                            $ref_number = $val2['ref_number'];
                            $prj_kode = $val2['prj_kode'];
                            $sit_kode = $val2['sit_kode'];
                            $total_detail = $val2['total'];

                            if ($ref_number == $trano && $v['prj_kode'] == $prj_kode && $v['sit_kode'] == $sit_kode && $total == $total_detail) {
                                $data['data'][$k]['kode_brg'] = $val2['kode_brg'];
                                $data['data'][$k]['workid'] = $val2['workid'];
                            }
                        }
                    }
                }

                if ($type == 'REM') {

                    if ($tranoedit != '')
                        $where = "trano = '$tranoedit' AND rem_no = '$trano'";
                    else
                        $where = "rem_no = '$trano'";

                    $fetch = $this->paymentREM->fetchAll($where);
                }

                if ($type == 'PPNREM') {
                    $fetch = $this->paymentPPNREM->fetchAll($where . " AND voc_trano = '$tranoAsli'");
                }

                $tots = 0;
                $tots2 = 0;
                $totalitem = 0;

                if ($fetch->toArray()) {

                    if ($tranoedit != '') {
                        if ($type != 'REM') {
                            $data['data'][$k]['total_payment'] = $fetch[0]['total_bayar'];
                            if ($type == 'RPI') {
                                if ($v['valuta'] != 'IDR')
                                    $data['data'][$k]['total_payment_konversi'] = $fetch[0]['total_bayar2'];
                                else
                                    $data['data'][$k]['total_payment_konversi'] = 0;
                            }
                        } else {
                            $data['data'][$k]['total_payment'] = $fetch[0]['total'];
                        }
                    } else {

                        if ($type != 'RPI') {
                            foreach ($fetch as $k2 => $v2) {
                                if ($type != 'REM') {
                                    $tots += floatval($v2['total_bayar']);
                                } else {
                                    if ($v2['voc_trano'] == '')
                                        continue;
                                    $tots += floatval($v2['total']);
                                }
                            }

                            $data['data'][$k]['total_payment'] = floatval($v['total']) - $tots;
                        }


                        if ($type == 'RPI') {
                            if ($fetch2->toArray()) {
                                $fetch2 = $fetch2->toArray();
                                foreach ($fetch2 as $key => $val) {
                                    $tots2 += floatval($val['total_bayar2']);
                                    if ($data['data'][$k]['kode_brg'] == $val['kode_brg'] &&
                                            $v['prj_kode'] == $val['prj_kode'] &&
                                            $v['sit_kode'] == $val['sit_kode'] &&
                                            $data['data'][$k]['workid'] == $val['workid']) {

                                        $tots += floatval($val['total_bayar']);
                                        $totalitem = $val['total'] - $v['holding_tax_val'] - $v['deduction'];
                                    }
                                }
                                $data['data'][$k]['total_payment'] = $totalitem - $tots;
                                $data['data'][$k]['total_paid'] = $tots;

                                if ($v['valuta'] != 'IDR')
                                    $data['data'][$k]['total_payment_konversi'] = floatval(($totalitem - $tots) * $currentRateidr);
                                else
                                    $data['data'][$k]['total_payment_konversi'] = 0;
                            } else {
                                $data['data'][$k]['total_payment'] = floatval($v['total']);
            }
        }
                    }
                } else {
                    $data['data'][$k]['total_payment'] = floatval($v['total']);
                }
            }
        }
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano'); //voucer trano
        $foredit = $this->getRequest()->getParam('foredit');
        //voucher ref number (ARF/RPI/REM)
        $tranoedit = str_replace('_', "/", $this->getRequest()->getParam("tranoedit"));
        $type = $this->getRequest()->getParam('type');

//        var_dump($trano,$foredit,$tranoedit,$type);die;

        $pay = array();

        if ($foredit != '')
            $foredit = true;

        if ($foredit) {
            if ($trano == '') {
                if ($type == 'ARF' || $type == 'BRFP') {
                    $pays = $this->paymentARF->fetchAll("trano = '$tranoedit'");
                    if ($pays) {
                        foreach ($pays as $k => $v) {
                            $arf = $v['doc_trano'];
                            $temp = $this->paymentARF->fetchAll("trano != '$tranoedit' AND doc_trano = '$arf'");
                            foreach ($temp as $k2 => $v2) {
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentARF->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['doc_trano'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total_bayar'],
                        "total_payment" => $temp['total_bayar'],
                    );
                }
        
                if ($type == 'RPI') {
                    $pays = $this->paymentRPI->fetchAll("trano = '$tranoedit'");
                    if ($pays) {
                        foreach ($pays as $k => $v) {
                            $rpi = $v['doc_trano'];
                            $temp = $this->paymentRPI->fetchAll("trano != '$tranoedit' AND doc_trano = '$rpi'");
                            foreach ($temp as $k2 => $v2) {
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentRPI->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['doc_trano'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total_bayar'],
                        "total_payment" => $temp['total_bayar'],
                    );
                }
        
                if ($type == 'REM') {
                    $pays = $this->paymentREM->fetchAll("trano = '$tranoedit'");
                    if ($pays) {
                        foreach ($pays as $k => $v) {
                            $rem = $v['rem_no'];
                            $temp = $this->paymentREM->fetchAll("trano != '$tranoedit' AND rem_no = '$rem'");
                            foreach ($temp as $k2 => $v2) {
                                $v2['total_bayar'] = $v2['total'];
                                $pay[] = $v2;
                            }
                        }
                    }
                    $temp = $this->paymentREM->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['rem_no'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total'],
                        "total_payment" => $temp['total'],
                    );
                }

                if ($type == 'PPNREM') {
                    $pays = $this->paymentPPNREM->fetchAll("trano = '$tranoedit'");
                    if ($pays) {
                        foreach ($pays as $k => $v) {
                            $arf = $v['doc_trano'];
                            $temp = $this->paymentPPNREM->fetchAll("trano != '$tranoedit' AND doc_trano = '$arf'");
                            foreach ($temp as $k2 => $v2) {
                                $pay[] = $v2;
        }
                        }
                    }
                    $temp = $this->paymentPPNREM->fetchRow("trano = '$tranoedit'")->toArray();
                    $data[] = array(
                        "ref_number" => $temp['doc_trano'],
                        "prj_kode" => $temp['prj_kode'],
                        "sit_kode" => $temp['sit_kode'],
                        "valuta" => $temp['val_kode'],
                        "total" => $temp['total'],
                        "total_bayar" => $temp['total_bayar'],
                        "total_payment" => $temp['total_bayar'],
                    );
                }
            } else {
                if ($type == 'ARF' || $type == 'BRFP') {
                    $pays = $this->paymentARF->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays) {
                        $pay = $pays->toArray();
                    }
                }

                if ($type == 'RPI') {
                    $pays = $this->paymentRPI->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
//                    var_dump($pays->toArray());
                    if ($pays) {
                        $pay = $pays->toArray();
                        $cek = $this->voucher->fetchRow("trano = '$trano'");
                        if ($cek) {
                            $isPPN = $cek['status_bpv_ppn'];
                            if ($isPPN == 1) {
                                foreach ($pay as $k3 => $v3) {
                                    $cek = $this->voucher->fetchRow("trano = '{$v3['voc_trano']}'");
                                    if ($cek['status_bpv_ppn'] == 0)
                                        unset($pay[$k3]);
                                }
                            }
                        }
                    }
                }

                if ($type == 'REM') {
                    $pays = $this->paymentREM->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays) {
                        $pay = $pays->toArray();
        }
      
                    foreach ($pay as $k3 => $v3) {
                        $pay[$k3]['total_bayar'] = $v3['total'];
                    }
                }

                if ($type == 'PPNREM') {
                    $pays = $this->paymentPPNREM->fetchAll("voc_trano = '$trano' AND trano != '$tranoedit'");
                    if ($pays) {
                        $pay = $pays->toArray();
    }
                }
    
                $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();
            }
        } else {
            $pay = $this->getLastPayment($trano, $foredit, $tranoedit);
            $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();
            $rpiTrano = $data[0]['ref_number'];
    
            $totalMaterai = 0;
            //Cek payment sebelumnya untuk materai sudah terbayar apa belum
            $select = $this->db->select()
                    ->from(array($this->paymentRPI->__name()))
                    ->where("doc_trano = ?", $rpiTrano)
                    ->where("sts_materai_payment = 'Y'");

            $cek = $this->db->fetchAll($select);
            if (!$cek) {
                $rpih = new Default_Models_RequestPaymentInvoiceH();
                $select = $this->db->select()
                        ->from(array($rpih->__name()))
                        ->where("trano=?", $rpiTrano);
        
                $cek = $this->db->fetchRow($select);

                if ($cek['with_materai'] == 'Y') {
                    $totalMaterai = $cek['materai'];
                }
            }
        }

        $pay_total = 0;

        foreach ($pay as $key => $val) {
            $pay_total += floatval($val['total_bayar']);
        }

        $gtotal = 0;

        foreach ($data as $key => $val) {
            $gtotal += floatval($val['total']);
    }
    
        if ($totalMaterai > 0) {
            $materai = true;
            $gtotal += $totalMaterai;
                }
        $return = array("success" => true, "gtotal" => $gtotal, "voc_data" => $data, "paid" => $pay_total, "materai" => $materai, "total_materai" => $totalMaterai);
               
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentpoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano'); //voucer trano
        $foredit = $this->getRequest()->getParam('foredit');
        $tranoedit = str_replace('_', "/", $this->getRequest()->getParam("tranoedit"));
        

        $pay = array();


            $data = $this->procurementpo->fetchAll("trano = '$trano'")->toArray();
            $payment = $this->paymentPO->fetchAll("doc_trano='$trano'")->toArray();
            $poTrano = $data[0]['trano'];

            foreach ($payment as $key => $val) {
            $pay_total += floatval($val['total_bayar']);
        }

        $gtotal = 0;
        $gtotal = floatval($data[0]['totalspl']);

        $return = array("success" => true, "gtotal" => $gtotal, "voc_data" => $data, "paid" => $pay_total);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    
    public function getpocodlistdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = str_replace('_', "/", $this->getRequest()->getParam("trano"));
        $tranoedit = str_replace('_', "/", $this->getRequest()->getParam("tranoedit"));

        $data['data'] = $this->procurementpo->fetchRow("trano = '$trano'")->toArray();
        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

       
                $total = $data['data']['totalspl'];
                $trano = $data['data']['trano'];
                if ($tranoedit != '') {
                    $where = "trano = '$tranoedit' AND doc_trano = '$trano'";
                } else {
                    $where = "doc_trano = '$trano'";
                }
               
                $fetch = $this->paymentPO->fetchAll($where . " AND doc_trano = '$trano'");
                $data['data']['total_payment'] = floatval($data['data']['total']);                

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function doinsertrpivoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();

        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');
        $payment_valuta = $this->getRequest()->getParam('payment_valuta');
        $paymentIDR = ($this->getRequest()->getParam("paymentIDR") == 'true') ? true : false;

        if ($coaPC == '') {

            if ($form['valuta-1'] == 'USD' && ($paymentIDR || $payment_valuta == 'IDR'))
                $valuta = 'IDR';
            else
                $valuta = $form['valuta-1'];

            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans'],
                        "val_kode" => $valuta
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }
        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);
        $form['voc-materai'] = str_replace(",", "", $form['voc-materai']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['val_rate_text'];
        else
            $rateidr = 0;

        $rpiH = new Default_Models_RequestPaymentInvoiceH();
        $rpiD = new Default_Models_RequestPaymentInvoice();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');


        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        foreach ($list as $k => $v) {
            $rpi = $rpiH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $rpid = $rpiD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            $rateIDR_Ori = $rpi['rateidr'];

            foreach ($rpid as $key => $val) {

                if ($v['prj_kode'] == $val['prj_kode'] && $v['sit_kode'] == $val['sit_kode'] && $v['workid'] == $val['workid'] && $v['kode_brg'] == $val['kode_brg']) {

                    $total = $val['qty'] * $val['harga'];
                    $arrayInsert = array(
                        'trano' => $trano,
                        'tgl' => $tgl,
                        'voc_trano' => $form['voc-number'],
                        "prj_kode" => $val['prj_kode'],
                        "prj_nama" => $val['prj_nama'],
                        "sit_kode" => $val['sit_kode'],
                        "sit_nama" => $val['sit_nama'],
                        "urut" => $val['urut'],
                        "workid" => $val['workid'],
                        "workname" => $val['workname'],
                        "kode_brg" => $val['kode_brg'],
                        "nama_brg" => $val['nama_brg'],
                        "sat_kode" => $val['uom'],
                        'coa_kode' => $val['coa_kode'],
                        'coa_nama' => $val['coa_nama'],
                        'sat_kode' => $val['sat_kode'],
                        'qty' => $val['qty'],
                        'harga' => $val['harga'],
                        'uid' => $uid,
                        'invoice_no' => $rpi['invoice_no'],
                        'total' => (floatval($val['qty']) * floatval($val['harga'])),
                        'total_bayar' => $v['total_payment'],
                        'total_bayar2' => $v['total_payment_konversi'],
                        'jenis_document' => 'RPI',
                        'val_kode' => $val['val_kode'],
                        'val_kode2' => 'IDR',
                        'rateidr' => $rateidr,
                        'ket' => $form['notes'],
                        'doc_trano' => $v['ref_number'],
                        'stspayment' => 'Y',
                        'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                        'pola_bayar' => $form['pay-type'],
                        'tgl_jtt' => $date
                    );
                    $this->paymentRPId->insert($arrayInsert);
                }
            }

            if ($form['voc-materai'] > 0) {
                $withMaterai = true;
            }

            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $rpi['prj_kode'],
                'prj_nama' => $rpi['prj_nama'],
                'sit_kode' => $rpi['sit_kode'],
                'sit_nama' => $rpi['sit_nama'],
                'uid' => $uid,
                'invoice_no' => $rpi['invoice_no'],
                'total' => $rpi['total'],
                'total_bayar' => $v['total_payment'],
                'total_bayar2' => $v['total_payment_konversi'],
                'jenis_document' => 'RPI',
                'val_kode' => $rpi['val_kode'],
                'val_kode2' => 'IDR',
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $rpi['total'],
                'pola_bayar' => $form['pay-type'],
                'tgl_jtt' => $date,
                'sts_materai_payment' => ($withMaterai) ? 'Y' : 'N',
                'materai' => $form['voc-materai']
            );
            $this->paymentRPI->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            //cek BPV apakah WHT
            $select = $this->db->select()
                    ->from(array($this->voucher->__name()))
                    ->where("trano=?", $form['voc-number']);
            $cek = $this->db->fetchRow($select);
            $isWht = false;
            if ($cek['status_bpv_wht'] == '1') {
                $isWht = !$isWht;
            }

            if ($val2['valuta'] == 'IDR') {
                if (!$isWht) {
                    $coas = QDC_Finance_Coa::factory()->getCoaAPIDR();
                    $coa_kode = $coas[0];
                } else {
                    $coas = QDC_Finance_Coa::factory()->getCoaWHT();
                    $coa_kode = $coas[0];
                }

                $coa_kode2 = '';
            } elseif ($val2['valuta'] == 'USD') {
                $coas = QDC_Finance_Coa::factory()->getCoaAPUSD();
                $coa_kode = $coas[0];
                $coa_kode2 = $coas[1];
            }
            $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

            //nilai payment original
            $insertcip = array(
                "trano" => $trano,
                "voc_trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa['coa_kode'],
                "coa_nama" => $coa['coa_nama'],
                "debit" => $val2['total_payment'],
                "credit" => 0,
                "val_kode" => $val2['valuta'],
                'rateidr' => $rateIDR_Ori,
                "type" => 'BPV'
            );
            $this->jurnal_bank->insert($insertcip);

            //nilai payment exchange
            if ($coa_kode2 != '') {
                $coa = $this->coa->fetchRow("coa_kode = '$coa_kode2'");
                $insertcip = array(
                    "trano" => $trano,
                    "voc_trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa['coa_kode'],
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => (floatval($val2['total_payment']) * $rateIDR_Ori) - floatval($val2['total_payment']),
                    "credit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $rateIDR_Ori,
                    "type" => 'BPV'
                );

                $this->jurnal_bank->insert($insertcip);
            }


            if ($bank_coa['moneyInTransfer'] == '') {
                $totalPayConversion = 0;
                foreach ($bank_coa['data'] as $k3 => $v3) {
                    if ($paymentIDR || ($payment_valuta == 'IDR' && $val2['valuta'] != 'IDR')) {
                        $totalInsert = $val2['total_payment_konversi'];
                        $totalPayConversion = $totalInsert;
                    } else {
                        $totalInsert = $val2['total_payment'];
                    }

                    if ($val2['valuta'] != 'IDR' && (!$paymentIDR || $payment_valuta != 'IDR')) {
                        if (strpos($v3['coa_nama'], 'Exchange') !== false) {
                            $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                            $totalPayConversion = $totalInsert + $val2['total_payment'];
                        }
                    }

                    $insertbank = array(
                        "trano" => $trano,
                        "voc_trano" => $form['voc-number'],
                        "ref_number" => $val2['ref_number'],
                        "prj_kode" => $val2['prj_kode'],
                        "sit_kode" => $val2['sit_kode'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $v3['coa_kode'],
                        "coa_nama" => $v3['coa_nama'],
                        "credit" => $totalInsert,
                        "debit" => 0,
                        "val_kode" => $val2['valuta'],
                        'rateidr' => $rateidr,
                        "type" => 'BPV'
                    );
                    $this->jurnal_bank->insert($insertbank);
                }
                if ($coa_kode2 != '') {
                    $totalOri = $val2['total_payment'] * $rateIDR_Ori;
                    $totalPayment = ($totalPayConversion ? $totalPayConversion : ($totalInsert * rateidr));
                    $selisih = $totalPayment - $totalOri;

                    $coa = $this->coa->fetchRow("coa_nama like '%Exchange%Rate%Diff%'");
                    if ($selisih > 0) {

                        $insertgain = array(
                            "trano" => $trano,
                            "voc_trano" => $form['voc-number'],
                            "ref_number" => $val2['ref_number'],
                            "prj_kode" => $val2['prj_kode'],
                            "sit_kode" => $val2['sit_kode'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $coa['coa_kode'],
                            "coa_nama" => $coa['coa_nama'],
                            "debit" => $selisih,
                            "credit" => 0,
                            "val_kode" => $val2['valuta'],
                            'rateidr' => $rateidr,
                            "type" => 'BPV'
                        );
                    } else {
                        $selisih = -1 * $selisih;
                        $insertgain = array(
                            "trano" => $trano,
                            "voc_trano" => $form['voc-number'],
                            "ref_number" => $val2['ref_number'],
                            "prj_kode" => $val2['prj_kode'],
                            "sit_kode" => $val2['sit_kode'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $coa['coa_kode'],
                            "coa_nama" => $coa['coa_nama'],
                            "credit" => $selisih,
                            "debit" => 0,
                            "val_kode" => $val2['valuta'],
                            'rateidr' => $rateidr,
                            "type" => 'BPV'
                        );
                    }
                    if ($selisih != 0)
                        $this->jurnal_bank->insert($insertgain);
                }
            } else {
                $totalInsert = $val2['total_payment'];
                $coas = $bank_coa['data'];
                $spendMoney = new Finance_Models_BankSpendMoney();

                $insertbank = array(
                    "trano" => $trano,
                    "voc_trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coas['coa_kode'],
                    "coa_nama" => $coas['coa_nama'],
                    "credit" => $totalInsert,
                    "debit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $rateidr,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);

                if ($val2['valuta'] == 'USD') {
                    $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                    $insertbank = array(
                        "trano" => $trano,
                        "voc_trano" => $form['voc-number'],
                        "ref_number" => $val2['ref_number'],
                        "prj_kode" => $val2['prj_kode'],
                        "sit_kode" => $val2['sit_kode'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coas['coa_kode'],
                        "coa_nama" => $coas['coa_nama'],
                        "credit" => $totalInsert,
                        "debit" => 0,
                        "val_kode" => $val2['valuta'],
                        'rateidr' => $rateidr,
                        "type" => 'BPV'
                    );
                    $this->jurnal_bank->insert($insertbank);
                }

                // Insert ke jurnal bank out
//                foreach ($bank_coa['data2'] as $k3 => $v3) {
//                    $coaKode = $v3['coa_kode'];
//                    if ($coaKode != QDC_Finance_Coa::factory()->getCoaMoneyInTransfer()) {
//                        $insertbank = array(
//                            "trano" => $trano,
//                            "voc_trano" => $form['voc-number'],
//                            "ref_number" => $val2['ref_number'],
//                            "prj_kode" => $val2['prj_kode'],
//                            "sit_kode" => $val2['sit_kode'],
//                            "tgl" => $tgl,
//                            "uid" => $uid,
//                            "coa_kode" => $v3['coa_kode'],
//                            "coa_nama" => $v3['coa_nama'],
//                            "credit" => $totalInsert,
//                            "debit" => 0,
//                            "val_kode" => $val2['valuta'],
//                            'rateidr' => $currentRateidr
//                        );
//                    } else {
//                        $insertbank = array(
//                            "trano" => $trano,
//                            "voc_trano" => $form['voc-number'],
//                            "ref_number" => $val2['ref_number'],
//                            "prj_kode" => $val2['prj_kode'],
//                            "sit_kode" => $val2['sit_kode'],
//                            "tgl" => $tgl,
//                            "uid" => $uid,
//                            "coa_kode" => $v3['coa_kode'],
//                            "coa_nama" => $v3['coa_nama'],
//                            "credit" => 0,
//                            "debit" => $totalInsert,
//                            "val_kode" => $val2['valuta'],
//                            'rateidr' => $currentRateidr
//                        );
//                    }
//                    $spendMoney->insert($insertbank);
//                }
            }
        }

        if ($withMaterai) {
            $coas = QDC_Finance_Coa::factory()->getCoaAPIDR();
            $coa_kode = $coas[0];
            $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

            $insertcip = array(
                "trano" => $trano,
                "voc_trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa['coa_kode'],
                "coa_nama" => $coa['coa_nama'],
                "debit" => $form['voc-materai'],
                "credit" => 0,
                "val_kode" => 'IDR',
                'rateidr' => $rateidr,
                "type" => 'BPV',
                "memo" => "Stamp Duty"
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {
                $totalInsert = $form['voc-materai'];
                $insertbank = array(
                    "trano" => $trano,
                    "voc_trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    "debit" => 0,
                    "val_kode" => 'IDR',
                    'rateidr' => $rateidr,
                    "type" => 'BPV',
                    "memo" => "Stamp Duty"
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }
        
        
        //file
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $rpi['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename'],
                    "ref_number" => $v['ref_number']
                );
             
                $this->DEFAULT->Files->insert($arrayInsert);
                
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
       
        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertarfvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans']
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

//        $lastTrans = $counter->getLastTrans($form['trans']);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $arfH = new Default_Models_AdvanceRequestFormH();
        $arfD = new Default_Models_AdvanceRequestFormD();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');

        foreach ($list as $k => $v) {
            $arf = $arfH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $arfd = $arfD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            //Insert ke Accounting Bank Payment
//            $arrayBankPayment = array(
//                "trano" => $trano,
//                "ref_number" => $v['ref_number'],
//                "tgl" => date("Y-m-d H:i:s"),
//                "uid" => $this->session->userName
//            );
//            $coaModel = new Finance_Models_MasterCoa();
//
//            //Debit, Account Payable
//            if ($arf['val_kode'] == 'IDR')
//                $coa = $this->COA['AP_IDR'];
//            elseif ($arf['val_kode'] == 'USD')
//                $coa = $this->COA['AP_USD'];
//
//            $coas = $coaModel->fetchRow("coa_kode = '$coa'");
//
//            $arrayBankPayment['coa_kode'] = $coa;
//            $arrayBankPayment['coa_nama'] = $coas['coa_nama'];
//            $arrayBankPayment['debit'] = $v['total_payment'];
//            $arrayBankPayment['credit'] = '';
//
//            $this->accBankPayment->insert($arrayBankPayment);
//
//            //Kredit, COA bank
//            $coabank = $this->accBankPayment->fetchRow("trano_type = '{$form['trans']}'");

            foreach ($arfd as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'requester' => $arf['requester'],
                    'pic' => $arf['pic'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'ARF',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
//                    'vatradio' => $jsonEtc[0]['vatradio'],
//                    'ppn' => $ppn_d,
//                    'ppnwht' => $ppnwht_d,
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
//                    'pola_bayar' => $form['pay-type'],
//                    'tgl_jtt' => $date
                );
                $this->paymentARFd->insert($arrayInsert);

//                if ($arf['bt'] == 'Y')
//                {
                $tglAkhir = new DateTime('now');
                $waktu = 13; //Waktu aging buat ARF, tgl dibuatnya payment dihitung...
                $tglAkhir->add(new DateInterval("P" . $waktu . "D"));
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'tgl_akhir' => $tglAkhir->format('Y-m-d H:i:s'),
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    'sat_kode' => $val['uom'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $val['requester'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'arf_no' => $v['ref_number']
                );
                $this->FINANCE->ArfAging->insert($arrayInsert);
//                }
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $arf['prj_kode'],
                'prj_nama' => $arf['prj_nama'],
                'sit_kode' => $arf['sit_kode'],
                'sit_nama' => $arf['sit_nama'],
                'uid' => $uid,
                'requester' => $arf['requester'],
                'pic' => $arf['pic'],
                'total' => $arf['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'ARF',
                'val_kode' => $arf['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
//                'vatradio' => $jsonEtc[0]['vatradio'],
//                'ppn' => $ppn,
//                'ppnwht' => $ppnwht,
                'gtotal' => $arf['total'],
//                'pola_bayar' => $form['pay-type'],
//                'tgl_jtt' => $date
            );
            $this->paymentARF->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR') {
                    if (strpos($v3['coa_nama'], 'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }

        // file 
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $arf['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename'],
                    "ref_number" => $v['ref_number']
                );
             
                $this->DEFAULT->Files->insert($arrayInsert);
                
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        
        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatearfvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $trano = $this->getRequest()->getParam('trano');

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);

        foreach ($list as $k => $v) {

            $arrayUpdate = array(
                "total_bayar" => $v['total_payment'],
                "ket" => $form['notes']
            );

            $this->paymentARF->update($arrayUpdate, "trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
            $this->paymentARFd->update($arrayUpdate, "trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
        }


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdaterpivoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $trano = str_replace('_', "/", $this->getRequest()->getParam("trano"));

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);

        foreach ($list as $k => $v) {

            $arrayUpdate = array(
                "total_bayar" => $v['total_payment'],
                "total_bayar2" => $v['total_payment_konversi'],
                "ket" => $form['notes'],
                "pola_bayar" => $form['pay-type'],
                "tgl_jtt" => date('Y-m-d', strtotime($form['date']))
            );

            $this->paymentRPI->update($arrayUpdate, "trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
            $this->paymentRPId->update($arrayUpdate, "trano = '$trano' AND doc_trano = '{$v['ref_number']}'");
        }


        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function getLastPayment($trano = '') {
        $data = $this->voucher->fetchAll("trano = '$trano'")->toArray();

        if ($data[0]['item_type'] == 'RPI') {
            $pay = $this->paymentRPI->fetchAll("voc_trano = '$trano'");
            if (!$pay) {
                foreach ($data as $k => $v) {
                    $temp = $this->paymentRPI->fetchAll("doc_trano = '{$v['ref_number']}'");
                    if ($temp) {
                        $temp = $temp->toArray();
                        foreach ($temp as $k2 => $v2) {
                            $pay[] = $v2;
                        }
                    }
                }
            } else {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        } elseif ($data[0]['item_type'] == 'ARF' || $data[0]['item_type'] == 'BRF' || $data[0]['item_type'] == 'BRFP'  ) {
            $pay = $this->paymentARF->fetchAll("voc_trano = '$trano'");
            if (!$pay) {
                foreach ($data as $k => $v) {
                    $temp = $this->paymentARF->fetchAll("doc_trano = '{$v['ref_number']}'");
                    if ($temp) {
                        $temp = $temp->toArray();
                        foreach ($temp as $k2 => $v2) {
                            $pay[] = $v2;
                        }
                    }
                }
            } else {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        } elseif ($data[0]['item_type'] == 'REM') {
            $sql = " SELECT *,total as total_bayar FROM finance_payment_reimbursement where voc_trano = '$trano'";
            $fetch = $this->db->query($sql);
            $pay = $fetch->fetchAll();
            if (!$pay) {
                foreach ($data as $k => $v) {
                    $sql = " SELECT *,total as total_bayar FROM finance_payment_reimbursement where rem_no = '{$v['ref_number']}'";
                    $fetch = $this->db->query($sql);
                    $temp = $fetch->fetchAll();
                    if ($temp) {
//                        $temp = $temp->toArray();
                        foreach ($temp as $k2 => $v2) {
                            $pay[] = $v2;
                        }
                    }
                }
            } else {
//                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        }
        if ($data[0]['item_type'] == 'PPNREM') {
            $pay = $this->paymentPPNREM->fetchAll("voc_trano = '$trano'");
            if (!$pay) {
                foreach ($data as $k => $v) {
                    $temp = $this->paymentPPNREM->fetchAll("doc_trano = '{$v['ref_number']}'");
                    if ($temp) {
                        $temp = $temp->toArray();
                        foreach ($temp as $k2 => $v2) {
                            $pay[] = $v2;
                        }
                    }
                }
            } else {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        }
        if (!$pay)
            $pay = array();
        return $pay;
    }
    private function getLastPaymentPO($trano = '') {
        $data = $this->procurementpo->fetchAll("trano = '$trano'")->toArray();

            $pay = $this->paymentPO->fetchAll("doc_trano = '$trano'");
            if (!$pay) {
                foreach ($data as $k => $v) {
                    $temp = $this->paymentPO->fetchAll("doc_trano = '{$v['trano']}'");
                    if ($temp) {
                        $temp = $temp->toArray();
                        foreach ($temp as $k2 => $v2) {
                            $pay[] = $v2;
                        }
                    }
                }
            } else {
                $pay = $pay->toArray();
//                $pay = $pay[0];
            }
        
       
        if (!$pay)
            $pay = array();
        return $pay;
    }

    public function getpaymenthistoryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $return['data'] = $this->getLastPayment($trano);

        $ldap = new Default_Models_Ldap();
        foreach ($return['data'] as $k => $v) {
            $uid = $v['uid'];
            if ($username[$uid] == '') {
//                $othername = $ldap->getAccount($uid);
//                $name = $othername['displayname'][0];
                $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                $username[$uid] = $name;
            }

            $return['data'][$k]['username'] = $username[$uid];
        }

        echo Zend_Json::encode($return);
    }

    public function getpaymentpohistoryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $return['data'] = $this->getLastPaymentPO($trano);

        $ldap = new Default_Models_Ldap();
        foreach ($return['data'] as $k => $v) {
            $uid = $v['uid'];
            if ($username[$uid] == '') {
                $name = QDC_User_Ldap::factory(array("uid" => $uid))->getName();
                $username[$uid] = $name;
            }

            $return['data'][$k]['username'] = $username[$uid];
        }

        echo Zend_Json::encode($return);
    }
    public function editpaymentvoucherarfAction() {
        $trano = $this->getRequest()->getParam("trano");

        $dataEdit = $this->paymentARF->fetchRow("trano = '$trano'");

        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $this->view->type = 'ARF';
        $this->view->data = $dataEdit;
        $this->view->trano = $trano;
    }

    public function editpaymentvoucherrpiAction() {
        $trano = str_replace('_', "/", $this->getRequest()->getParam("trano"));
        $dataEdit = $this->paymentRPI->fetchRow("trano = '$trano'");

        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $this->view->type = 'RPI';
        $this->view->data = $dataEdit;
        $this->view->trano = $this->getRequest()->getParam("trano");
    }

    public function checktransactiontypeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');
        $func = $this->getRequest()->getParam('callbackFunc');

        if ($type != 'PC') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $type
                    )
            );
        } else {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaDetail('1-1100');
        }
        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $return = array(
            "success" => true,
            "data" => $bank_coa
        );

        echo Zend_Json::encode($return);
    }

    public function paymentPpnRemAction() {
        $this->view->type = 'PPNREM';
    }

    public function indexAction() {
        
    }

    public function doinsertppnremvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));

        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');
        $payment_valuta = $this->getRequest()->getParam('payment_valuta');
        $paymentIDR = ($this->getRequest()->getParam("paymentIDR") == 'true') ? true : false;

        if ($coaPC == '') {
            if ($form['valuta-1'] == 'USD' && ($paymentIDR || $payment_valuta == 'IDR'))
                $valuta = 'IDR';
            else
                $valuta = $form['valuta-1'];



            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans'],
                        "val_kode" => $valuta
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }
        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }


        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $ppnH = new Finance_Models_PpnReimbursementH();
        $ppnD = new Finance_Models_PpnReimbursementD();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');


        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];


        foreach ($list as $k => $v) {
            $h = $ppnH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $d = $ppnD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            foreach ($d as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'sat_kode' => $val['sat_kode'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'total_bayar2' => $v['total_payment_konversi'],
                    'jenis_document' => 'PPNREM',
                    'val_kode' => $val['val_kode'],
                    'val_kode2' => 'IDR',
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
//                    'vatradio' => $jsonEtc[0]['vatradio'],
//                    'ppn' => $ppn_d,
//                    'ppnwht' => $ppnwht_d,
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                    'pola_bayar' => $form['pay-type'],
                    'tgl_jtt' => $date
                );
                $this->paymentPPNREMd->insert($arrayInsert);
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $h['prj_kode'],
                'prj_nama' => $h['prj_nama'],
                'sit_kode' => $h['sit_kode'],
                'sit_nama' => $h['sit_nama'],
                'uid' => $uid,
                'invoice_no' => $h['invoice_no'],
                'total' => $h['total'],
                'total_bayar' => $v['total_payment'],
                'total_bayar2' => $v['total_payment_konversi'],
                'jenis_document' => 'RPI',
                'val_kode' => $h['val_kode'],
                'val_kode2' => 'IDR',
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $h['total'],
                'pola_bayar' => $form['pay-type'],
                'tgl_jtt' => $date
            );
            $this->paymentPPNREM->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
//            $coa_kode = '2-1100';
            if ($val2['valuta'] == 'IDR') {
                $coas = QDC_Finance_Coa::factory()->getCoaPaidAdvanceIDR();
                $coa_kode = $coas[0];
                $coa_kode2 = '';
            } elseif ($val2['valuta'] == 'USD') {
//                $coas = QDC_Finance_Coa::factory()->getCoaAPUSD();
//                $coa_kode = $coas[0];
//                $coa_kode2 = $coas[1];
                $coas = QDC_Finance_Coa::factory()->getCoaPaidAdvanceIDR();
                $coa_kode = $coas[0];
                $coa_kode2 = $coas[0];
            }
            $coa = $this->coa->fetchRow("coa_kode = '$coa_kode'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa['coa_kode'],
                "coa_nama" => $coa['coa_nama'],
                "debit" => $val2['total_payment'],
                "credit" => 0,
                "val_kode" => $val2['valuta'],
                'rateidr' => $currentRateidr,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);
            if ($coa_kode2 != '') {
                $coa = $this->coa->fetchRow("coa_kode = '$coa_kode2'");
                $insertcip = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coa['coa_kode'],
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => (floatval($val2['total_payment']) * $rateidr) - floatval($val2['total_payment']),
                    "credit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $currentRateidr,
                    "type" => 'BPV'
                );

                $this->jurnal_bank->insert($insertcip);
            }

            if ($bank_coa['moneyInTransfer'] == '') {
                foreach ($bank_coa['data'] as $k3 => $v3) {

                    if ($paymentIDR || $payment_valuta == 'IDR')
                        $totalInsert = $val2['total_payment_konversi'];
                    else
                        $totalInsert = $val2['total_payment'];

                    if ($val2['valuta'] != 'IDR' && (!$paymentIDR || $payment_valuta != 'IDR')) {
                        if (strpos($v3['coa_nama'], 'Exchange') !== false)
                            $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                    }

                    $insertbank = array(
                        "trano" => $form['voc-number'],
                        "ref_number" => $val2['ref_number'],
                        "prj_kode" => $val2['prj_kode'],
                        "sit_kode" => $val2['sit_kode'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $v3['coa_kode'],
                        "coa_nama" => $v3['coa_nama'],
                        "credit" => $totalInsert,
                        "debit" => 0,
                        "val_kode" => $val2['valuta'],
                        'rateidr' => $currentRateidr,
                        "type" => 'BPV'
                    );
                    $this->jurnal_bank->insert($insertbank);
                }
            }
            else {
                $totalInsert = $val2['total_payment'];
                $coas = $bank_coa['data'];
                $spendMoney = new Finance_Models_BankSpendMoney();

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $coas['coa_kode'],
                    "coa_nama" => $coas['coa_nama'],
                    "credit" => $totalInsert,
                    "debit" => 0,
                    "val_kode" => $val2['valuta'],
                    'rateidr' => $currentRateidr,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);

                if ($val2['valuta'] == 'USD') {
                    $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                    $insertbank = array(
                        "trano" => $form['voc-number'],
                        "ref_number" => $val2['ref_number'],
                        "prj_kode" => $val2['prj_kode'],
                        "sit_kode" => $val2['sit_kode'],
                        "tgl" => $tgl,
                        "uid" => $uid,
                        "coa_kode" => $coas['coa_kode'],
                        "coa_nama" => $coas['coa_nama'],
                        "credit" => $totalInsert,
                        "debit" => 0,
                        "val_kode" => $val2['valuta'],
                        'rateidr' => $currentRateidr,
                        "type" => 'BPV'
                    );
                    $this->jurnal_bank->insert($insertbank);
                }

                // Insert ke jurnal bank out
                foreach ($bank_coa['data2'] as $k3 => $v3) {
                    $coaKode = $v3['coa_kode'];
                    if ($coaKode != QDC_Finance_Coa::factory()->getCoaMoneyInTransfer()) {
                        $insertbank = array(
                            "trano" => $form['voc-number'],
                            "ref_number" => $val2['ref_number'],
                            "prj_kode" => $val2['prj_kode'],
                            "sit_kode" => $val2['sit_kode'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $v3['coa_kode'],
                            "coa_nama" => $v3['coa_nama'],
                            "credit" => $totalInsert,
                            "debit" => 0,
                            "val_kode" => $val2['valuta'],
                            'rateidr' => $currentRateidr
                        );
                    } else {
                        $insertbank = array(
                            "trano" => $form['voc-number'],
                            "ref_number" => $val2['ref_number'],
                            "prj_kode" => $val2['prj_kode'],
                            "sit_kode" => $val2['sit_kode'],
                            "tgl" => $tgl,
                            "uid" => $uid,
                            "coa_kode" => $v3['coa_kode'],
                            "coa_nama" => $v3['coa_nama'],
                            "credit" => 0,
                            "debit" => $totalInsert,
                            "val_kode" => $val2['valuta'],
                            'rateidr' => $currentRateidr
                        );
                    }
                    $spendMoney->insert($insertbank);
                }
            }
        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cancelesfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();


        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;
        $lastT = $lastTrans['id'];

        $where = "id=" . $lastT;

        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ESF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ESF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => 'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settledCancel->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinserterfvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans']
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $erfH = new Procurement_Models_EntertainmentRequestH();
        $erfD = new Procurement_Models_EntertainmentRequestD();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');

        foreach ($list as $k => $v) {
            $erf = $erfH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $erfd = $erfD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            foreach ($erfd as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'sat_kode' => $val['sat_kode'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'requester' => $erf['requester'],
                    'pic' => $erf['pic'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'ERF',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga']))
                );
                $this->paymentARFd->insert($arrayInsert);


                $tglAkhir = new DateTime('now');
                $waktu = 13; //Waktu aging buat ARF, tgl dibuatnya payment dihitung...
                $tglAkhir->add(new DateInterval("P" . $waktu . "D"));
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'tgl_akhir' => $tglAkhir->format('Y-m-d H:i:s'),
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    'sat_kode' => $val['uom'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $val['requester'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'arf_no' => $v['ref_number']
                );
                $this->FINANCE->ArfAging->insert($arrayInsert);
//                }
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $erf['prj_kode'],
                'prj_nama' => $erf['prj_nama'],
                'sit_kode' => $erf['sit_kode'],
                'sit_nama' => $erf['sit_nama'],
                'uid' => $uid,
                'requester' => $erf['requester'],
                'pic' => $erf['pic'],
                'total' => $erf['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'ERF',
                'val_kode' => $erf['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $erf['total'],
//                'pola_bayar' => $form['pay-type'],
//                'tgl_jtt' => $date
            );
            $this->paymentARF->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR') {
                    if (strpos($v3['coa_nama'], 'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentvouchererfAction() {
        $trano = $this->getRequest()->getParam("trano");

        $dataEdit = $this->paymentARF->fetchRow("trano = '$trano'");
        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $this->view->type = 'ERF';
        $this->view->data = $dataEdit;
        $this->view->trano = $trano;
    }

    public function editsettledesfAction() {
        $request = $this->getRequest();
        $cancel = $request->getParam('cancel');
        $trano = $request->getParam('trano');
        $asfd = $this->settledDetail->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        if ($cancel)
            $asfd = $this->settledCancelDetail->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();

        $id = 1;
        foreach ($asfd as $key => $val) {
            $asfd[$key]['id'] = $id;
            $id++;
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $asfd[$key][$key2] = '';
            }

            $asfd[$key]['uom'] = $val['sat_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfd);
        $this->view->json = $jsonData;

        $sql = "SELECT * FROM finance_settled WHERE trano ='$trano'";
        if ($cancel)
            $sql = "SELECT * FROM finance_settledcancel WHERE trano ='$trano'";

        $fetch = $this->db->query($sql);
        $return = $fetch->fetch();

        $this->view->trano = $return['trano'];
        $this->view->docTrano = $return['doc_trano'];
        $this->view->valKode = $return['val_kode'];
        $this->view->total = $return['total'];
        $this->view->totalBayar = $return['total_bayar'];
        $this->view->status = $return['stspayment'];
        $this->view->ket = $return['ket'];
        $this->view->rate = $return['rateidr'];
//        $this->view->coa_kode = $return['coa_kode'];
        $this->view->vatradio = $return['vatradio'];
        $this->view->gtotal = $return['gtotal'];
        $this->view->ppn = $return['ppn'];
        $this->view->ppnwht = $return['ppnwht'];
        $this->view->payType = $return['pola_bayar'];
        $this->view->date = $return['tgl_jtt'];
        if ($cancel)
            $this->view->cancel = true;
    }

    public function paymentvouchererfAction() {
        $this->view->type = 'ERF';
    }

    public function settledesfAction() {
        $this->view->cancel = $this->getRequest()->getParam("cancel");
    }

    public function setesfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");
        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $doc_trano = $jsonEtc[0]['doc_trano'];

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($jsonEtc[0]['type']);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;
        $lastT = $lastTrans['id'];

        $where = "id=" . $lastT;
        $counter->update(array("urut" => $last), $where);

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $urut = 1;
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['harga'];

            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $total;
                $ppnwht_d = 0;
                $gtotal = $total + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $total;
                $gtotal = $total + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $total;
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ESF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prj_kode' => $asf['prj_kode'],
            'prj_nama' => $asf['prj_nama'],
            'sit_kode' => $asf['sit_kode'],
            'sit_nama' => $asf['sit_nama'],
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'jenis_document' => 'ESF',
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'requester' => $jsonEtc[0]['requester'],
            'orangpic' => $jsonEtc[0]['pic'],
            'arf_no' => $jsonData[0]['arf_no'],
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => 'Y',
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );

        $fetch = $this->settled->insert($arrayInsert);

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function settledesfcancellistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settledcancel where jenis_document=\'ESF\'  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledesfcancellistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_settledcancel  WHERE jenis_document=\'ESF\' and ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function settledesflistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,doc_trano,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_settled where jenis_document =\'ESF\'  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function updatecancelesfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ESF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledCancelDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );


        $fetch = $this->settledCancel->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatesetesfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $asfH = new Default_Models_AdvanceSettlementFormH();

        $posts = $this->getRequest()->getParam("posts");
        $etc = $this->getRequest()->getParam("etc");

        $jsonData = Zend_Json::decode($posts);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];
        $doc_trano = $jsonEtc[0]['doc_trano'];

        if ($jsonEtc[0]['val_kode'] != 'IDR')
            $rateidr = $jsonEtc[0]['rateidr'];
        else
            $rateidr = 0;

        $asf = $asfH->fetchRow("trano='$doc_trano'")->toArray();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        if ($jsonEtc[0]['date'] != '')
            $date = date('Y-m-d', strtotime($jsonEtc[0]['date']));
        else
            $date = date('Y-m-d');

        if ($jsonEtc[0]['pola'] == 'CASH')
            $date = date('Y-m-d');


        if ($jsonEtc[0]['status'] == 'true')
            $status = 'Y';
        else
            $status = 'N';

        if ($jsonEtc[0]['vatradio'] == 1) {
            $ppn = $jsonEtc[0]['pajak'];
            $ppnwht = 0;
        } elseif ($jsonEtc[0]['vatradio'] == 3) {
            $ppn = 0;
            $ppnwht = $jsonEtc[0]['pajak'];
        } else {
            $ppn = 0;
            $ppnwht = 0;
        }

        $urut = 1;
        $this->settledDetail->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($jsonEtc[0]['vatradio'] == 1) {
                $ppn_d = 0.1 * $val['total'];
                $ppnwht_d = 0;
                $gtotal = $val['total'] + $ppn_d;
            } elseif ($jsonEtc[0]['vatradio'] == 3) {
                $ppn_d = 0;
                $ppnwht_d = 0.1 * $val['total'];
                $gtotal = $val['total'] + $ppnwht_d;
            } else {
                $ppn_d = 0;
                $ppnwht_d = 0;
                $gtotal = $val['total'];
            }


            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "urut" => $urut,
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "sat_kode" => $val['uom'],
                'coa_kode' => $val['coa_kode'],
                'coa_nama' => $val['coa_nama'],
                'qty' => $val['qty'],
                'harga' => $val['harga'],
                'uid' => $uid,
                'total' => $asf['total'],
                'total_bayar' => $jsonEtc[0]['total_bayar'],
                'jenis_document' => 'ESF',
                'val_kode' => $jsonEtc[0]['val_kode'],
                'rateidr' => $rateidr,
                'requester' => $jsonEtc[0]['requester'],
                'orangpic' => $jsonEtc[0]['pic'],
                'arf_no' => $val['arf_no'],
                'ket' => $jsonEtc[0]['ket'],
                'doc_trano' => $doc_trano,
                'stspayment' => 'Y',
                'vatradio' => $jsonEtc[0]['vatradio'],
                'ppn' => $ppn_d,
                'ppnwht' => $ppnwht_d,
                'gtotal' => $gtotal,
                'pola_bayar' => $jsonEtc[0]['pola'],
                'tgl_jtt' => $date
            );
            $urut++;

            $this->settledDetail->insert($arrayInsert);
        }

        $arrayInsert = array(
            'tgl' => $tgl,
            'uid' => $uid,
            'total' => $asf['total'],
            'total_bayar' => $jsonEtc[0]['total_bayar'],
            'val_kode' => $jsonEtc[0]['val_kode'],
            'rateidr' => $rateidr,
            'ket' => $jsonEtc[0]['ket'],
            'doc_trano' => $doc_trano,
            'stspayment' => $status,
            'vatradio' => $jsonEtc[0]['vatradio'],
            'ppn' => $ppn,
            'ppnwht' => $ppnwht,
            'gtotal' => $jsonEtc[0]['gtotal'],
            'pola_bayar' => $jsonEtc[0]['pola'],
            'tgl_jtt' => $date
        );


        $fetch = $this->settled->update($arrayInsert, "trano = '$trano'");

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doinsertbrfvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans']
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

    
        $brfh = new Procurement_Models_BusinessTripHeader();
        $brfd = new Procurement_Models_BusinessTripDetail();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');

        foreach ($list as $k => $v) {
            $brf = $brfh->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $brfd = $brfd->fetchAll("trano='{$v['ref_number']}'")->toArray();

            foreach ($brfd as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'requester' => $brf['requester'],
                    'pic' => $brf['pic'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'BRF',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                );
                $this->paymentARFd->insert($arrayInsert);

                $tglAkhir = new DateTime('now');
                $waktu = 13; //Waktu aging buat ARF, tgl dibuatnya payment dihitung...
                $tglAkhir->add(new DateInterval("P" . $waktu . "D"));
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'tgl_akhir' => $tglAkhir->format('Y-m-d H:i:s'),
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    'sat_kode' => $val['uom'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $val['requester'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'arf_no' => $v['ref_number']
                );
                $this->FINANCE->ArfAging->insert($arrayInsert);
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $brf['prj_kode'],
                'prj_nama' => $brf['prj_nama'],
                'sit_kode' => $brf['sit_kode'],
                'sit_nama' => $brf['sit_nama'],
                'uid' => $uid,
                'requester' => $brf['requester'],
                'pic' => $brf['pic'],
                'total' => $brf['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'BRF',
                'val_kode' => $brf['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $brf['total'],
            );
            $this->paymentARF->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR') {
                    if (strpos($v3['coa_nama'], 'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }
        
        // file 
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $brf['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename'],
                    "ref_number" => $v['ref_number']
                );
             
                $this->DEFAULT->Files->insert($arrayInsert);
                
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }


        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
     public function doinsertpopaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

       
        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();
 
         
        
        if ($form['valuta-1'] == 'USD' && ($paymentIDR || $payment_valuta == 'IDR'))
                $valuta = 'IDR';
            else
                $valuta = $form['valuta-1'];

            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans'],
                        "val_kode" => $valuta
                    )
            );
            
            
         if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }   
        
        
        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

         $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);
        
        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['val_rate_text'];
        else
            $rateidr = 0;
        
        
        
        $po = new Default_Models_PurchaseOrderH();
        $pod = new Default_Models_PurchaseOrder();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');
        
        
        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];
        

        foreach ($list as $k => $v) {
            $po = $po->fetchRow("trano='{$v['trano']}'")->toArray();
            $pod = $pod->fetchAll("trano='{$v['trano']}'")->toArray();


            foreach ($pod as $key => $val) {

                    $total = $val['qty'] * $val['harga'];
                    $arrayInsert = array(
                        'trano' => $trano,
                        'tgl' => $tgl,
                        "prj_kode" => $val['prj_kode'],
                        "prj_nama" => $val['prj_nama'],
                        "sit_kode" => $val['sit_kode'],
                        "sit_nama" => $val['sit_nama'],
                        "urut" => $val['urut'],
                        "workid" => $val['workid'],
                        "workname" => $val['workname'],
                        "kode_brg" => $val['kode_brg'],
                        "nama_brg" => $val['nama_brg'],
                        "sat_kode" => $val['uom'],
                        'qty' => $val['qty'],
                        'harga' => $val['harga'],
                        'uid' => $uid,
                        'total' => (floatval($val['qty']) * floatval($val['harga'])),
                        'total_bayar' => $v['total_payment'],
                        'total_bayar2' => (floatval($v['total_payment']) * $rateidr),
                        'val_kode' => $val['val_kode'],
                        'val_kode2' => 'IDR',
                        'rateidr' => $rateidr,
                        'ket' => $form['notes'],
                        'doc_trano' => $v['trano'],
                        'stspayment' => 'Y',
                        'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                        'pola_bayar' => $form['pay-type'],
                        'tgl_jtt' => $date
                    );
                    $this->paymentPOD->insert($arrayInsert);

            }

           

            $arrayInsert = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'prj_kode' => $po['prj_kode'],
                'prj_nama' => $po['prj_nama'],
                'sit_kode' => $po['sit_kode'],
                'sit_nama' => $po['sit_nama'],
                'uid' => $uid,
                'total' => $po['total'],
                'total_bayar' => $v['total_payment'],
                'total_bayar2' => (floatval($v['total_payment']) * $rateidr),
                'val_kode' => $po['val_kode'],
                'val_kode2' => 'IDR',
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['trano'],
                'stspayment' => 'Y',
                'gtotal' => $po['total'],
                'pola_bayar' => $form['pay-type'],
                'tgl_jtt' => $date,
            );
            $this->paymentPO->insert($arrayInsert);
        }
        
               
           foreach ($jurnal as $key2 => $val2) {

            unset($val2['tipe']);
            unset($val2['urut']);
            unset($val2['is_minus']);
            $val2['type'] = 'PO';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['prj_kode'] = $po['prj_kode'];
            $val2['sit_kode'] = $po['sit_kode'];
            $val2['trano'] = $trano;
            $val2['ref_number'] = $form['voc-number'];
            $val2['val_kode'] = $po['val_kode'];
            $val2['rateidr'] = $po['rateidr'];

            $this->jurnal_bank->insert($val2);
           }
        

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function doinsertbrfpvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans']
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $arfH = new Default_Models_AdvanceRequestFormH();
        $arfD = new Default_Models_AdvanceRequestFormD();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');

        foreach ($list as $k => $v) {
            $arf = $arfH->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $arfd = $arfD->fetchAll("trano='{$v['ref_number']}'")->toArray();

            foreach ($arfd as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => $val['uom'],
                    'coa_kode' => $val['coa_kode'],
                    'coa_nama' => $val['coa_nama'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $uid,
                    'requester' => $arf['requester'],
                    'pic' => $arf['pic'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'BRFP',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga'])),
                );
                $this->paymentARFd->insert($arrayInsert);

                $tglAkhir = new DateTime('now');
                $waktu = 13; //Waktu aging buat ARF, tgl dibuatnya payment dihitung...
                $tglAkhir->add(new DateInterval("P" . $waktu . "D"));
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'tgl_akhir' => $tglAkhir->format('Y-m-d H:i:s'),
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    'sat_kode' => $val['uom'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'uid' => $val['requester'],
                    'total' => (floatval($val['qty']) * floatval($val['harga'])),
                    'total_bayar' => $v['total_payment'],
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'arf_no' => $v['ref_number']
                );
                $this->FINANCE->ArfAging->insert($arrayInsert);
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $arf['prj_kode'],
                'prj_nama' => $arf['prj_nama'],
                'sit_kode' => $arf['sit_kode'],
                'sit_nama' => $arf['sit_nama'],
                'uid' => $uid,
                'requester' => $arf['requester'],
                'pic' => $arf['pic'],
                'total' => $arf['total'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'BRFP',
                'val_kode' => $arf['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $arf['total'],
            );
            $this->paymentARF->insert($arrayInsert);
        }

        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR') {
                    if (strpos($v3['coa_nama'], 'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }
        
        // file 
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $arf['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename'],
                    "ref_number" => $v['ref_number']
                );
             
                $this->DEFAULT->Files->insert($arrayInsert);
                
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }


        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
     public function getpocodlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else {
            $search = "AND "."$option like '%" . $textsearch . "%' ";
        }

        $sql = "select trano,tgl,prj_kode,val_kode from erpdb.procurement_poh where  approve='400' $search";
        $data['data'] = $this->db->fetchAll($sql);
        $data['total'] = $this->db->fetchOne("SELECT FOUND_ROWS()");
        
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getPaymentListAction() {
        $this->_helper->viewRenderer->setNoRender();

        $bpvTrano = $this->_getParam("bpv_trano");
        $trano = $this->_getParam("trano");
        $refNumber = $this->_getParam("ref_number");
        $supKode = $this->_getParam("sup_kode");
        $type = $this->_getParam("type");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        if ($type == 'RPI') {
            $select = $this->db->select()
                    ->from(array($this->paymentRPI->__name()))
                    ->group(array("trano"));
            $select = $this->db->select()
                    ->from(array($select), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                    )
            );
        }

        if ($type == 'ARF') {
            $select = $this->db->select()
                    ->from(array($this->paymentARF->__name()))
                    ->where("jenis_document = 'ARF'")
                    ->group(array("trano"));
            $select = $this->db->select()
                    ->from(array($select), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                    )
            );
        }

        if ($type == 'REM') {
            $select = $this->db->select()
                    ->from(array($this->paymentREM->__name()), array(
                        "trano",
                        "tgl",
                        "doc_trano" => "rem_no",
                        "voc_trano",
                        "total_bayar" => "total",
                        "val_kode",
                        "prj_kode",
                        "sit_kode",
                        "prj_nama",
                        "sit_nama",
                        "jenis_document" => new Zend_Db_Expr("'REM'")
                    ))
                    ->group(array("trano"));
            $select = $this->db->select()
                    ->from(array($select), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                    )
            );
        }

        if ($type == 'PPN-REM') {
            $select = $this->db->select()
                    ->from(array($this->paymentPPNREM->__name()))
                    ->group(array("trano"));
            $select = $this->db->select()
                    ->from(array($select), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                    )
            );
        }

        if ($type == 'BRFP') {
            $select = $this->db->select()
                    ->from(array($this->paymentARF->__name()))
                    ->where("jenis_document = 'BRFP'")
                    ->group(array("trano"));
            $select = $this->db->select()
                    ->from(array($select), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                    )
            );
        }

        if ($supKode && $type == 'RPI') {
            $rpi = new Default_Models_RequestPaymentInvoiceH();
            $subselect = $this->db->select()
                    ->from(array($rpi->__name()), array(
                        "trano"
                    ))
                    ->where("sup_kode LIKE '%$supKode%'");

            $subselect2 = $this->db->select()
                    ->from(array("a" => $subselect), array(
                        "rpi_trano" => "trano"
                    ))
                    ->joinLeft(array("b" => $this->paymentRPI->__name()), "a.trano = b.doc_trano")
                    ->where("b.trano IS NOT NULL")
                    ->group(array("b.trano"));

            $select = $this->db->select()
                    ->from(array($subselect2), array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
            ));
        }

        $select = $select
                ->order(array("tgl DESC"))
                ->limit($limit, $offset);

        if ($trano)
            $select = $select->where("trano LIKE '%$trano%'");
        if ($bpvTrano)
            $select = $select->where("voc_trano LIKE '%$bpvTrano%'");
        if ($refNumber)
            $select = $select->where("doc_trano LIKE '%$refNumber%'");

        $data = $this->db->fetchAll($select);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode(array(
                    "success" => true,
                    "data" => $data,
                    "count" => $count
        ));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }


    public function paymentvoucherpmealAction() {
        $this->view->type = 'PBOQ3';
    }

    public function doinsertpmealvoucherpaymentAction() {
      
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jsonfile = Zend_Json::decode($this->_getParam("filedata"));
        $urut = 1;
        $activityCount=0;
        $activityFile=array();
        //Khusus untuk pembayaran petty cash. Dapatkan coa kode dari yg dipilih oleh User
        $coaPC = $this->getRequest()->getParam('coaPC');

        if ($coaPC == '') {
            $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                    array(
                        "type" => $form['trans']
                    )
            );
        } else {
            $bank_coa['data'] = array(QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coaPC)));
        }

        if ($bank_coa === false) {
            echo "{success: false,message : 'Please Insert COA Bank to continue this payment '}";
            die;
        }

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);
        $form['voc-val'] = str_replace(",", "", $form['voc-val']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

    
        $pmealh = new Default_Models_PieceMealH();
        $pmeald = new Default_Models_PieceMeal();
        
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');
       
        foreach ($list as $k => $v) {
           
            $pmeal = $pmealh->fetchRow("notran='{$v['ref_number']}'")->toArray();
            $pmeald = $pmeald->fetchAll("notran='{$v['ref_number']}'")->toArray();
       
            foreach ($pmeald as $key => $val) {
                $total = $val['qty'] * $val['harga'];
                $arrayInsert = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "urut" => $val['urut'],
                    "workid" => '',
                    "workname" => '',
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "sat_kode" => '',
                    'coa_kode' => '',
                    'coa_nama' => '',
                    'qty' => $val['qty'],
                    'harga' => $val['harga_borong'],
                    'uid' => $uid,
                    'requester' => '',
                    'pic' => '',
                    'total' => (floatval($val['qty']) * floatval($val['harga_borong'])),
                    'total_bayar' => $v['total_payment'],
                    'jenis_document' => 'PBOQ3',
                    'val_kode' => $val['val_kode'],
                    'rateidr' => $rateidr,
                    'ket' => $form['notes'],
                    'doc_trano' => $v['ref_number'],
                    'stspayment' => 'Y',
                    'gtotal' => (floatval($val['qty']) * floatval($val['harga_borong'])),
                );
                $this->paymentPBOQ3d->insert($arrayInsert);
            
            }
            $arrayInsert = array(
                'trano' => $trano,
                'voc_trano' => $form['voc-number'],
                'tgl' => $tgl,
                'prj_kode' => $pmeal['prj_kode'],
                'prj_nama' => $pmeal['prj_nama'],
                'sit_kode' => $pmeal['sit_kode'],
                'sit_nama' => $pmeal['sit_nama'],
                'uid' => $uid,
                'requester' => '',
                'pic' => '',
                'total' => $pmeal['jumlah'],
                'total_bayar' => $v['total_payment'],
                'jenis_document' => 'BRF',
                'val_kode' => $pmeal['val_kode'],
                'rateidr' => $rateidr,
                'ket' => $form['notes'],
                'doc_trano' => $v['ref_number'],
                'stspayment' => 'Y',
                'gtotal' => $pmeal['jumlah'],
            );
            $this->paymentPBOQ3->insert($arrayInsert);
        }

           
        //insert jurnal bank

        foreach ($list as $key2 => $val2) {
            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");

            $insertcip = array(
                "trano" => $form['voc-number'],
                "voc_trano" => $form['voc-number'],
                "ref_number" => $val2['ref_number'],
                "prj_kode" => $val2['prj_kode'],
                "sit_kode" => $val2['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $cip_coa['coa_kode'],
                "coa_nama" => $cip_coa['coa_nama'],
                "debit" => $val2['total_payment'],
                'val_kode' => $val2['valuta'],
                "credit" => 0,
                "type" => 'BPV'
            );

            $this->jurnal_bank->insert($insertcip);

            foreach ($bank_coa['data'] as $k3 => $v3) {

                $totalInsert = $val2['total_payment'];
                if ($val2['valuta'] != 'IDR') {
                    if (strpos($v3['coa_nama'], 'Exchange') !== false)
                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
                }

                $insertbank = array(
                    "trano" => $form['voc-number'],
                    "voc_trano" => $form['voc-number'],
                    "ref_number" => $val2['ref_number'],
                    "prj_kode" => $val2['prj_kode'],
                    "sit_kode" => $val2['sit_kode'],
                    "tgl" => $tgl,
                    "uid" => $uid,
                    "coa_kode" => $v3['coa_kode'],
                    "coa_nama" => $v3['coa_nama'],
                    "credit" => $totalInsert,
                    'val_kode' => $val2['valuta'],
                    "debit" => 0,
                    "type" => 'BPV'
                );
                $this->jurnal_bank->insert($insertbank);
            }
        }
        
        // file 
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $brf['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename'],
                     "ref_number" => $v['ref_number']
                );
             
                $this->DEFAULT->Files->insert($arrayInsert);
                
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
        }

?>
