<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/22/11
 * Time: 10:04 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_PaymentreimbursementController extends Zend_Controller_Action {

    private $reimbursementD;
    private $reimbursementH;
    private $payment_reimbursD;
    private $payment_reimbursH;
    private $nota_debit_detail;
    private $nota_debit_header;
    private $session;
    private $coa;
    private $bank;
    private $db;
    private $payment_nota_debit_detail;
    private $payment_nota_debit_header;
    private $report_reimburs;
    private $log_trans;
    private $coa_bank;
    private $jurnal_bank;
    private $DEFAULT;
    private $FINANCE;

    public function init() {
        $this->session = new Zend_Session_Namespace('login');
        $this->reimbursementD = new Finance_Models_ReimbursDetail();
        $this->reimbursementH = new Finance_Models_ReimbursHeader();
        $this->payment_reimbursD = new Finance_Models_PaymentReimbursD();
        $this->payment_reimbursH = new Finance_Models_PaymentReimbursH();
        $this->nota_debit_detail = new Finance_Models_NDreimbursDetail();
        $this->nota_debit_header = new Finance_Models_NDreimbursHeader();
        $this->coa = new Finance_Models_MasterCoa();
        $this->bank = new Finance_Models_MasterBank();
        $this->db = Zend_Registry::get('db');
        $this->payment_nota_debit_detail = new Finance_Models_paymentNDreimbursD();
        $this->payment_nota_debit_header = new Finance_Models_paymentNDreimbursH();
        $this->report_reimburs = new Finance_Models_Reimbursreport();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->coa_bank = new Finance_Models_MasterCoaBank();
        $this->jurnal_bank = new Finance_Models_AccountingJurnalBank();

        $models = array(
            "MasterCounter"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);

        $models = array(
            "AccountingCloseAR",
            "AccountingJurnalBank"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function paymentreimbursementAction() {
        
    }

    public function insertpaymentreimbursementAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function getreimbursheaderAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else if ($textsearch != null && $option == 1) {
            $search = "trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "prj_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "sit_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 5) {
            $search = "sit_nama like '%$textsearch%' ";
        } else {
            $search = "";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data = $this->reimbursementH->viewheaderreimburs($offset, $limit, $dir, $sort, $search);
//        $return['total'] = $this->reimbursementH->fetchAll()->count();
//        $return['data'] = $data;

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getformpayreimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $payment_trano = $this->getRequest()->getParam('payment_trano');
        $dn_trano = $this->getRequest()->getParam('dntrano');
        $paid_dn_trano = $this->getRequest()->getParam('paiddntrano');

        $payment_trano = str_replace("_", "/", $payment_trano);
        $paid_dn_trano = str_replace("_", "/", $paid_dn_trano);

        $data = $this->reimbursementH->ViewFormPayReimburs($trano);
        $paidlistsum = $this->payment_reimbursH->PaidListSum($trano, $payment_trano);
        $paymentreimburs = $this->payment_reimbursD->paymentreimbursdetail($payment_trano);
        $coa = $this->coa->getcoa();
        $debitnote = $this->nota_debit_detail->fetchAll("prem_no = '$payment_trano'")->toArray();

        $paidquery = "SELECT PD.type,PH.paymentnotes,PH.statusppn,PH.total FROM finance_payment_reimbursement_nd PH
                      LEFT JOIN finance_payment_reimbursementd_nd PD ON PH.trano = PD.trano
                       WHERE PH.dn_no = '$dn_trano'";
        $fetch = $this->db->query($paidquery);
        $paiddebitnote = $fetch->fetchAll();
        foreach ($data as $k => $v) {
            $transType = $this->DEFAULT->MasterCounter->getTransTypeFlip($paid_dn_trano);
            $data[$k]['trans_type'] = $transType;
        }
        $totalpaid = 0;
        foreach ($debitnote as $k => $v) {
            $totalpaid += ($v['qty'] * $v['harga']);
        }
//        $paiddebitnote = $this->payment_nota_debit_detail->fetchRow("trano = '$paid_dn_trano'")->toArray();

        $return = array("success" => true, "data" => $data, "sumpaidlist" => $paidlistsum, "paymentreimburs" => $paymentreimburs, "coa" => $coa, "debitnote" => $totalpaid, "paiddebitnote" => $paiddebitnote);

//        $jsonparam = Zend_Json::encode($param);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
//        $this->getResponse()->setBody($jsonparam);
    }

    public function getviewreimbursitemlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->reimbursementD->ViewReimbursItemList($trano);

        $return['data'] = $data;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentreimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $rem_trano = $this->getRequest()->getParam('rem_trano');
        $type_trano = $this->getRequest()->getParam('type_trano');


        $semua = $this->payment_reimbursD->fetchAll("rem_no = '$rem_trano'");
        if ($semua) {
            $balance = 0;
            $semua = $semua->toArray();
            foreach ($semua as $k => $v) {
                $balance += floatVal($v['total']);
            }

            $data = $this->reimbursementH->ViewFormPayReimburs($rem_trano);
            $totalHarusDibayar = floatVal($data[0]['total']);
            $totalBayar = floatVal(str_replace(",", "", $payment['payment-value']));
//            if ($totalBayar > ($totalHarusDibayar - $balance))
//            {
//                $json = "{success: false, msg: 'Payment value is Greater than balance!'}";
//
//                $this->getResponse()->setHeader('Content-Type','text/javascript');
//                $this->getResponse()->setBody($json);
//                return false;
//            }
        }
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans($type_trano);
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = $lastTrans['tra_no'] . '-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);

        $tgl = date('Y-m-d H:i:s');
        $urut = 1;

        if ($itemlist[0]['val_kode'] == 'USD')
            $rateidr = $payment[0]['rate_text'];
        else
            $rateidr = 0;

        foreach ($itemlist as $key => $val) {
            $jumlah = $val['qty'] * $val['harga'];

            $arrayInsertDetail = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'rem_no' => $rem_trano,
                'urut' => $urut,
                'prj_kode' => $transdetail['prj_kode'],
                'prj_nama' => $transdetail['prj_nama'],
                'sit_kode' => $transdetail['sit_kode'],
                'sit_nama' => $transdetail['sit_nama'],
                'ket' => $transdetail['description'],
                'cus_kode' => $transdetail['cus_kode'],
                'workid' => $transdetail['workid'],
                'workname' => $transdetail['worknama'],
                'kode_brg' => $val['kode_brg'],
                'nama_brg' => $val['nama_brg'],
                'qty' => str_replace(",", "", $val['qty']),
                'harga' => str_replace(",", "", $val['harga']),
                'rateidr' => str_replace(",", "", $rateidr),
                'val_kode' => $val['val_kode'],
                'jumlah' => $jumlah,
                'statusppn' => $payment['ppn'],
                'total' => str_replace(",", "", $payment['payment-value']),
                'type' => $payment['option'],
                'petugas' => $payment['user-payment']
            );
            $urut++;

            $this->payment_reimbursD->insert($arrayInsertDetail);
        }

        $arrayInsertHeader = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'rem_no' => $rem_trano,
            'prj_kode' => $transdetail['prj_kode'],
            'prj_nama' => $transdetail['prj_nama'],
            'sit_kode' => $transdetail['sit_kode'],
            'sit_nama' => $transdetail['sit_nama'],
            'ket' => $transdetail['description'],
            'cus_kode' => $transdetail['cus_kode'],
            'statusppn' => $payment['ppn'],
            'val_kode' => $val['val_kode'],
            'user' => $payment['user-payment'],
            'rateidr' => str_replace(",", "", $rateidr),
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes']
        );

        $this->payment_reimbursH->insert($arrayInsertHeader);

        $response = array("success" => true);
        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaidlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->payment_reimbursH->viewpaidlist($trano);

        $return['data'] = $data;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentreimbursementAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function getpaymentreimbursdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else if ($textsearch != null && $option == 1) {
            $search = "WHERE a.trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "WHERE a.rem_no like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "WHERE a.tgl like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "WHERE a.prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 5) {
            $search = "WHERE a.sit_kode like '%$textsearch%' ";
        } else {
            $search = "";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

//        $data = $this->payment_reimbursH->viewalltranspayment($offset,$limit,$dir,$sort,$search);

        $query = "SELECT SQL_CALC_FOUND_ROWS a.*,(IF(b.trano IS NULL,0,1)) AS ada_nd,(IF(c.trano IS NULL,0,1)) AS ada_nd_payment
                    FROM (finance_payment_reimbursement a LEFT JOIN finance_nd_reimbursement b on a.trano = b.prem_no)
                    LEFT JOIN finance_payment_reimbursementd_nd c ON b.trano = c.dn_no
                    $search
                    ORDER BY $sort $dir LIMIT $offset,$limit ";

        $fetch = $this->db->query($query);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepaymentreimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $rem_trano = $this->getRequest()->getParam('rem_trano');
        $type_trano = $this->getRequest()->getParam('type_trano');
        $trans_number = $this->getRequest()->getParam('trans_number');
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));

        $paytotal = floatVal(str_replace(",", "", $payment['payment-value']));

        $dn = $this->nota_debit_header->fetchRow("prem_no = '$trans_number'");

        $dntotal = floatVal(str_replace(",", "", $dn['total']));

        if ($paytotal < $dntotal) {
            $json = "{success: false, msg: 'Payment value is Lower than Debit Note Reimbursement!'}";

            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);
            return false;
        }

        $where = "trano = '$trans_number'";

//        foreach ($itemlist as $key => $val)
//        {
//            $updatedetailpayment = array(
//                'statusppn' => $payment['ppn'],
//                'total' => str_replace(",","",$payment['payment-value']),
//                'type' => $payment['option'],
//                'petugas' => $payment['user-payment']
//            );
//        }
        $log['payreimburs-detail-before'] = $this->payment_reimbursD->fetchAll("trano = '$trans_number'")->toArray();

        $updatedetailpayment = array(
            'statusppn' => $payment['ppn'],
            'total' => str_replace(",", "", $payment['payment-value']),
            'type' => $payment['option'],
            'petugas' => $payment['user-payment']
        );

        $this->payment_reimbursD->update($updatedetailpayment, $where);

        $log2['payreimburs-detail-after'] = $this->payment_reimbursD->fetchAll("trano = '$trans_number'")->toArray();
        $log['payreimburs-header-before'] = $this->payment_reimbursH->fetchRow("trano = '$trans_number'")->toArray();

        $updateheaderpayment = array(
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes'],
            'user' => $payment['user-payment'],
            'statusppn' => $payment['ppn'],
        );

        $this->payment_reimbursH->update($updateheaderpayment, $where);

        $log2['payreimburs-header-after'] = $this->payment_reimbursH->fetchRow("trano = '$trans_number'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $trans_number,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $transdetail['prj_kode'],
            "sit_kode" => $transdetail['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );

        $this->log_trans->insert($arrayLog);

        $json = "{success: true,number : '$trans_number'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function debitnotereimbursementAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function insertdebitnoteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $rem_trano = $this->getRequest()->getParam('rem_trano');
//        $type_trano = $this->getRequest()->getParam('type_trano');
        $prem_no = $this->getRequest()->getParam('trans_number');
        $banklist = Zend_Json::decode($this->getRequest()->getParam('banklist'));
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));
        $vBefore = Zend_Json::decode($this->getRequest()->getParam('value_before'));
        $vAfter = Zend_Json::decode($this->getRequest()->getParam('value_after'));

//        var_dump($payment);die;

        $semua = $this->payment_reimbursD->fetchAll("rem_no = '$rem_trano'");
        if ($semua) {
            $balance = 0;
            $semua = $semua->toArray();
            foreach ($semua as $k => $v) {
                $balance += floatVal($v['total']);
            }

            $data = $this->reimbursementH->ViewFormPayReimburs($rem_trano);
            $totalHarusDibayar = floatVal($data[0]['total']);
            $totalBayar = floatVal(str_replace(",", "", $payment['payment-value']));
            if ($totalBayar > $totalHarusDibayar) {
                $json = "{success: false, msg: 'Payment value is Greater than balance!'}";

                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);
                return false;
            }
        }
        $counter = new Default_Models_MasterCounter();


        $lastTrans = $counter->getLastTrans('DN01');
        $lastTrans['urut'] = $lastTrans['urut'] + 1;
        $trano = 'DN01-' . $lastTrans['urut'];
        $counter->update(array("urut" => $lastTrans['urut']), "id=" . $lastTrans['id']);

//        $lastTrans = $counter->getLastTrans($type_trano);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        $tgl = date('Y-m-d H:i:s');
        $urut = 1;

        if ($itemlist[0]['val_kode'] == 'USD')
            $rateidr = $payment[0]['rate_text'];
        else
            $rateidr = 0;

        $b = new Finance_Models_NDreimbursDetailAdditional();
        $rateidr = floatval(str_replace(",", "", $rateidr));
        foreach ($itemlist as $key => $val) {
            $jumlah = $val['qty'] * $val['harga'];
            $harga = $val['harga'];
            if ($payment['payment-value'] != $jumlah) {
                $jumlah = $payment['payment-value'];
                $harga = $payment['payment-value'];
            }

            $arrayInsertDetail = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'prem_no' => $prem_no,
                'urut' => $urut,
                'prj_kode' => $transdetail['prj_kode'],
                'prj_nama' => $transdetail['prj_nama'],
                'sit_kode' => $transdetail['sit_kode'],
                'sit_nama' => $transdetail['sit_nama'],
                'ket' => $transdetail['description'],
                'cus_kode' => $transdetail['cus_kode'],
                'workid' => $transdetail['workid'],
                'workname' => $transdetail['worknama'],
                'kode_brg' => $val['kode_brg'],
                'nama_brg' => $val['nama_brg'],
                'qty' => str_replace(",", "", $val['qty']),
                'harga' => str_replace(",", "", $harga),
                'rateidr' => str_replace(",", "", $rateidr),
                'val_kode' => $val['val_kode'],
                'jumlah' => $jumlah,
                'statusppn' => $payment['ppn'],
                'total' => str_replace(",", "", $payment['payment-value']),
                'type' => $payment['option'],
                'petugas' => $payment['user-payment'],
                'coa_kode' => $payment['coa-code'],
                'coa_nama' => $payment['coa-name']
            );
            $urut++;

            $lastId = $this->nota_debit_detail->insert($arrayInsertDetail);
            //Insert addtional value before tax
            foreach ($vBefore as $k => $v) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prem_no" => $prem_no,
                    "id_nd" => $lastId,
                    "total" => $v['total'],
                    "ket" => $v['ket'],
                    "type" => $v['type'],
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "tgl" => date("Y-m-d H:i:s"),
                    "pos" => "BEFORE_TOTAL",
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $rateidr
                );
                $b->insert($arrayInsert);
            }

            //Insert addtional value after tax
            foreach ($vAfter as $k => $v) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prem_no" => $prem_no,
                    "id_nd" => $lastId,
                    "total" => $v['total'],
                    "ket" => $v['ket'],
                    "type" => $v['type'],
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "tgl" => date("Y-m-d H:i:s"),
                    "pos" => "AFTER_TOTAL",
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $rateidr
                );
                $b->insert($arrayInsert);
            }
        }

        $arrayInsertHeader = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'prem_no' => $prem_no,
            'prj_kode' => $transdetail['prj_kode'],
            'prj_nama' => $transdetail['prj_nama'],
            'sit_kode' => $transdetail['sit_kode'],
            'sit_nama' => $transdetail['sit_nama'],
            'ket' => $transdetail['description'],
            'cus_kode' => $transdetail['cus_kode'],
            'statusppn' => $payment['ppn'],
            'val_kode' => $payment['option-paymentvaluta'],
            'user' => $payment['user-payment'],
            'rateidr' => str_replace(",", "", $rateidr),
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes'],
            'paymentterm' => $payment['payment-term'],
            'top' => $payment['top'],
            'coa_kode' => $payment['coa-code'],
            'coa_nama' => $payment['coa-name'],
            'bnk_kode' => $banklist['bnk_kode'],
            'bnk_nama' => $banklist['bnk_nama'],
            'bnk_norek' => $banklist['bnk_norek'],
            'bnk_noreknama' => $banklist['bnk_noreknama'],
            'bnk_cabang' => $banklist['bnk_cabang'],
            'bnk_alamat' => $banklist['bnk_alamat'],
            'bnk_kota' => $banklist['bnk_kota'],
            'destination' => $payment['destination'],
            'destinationaddress' => $payment['destination-add']
        );
        $this->nota_debit_header->insert($arrayInsertHeader);

        foreach ($jurnal as $key2 => $val2) {

            unset($val2['tipe']);
            unset($val2['urut']);
            unset($val2['is_minus']);
            $val2['type'] = 'DEBITNOTE';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['prj_kode'] = $transdetail['prj_kode'];
            $val2['sit_kode'] = $transdetail['sit_kode'];
            $val2['trano'] = $trano;
            $val2['val_kode'] = $payment['option-paymentvaluta'];
            $val2['rateidr'] = $rateidr;

            $this->FINANCE->AccountingCloseAR->insert($val2);
        }

        $response = array("success" => true);
        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcoalistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $query = $this->getRequest()->getParam('query');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option == 1) {
            $search = "coa_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "coa_nama like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "tipe like '%$textsearch%' ";
        }

        if ($query != '') {
            $search = "coa_kode like '%$query%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = $this->coa->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $data['total'] = $this->coa->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function reportreimbursementAction() {
        
    }

    public function getdebitnotelistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = "";
        } else if ($textsearch != null && $option == 1) {
            $search = "DH.trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "DH.prem_no like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "rem_no like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "DH.prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 5) {
            $search = "DH.sit_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 6) {
            $search = "DH.cus_kode like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->nota_debit_header->viewdebitnotelist($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getbanklistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $withCoa = $this->getRequest()->getParam("with_coa");

        $data['data'] = $this->bank->fetchAll(null, array($sort . " " . $dir), $limit, $offset)->toArray();
        if ($withCoa != '') {
            foreach ($data['data'] as $k => $v) {
                
            }
        }
        $data['total'] = $this->bank->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listmanagerAction() {
        $this->_helper->viewRenderer->setNoRender();

        $sql = "
                SELECT ml.uid as uid_manager, mrt.display_name FROM
                    master_login ml
                LEFT JOIN
                    master_role mr
                ON
                    ml.id = mr.id_user
                LEFT JOIN
                    master_role_type mrt
                ON
                    mr.id_role = mrt.id
                WHERE
                    mrt.role_name = 'BOD'
                    AND
                    mrt.display_name != 'Executive Assistant'
                GROUP BY ml.uid
        ";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        if ($hasil) {
            $ldapdir = new Default_Models_Ldap();
            foreach ($hasil as $key => $val) {
//                $account = $ldapdir->getAccount($val['uid_manager']);
//                $hasil[$key]['display_name'] = $account['displayname'][0] ;
                $hasil[$key]['display_name'] = QDC_User_Ldap::factory(array("uid" => $val['uid_manager']))->getName();
                $hasil[$key]['role_name'] = $val['display_name'];
            }
        }
        $return['posts'] = $hasil;
        $return['count'] = count($hasil);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function paymentdebitnotereimbursementAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function insertpaymentdebitnoteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $dn_trano = $this->getRequest()->getParam('dn_trano');
        $type_trano = $this->getRequest()->getParam('type_trano');
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));
        $jsonDeduction = $this->getRequest()->getParam('deduction');
        $deduction = Zend_Json::decode($jsonDeduction);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($type_trano);

        $totalDeduction = 0;
        if ($deduction) {
            foreach ($deduction as $k => $v) {
                $totalDeduction+= $v['total'];
            }
        }
//        $lastTrans = $counter->getLastTrans($type_trano);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        $tgl = date('Y-m-d H:i:s');
        $urut = 1;

        if ($itemlist[0]['val_kode'] == 'USD')
            $rateidr = $payment[0]['rate_text'];
        else
            $rateidr = 0;

        foreach ($itemlist as $key => $val) {
            $jumlah = $val['qty'] * $val['harga'];

            $arrayInsertDetail = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'dn_no' => $dn_trano,
                'urut' => $urut,
                'prj_kode' => $transdetail['prj_kode'],
                'prj_nama' => $transdetail['prj_nama'],
                'sit_kode' => $transdetail['sit_kode'],
                'sit_nama' => $transdetail['sit_nama'],
                'ket' => $transdetail['description'],
                'cus_kode' => $transdetail['cus_kode'],
                'workid' => $transdetail['workid'],
                'workname' => $transdetail['worknama'],
                'kode_brg' => $val['kode_brg'],
                'nama_brg' => $val['nama_brg'],
                'qty' => str_replace(",", "", $val['qty']),
                'harga' => str_replace(",", "", $val['harga']),
                'rateidr' => str_replace(",", "", $rateidr),
                'val_kode' => $transdetail['val_kode'],
                'jumlah' => $jumlah,
                'statusppn' => $payment['ppn'],
                'total' => str_replace(",", "", $payment['payment-value']),
                'type' => $payment['option'],
                'petugas' => $payment['user-payment']
            );
            $urut++;

            $this->payment_nota_debit_detail->insert($arrayInsertDetail);
        }

        $arrayInsertHeader = array(
            'trano' => $trano,
            'tgl' => $tgl,
            'dn_no' => $dn_trano,
            'prj_kode' => $transdetail['prj_kode'],
            'prj_nama' => $transdetail['prj_nama'],
            'sit_kode' => $transdetail['sit_kode'],
            'sit_nama' => $transdetail['sit_nama'],
            'ket' => $transdetail['description'],
            'cus_kode' => $transdetail['cus_kode'],
            'statusppn' => $payment['ppn'],
            'val_kode' => $transdetail['val_kode'],
            'user' => $payment['user-payment'],
            'rateidr' => str_replace(",", "", $rateidr),
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes'],
            'total_deduction' => $totalDeduction,
            'deduction_list' => $jsonDeduction
        );

        $this->payment_nota_debit_header->insert($arrayInsertHeader);

        $rateidr = floatval(str_replace(",", "", $rateidr));

        foreach ($jurnal as $key2 => $val2) {

            unset($val2['tipe']);
            unset($val2['urut']);
            $val2['type'] = 'BPV';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['prj_kode'] = $transdetail['prj_kode'];
            $val2['sit_kode'] = $transdetail['sit_kode'];
            $val2['trano'] = $trano;
            $val2['val_kode'] = $transdetail['val_kode'];
            $val2['rateidr'] = $rateidr;

            $this->jurnal_bank->insert($val2);
        }


        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function outstandingreportreimbursementAction() {
        
    }

    public function viewreportreimbursAction() {
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');

        $hasil = $this->report_reimburs->getReimbursReport($prj_kode, $sit_kode);
        $this->view->result = $hasil;
    }

    public function detailoutstandingAction() {
        $trano = $this->getRequest()->getParam('trano');

        $reimbursH = $this->reimbursementH->fetchAll("trano = '$trano'")->toArray();
        $reimbursD = $this->reimbursementD->fetchAll("trano = '$trano'")->toArray();

        $prj_kode = $reimbursH[0]['prj_kode'];

        $fetch = $this->db->query("SELECT cus_nama FROM master_customer m LEFT JOIN master_project mp ON m.cus_kode = mp.cus_kode
                                    WHERE Prj_Kode = '$prj_kode'");

        $customer = $fetch->fetchAll();

        $payreimburs = $this->payment_reimbursH->fetchAll("rem_no = '$trano'")->toArray();

        $payTrano = array();
        if ($payreimburs) {
            foreach ($payreimburs as $k => $v) {
                $payTrano[] = "'" . $v['trano'] . "'";
            }

            $trano2 = implode(",", $payTrano);
            $dn = $this->nota_debit_header->fetchAll("prem_no IN ($trano2)")->toArray();

            $debitnote = array();
            if ($dn) {
                foreach ($dn as $k => $v) {
                    $debitnote[] = "'" . $v['trano'] . "'";
                }

                $dntrano = implode(",", $debitnote);
                $paydebitnote = $this->payment_nota_debit_header->fetchAll("dn_no IN ($dntrano)")->toArray();
            }
        }

        $this->view->paydebitnote = $paydebitnote;
        $this->view->debitnote = $dn;
        $this->view->payreimburs = $payreimburs;
        $this->view->customer = $customer;
        $this->view->reimbursH = $reimbursH;
        $this->view->reimbursD = $reimbursD;
        $this->view->trano = $trano;
    }

    public function getoutstandingdataAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $reimbursdata = $this->reimbursementD->ViewReimbursItemList($trano);

        $return = array('success' => true, 'reimbursdata' => $reimbursdata);

//        $data = array('success' => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editdebitnotereimbursAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function updatedebitnoteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $rem_trano = $this->getRequest()->getParam('rem_trano');
        $prem_no = $this->getRequest()->getParam('trans_number');
        $banklist = Zend_Json::decode($this->getRequest()->getParam('banklist'));
        $dntrano = $this->getRequest()->getParam('trano');
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));
        
        $semua = $this->payment_reimbursD->fetchAll("rem_no = '$rem_trano'");
        if ($semua) {
            $balance = 0;
            $semua = $semua->toArray();
            foreach ($semua as $k => $v) {
                $balance += floatVal($v['total']);
            }

            $data = $this->reimbursementH->ViewFormPayReimburs($rem_trano);
            $totalHarusDibayar = floatVal($data[0]['total']);
            $totalBayar = floatVal(str_replace(",", "", $payment['payment-value']));
            if ($totalBayar > $totalHarusDibayar) {
                $json = "{success: false, msg: 'Debit Note value is Greater than Payment Reimbursement!'}";

                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);
                return false;
            }

            $paidND = $this->payment_nota_debit_header->fetchAll("dn_no = '$dntrano'");
            if (!$paidND) {
                $totalpaydn = 0;
            } else {
                $paidND = $paidND->toArray();
                $totalpaydn = $paidND[0]['total'];
            }

            if ($totalBayar < $totalpaydn) {
                $json = "{success: false, msg: 'Debit Note value is Lower than Payment Debit Note Reimbursement!'}";

                $this->getResponse()->setHeader('Content-Type', 'text/javascript');
                $this->getResponse()->setBody($json);
                return false;
            }
        }

        $log['dnreimburs-detail-before'] = $this->nota_debit_detail->fetchAll("trano = '$dntrano'")->toArray();
  
        $tgl = date('Y-m-d H:i:s');
        $urut = 1;

        if ($itemlist[0]['val_kode'] == 'USD')
            $rateidr = $payment[0]['rate_text'];
        else
            $rateidr = 0;

        $this->nota_debit_detail->delete("trano = '$dntrano'");

        foreach ($itemlist as $key => $val) {
            $jumlah = $val['qty'] * $val['harga'];

            $arrayInsertDetail = array(
                'trano' => $dntrano,
                'tgl' => $tgl,
                'prem_no' => $prem_no,
                'urut' => $urut,
                'prj_kode' => $transdetail['prj_kode'],
                'prj_nama' => $transdetail['prj_nama'],
                'sit_kode' => $transdetail['sit_kode'],
                'sit_nama' => $transdetail['sit_nama'],
                'ket' => $transdetail['description'],
                'cus_kode' => $transdetail['cus_kode'],
                'workid' => $transdetail['workid'],
                'workname' => $transdetail['worknama'],
                'kode_brg' => $val['kode_brg'],
                'nama_brg' => $val['nama_brg'],
                'qty' => str_replace(",", "", $val['qty']),
                'harga' => str_replace(",", "", $val['harga']),
                'rateidr' => str_replace(",", "", $rateidr),
                'val_kode' => $val['val_kode'],
                'jumlah' => $jumlah,
                'statusppn' => $payment['ppn'],
                'total' => str_replace(",", "", $payment['payment-value']),
                'type' => $payment['option'],
                'petugas' => $payment['user-payment'],
                'coa_kode' => $payment['coa-code'],
                'coa_nama' => $payment['coa-name']
            );
            $urut++;

            $this->nota_debit_detail->insert($arrayInsertDetail);
        }
   
        //Cek jurnal AR sudah ada yg diclose apa belum...
        $cek = $this->FINANCE->AccountingCloseAR->fetchAll("trano = '$dntrano' AND stsclose = 1");
        if (count($cek->toArray()) == 0) {
            $this->FINANCE->AccountingCloseAR->delete("trano = '$dntrano'");
        }

        //insert jurnal bank
        foreach ($jurnal as $key2 => $val2) {
            unset($val2['tipe']);
            unset($val2['urut']);
            unset($val2['is_minus']);
            $val2['type'] = 'BPV';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['rateidr'] = $rateidr;
            $val2['prj_kode'] = $transdetail['prj_kode'];
            $val2['sit_kode'] = $transdetail['sit_kode'];
            $val2['trano'] = $dntrano;
            $val2['val_kode'] = $transdetail['val_kode'];
            if ($val2['trano'] != $dntrano && $val2['ref_number'] != $prem_no) {
                $this->FINANCE->AccountingCloseAR->insert($val2);
            } else {
                //Cek yang sudah ada di tabel jurnal bank...
                $cek = $this->FINANCE->AccountingCloseAR->fetchRow("trano = '$dntrano' AND ref_number = '$prem_no' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}'");
                if ($cek) {
                    if ($cek['stsclose'] != 1 && $cek['stspost'] != 1) {
                        $this->FINANCE->AccountingCloseAR->delete("trano = '$dntrano' AND ref_number = '$prem_no' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}' AND tgl != '{$val2['tgl']}'");
                        $this->FINANCE->AccountingCloseAR->insert($val2);
                    }
                } else {
                    $val2['trano'] = $dntrano;
                    $val2['ref_number'] = $val2['ref_number'];
                    $this->FINANCE->AccountingCloseAR->insert($val2);
                }
            }
        }
   
        $log2['dnreimburs-detail-after'] = $this->nota_debit_detail->fetchAll("trano = '$dntrano'")->toArray();
        $log['dnreimburs-header-before'] = $this->nota_debit_header->fetchAll("trano = '$dntrano'")->toArray();

        $updateNDheader = array(
            'prj_kode' => $transdetail['prj_kode'],
            'prj_nama' => $transdetail['prj_nama'],
            'sit_kode' => $transdetail['sit_kode'],
            'sit_nama' => $transdetail['sit_nama'],
            'ket' => $transdetail['description'],
            'cus_kode' => $transdetail['cus_kode'],
            'statusppn' => $payment['ppn'],
            'val_kode' => $val['val_kode'],
            'user' => $payment['user-payment'],
            'rateidr' => str_replace(",", "", $rateidr),
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes'],
            'paymentterm' => $payment['payment-term'],
            'top' => $payment['top'],
            'coa_kode' => $payment['coa-code'],
            'coa_nama' => $payment['coa-name'],
            'bnk_kode' => $banklist['bnk_kode'],
            'bnk_nama' => $banklist['bnk_nama'],
            'bnk_norek' => $banklist['bnk_norek'],
            'bnk_noreknama' => $banklist['bnk_noreknama'],
            'bnk_cabang' => $banklist['bnk_cabang'],
            'bnk_alamat' => $banklist['bnk_alamat'],
            'bnk_kota' => $banklist['bnk_kota'],
            'destination' => $payment['destination'],
            'destinationaddress' => $payment['destination-add']
        );

        $this->nota_debit_header->update($updateNDheader, "trano = '$dntrano'");

        $log2['dnreimburs-header-after'] = $this->nota_debit_header->fetchAll("trano = '$dntrano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $dntrano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $transdetail['prj_kode'],
            "sit_kode" => $transdetail['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        $return = array('success' => true, 'number' => $dntrano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editpaymentdebitnotereimbursAction() {
        $this->view->userPayment = $this->session->userName;
    }

    public function getpaiddebitnotereimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $textsearch = str_replace("_", "/", $textsearch);

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option == 1) {
            $search = "PDN.trano like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 2) {
            $search = "PDN.dn_no like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "PDN.prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "PDN.sit_kode like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

//        $data['data'] = $this->payment_nota_debit_header->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();
//        $data['total'] = $this->payment_nota_debit_header->fetchAll()->count();

        $data = $this->payment_nota_debit_header->viewpaiddebitnote($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepaymentdebitnoteAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $transdetail = Zend_Json::decode($this->getRequest()->getParam('transdetail'));
        $itemlist = Zend_Json::decode($this->getRequest()->getParam('itemslist'));
        $payment = Zend_Json::decode($this->getRequest()->getParam('payment'));
        $dn_trano = $this->getRequest()->getParam('dn_trano');
//        $type_trano = $this->getRequest()->getParam('type_trano');
        $paid_dn_trano = $this->getRequest()->getParam('paid_dn_trano');
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));

        $jsonDeduction = $this->getRequest()->getParam('deduction');
        $deduction = Zend_Json::decode($jsonDeduction);

//        var_dump($type_trano);die;
        $log['paiddn-detail-before'] = $this->payment_nota_debit_detail->fetchAll("trano = '$paid_dn_trano'")->toArray();

        $this->payment_nota_debit_detail->delete("trano = '$paid_dn_trano'");

        $tgl = date('Y-m-d H:i:s');
        $urut = 1;

        if ($itemlist[0]['val_kode'] == 'USD')
            $rateidr = $payment[0]['rate_text'];
        else
            $rateidr = 0;

        $totalDeduction = 0;
        if ($deduction) {
            foreach ($deduction as $k => $v) {
                $totalDeduction+= $v['total'];
            }
        }

        foreach ($itemlist as $key => $val) {
            $jumlah = $val['qty'] * $val['harga'];

            $arrayInsertDetail = array(
                'trano' => $paid_dn_trano,
                'tgl' => $tgl,
                'dn_no' => $dn_trano,
                'urut' => $urut,
                'prj_kode' => $transdetail['prj_kode'],
                'prj_nama' => $transdetail['prj_nama'],
                'sit_kode' => $transdetail['sit_kode'],
                'sit_nama' => $transdetail['sit_nama'],
                'ket' => $transdetail['description'],
                'cus_kode' => $transdetail['cus_kode'],
                'workid' => $transdetail['workid'],
                'workname' => $transdetail['worknama'],
                'kode_brg' => $val['kode_brg'],
                'nama_brg' => $val['nama_brg'],
                'qty' => str_replace(",", "", $val['qty']),
                'harga' => str_replace(",", "", $val['harga']),
                'rateidr' => str_replace(",", "", $rateidr),
                'val_kode' => $val['val_kode'],
                'jumlah' => $jumlah,
                'statusppn' => $payment['ppn'],
                'total' => str_replace(",", "", $payment['payment-value']),
                'type' => $payment['option'],
                'petugas' => $payment['user-payment']
            );
            $urut++;

            $this->payment_nota_debit_detail->insert($arrayInsertDetail);
        }

        $log2['paiddn-detail-after'] = $this->payment_nota_debit_detail->fetchAll("trano = '$paid_dn_trano'")->toArray();
        $log['paiddn-header-before'] = $this->payment_nota_debit_header->fetchAll("trano = '$paid_dn_trano'")->toArray();

        $updatepaiddnheader = array(
            'prj_kode' => $transdetail['prj_kode'],
            'prj_nama' => $transdetail['prj_nama'],
            'sit_kode' => $transdetail['sit_kode'],
            'sit_nama' => $transdetail['sit_nama'],
            'ket' => $transdetail['description'],
            'cus_kode' => $transdetail['cus_kode'],
            'statusppn' => $payment['ppn'],
            'val_kode' => $val['val_kode'],
            'user' => $payment['user-payment'],
            'rateidr' => str_replace(",", "", $rateidr),
            'total' => str_replace(",", "", $payment['payment-value']),
            'paymentnotes' => $payment['payment-notes'],
            'total_deduction' => $totalDeduction,
            'deduction_list' => $jsonDeduction
        );

        $this->payment_nota_debit_header->update($updatepaiddnheader, "trano = '$paid_dn_trano'");

        $rateidr = floatval(str_replace(",", "", $rateidr));

        //Cek jurnal AR sudah ada yg diclose apa belum...
        $cek = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$paid_dn_trano' AND stsclose = 1");
        if (count($cek->toArray()) == 0) {
            $this->FINANCE->AccountingJurnalBank->delete("trano = '$paid_dn_trano'");
        }

        foreach ($jurnal as $key2 => $val2) {

            unset($val2['tipe']);
            unset($val2['status_doc_rpc']);
            unset($val2['status_doc_cip']);
            unset($val2['tipe_jurnal']);
            unset($val2['urut']);
            unset($val2['ket']);
            $val2['type'] = 'BPV';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['prj_kode'] = $transdetail['prj_kode'];
            $val2['sit_kode'] = $transdetail['sit_kode'];
            $val2['trano'] = $paid_dn_trano;
            $val2['ref_number'] = $val2['ref_number'];
            $val2['val_kode'] = $transdetail['val_kode'];
            $val2['rateidr'] = $rateidr;

            if ($val2['trano'] != $paid_dn_trano && $val2['ref_number'] != $dn_trano) {
                $this->FINANCE->AccountingJurnalBank->insert($val2);
            } else {
                //Cek yang sudah ada di tabel jurnal bank...
                $cek = $this->FINANCE->AccountingCloseAR->fetchRow("trano = '$paid_dn_trano' AND ref_number = '$dn_trano' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}'");
                if ($cek) {
                    if ($cek['stsclose'] != 1 && $cek['stspost'] != 1) {
                        $this->FINANCE->AccountingJurnalBank->delete("trano = '$paid_dn_trano' AND ref_number = '$dn_trano' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}' AND tgl != '{$val2['tgl']}'");
                        $this->FINANCE->AccountingJurnalBank->insert($val2);
                    }
                } else {
                    $val2['trano'] = $paid_dn_trano;
                    $val2['ref_number'] = $val2['ref_number'];
                    $this->FINANCE->AccountingJurnalBank->insert($val2);
                }
            }
        }

        $log2['paiddn-header-after'] = $this->payment_nota_debit_header->fetchAll("trano = '$paid_dn_trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $paid_dn_trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $transdetail['prj_kode'],
            "sit_kode" => $transdetail['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        $json = "{success: true, number : '$paid_dn_trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function paymentvoucherremAction() {
        $this->view->type = 'REM';
    }

    public function doinsertremvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));

        $bank_coa = QDC_Finance_Coa::factory()->getCoaBank(
                array(
                    "type" => $form['trans']
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

//        $lastTrans = $counter->getLastTrans($form['trans']);
//        $last = abs($lastTrans['urut']);
//        $last = $last + 1;
//        $trano = $lastTrans['tra_no'] .'-'. $last;
//        $where = "id=".$lastTrans['id'];
//        $counter->update(array("urut" => $last),$where);

        $trano = $counter->setNewTrans($form['trans']);

        if ($form['valuta-1'] == 'USD')
            $rateidr = $form['rateidr'];
        else
            $rateidr = 0;

        $REMd = new Finance_Models_ReimbursDetail();
        $REMh = new Finance_Models_ReimbursHeader();
        $uid = $this->session->userName;
        $tgl = date('Y-m-d');
        if ($form['date'] != '')
            $date = date('Y-m-d', strtotime($form['date']));
        else
            $date = date('Y-m-d');
        $urut = 1;

        foreach ($list as $k => $v) {
            $rem = $REMh->fetchRow("trano='{$v['ref_number']}'")->toArray();
            $remd = $REMd->fetchAll("trano='{$v['ref_number']}'")->toArray();

//            var_dump($rem,$remd);die;

            foreach ($remd as $key => $val) {
                $jumlah = $val['harga'] * $val['qty'];
                $insertdetail = array(
                    'trano' => $trano,
                    'tgl' => $tgl,
                    'voc_trano' => $form['voc-number'],
                    'rem_no' => $v['ref_number'],
                    'urut' => $urut,
                    'prj_kode' => $val['prj_kode'],
                    'prj_nama' => $val['prj_nama'],
                    'sit_kode' => $val['sit_kode'],
                    'sit_nama' => $val['sit_nama'],
                    'petugas' => $uid,
                    'total' => $v['total_payment'],
                    'val_kode' => $val['val_kode'],
                    'kode_brg' => $val['kode_brg'],
                    'nama_brg' => $val['nama_brg'],
                    'qty' => $val['qty'],
                    'harga' => $val['harga'],
                    'jumlah' => $jumlah,
                    'rateidr' => $rateidr,
                    'type' => $form['pay-type'],
                    'ket' => $form['notes'],
                    'cus_kode' => $val['cus_kode']
                );

                $this->payment_reimbursD->insert($insertdetail);
            }
            $insertheader = array(
                'trano' => $trano,
                'tgl' => $tgl,
                'voc_trano' => $form['voc-number'],
                'rem_no' => $v['ref_number'],
                'prj_kode' => $rem['prj_kode'],
                'prj_nama' => $rem['prj_nama'],
                'sit_kode' => $rem['sit_kode'],
                'sit_nama' => $rem['sit_nama'],
                'total' => $v['total_payment'],
                'val_kode' => $rem['val_kode'],
                'user' => $uid,
                'rateidr' => $rateidr,
                'type' => $form['pay-type'],
                'ket' => $form['notes'],
                'cus_kode' => $rem['cus_kode']
            );

            $this->payment_reimbursH->insert($insertheader);
        }

        //insert jurnal bank

        foreach ($jurnal as $key2 => $val2) {

            unset($val2['tipe']);
            unset($val2['urut']);
            unset($val2['is_minus']);
            $val2['type'] = 'BPV';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['prj_kode'] = $rem['prj_kode'];
            $val2['sit_kode'] = $rem['sit_kode'];
            $val2['trano'] = $form['voc-number'];
            $val2['ref_number'] = $val2['ref_number'];
            $val2['val_kode'] = $rem['val_kode'];
            $val2['rateidr'] = $rem['rateidr'];

            $this->jurnal_bank->insert($val2);


//            $cip_coa = $this->coa->fetchRow("coa_kode = '{$val2['coa_kode']}'");
//
//            $insertrem = array(
//                "trano" => $form['voc-number'],
//                "ref_number" => $val2['ref_number'],
//                "prj_kode" => $val2['prj_kode'],
//                "sit_kode" => $val2['sit_kode'],
//                "tgl" => date('Y-m-d H:i:s'),
//                "uid" => $uid,
//                "coa_kode" => $cip_coa['coa_kode'],
//                "coa_nama" => $cip_coa['coa_nama'],
//                "debit" => $val2['total_payment'],
//                'val_kode' => $val2['valuta'],
//                "credit" => 0,
//                "type" => 'BPV'
//            );
//
//            $this->jurnal_bank->insert($insertrem);
//            foreach($bank_coa['data'] as $k3 => $v3)
//            {
//
//                $totalInsert = $val2['total_payment'];
//                if ($val2['valuta'] != 'IDR')
//                {
//                    if (strpos($v3['coa_nama'],'Exchange') !== false)
//                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
//                }
//                $insertbank = array(
//                    "trano" => $form['voc-number'],
//                    "ref_number" => $val2['ref_number'],
//                    "prj_kode" => $val2['prj_kode'],
//                    "sit_kode" => $val2['sit_kode'],
//                    "tgl" => date('Y-m-d H:i:s'),
//                    "uid" => $uid,
//                    "coa_kode" => $bank_coa['coa_kode'],
//                    "coa_nama" => $bank_coa['coa_nama'],
//                    "credit" => $totalInsert,
//                    'val_kode' => $val2['valuta'],
//                    "debit" => 0,
//                    "type" => 'BPV'
//                );
//
//                $this->jurnal_bank->insert($insertbank);
//            }
//
//                $totalInsert = $val2['total_payment'];
//                if ($val2['valuta'] != 'IDR')
//                {
//                    if (strpos($v3['coa_nama'],'Exchange') !== false)
//                        $totalInsert = (floatval($val2['total_payment']) * $rateidr) - $val2['total_payment'];
//                }
//                $insertbank = array(
//                    "trano" => $form['voc-number'],
//                    "ref_number" => $val2['ref_number'],
//                    "prj_kode" => $val2['prj_kode'],
//                    "sit_kode" => $val2['sit_kode'],
//                    "tgl" => date('Y-m-d H:i:s'),
//                    "uid" => $uid,
//                    "coa_kode" => $v3['coa_kode'],
//                    "coa_nama" => $v3['coa_nama'],
//                    "credit" => $totalInsert,
//                    'val_kode' => $val2['valuta'],
//                    "debit" => 0,
//                    "type" => 'BPV'
//                );
//
//                $this->jurnal_bank->insert($insertbank);
//            }
        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaymentremlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS trano,rem_no,prj_kode,prj_nama,sit_kode,sit_nama FROM finance_payment_reimbursement  ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function getpaymentremlistbyparamAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('nilai');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM finance_payment_reimbursement  WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function editpaymentvoucherremAction() {
        $trano = $this->getRequest()->getParam("trano");
        $trano = str_replace('_', "/", $trano);
        $dataEdit = $this->payment_reimbursH->fetchRow("trano = '$trano'");

        if ($dataEdit)
            $dataEdit = $dataEdit->toArray();

        $dataEdit['jenis_document'] = 'REM';

        //Ambil jurnal bank apabila ada...
        $cekDetail = $this->payment_reimbursD->fetchAll("trano = '$trano'");
        $arrayJurnal['data'] = array();
        if ($cekDetail) {
            $urut = 1;
            foreach ($cekDetail as $k => $v) {
                $cekDetail[$k]['urut'] = $urut;
                $refNumber = $v['rem_no'];
                $cekJurnal = $this->jurnal_bank->fetchAll("trano = '$trano' AND ref_number = '$refNumber'");
                if ($cekJurnal) {
                    $cekJurnal = $cekJurnal->toArray();
                    foreach ($cekJurnal as $k2 => $v2) {
                        $cekJurnal[$k2]['urut'] = $urut;
                        $cekJurnal[$k2]['tipe'] = $v2['type'];
                        $arrayJurnal['data'][] = $cekJurnal[$k2];
                    }
                    $urut++;
                }
            }
        }

        $this->view->transType = $this->DEFAULT->MasterCounter->getTransTypeFlip($trano);
        $trano = str_replace('/', "_", $trano);
        $this->view->type = 'REM';
        $this->view->data = $dataEdit;
        $this->view->trano = $trano;
        $this->view->jsonJurnal = Zend_Json::encode($arrayJurnal);
    }

    public function doupdateremvoucherpaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $form = Zend_Json::decode($this->getRequest()->getParam('form'));
        $list = Zend_Json::decode($this->getRequest()->getParam('list'));
        $trano = $this->getRequest()->getParam('trano');
        $trano = str_replace('_', "/", $trano);
        $jurnal = Zend_Json::decode($this->getRequest()->getParam('jurnal'));

        $form['pay-value'] = str_replace(",", "", $form['pay-value']);
        $form['rateidr'] = str_replace(",", "", $form['rateidr']);

        foreach ($list as $k => $v) {

            $arrayUpdate = array(
                "total" => $v['total_payment'],
                "ket" => $form['notes'],
                "type" => $form['pay-type']
//                "tgl_jtt" => date('Y-m-d',strtotime($form['date']))
            );

            $this->payment_reimbursH->update($arrayUpdate, "trano = '$trano' AND rem_no = '{$v['ref_number']}'");
            $this->payment_reimbursD->update($arrayUpdate, "trano = '$trano' AND rem_no = '{$v['ref_number']}'");

            //insert jurnal bank

            foreach ($jurnal as $key2 => $val2) {

                if ($val2['trano'] != $trano && $val2['ref_number'] != $v['ref_number'])
                    continue;

                unset($val2['tipe']);
                unset($val2['urut']);
                $val2['type'] = 'BPV';
                $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
                $val2['tgl'] = new Zend_Db_Expr("NOW()");
                //Cek yang sudah ada di tabel jurnal bank...
                $cek = $this->jurnal_bank->fetchRow("trano = '$trano' AND ref_number = '{$val2['ref_number']}' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}'");
                if ($cek) {
                    $val2['rateidr'] = $form['rateidr'];
                    if ($cek['stsclose'] != 1 && $cek['stspost'] != 1) {
                        $this->jurnal_bank->delete("trano = '$trano' AND ref_number = '{$val2['ref_number']}' AND prj_kode = '{$val2['prj_kode']}'  AND sit_kode = '{$val2['sit_kode']}' AND coa_kode = '{$val2['coa_kode']}'");
                        $this->jurnal_bank->insert($val2);
                    }
                } else {
                    $val2['trano'] = $trano;
                    $val2['prj_kode'] = $v['prj_kode'];
                    $val2['sit_kode'] = $v['sit_kode'];
                    $val2['trano'] = $trano;
                    $val2['ref_number'] = $val2['ref_number'];
                    $this->jurnal_bank->insert($val2);
                }

                unset($jurnal[$key2]);
            }
        }

        foreach ($jurnal as $key2 => $val2) {
            unset($val2['tipe']);
            unset($val2['urut']);
            unset($val2['is_minus']);
            $val2['type'] = 'BPV';
            $val2['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $val2['tgl'] = new Zend_Db_Expr("NOW()");
            $val2['rateidr'] = $form['rateidr'];
            $val2['prj_kode'] = $v['prj_kode'];
            $val2['sit_kode'] = $v['sit_kode'];
            $val2['trano'] = $trano;
            $val2['ref_number'] = $val2['ref_number'];
            $this->jurnal_bank->insert($val2);
        }

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
