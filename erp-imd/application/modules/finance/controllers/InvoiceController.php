<?php

class Finance_InvoiceController extends Zend_Controller_Action {

    private $db;
    private $session;
    private $project;
    private $budget;
    private $requestInovice;
    private $workflow;
    private $const;
    private $files;
    private $workflowTrans;
    private $invoice;
    private $invoiceD;
    private $paymentInvoice;
    private $log;
    private $jurnalar;
    private $coa;
    private $FINANCE;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->project = new Default_Models_MasterProject();
        $this->budget = new Default_Models_Budget();
        $this->requestInovice = new Finance_Models_RequestInvoice();
        $this->invoice = new Finance_Models_Invoice();
        $this->invoiceD = new Finance_Models_InvoiceDetail();
        $this->paymentInvoice = new Finance_Models_PaymentInvoice();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
        $this->files = new Default_Models_Files();
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->log = new Admin_Models_Logtransaction();
        $this->jurnalar = new Finance_Models_AccountingCloseAR();
        $this->coa = new Finance_Models_MasterCoa();

        //Pakai library QDC untuk autoload model...
        //Definisikan model yg akan dipakai, kosongkan jika ingin memakai semua model (not recommended)..
        $models = array(
            "AccountingJurnalBank",
            "AccountingCloseAR"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function invoiceAction() {
        
    }

    public function requestinvoiceAction() {
        
    }

    public function getprojectinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");

        if ($sitKode != '') {
            $where = " AND sit_kode = '$sitKode'";
        }
        $pro = $this->project->getProjectAndCustomer($prjKode, true);
        if ($pro) {
            $cusKode = $pro[0]['cus_kode'];
            $cusNama = $pro[0]['cus_nama'];
            $ret['customer'] = array(
                "kode" => $cusKode,
                "nama" => $cusNama
            );
        }

        $boq2CurrentIDR = 0;
        $boq2CurrentUSD = 0;

        if ($sitKode != '') {
            $boq = $this->budget->getBoq2('summary-current', $prjKode, $sitKode);
            if ($boq) {
                $boq2CurrentIDR = $boq['totalCurrentIDR'];
                $boq2CurrentUSD = $boq['totalCurrentHargaUSD'];
            } else
                $ret['success'] = false;
        }
        else {
            $sites = new Default_Models_MasterSite();
            $site = $sites->fetchAll("prj_kode = '$prjKode'");
            if ($site) {
                $site = $site->toArray();
                foreach ($site as $k => $v) {
                    $sitKode = $v['sit_kode'];
                    $boq = $this->budget->getBoq2('summary-current', $prjKode, $sitKode);
                    if ($boq) {
                        $boq2CurrentIDR += floatval($boq['totalCurrentIDR']);
                        $boq2CurrentUSD += floatval($boq['totalCurrentHargaUSD']);
                    }
                }
            }
        }
        $req = $this->requestInovice->fetchAll("prj_kode = '$prjKode' $where");
        if ($req) {
            $req = $req->toArray();
            $totalRequestIDR = 0;
            $totalRequestUSD = 0;
            $requested = array();
            foreach ($req as $k => $v) {
                if ($v['val_kode'] == 'IDR')
                    $totalRequestIDR += floatval($v['total']);
                elseif ($v['val_kode'] == 'USD')
                    $totalRequestUSD += floatval($v['total']);

                $requested[] = array(
                    "trano" => $req['trano'],
                    "co_no" => $req['co_no'],
                    "statusppn" => $req['statusppn'],
                    "total" => $req['total'],
                    "val_kode" => $req['val_kode'],
                );
            }


            $ret['summary'] = array(
                "boq2IDR" => $boq2CurrentIDR,
                "boq2USD" => $boq2CurrentUSD,
                "requestIDR" => $totalRequestIDR,
                "requestUSD" => $totalRequestUSD,
                "balanceIDR" => $boq2CurrentIDR - $totalRequestIDR,
                "balanceUSD" => $boq2CurrentUSD - $totalRequestUSD
            );

            $ret['detail'] = $requested;
            $ret['success'] = true;
        }
        $json = Zend_Json::encode($ret);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function apprequestinvoiceAction() {

        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/RINV';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '') {
            $data = $this->getRequest()->getParams();
            unset($data['module']);
            unset($data['controller']);
            unset($data['action']);
            unset($data['q']);
            unset($data['from']);
            $deleted = $data['deletedfile'];
            unset($data['deletedfile']);


            $file = Zend_Json::decode($data['file']);
            $data['uid'] = $this->session->userName;
            $data['tgl'] = date("d M Y H:i:s");

            $this->view->jsonFile = $data['file'];
            $this->view->jsonInvoiceDetail = $data['invoice_detail'];

            unset($data['file']);
            unset($data['invoice_detail']);
            $this->view->file = $file;
            $this->view->data = $data;
            $this->view->jsonData = Zend_Json::encode($data);
            if ($from == 'edit') {
                $this->view->trano = $data['trano'];
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApprovalGeneric($approve);
                    $jsonData2['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $file = $this->files->fetchAll("trano = '$approve'");
                    $reqInvoice = $this->requestInovice->fetchRow("trano = '$approve'")->toArray();

                    $reqInvoice['tgl'] = date("d M Y H:i:s", strtotime($reqInvoice['tgl']));
                    $cusKode = $this->project->getProjectAndCustomer($reqInvoice['prj_kode']);
                    $reqInvoice['cus_nama'] = $cusKode[0]['cus_nama'];
                    $invoiceDetail = $reqInvoice['invoice_detail'];

//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];


                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->data = $reqInvoice;
                    $this->view->file = $file;
                    $this->view->trano = $approve;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->jsonInvoiceDetail = $invoiceDetail;
                }
            }
        }
    }

    public function insertrequestinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $invoice_detail = $this->getRequest()->getParam('invoice_detail');

        $jsonData = Zend_Json::decode($json);
        $jsonFile = Zend_Json::decode($file);
        $jsonInvoiceDetail = Zend_Json::decode($invoice_detail);


        //$itemID='',$workflowType='',$paramArray='',$approve='',$items='',$prjKode='',$generic=false,$revisi=false
        $items = $jsonData;
        $items["prj_kode"] = $jsonData['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "RINV",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonData['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $jsonData['tgl'] = date("Y-m-d H:i:s", strtotime($jsonData['tgl']));
        $jsonData['trano'] = $trano;
        $jsonData['invoice_detail'] = $invoice_detail=='Array' ? array() : $invoice_detail;

        $this->requestInovice->insert($jsonData);

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addinvoiceAction() {
        
    }

    public function getrequestinvoicedetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $all = $this->getRequest()->getParam('all');
        $check = $this->getRequest()->getParam('checkworkflow');
        $checkInvoiced = $this->getRequest()->getParam('checkinvoiced');

        if ($all == '' || $all == 'true') {
            $all = true;
            if ($option == '')
                $option = 'trano';
        }
        else {
            $all = false;
            if ($option == '')
                $option = 'trano';
        }
        if ($textsearch == '')
            $search = "";
        else
            $search = $option . " LIKE '%" . $textsearch . "%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->requestInovice->viewallrequest($offset, $limit, $dir, $sort, $search, $all, $textsearch, $check, $checkInvoiced);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function appinvoiceAction() {

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);



        $deleted = $data['deletedfile'];
        $this->view->deletedFile = $deleted;

        $file = Zend_Json::decode($data['file']);
        $this->view->file = $file;
        $this->view->jsonFile = $data['file'];

        $invoice = Zend_Json::decode($data['invoiceDetail']);
        $bank = Zend_Json::decode($data['bank']);
        $data['uid'] = $this->session->userName;

        if ($data['statusppn'] == 'YES')
            $data['statusppn'] = 'Y';
        else
            $data['statusppn'] = 'N';

        $deduction_before = $deduction_after = $total = 0;
        foreach ($invoice as $k => $v) {
            $total += floatval($v['total']);
            if (count($v['deduction_before']) > 0) {
                foreach ($v['deduction_before'] as $key => $val) {
                    $deduction_before += $val['total'];
                }
            }
            $invoice[$k]['total_deduction_before'] = $deduction_before;
            if (count($v['deduction_after']) > 0) {
                foreach ($v['deduction_after'] as $key => $val) {
                    $deduction_after += $val['total'];
                }
            }
            $invoice[$k]['total_deduction_after'] = $deduction_after;
        }

        $this->view->invoice = $invoice;
        $this->view->bank = $bank;
        $this->view->total = $total;

        $isEdit = $data['from'];
        if ($isEdit) {
            unset($data['from']);
            $this->view->edit = true;
        }


        $this->view->data = $data;
        $this->view->jsonData = Zend_Json::encode($data);
    }

    public function insertinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);

        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans('INV');
        $file = Zend_Json::decode($data['file']);
        $detail = Zend_Json::decode($data['invoiceDetail']);
        $bank = Zend_Json::decode($data['bank']);
        $jurnal = Zend_Json::decode($data['jurnal']);

        if (count($file) > 0) {
            foreach ($file as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $data['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $urut = 0;
        $total = 0;
        $dd = new Finance_Models_InvoiceDetailDeduction();

        $totalInvoice = $totalPPN = $totalDeductBefore = $totalDeductAfter = 0;

        foreach ($detail as $k => $v) {
            unset($detail[$k]['id']);

            $deductionBefore = $v['deduction_before'];
            $deductionAfter = $v['deduction_after'];

            unset($detail[$k]['deduction_before']);
            unset($detail[$k]['deduction_after']);

            $detail[$k]['urut'] = $urut + 1;
            $detail[$k]['prj_kode'] = $data['prj_kode'];
            $detail[$k]['prj_nama'] = $data['prj_nama'];
            $detail[$k]['sit_kode'] = $data['sit_kode'];
            $detail[$k]['sit_nama'] = $data['sit_nama'];
            $detail[$k]['cus_kode'] = $data['cus_kode'];

            if ($detail[$k]['ppn'] > 0)
                $detail[$k]['statusppn'] = 'Y';
            else
                $detail[$k]['statusppn'] = 'N';

            if ($detail[$k]['holding_tax_val'] > 0)
                $detail[$k]['holding_tax_status'] = 'Y';
            else
                $detail[$k]['holding_tax_status'] = 'N';

            $valPPN = $detail[$k]['ppn'];
            $totalPPN += $valPPN;
            unset($detail[$k]['ppn']);

            $detail[$k]['date_created'] = date("Y-m-d H:i:s");
            $detail[$k]['tgl'] = $data['tgl'];

            $detail[$k]['trano'] = $trano;
            $detail[$k]['uid'] = $data['uid'];

            if ($v['invoice_detail'] != '')
                $detail[$k]['invoice_detail'] = Zend_Json::encode($v['invoice_detail'])=='Array' ? array() : Zend_Json::encode($v['invoice_detail']);

            $lastID = $this->invoiceD->insert($detail[$k]);

            if (count($deductionBefore) > 0) {
                foreach ($deductionBefore as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
                        "riv_no" => $detail[$k]['riv_no'],
                        "id_invoiced" => $lastID,
                        "tgl" => date("Y-m-d H:i:s"),
                        "uid" => $data['uid'],
                        "total" => $val['total'],
                        "ket" => $val['ket'],
                        "type" => "DEDUCTION",
                        "pos" => "BEFORE_TOTAL",
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $val['coa_nama'],
                    );

                    $dd->insert($arrayInsert);
                    $totalDeductBefore += $val['total'];
                }
            }

            if (count($deductionAfter) > 0) {
                foreach ($deductionAfter as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
                        "riv_no" => $detail[$k]['riv_no'],
                        "id_invoiced" => $lastID,
                        "tgl" => date("Y-m-d H:i:s"),
                        "uid" => $data['uid'],
                        "total" => $val['total'],
                        "ket" => $val['ket'],
                        "type" => "DEDUCTION",
                        "pos" => "AFTER_TOTAL",
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $val['coa_nama'],
                    );

                    $dd->insert($arrayInsert);
                    $totalDeductAfter += $val['total'];
                }
            }

            $total += floatval($detail[$k]['total']);
            $urut++;

            foreach ($jurnal as $k2 => $v2) {
                $memo = $v2['memo'];
                if ($memo == 'Invoice') {
                    $memo = 'Invoice ' . $trano;
                }
                $insertar = array(
                    "trano" => $trano,
                    "ref_number" => $v2['ref_number'],
                    "tgl" => new Zend_Db_Expr("NOW()"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "coa_kode" => $v2['coa_kode'],
                    "coa_nama" => $v2['coa_nama'],
                    "credit" => $v2['credit'],
                    "debit" => $v2['debit'],
                    'prj_kode' => $v2['prj_kode'],
                    'sit_kode' => $v2['sit_kode'],
                    'val_kode' => $data['val_kode'],
                    'rateidr' => $data['rateidr'],
                    'memo' => $memo,
                    'type' => 'AR-INV'
                );
                $this->jurnalar->insert($insertar);
            }
        }

        unset($data['invoiceDetail']);
        unset($data['bank']);
        unset($data['cus_nama']);
        unset($data['file']);
        unset($data['deletedfile']);
        unset($data['jurnal']);

        $data['bnk_norek'] = $bank['bnk_norek'];
        $data['bnk_cabang'] = $bank['bnk_cabang'];
        $data['bnk_alamat'] = $bank['bnk_alamat'];
        $data['bnk_nama'] = $bank['bnk_nama'];
        $data['bnk_noreknama'] = $bank['bnk_noreknama'];
        $data['bnk_kota'] = $bank['bnk_kota'];
        $data['bnk_kode'] = $bank['bnk_kode'];
        $data['total'] = $total;
        $data['total_invoice'] = (($total - $totalDeductBefore) + $totalPPN) - $totalDeductAfter;
        $data['date_created'] = date("Y-m-d H:i:s");
        $data['trano'] = $trano;

        //Payment terms string
        $data['paymentterm'] = (($data['top'] == 0 || $data['top'] == '') ? 30 : $data['top']) . " Days after invoiced received";

        $this->invoice->insert($data);

        $rivNo = $data['riv_no'];
        $myUid = $this->session->userName;
        $ldap = new Default_Models_Ldap();
//        $acc = $ldap->getAccount($myUid);
//        $myName = $acc['displayname'][0];
        $myName = QDC_User_Ldap::factory(array("uid" => $myUid))->getName();

        $cek = $this->requestInovice->fetchRow("trano = '$rivNo'");
        {
            $requestUid = $cek['uid'];
        }

        $date = date("d M Y H:i:s");
        $conversation = new Default_Models_Conversation();
        $arrayInsert = array(
            "id_reply" => 0,
            "workflow_item_id" => 0,
            "uid_sender" => $myUid,
            "uid_receiver" => $requestUid,
            "message" => "Your Request Invoice : $rivNo has been Invoiced by $myName at $date. Invoice Number : $trano",
            "date" => date('Y-m-d H:i:s'),
            "trano" => $rivNo,
            "prj_kode" => ''
        );

        $conversation->insert($arrayInsert);

        echo "{success: true, trano: '$trano'}";
    }

    public function reportinvoiceAction() {
        
    }

    public function getinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == '')
            $search = "";
        else
            $search = $option . " LIKE '%" . $textsearch . "%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->invoice->viewallrequest($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function paymentinvoiceAction() {
        $uid = $this->session->userName;
        $users = new Default_Models_Ldap();
//        $user = $users->getAccount($uid);
//        $this->view->userPayment = $user['displayname'][0];
        $this->view->userPayment = QDC_User_Ldap::factory(array("uid" => $uid))->getName();

        $coas = $this->coa->fetchRow("coa_kode = '1-2010'"); //COA AR IDR
        $coa['coaARIDR'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2021'"); //COA AR USD
        $coa['coaARUSD'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2022'"); //COA AR USD Exchange
        $coa['coaARUSDEx'] = $coas->toArray();

        $coas = $this->coa->fetchRow("coa_kode = '1-4717'"); //COA WHT
        $coa['coaWHT'] = $coas->toArray();

        $coas = $this->coa->fetchRow("coa_kode = '8-1210'"); //COA Gain Loss
        $coa['coaGainLoss'] = $coas->toArray();
        $this->view->coa = Zend_Json::encode($coa);
    }

    public function editpaymentinvoiceAction() {
        $uid = $this->session->userName;
        $users = new Default_Models_Ldap();
//        $user = $users->getAccount($uid);
//        $this->view->userPayment = $user['displayname'][0];
        $this->view->userPayment = QDC_User_Ldap::factory(array("uid" => $uid))->getName();

        $coas = $this->coa->fetchRow("coa_kode = '1-2010'"); //COA AR IDR
        $coa['coaARIDR'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2021'"); //COA AR USD
        $coa['coaARUSD'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2022'"); //COA AR USD Exchange
        $coa['coaARUSDEx'] = $coas->toArray();

        $coas = $this->coa->fetchRow("coa_kode = '1-4717'"); //COA WHT
        $coa['coaWHT'] = $coas->toArray();

        $coas = $this->coa->fetchRow("coa_kode = '8-1210'"); //COA Gain Loss
        $coa['coaGainLoss'] = $coas->toArray();
        $this->view->coa = Zend_Json::encode($coa);
    }

    public function getformpayinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->invoice->fetchRow("trano = '$trano'")->toArray();
        $dataDetail = $this->invoiceD->fetchAll("trano = '$trano'")->toArray();
        $paidlistsum = $this->paymentInvoice->getpaidlist($trano);

        $d = new Finance_Models_InvoiceDetailDeduction();
        $bf = $af = 0;
        $db = $d->fetchAll("trano = '$trano' AND type = 'DEDUCTION' AND pos = 'BEFORE_TOTAL'");
        if ($db) {
            $db = $db->toArray();
            foreach ($db as $k => $v) {
                $bf += $v['total'];
            }
        }

        $db = $d->fetchAll("trano = '$trano' AND type = 'DEDUCTION' AND pos = 'AFTER_TOTAL'");
        if ($db) {
            $db = $db->toArray();
            foreach ($db as $k => $v) {
                $af += $v['total'];
            }
        }

        $withPPN = false;
        $data['total'] = 0;
        foreach ($dataDetail as $k => $v) {
            $data['total'] += $v['total'];
            if ($v['statusppn'] == 'Y')
                $withPPN = true;
        }

        $data['total_with_deduction'] = ($data['total'] - $bf - $af);
        $data['total_deduction_before'] = $bf;
        $data['total_deduction_after'] = $af;
        if ($withPPN)
            $data['total_ppn'] = ($data['total'] - $bf) * 0.1;
        else
            $data['total_ppn'] = 0;

        $data['total_deduction'] = $bf + $af;
//        $data['total_invoice'] = (($data['total'] - $bf) + $data['total_ppn']) - $af;

        $users = new Default_Models_Ldap();
//        $user = $users->getAccount($data['uid']);
//        $data['username'] = $user['displayname'][0];
        $data['username'] = QDC_User_Ldap::factory(array("uid" => $data['uid']))->getName();
        $data['tgl'] = date("d M Y", strtotime($data['tgl']));
        $totalPaid = 0;

        foreach ($paidlistsum as $k => $v) {
            $totalPaid += floatval($v['total']);
            $paidlistsum[$k]['tgl'] = date("Y-m-d", strtotime($v['tgl']));
        }

        $return = array("success" => true, "data" => $data, "sumpaidlist" => $totalPaid, "data_paid" => $paidlistsum);

//        $jsonparam = Zend_Json::encode($param);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getforeditpayinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $inv_no = $this->getRequest()->getParam('inv_no');

        $data = $this->paymentInvoice->getpaiddetail("$trano");
        $payinv = $this->paymentInvoice->fetchAll("inv_no = '$inv_no'");
        $totalpaid = 0;
        if ($payinv->toArray()) {
            $payinv = $payinv->toArray();
            foreach ($payinv as $key => $val) {
                if ($val['trano'] == $data['data'][0]['trano']) {
                    unset($payinv[$key]);
                    continue;
                }
                $totalpaid += ($val['total']);
            }
        }
        $payinv = array_values($payinv);

        $databank['data'] = $this->FINANCE->AccountingJurnalBank->fetchAll("trano ='$trano'");
        $totalbank = 0;
        if ($databank['data']->toArray()) {
            $databank['data'] = $databank['data']->toArray();
            if ($databank['data'][0]['val_kode'] != IDR) {
                foreach ($databank['data'] as $key => $val) {
                    if ($val['memo'] == 'Payment Invoice')
                        $totalbank += ($val['debit']);
                }
            }
        }
        $databank['totalbank'] = $totalbank;
        $data['sumpaidlist'] = $totalpaid;
        $deduction = Zend_Json::decode($data['data'][0]['deduction_list']);

        $return = array("success" => true, "data" => $data, "databank" => $databank, "datapaid" => $payinv, "deduction" => $deduction);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getviewinvoiceitemlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data['data'] = $this->invoiceD->getitemlist($trano);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaidlistAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data['data'] = $this->paymentInvoice->getpaidlist($trano);
        $users = new Default_Models_Ldap();
        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['tgl'] = date("d M Y", strtotime($v['tgl']));
//            $user = $users->getAccount($v['uid']);
//            $data['data'][$k]['username'] = $user['displayname'][0];
            $data['data'][$k]['username'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertpaymentinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);
        $counter = new Default_Models_MasterCounter();

        $mcb = new Finance_Models_MasterCoaBank();
        $type = $data['type'];

        $bank = $mcb->getBankFromType($type);

        $trano = $counter->setNewTrans($type);
        $payment = Zend_Json::decode($data['payment']);
        $jurnal = Zend_Json::decode($data['jurnal']);
        $invoice = Zend_Json::decode($data['invoice']);
        $deduction = Zend_Json::decode($data['deduction']);

        $tgl = $this->_getParam("tgl");
        if ($tgl == '')
            $tgl = date("Y-m-d");

        $totalDeduction = 0;
        if ($deduction) {
            foreach ($deduction as $k => $v) {
                $totalDeduction+= $v['total'];
            }
        }


        $rate = floatval(str_replace(",", '', $payment['val_rate_text']));

        foreach ($invoice as $k => $v) {
            $inv = $v['trano'];
            $totalInv = $v['total'];
            $detail = $v['rec']['data'];
            $arrayInsert = array(
                "inv_no" => $inv,
                "trano" => $trano,
                "total" => floatval(str_replace(",", '', $payment['payment-value'])),
                "total_wht" => floatval(str_replace(",", '', $detail['wht-value'])),
                "total_deduction" => $totalDeduction,
                "deduction_list" => $data['deduction'],
                "total_invoice" => $totalInv,
                "tgl" => $tgl,
                "prj_kode" => $detail['prj_kode'],
                "prj_nama" => $detail['prj_nama'],
                "sit_kode" => $detail['sit_kode'],
                "sit_nama" => $detail['sit_nama'],
                "cus_kode" => $detail['cus_kode'],
                "val_kode" => $detail['val_kode'],
                "rateidr" => $rate,
                "uid" => $this->session->userName,
                "statusppn" => $detail['ppn'],
                "bnk_norek" => $bank['bnk_norek'],
                "bnk_cabang" => $bank['bnk_cabang'],
                "bnk_alamat" => $bank['bnk_alamat'],
                "bnk_nama" => $bank['bnk_nama'],
                "bnk_noreknama" => $bank['bnk_noreknama'],
                "bnk_kota" => $bank['bnk_kota'],
                "bnk_kode" => $bank['bnk_kode'],
                "coa_kode" => $detail['coa_kode'],
                "coa_nama" => $detail['coa_nama'],
                "paymentterm" => $detail['paymentterm'],
                "paymentnotes" => $detail['payment-notes']
            );

            $this->paymentInvoice->insert($arrayInsert);
        }

        //insert ke jurnal bank...
        foreach ($jurnal as $k => $v) {
            unset($v['tipe']);
            $v['type'] = 'AR-INV';
            $v['ref_number'] = $inv;
            $v['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $v['tgl'] = $tgl;
            $v['prj_kode'] = $detail['prj_kode'];
            $v['sit_kode'] = $detail['sit_kode'];
            $v['trano'] = $trano;

            if ($v['rateidr'] == 0)
                $v['rateidr'] = $rate;

            $v['val_kode'] = $detail['val_kode'];

            $memoDeduction = strpos($v['memo'], 'Deduction');
            if ($bank['val_kode'] != $detail['val_kode'])
                if ($memoDeduction !== false)
                    $v['val_kode'] = $bank['val_kode'];

            $this->FINANCE->AccountingJurnalBank->insert($v);
        }


        $return = array("success" => true, "number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatepaymentinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParams();
        $trano = $data['trano'];
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);
        $counter = new Default_Models_MasterCounter();

        $mcb = new Finance_Models_MasterCoaBank();
        $type = $data['type'];

        $bank = $mcb->getBankFromType($type);

        $payment = Zend_Json::decode($data['payment']);
        $jurnal = Zend_Json::decode($data['jurnal']);
        $invoice = Zend_Json::decode($data['invoice']);
        $deduction = Zend_Json::decode($data['deduction']);


        $dat = $this->paymentInvoice->fetchAll("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log['payinv-detail-before'] = $dat;
        $this->paymentInvoice->delete("trano = '$trano'");

        $tgl = $this->_getParam("tgl");
        if ($tgl == '')
            $tgl = date("Y-m-d");

        $totalDeduction = 0;
        if ($deduction) {
            foreach ($deduction as $k => $v) {
                $totalDeduction+= $v['total'];
            }
        }

        $rate = floatval(str_replace(",", '', $payment['val_rate_text']));

        foreach ($invoice as $k => $v) {
            $inv = $v['trano'];
            $totalInv = $v['total'];
            $detail = $v['rec']['data'];
            $arrayInsert = array(
                "inv_no" => $inv,
                "trano" => $trano,
                "total" => floatval(str_replace(",", '', $payment['payment-value'])),
                "total_wht" => floatval(str_replace(",", '', $detail['wht-value'])),
                "total_deduction" => $totalDeduction,
                "deduction_list" => $data['deduction'],
                "total_invoice" => $totalInv,
                "tgl" => $tgl,
                "prj_kode" => $detail['prj_kode'],
                "prj_nama" => $detail['prj_nama'],
                "sit_kode" => $detail['sit_kode'],
                "sit_nama" => $detail['sit_nama'],
                "cus_kode" => $detail['cus_kode'],
                "val_kode" => $detail['val_kode'],
                "rateidr" => $rate,
                "uid" => $this->session->userName,
                "statusppn" => $detail['ppn'],
                "bnk_norek" => $bank['bnk_norek'],
                "bnk_cabang" => $bank['bnk_cabang'],
                "bnk_alamat" => $bank['bnk_alamat'],
                "bnk_nama" => $bank['bnk_nama'],
                "bnk_noreknama" => $bank['bnk_noreknama'],
                "bnk_kota" => $bank['bnk_kota'],
                "bnk_kode" => $bank['bnk_kode'],
                "coa_kode" => $detail['coa_kode'],
                "coa_nama" => $detail['coa_nama'],
                "paymentterm" => $detail['paymentterm'],
                "paymentnotes" => $detail['payment-notes']
            );

            $this->paymentInvoice->insert($arrayInsert);
        }
        $dat = $this->paymentInvoice->fetchAll("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log2['payinv-detail-before'] = $dat;


        $dat = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log['jurnalbank-detail-before'] = $dat;
        $this->FINANCE->AccountingJurnalBank->delete("trano = '$trano'");

        //insert ke jurnal bank...
        foreach ($jurnal as $k => $v) {
            unset($v['tipe']);
            $v['type'] = 'AR-INV';
            $v['ref_number'] = $inv;
            $v['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $v['tgl'] = $tgl;
            $v['prj_kode'] = $detail['prj_kode'];
            $v['sit_kode'] = $detail['sit_kode'];
            $v['trano'] = $trano;

            if ($v['rateidr'] == 0)
                $v['rateidr'] = $rate;

            $v['val_kode'] = $detail['val_kode'];

            $memoDeduction = strpos($v['memo'], 'Deduction');
            if ($bank['val_kode'] != $detail['val_kode'])
                if ($memoDeduction !== false)
                    $v['val_kode'] = $bank['val_kode'];


            $this->FINANCE->AccountingJurnalBank->insert($v);
        }

        $dat = $this->FINANCE->AccountingJurnalBank->fetchAll("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log2['jurnalbank-detail-after'] = $dat;

        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $return = array("success" => true, "number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editinvoiceAction() {
        
    }

    public function editrequestinvoiceAction() {
        $trano = $this->getRequest()->getParam('trano');
        $this->view->trano = $trano;
    }

    public function getdatarequestinvoiceforeditAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data = $this->requestInovice->fetchRow("trano = '$trano'");
        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file) {
            $file = $file->toArray();
        }

        $ret = $data->toArray();

        $sitKode = $data['sit_kode'];
        $prjKode = $data['prj_kode'];
        $valKode = $data['val_kode'];

        $pro = $this->project->getProjectAndCustomer($prjKode);
        if ($pro) {
            $cusNama = $pro[0]['cus_nama'];
        }

        $boq2CurrentIDR = 0;
        $boq2CurrentUSD = 0;
        $totalInvoicedIDR = 0;
        $totalPaymentIDR = 0;
        $totalInvoicedUSD = 0;
        $totalPaymentUSD = 0;

        if ($sitKode != '') {
            $where = " AND sit_kode = '$sitKode'";
        }

        if ($sitKode != '') {
            $boq = $this->budget->getBoq2('summary-current', $prjKode, $sitKode);
            if ($boq) {
                $boq2CurrentIDR = $boq['totalCurrentIDR'];
                $boq2CurrentUSD = $boq['totalCurrentHargaUSD'];
            }
            $invAndPayment = $this->requestInovice->getIvoiceAndPayment($trano, $prjKode, $sitKode);
            $totalInvoicedIDR = floatval($invAndPayment['totalInvoiceIDR']);
            $totalInvoicedUSD = floatval($invAndPayment['totalInvoiceUSD']);
            $totalPaymentIDR = floatval($invAndPayment['totalPaymentIDR']);
            $totalPaymentUSD = floatval($invAndPayment['totalPaymentUSD']);

            $totretIDR = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'IDR'");
            $totalRequestIDR = 0;
            if ($totretIDR) {
                $totretIDR = $totretIDR->toArray();
                foreach ($totretIDR as $k => $v) {
                    $totalRequestIDR += floatval($v['total']);
                }
            }
            $totretUSD = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'USD'");
            $totalRequestUSD = 0;
            if ($totretUSD) {
                $totretUSD = $totretUSD->toArray();
                foreach ($totretUSD as $k => $v) {
                    $totalRequestUSD += floatval($v['total']);
                }
            }
        } else {
            $sites = new Default_Models_MasterSite();
            $site = $sites->fetchAll("prj_kode = '$prjKode'");
            if ($site) {
                $site = $site->toArray();
                foreach ($site as $k => $v) {
                    $sitKode = $v['sit_kode'];
                    $boq = $this->budget->getBoq2('summary-current', $prjKode, $sitKode);
                    if ($boq) {
                        $boq2CurrentIDR += floatval($boq['totalCurrentIDR']);
                        $boq2CurrentUSD += floatval($boq['totalCurrentHargaUSD']);
                    }
                    $invAndPayment = $this->requestInovice->getIvoiceAndPayment($trano, $prjKode, $sitKode);
                    $totalInvoicedIDR += floatval($invAndPayment['totalInvoiceIDR']);
                    $totalInvoicedUSD += floatval($invAndPayment['totalInvoiceUSD']);
                    $totalPaymentIDR += floatval($invAndPayment['totalPaymentIDR']);
                    $totalPaymentUSD += floatval($invAndPayment['totalPaymentUSD']);

                    $totretIDR = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'IDR'");
                    $totalRequestIDR = 0;
                    if ($totretIDR) {
                        $totretIDR = $totretIDR->toArray();
                        foreach ($totretIDR as $k => $v) {
                            $totalRequestIDR += floatval($v['total']);
                        }
                    }
                    $totretUSD = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'USD'");
                    $totalRequestUSD = 0;
                    if ($totretUSD) {
                        $totretUSD = $totretUSD->toArray();
                        foreach ($totretUSD as $k => $v) {
                            $totalRequestUSD += floatval($v['total']);
                        }
                    }
                }
            }
        }

        $ret['cus_nama'] = $cusNama;
        $ret['boq2IDR'] = $boq2CurrentIDR;
        $ret['boq2USD'] = $boq2CurrentUSD;
        $ret['totalInvoiceIDR'] = $totalInvoicedIDR;
        $ret['totalInvoiceUSD'] = $totalInvoicedUSD;
        $ret['totalPaymentIDR'] = $totalPaymentIDR;
        $ret['totalPaymentUSD'] = $totalPaymentUSD;
        $ret['totalRequestOtherIDR'] = $totalRequestIDR;
        $ret['totalRequestOtherUSD'] = $totalRequestUSD;

        $return['data'] = $ret;
        $return['file'] = $file;
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updaterequestinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $deletedFile = $this->getRequest()->getParam('deletedfile');
        $jsonData = Zend_Json::decode($json);
        $jsonFile = Zend_Json::decode($file);
        $jsonDeletedFile = Zend_Json::decode($deletedFile);

        $invoice_detail = $this->getRequest()->getParam('invoice_detail');
        $jsonInvoiceDetail = Zend_Json::decode($invoice_detail);

        $trano = $jsonData['trano'];
        unset($jsonData['trano']);

        $jsonData['invoice_detail'] = $invoice_detail;

        //$itemID='',$workflowType='',$paramArray='',$approve='',$items='',$prjKode='',$generic=false,$revisi=false
        $items = $jsonData;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "RINV",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonData['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
//            "workflowType" => "RINV",
//        $jsonData['tgl'] = date("Y-m-d H:i:s",strtotime($jsonData['tgl']));
//        $jsonData['trano'] = $trano;
        //Insert ke log transaction
        $old = $this->requestInovice->fetchRow("trano = '$trano'")->toArray();
        $log['rinv-detail-before'] = $old;
        $log2['rinv-detail-after'] = $jsonData;
        //Update Request Invoice
        unset($jsonData['tgl']);
        unset($jsonData['uid']);
        $this->requestInovice->update($jsonData, "trano = '$trano'");

        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData['prj_kode'],
            "sit_kode" => $jsonData['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        if (count($jsonDeletedFile) > 0) {
            foreach ($jsonDeletedFile as $key => $val) {
                $this->files->delete("id = {$val['id']}");
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getinvoicedetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == '')
            $search = null;
        else
            $search = $option . " LIKE '%" . $textsearch . "%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $invoices = $this->invoice->fetchAll($search, array($sort . ' ' . $dir), $limit, $offset);
        if ($invoices) {
            $data['data'] = $invoices->toArray();
            $data['count'] = $this->invoice->fetchAll($search)->count();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdatainvoiceforeditAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('search');
        $data = $this->invoice->fetchRow("trano = '$trano'")->toArray();
        $dataDetail = $this->invoiceD->fetchAll("trano = '$trano'");

        $pro = $this->project->getProjectAndCustomer($data['prj_kode']);
        
        if ($pro) {
            $data['cus_nama'] = $pro[0]['cus_nama'];
        }

        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file) {
            $file = $file->toArray();
        }

        $riv_no = $data['riv_no'];
        $dataRequest = $this->requestInovice->fetchRow("trano = '$riv_no'");
        
        if ($dataRequest)
            $dataRequest = $dataRequest->toArray();

        $dataPayment = $this->paymentInvoice->fetchAll("inv_no = '$trano'");
        
        if ($dataPayment)
            $dataPayment = $dataPayment->toArray();

        $ldap = new Default_Models_Ldap();
        $dataRequest['uid_request'] = QDC_User_Ldap::factory(array("uid" => $data['uid']))->getName();

        $totPayment = 0;
        foreach ($dataPayment as $k => $v) {
            $totPayment += floatval($v['total']);
        }

        $data['totalPayment'] = $totPayment;
        $return['header'] = $data;
        $return['detail'] = $dataDetail->toArray();
        $i = 1;

        $dd = new Finance_Models_InvoiceDetailDeduction();

        foreach ($return['detail'] as $k => $v) {
            $id = $v['id'];
            $cekD = $dd->fetchAll("trano='$trano' AND id_invoiced = $id AND type = 'DEDUCTION' AND pos = 'BEFORE_TOTAL'");
            $beforeD = $afterD = 0;
            if ($cekD) {
                $cekD = $cekD->toArray();
                foreach ($cekD as $k2 => $v2) {
                    $beforeD += $v2['total'];
                }
                $return['detail'][$k]['deduction_before'] = $cekD;
            } else
                $return['detail'][$k]['deduction_before'] = array();

            $cekD = $dd->fetchAll("trano='$trano' AND id_invoiced = $id AND type = 'DEDUCTION' AND pos = 'AFTER_TOTAL'");

            if ($cekD) {
                $cekD = $cekD->toArray();
                foreach ($cekD as $k2 => $v2) {
                    $afterD += $v2['total'];
                }
                $return['detail'][$k]['deduction_after'] = $cekD;
            } else
                $return['detail'][$k]['deduction_after'] = array();
            if ($v['statusppn'] == 'Y') {
                $return['detail'][$k]['ppn'] = 0.1 * $return['detail'][$k]['total'];
            } else
                $return['detail'][$k]['ppn'] = 0;

            $return['detail'][$k]['deduction'] = $beforeD + $afterD;
            
            $v['invoice_detail'] = $v['invoice_detail']=='Array' ? '[]' : $v['invoice_detail'];

            if ($v['invoice_detail']) {
                $return['detail'][$k]['invoice_detail'] = Zend_Json::decode($v['invoice_detail']);
                $return['withdetail'] = true;
            }
            $i++;
            
        }        
        $dataRequest['tgl'] = $data['tgl'];
        $return['request'] = $dataRequest;
        $return['file'] = $file;

        $coa = $this->FINANCE->AccountingCloseAR->fetchAll("trano='$trano'");
        if ($coa) {
            $coa = $coa->toArray();
            $return['jurnal'] = array("data" => $coa, "total" => count($coa));
        } else
            $return['jurnal'] = array();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);

        $counter = new Default_Models_MasterCounter();

        $detail = Zend_Json::decode($data['invoiceDetail']);
        $bank = Zend_Json::decode($data['bank']);
        $file = Zend_Json::decode($data['file']);
        $deletedfile = Zend_Json::decode($data['deletedfile']);
        $jurnal = Zend_Json::decode($data['jurnal']);

        $trano = $data['trano'];

        $dat = $this->invoiceD->fetchAll("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log['inv-detail-before'] = $dat;
        $this->invoiceD->delete("trano = '$trano'");

        $urut = 0;
        $total = 0;
        $dd = new Finance_Models_InvoiceDetailDeduction();
        $dd->delete("trano = '$trano'");
        $totalInvoice = $totalPPN = $totalDeductBefore = $totalDeductAfter = 0;
        foreach ($detail as $k => $v) {
            unset($detail[$k]['id']);

            $deductionBefore = $v['deduction_before'];
            $deductionAfter = $v['deduction_after'];

            unset($detail[$k]['deduction_before']);
            unset($detail[$k]['deduction_after']);

            $detail[$k]['urut'] = $urut + 1;
            $detail[$k]['prj_kode'] = $data['prj_kode'];
            $detail[$k]['prj_nama'] = $data['prj_nama'];
            $detail[$k]['sit_kode'] = $data['sit_kode'];
            $detail[$k]['sit_nama'] = $data['sit_nama'];
            $detail[$k]['cus_kode'] = $data['cus_kode'];
            $detail[$k]['tgl'] = date("Y-m-d H:i:s", strtotime($data['tgl']));
            $detail[$k]['trano'] = $trano;
            $detail[$k]['uid'] = $data['uid'];

            if ($detail[$k]['ppn'] > 0)
                $detail[$k]['statusppn'] = 'Y';
            else
                $detail[$k]['statusppn'] = 'N';

            if ($detail[$k]['holding_tax_val'] > 0)
                $detail[$k]['holding_tax_status'] = 'Y';
            else
                $detail[$k]['holding_tax_status'] = 'N';

            $valPPN = $detail[$k]['ppn'];
            $totalPPN += $valPPN;
            unset($detail[$k]['ppn']);

            if ($v['invoice_detail'] != null)
                $detail[$k]['invoice_detail'] = Zend_Json::encode($v['invoice_detail'])=='Array' ? array() : Zend_Json::encode($v['invoice_detail']);

            $log2['inv-detail-after'][] = $detail[$k];
            $lastID = $this->invoiceD->insert($detail[$k]);

            if (count($deductionBefore) > 0) {
                foreach ($deductionBefore as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
                        "riv_no" => $detail[$k]['riv_no'],
                        "id_invoiced" => $lastID,
                        "tgl" => date("Y-m-d H:i:s", strtotime($data['tgl'])),
                        "uid" => $data['uid'],
                        "total" => $val['total'],
                        "ket" => $val['ket'],
                        "type" => "DEDUCTION",
                        "pos" => "BEFORE_TOTAL",
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $val['coa_nama'],
                    );

                    $dd->insert($arrayInsert);
                    $totalDeductBefore += $val['total'];
                }
            }

            if (count($deductionAfter) > 0) {
                foreach ($deductionAfter as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
                        "riv_no" => $detail[$k]['riv_no'],
                        "id_invoiced" => $lastID,
                        "tgl" => date("Y-m-d H:i:s"),
                        "uid" => $data['uid'],
                        "total" => $val['total'],
                        "ket" => $val['ket'],
                        "type" => "DEDUCTION",
                        "pos" => "AFTER_TOTAL",
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $val['coa_nama'],
                    );

                    $dd->insert($arrayInsert);
                    $totalDeductAfter += $val['total'];
                }
            }

            $total += floatval($detail[$k]['total']);
            $urut++;

            $this->jurnalar->delete("trano='$trano'");

            foreach ($jurnal as $k2 => $v2) {
                $insertar = array(
                    "trano" => $trano,
                    "ref_number" => $v2['ref_number'],
                    "tgl" => date("Y-m-d H:i:s", strtotime($data['tgl'])),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "coa_kode" => $v2['coa_kode'],
                    "coa_nama" => $v2['coa_nama'],
                    "credit" => $v2['credit'],
                    "debit" => $v2['debit'],
                    'prj_kode' => $v2['prj_kode'],
                    'sit_kode' => $v2['sit_kode'],
                    'val_kode' => $data['val_kode'],
                    'rateidr' => $data['rateidr'],
                    'type' => 'AR-INV'
                );
                $this->jurnalar->insert($insertar);
            }
        }

        if (count($file) > 0) {
            foreach ($file as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $data['prj_kode'],
                    "date" => date("Y-m-d H:i:s", strtotime($data['tgl'])),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        if (count($deletedfile) > 0) {
            foreach ($deletedfile as $key => $val) {
                $this->files->delete("id = {$val['id']}");
            }
        }

        unset($data['invoiceDetail']);
        unset($data['bank']);
        unset($data['cus_nama']);
        unset($data['file']);
        unset($data['deletedfile']);
        unset($data['jurnal']);

        $data['bnk_norek'] = $bank['bnk_norek'];
        $data['bnk_cabang'] = $bank['bnk_cabang'];
        $data['bnk_alamat'] = $bank['bnk_alamat'];
        $data['bnk_nama'] = $bank['bnk_nama'];
        $data['bnk_noreknama'] = $bank['bnk_noreknama'];
        $data['bnk_kota'] = $bank['bnk_kota'];
        $data['bnk_kode'] = $bank['bnk_kode'];
        $data['total'] = $total;
        $data['total_invoice'] = (($total - $totalDeductBefore) + $totalPPN) - $totalDeductAfter;
        $data['tgl'] = date("Y-m-d H:i:s", strtotime($data['tgl']));
        $data['paymentterm'] = (($data['top'] == 0 || $data['top'] == '') ? 30 : $data['top']) . " Days after invoiced received";

        $dat = $this->invoice->fetchRow("trano = '$trano'");
        if ($dat) {
            $dat = $dat->toArray();
        }
        $log['inv-header-before'] = $dat;
        $this->invoice->update($data, "trano = '$trano'");
        $dat = $this->invoice->fetchRow("trano = '$trano'");
        $log2['inv-header-after'] = $dat;

        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        echo "{success: true, trano: '$trano'}";
    }

    public function detailReportInvoiceAction() {
        
    }

    public function getdatapaymentinvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $textsearch = str_replace("_", '/', $textsearch);

        if ($textsearch == '')
            $search = null;
        else
            $search = $option . " LIKE '%" . $textsearch . "%'";

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $invoices = $this->paymentInvoice->fetchAll($search, array($sort . ' ' . $dir), $limit, $offset);
        if ($invoices) {
            $data['data'] = $invoices->toArray();
            $data['count'] = $this->paymentInvoice->fetchAll($search)->count();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>