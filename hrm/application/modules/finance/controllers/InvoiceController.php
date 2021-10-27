<?php
class Finance_InvoiceController extends Zend_Controller_Action
{
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

    public function init()
    {
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
        $this->log = new Admin_Model_Logtransaction();
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

    public function invoiceAction()
    {
        
    }

    public function requestinvoiceAction()
    {

    }

    public function getprojectinvoiceAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");

        if ($sitKode != '')
        {
            $where = " AND sit_kode = '$sitKode'";
        }
        $pro = $this->project->getProjectAndCustomer($prjKode);
        if ($pro)
        {
            $cusKode = $pro[0]['cus_kode'];
            $cusNama = $pro[0]['cus_nama'];
            $ret['customer'] = array(
                "kode" => $cusKode,
                "nama" => $cusNama
            );
        }

        $boq2CurrentIDR = 0;
        $boq2CurrentUSD = 0;

        if ($sitKode != '')
        {
            $boq = $this->budget->getBoq2('summary-current',$prjKode,$sitKode);
            if ($boq)
            {
                $boq2CurrentIDR = $boq['totalCurrentIDR'];
                $boq2CurrentUSD = $boq['totalCurrentHargaUSD'];
            }
            else
                $ret['success'] = false;
        }
        else
        {
            $sites = new Default_Models_MasterSite();
            $site = $sites->fetchAll("prj_kode = '$prjKode'");
            if ($site)
            {
                $site = $site->toArray();
                foreach ($site as $k => $v)
                {
                    $sitKode = $v['sit_kode'];
                    $boq = $this->budget->getBoq2('summary-current',$prjKode,$sitKode);
                    if ($boq)
                    {
                        $boq2CurrentIDR += floatval($boq['totalCurrentIDR']);
                        $boq2CurrentUSD += floatval($boq['totalCurrentHargaUSD']);
                    }
                }
            }
        }
        $req = $this->requestInovice->fetchAll("prj_kode = '$prjKode' $where");
        if ($req)
        {
            $req = $req->toArray();
            $totalRequestIDR = 0;
            $totalRequestUSD = 0;
            $requested = array();
            foreach($req as $k => $v)
            {
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

    public function apprequestinvoiceAction()
    {

        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/RINV';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '')
        {
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
            unset($data['file']);
            $this->view->file = $file;
            $this->view->data = $data;
            $this->view->jsonData = Zend_Json::encode($data);
            if ($from == 'edit')
            {
                $this->view->trano = $data['trano'];
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }
        }
        else
        {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs)
            {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$this->session->idUser);
                if ($user  || $show)
                {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $file = $this->files->fetchAll("trano = '$approve'");
                    $reqInvoice = $this->requestInovice->fetchRow("trano = '$approve'")->toArray();

                    $reqInvoice['tgl'] = date("d M Y H:i:s",strtotime($reqInvoice['tgl']));
                    $cusKode = $this->project->getProjectAndCustomer($reqInvoice['prj_kode']);
                    $reqInvoice['cus_nama'] = $cusKode[0]['cus_nama'];

                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);


                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->data = $reqInvoice;
                    $this->view->file = $file;
                    $this->view->trano = $approve;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    
                }
            }
        }
    }

    public function insertrequestinvoiceAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $jsonData = Zend_Json::decode($json);
        $jsonFile = Zend_Json::decode($file);

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

        $jsonData['tgl'] = date("Y-m-d H:i:s",strtotime($jsonData['tgl']));
        $jsonData['trano'] = $trano;

        $this->requestInovice->insert($jsonData);

        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
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

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addinvoiceAction()
    {
        
    }

    public function getrequestinvoicedetailAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $all = $this->getRequest()->getParam('all');
        $check = $this->getRequest()->getParam('checkworkflow');
        $checkInvoiced = $this->getRequest()->getParam('checkinvoiced');

        if ($all == '' || $all == 'true')
        {
            $all = true;
            if ($option == '')
                $option = 'trano';
        }
        else
        {
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

        $data = $this->requestInovice->viewallrequest($offset,$limit,$dir,$sort,$search,$all,$textsearch,$check,$checkInvoiced);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function appinvoiceAction()
    {

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);

        $deleted = $data['deletedfile'];
        $this->view->deletedFile = $deleted;
//        unset($data['deletedfile']);

        $file = Zend_Json::decode($data['file']);
        $this->view->file = $file;
        $this->view->jsonFile = $data['file'];
  //      unset($data['file']);
        $invoice = Zend_Json::decode($data['invoiceDetail']);
        $bank = Zend_Json::decode($data['bank']);
        $data['uid'] = $this->session->userName;
        $data['tgl'] = date("d M Y H:i:s");

        if ($data['statusppn'] == 'YES')
            $data['statusppn'] = 'Y';
        else
            $data['statusppn'] = 'N';

        $total = 0;
        foreach($invoice as $K => $v)
        {
            $total += floatval($v['total']);
        }

        $this->view->invoice = $invoice;
        $this->view->bank = $bank;
        $this->view->total = $total;

        $isEdit = $data['from'];
        if ($isEdit)
        {
            unset($data['from']);
            $this->view->edit = true;
        }


        $this->view->data = $data;
        $this->view->jsonData = Zend_Json::encode($data);
    }

    public function insertinvoiceAction()
    {
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

        if (count($file) > 0)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
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
        foreach($detail as $k => $v)
        {
            unset($detail[$k]['id']);
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
            unset($detail[$k]['ppn']);

            $detail[$k]['tgl'] = date("Y-m-d H:i:s");
            $detail[$k]['trano'] = $trano;
            $detail[$k]['uid'] = $data['uid'];

            $this->invoiceD->insert($detail[$k]);

            $total += floatval($detail[$k]['total']);
            $urut++;

            foreach($jurnal as $k2 => $v2)
            {
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
                    'type' => 'AR-INV'
                );
                $this->jurnalar->insert($insertar);
            }

//            if ($data['val_kode'] == 'IDR')
//            {
//                $coaAR = '1-2010';
//                $coaAR2 = '';
//            }
//            else
//            {
//                $coaAR = '1-2021';
//                $coaAR2 = '1-2022';
//            }
//
//            $coa = $this->coa->fetchRow("coa_kode = '{$coaAR}'");
//            //Coa AR
//            $insertar = array(
//                "trano" => $trano,
//                "ref_number" => $data['riv_no'],
//                "tgl" => new Zend_Db_Expr("NOW()"),
//                "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                "coa_kode" => $coa['coa_kode'],
//                "coa_nama" => $coa['coa_nama'],
//                "debit" => floatval($detail[$k]['harga']),
//                "credit" => 0,
//                'prj_kode' => $data['prj_kode'],
//                'val_kode' => $data['val_kode'],
//                'sit_kode' => $data['sit_kode'],
//                'rateidr' => $data['rateidr']
//            );
//            $this->jurnalar->insert($insertar);
//            $coa = $this->coa->fetchRow("coa_kode = '{$v['coa_kode']}'");
//
//            //Coa Unbilled
//            $insertar = array(
//                "trano" => $trano,
//                "ref_number" => $data['riv_no'],
//                "tgl" => new Zend_Db_Expr("NOW()"),
//                "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                "coa_kode" => $coa['coa_kode'],
//                "coa_nama" => $coa['coa_nama'],
//                "credit" => floatval($detail[$k]['harga']),
//                "debit" => 0,
//                'prj_kode' => $data['prj_kode'],
//                'val_kode' => $data['val_kode'],
//                'sit_kode' => $data['sit_kode'],
//                'rateidr' => $data['rateidr']
//            );
//
//            $this->jurnalar->insert($insertar);
//
//            if ($detail[$k]['statusppn'] == 'Y')
//            {
//                //Coa PPN - AR
//                $coa = $this->coa->fetchRow("coa_kode = '{$coaAR}'");
//                $insertar = array(
//                    "trano" => $trano,
//                    "ref_number" => $data['riv_no'],
//                    "tgl" => new Zend_Db_Expr("NOW()"),
//                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                    "coa_kode" => $coa['coa_kode'],
//                    "coa_nama" => $coa['coa_nama'],
//                    "debit" => (floatval($valPPN)),
//                    "credit" => 0,
//                    'prj_kode' => $data['prj_kode'],
//                    'val_kode' => $data['val_kode'],
//                    'sit_kode' => $data['sit_kode'],
//                    'rateidr' => $data['rateidr']
//                );
//                $this->jurnalar->insert($insertar);
//                //Coa PPN
//                $coa = $this->coa->fetchRow("coa_kode = '2-3100'"); //another hardcode...
//                $insertar = array(
//                    "trano" => $trano,
//                    "ref_number" => $data['riv_no'],
//                    "tgl" => new Zend_Db_Expr("NOW()"),
//                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                    "coa_kode" => $coa['coa_kode'],
//                    "coa_nama" => $coa['coa_nama'],
//                    "credit" => (floatval($valPPN)),
//                    "debit" => 0,
//                    'prj_kode' => $data['prj_kode'],
//                    'val_kode' => $data['val_kode'],
//                    'sit_kode' => $data['sit_kode'],
//                    'rateidr' => $data['rateidr']
//                );
//                $this->jurnalar->insert($insertar);
//            }
//
//            //Holding tax tidak dijurnal saat invoice, melainkan saat payment invoice
////            if($detail[$k]['holding_tax_status'] == 'Y')
////            {
////                $coa = $this->coa->fetchRow("coa_kode = '2-2100'");
////                //Coa Holding Tax
////                $insertar = array(
////                    "trano" => $trano,
////                    "ref_number" => $data['riv_no'],
////                    "tgl" => new Zend_Db_Expr("NOW()"),
////                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
////                    "coa_kode" => $coa['coa_kode'],
////                    "coa_nama" => $coa['coa_nama'],
////                    "credit" => floatval($detail[$k]['holding_tax_val']),
////                    "debit" => 0,
////                    'prj_kode' => $data['prj_kode'],
////                    'val_kode' => $data['val_kode'],
////                    'sit_kode' => $data['sit_kode'],
////                    'rateidr' => $data['rateidr']
////                );
////                $this->jurnalar->insert($insertar);
////            }
//
//            if ($coaAR2 != '')
//            {
//                $coa = $this->coa->fetchRow("coa_kode = '{$coaAR2}'");
//                //Coa AR USD - Exchange
//                $insertar = array(
//                    "trano" => $trano,
//                    "ref_number" => $data['riv_no'],
//                    "tgl" => new Zend_Db_Expr("NOW()"),
//                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                    "coa_kode" => $coa['coa_kode'],
//                    "coa_nama" => $coa['coa_nama'],
//                    "debit" => ((floatval($detail[$k]['harga']) * floatval($data['rateidr']) - floatval($detail[$k]['harga']))),
//                    "credit" => 0,
//                    'prj_kode' => $data['prj_kode'],
//                    'val_kode' => $data['val_kode'],
//                    'sit_kode' => $data['sit_kode'],
//                    'rateidr' => $data['rateidr']
//                );
//                $this->jurnalar->insert($insertar);
//                $coa = $this->coa->fetchRow("coa_kode = '{$v['coa_kode']}'");
//                //Coa Unbilled USD - Exchange
//                $insertar = array(
//                    "trano" => $trano,
//                    "ref_number" => $data['riv_no'],
//                    "tgl" => new Zend_Db_Expr("NOW()"),
//                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                    "coa_kode" => $coa['coa_kode'],
//                    "coa_nama" => $coa['coa_nama'],
//                    "credit" => ((floatval($detail[$k]['harga']) * floatval($data['rateidr']) - floatval($detail[$k]['harga']))),
//                    "debit" => 0,
//                    'prj_kode' => $data['prj_kode'],
//                    'val_kode' => $data['val_kode'],
//                    'sit_kode' => $data['sit_kode'],
//                    'rateidr' => $data['rateidr']
//                );
//
//                $this->jurnalar->insert($insertar);
//                if ($detail[$k]['statusppn'] == 'Y')
//                {
//                    //Coa PPN AR USD - Exchange
//                    $coa = $this->coa->fetchRow("coa_kode = '{$coaAR2}'");
//                    $insertar = array(
//                        "trano" => $trano,
//                        "ref_number" => $data['riv_no'],
//                        "tgl" => new Zend_Db_Expr("NOW()"),
//                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                        "coa_kode" => $coa['coa_kode'],
//                        "coa_nama" => $coa['coa_nama'],
//                        "debit" => ((floatval($valPPN) * floatval($data['rateidr']) - (floatval($valPPN)))),
//                        "credit" => 0,
//                        'prj_kode' => $data['prj_kode'],
//                        'val_kode' => $data['val_kode'],
//                        'sit_kode' => $data['sit_kode'],
//                        'rateidr' => $data['rateidr']
//                    );
//                    $this->jurnalar->insert($insertar);
//                    //Coa PPN USD - Exchange
//                    $coa = $this->coa->fetchRow("coa_kode = '2-3100'");
//                    $insertar = array(
//                        "trano" => $trano,
//                        "ref_number" => $data['riv_no'],
//                        "tgl" => new Zend_Db_Expr("NOW()"),
//                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
//                        "coa_kode" => $coa['coa_kode'],
//                        "coa_nama" => $coa['coa_nama'],
//                        "credit" => ((floatval($valPPN) * floatval($data['rateidr']) - (floatval($valPPN)))),
//                        "debit" => 0,
//                        'prj_kode' => $data['prj_kode'],
//                        'val_kode' => $data['val_kode'],
//                        'sit_kode' => $data['sit_kode'],
//                        'rateidr' => $data['rateidr']
//                    );
//                    $this->jurnalar->insert($insertar);
//                }
//            }

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
        $data['tgl'] = date("Y-m-d H:i:s");
        $data['trano'] = $trano;
        
        $this->invoice->insert($data);

        $rivNo = $data['riv_no'];
        $myUid = $this->session->userName;
        $ldap = new Default_Models_Ldap();
        $acc = $ldap->getAccount($myUid);
        $myName = $acc['displayname'][0];

        $cek =$this->requestInovice->fetchRow("trano = '$rivNo'");
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

    public function reportinvoiceAction()
    {
        
    }

    public function getinvoiceAction ()
    {
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
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data = $this->invoice->viewallrequest($offset,$limit,$dir,$sort,$search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function paymentinvoiceAction()
    {
        $uid = $this->session->userName;
        $users = new Default_Models_Ldap();
        $user = $users->getAccount($uid);

        $this->view->userPayment = $user['displayname'][0];

        $coas = $this->coa->fetchRow("coa_kode = '1-2010'");//COA AR IDR
        $coa['coaARIDR'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2021'");//COA AR USD
        $coa['coaARUSD'] = $coas->toArray();
        $coas = $this->coa->fetchRow("coa_kode = '1-2022'");//COA AR USD Exchange
        $coa['coaARUSDEx'] = $coas->toArray();

        $this->view->coa = Zend_Json::encode($coa);


    }

    public function getformpayinvoiceAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->invoice->fetchRow("trano = '$trano'")->toArray();
        $paidlistsum = $this->paymentInvoice->getpaidlist ($trano);


        $users = new Default_Models_Ldap();
        $user = $users->getAccount($data['uid']);
        $data['username'] = $user['displayname'][0];
        $data['tgl'] = date("d M Y",strtotime($data['tgl']));
        $totalPaid = 0;

        foreach($paidlistsum as $k => $v)
        {
            $totalPaid += floatval($v['total']);
        }

        $return = array("success" => true,"data" => $data,"sumpaidlist" => $totalPaid);

//        $jsonparam = Zend_Json::encode($param);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getviewinvoiceitemlistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data['data'] = $this->invoiceD->getitemlist($trano);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpaidlistAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data['data'] = $this->paymentInvoice->getpaidlist($trano);
        $users = new Default_Models_Ldap();
        foreach($data['data'] as $k => $v)
        {
            $data['data'][$k]['tgl'] = date("d M Y",strtotime($v['tgl']));
            $user = $users->getAccount($v['uid']);
            $data['data'][$k]['username'] = $user['displayname'][0];
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertpaymentinvoiceAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $type = $this->getRequest()->getParam('type_trano');
        $inv = $this->getRequest()->getParam('inv_no');

        $data = $this->getRequest()->getParams();
        unset($data['module']);
        unset($data['controller']);
        unset($data['action']);
        unset($data['q']);
        $counter = new Default_Models_MasterCounter();

        $trano = $counter->setNewTrans($type);
        $detail = Zend_Json::decode($data['payment']);
        $bank = Zend_Json::decode($data['bank']);
        $jurnal = Zend_Json::decode($data['jurnal']);

//        if ($data['ppn'] == 'YES')
//            $data['ppn'] = 'Y';
//        else
//            $data['ppn'] = 'N';
        $arrayInsert = array(
            "inv_no" => $inv,
            "trano" => $trano,
            "total" => floatval(str_replace(",",'',$detail['payment-value'])),
            "tgl" => date("Y-m-d H:i:s"),
            "prj_kode" => $detail['prj_kode'],
            "prj_nama" => $detail['prj_nama'],
            "sit_kode" => $detail['sit_kode'],
            "sit_nama" => $detail['sit_nama'],
            "cus_kode" => $detail['cus_kode'],
            "val_kode" => $detail['val_kode'],
            "rateidr" => $detail['rateidr'],
            "uid" => $this->session->userName,
            "statusppn" => $detail['ppn'],
            "bnk_norek" => $bank['bnk_norek'],
            "bnk_cabang" => $bank['bnk_cabang'],
            "bnk_alamat" => $bank['bnk_alamat'],
            "bnk_nama" => $bank['bnk_nama'],
            "bnk_noreknama" => $bank['bnk_noreknama'],
            "bnk_kota" => $bank['bnk_kota'],
            "bnk_kode" => $bank['bnk_kode'],
            "coa_kode" => $bank['coa_kode'],
            "coa_nama" => $bank['coa_nama'],
            "paymentterm" => $bank['paymentterm'],
            "paymentnotes" => $detail['payment-notes']
        );
        
        $this->paymentInvoice->insert($arrayInsert);

        //insert ke jurnal bank...
        foreach($jurnal as $k => $v)
        {
            unset($v['tipe']);
            $v['type'] = 'AR-INV';
            $v['uid'] = QDC_User_Session::factory()->getCurrentUID();
            $v['tgl'] = new Zend_Db_Expr("NOW()");
            $v['prj_kode'] = $detail['prj_kode'];
            $v['sit_kode'] = $detail['sit_kode'];
            $v['trano'] = $trano;
            $v['ref_number'] = $inv;
            $v['val_kode'] = $detail['val_kode'];
            $v['rateidr'] = $detail['rateidr'];

            $this->FINANCE->AccountingJurnalBank->insert($v);
        }


        $return = array("success" => true,"number" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editinvoiceAction()
    {
        
    }

    public function editrequestinvoiceAction()
    {
        $trano = $this->getRequest()->getParam('trano');
        $this->view->trano = $trano;
    }

    public function getdatarequestinvoiceforeditAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $data = $this->requestInovice->fetchRow("trano = '$trano'");
        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file)
        {
            $file = $file->toArray();
        }

        $ret = $data->toArray();

        $sitKode = $data['sit_kode'];
        $prjKode = $data['prj_kode'];
        $valKode = $data['val_kode'];

        $pro = $this->project->getProjectAndCustomer($prjKode);
        if ($pro)
        {
            $cusNama = $pro[0]['cus_nama'];
        }

        $boq2CurrentIDR = 0;
        $boq2CurrentUSD = 0;
        $totalInvoicedIDR = 0;
        $totalPaymentIDR = 0;
        $totalInvoicedUSD = 0;
        $totalPaymentUSD = 0;

        if ($sitKode != '')
        {
            $where = " AND sit_kode = '$sitKode'";
        }

        if ($sitKode != '')
        {
            $boq = $this->budget->getBoq2('summary-current',$prjKode,$sitKode);
            if ($boq)
            {
                $boq2CurrentIDR = $boq['totalCurrentIDR'];
                $boq2CurrentUSD = $boq['totalCurrentHargaUSD'];
            }
            $invAndPayment = $this->requestInovice->getIvoiceAndPayment($trano,$prjKode,$sitKode);
            $totalInvoicedIDR = floatval($invAndPayment['totalInvoiceIDR']);
            $totalInvoicedUSD = floatval($invAndPayment['totalInvoiceUSD']);
            $totalPaymentIDR = floatval($invAndPayment['totalPaymentIDR']);
            $totalPaymentUSD = floatval($invAndPayment['totalPaymentUSD']);

            $totretIDR = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'IDR'");
            $totalRequestIDR = 0;
            if ($totretIDR)
            {
                $totretIDR = $totretIDR->toArray();
                foreach($totretIDR as $k => $v)
                {
                    $totalRequestIDR += floatval($v['total']);
                }
            }
            $totretUSD = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'USD'");
            $totalRequestUSD = 0;
            if ($totretUSD)
            {
                $totretUSD = $totretUSD->toArray();
                foreach($totretUSD as $k => $v)
                {
                    $totalRequestUSD += floatval($v['total']);
                }
            }
        }
        else
        {
            $sites = new Default_Models_MasterSite();
            $site = $sites->fetchAll("prj_kode = '$prjKode'");
            if ($site)
            {
                $site = $site->toArray();
                foreach ($site as $k => $v)
                {
                    $sitKode = $v['sit_kode'];
                    $boq = $this->budget->getBoq2('summary-current',$prjKode,$sitKode);
                    if ($boq)
                    {
                        $boq2CurrentIDR += floatval($boq['totalCurrentIDR']);
                        $boq2CurrentUSD += floatval($boq['totalCurrentHargaUSD']);
                    }
                    $invAndPayment = $this->requestInovice->getIvoiceAndPayment($trano,$prjKode,$sitKode);
                    $totalInvoicedIDR += floatval($invAndPayment['totalInvoiceIDR']);
                    $totalInvoicedUSD += floatval($invAndPayment['totalInvoiceUSD']);
                    $totalPaymentIDR += floatval($invAndPayment['totalPaymentIDR']);
                    $totalPaymentUSD += floatval($invAndPayment['totalPaymentUSD']);

                    $totretIDR = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'IDR'");
                    $totalRequestIDR = 0;
                    if ($totretIDR)
                    {
                        $totretIDR = $totretIDR->fetchAll();
                        foreach($totretIDR as $k => $v)
                        {
                            $totalRequestIDR += floatval($v['total']);
                        }
                    }
                    $totretUSD = $this->requestInovice->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano != '$trano' AND val_kode = 'USD'");
                    $totalRequestUSD = 0;
                    if ($totretUSD)
                    {
                        $totretUSD = $totretUSD->fetchAll();
                        foreach($totretUSD as $k => $v)
                        {
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
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function updaterequestinvoiceAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $deletedFile = $this->getRequest()->getParam('deletedfile');
        $jsonData = Zend_Json::decode($json);
        $jsonFile = Zend_Json::decode($file);
        $jsonDeletedFile = Zend_Json::decode($deletedFile);

        $trano = $jsonData['trano'];
        unset($jsonData['trano']);

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
        $this->requestInovice->update($jsonData,"trano = '$trano'");

        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
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

        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
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

        if (count($jsonDeletedFile) > 0)
        {
            foreach ($jsonDeletedFile as $key => $val)
            {
                $this->files->delete("id = {$val['id']}");
            }
        }

        $json = "{success: true, number : '$trano'}";

        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getinvoicedetailAction()
    {
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
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $invoices = $this->invoice->fetchAll($search,array($sort . ' ' . $dir), $limit, $offset);
        if ($invoices)
        {
            $data['data'] = $invoices->toArray();
            $data['count'] = $this->invoice->fetchAll($search)->count();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getdatainvoiceforeditAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('search');
        $data = $this->invoice->fetchRow("trano = '$trano'")->toArray();
        $dataDetail = $this->invoiceD->fetchAll("trano = '$trano'");

        $pro = $this->project->getProjectAndCustomer($data['prj_kode']);
        if ($pro)
        {
            $data['cus_nama'] = $pro[0]['cus_nama'];
        }

        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file)
        {
            $file = $file->toArray();
        }

        $riv_no = $data['riv_no'];
        $dataRequest = $this->requestInovice->fetchRow("trano = '$riv_no'")->toArray();
        $dataPayment = $this->paymentInvoice->fetchAll("inv_no = '$trano'");

        $ldap = new Default_Models_Ldap();
        $account = $ldap->getAccount($data['uid']);
        $dataRequest['uid_request'] = $account['displayname'][0];
        
        $totPayment = 0;
        foreach($dataPayment as $k => $v)
        {
            $totPayment += floatval($v['total']);
        }

        $data['totalPayment'] = $totPayment;
        $return['header'] = $data;
        $return['detail'] = $dataDetail->toArray();
        $i = 1;
        foreach($return['detail'] as $k => $v)
        {
            $return['detail'][$k]['id'] = $i;
            if ($v['statusppn'] == 'Y')
            {
                $return['detail'][$k]['ppn'] = 0.1 * $return['detail'][$k]['total'];
            }
            else
                $return['detail'][$k]['ppn'] = 0;
            $i++;
        }
        $return['request'] = $dataRequest;
        $return['file'] = $file;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function updateinvoiceAction()
    {
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

        $trano = $data['trano'];

        $dat = $this->invoiceD->fetchAll("trano = '$trano'");
        if ($dat)
        {
            $dat = $dat->toArray();
        }
        $log['inv-detail-before'] = $dat;
        $this->invoiceD->delete("trano = '$trano'");

        $urut = 0;
        $total = 0;
        foreach($detail as $k => $v)
        {
            unset($detail[$k]['id']);
            $detail[$k]['urut'] = $urut + 1;
            $detail[$k]['prj_kode'] = $data['prj_kode'];
            $detail[$k]['prj_nama'] = $data['prj_nama'];
            $detail[$k]['sit_kode'] = $data['sit_kode'];
            $detail[$k]['sit_nama'] = $data['sit_nama'];
            $detail[$k]['cus_kode'] = $data['cus_kode'];
            $detail[$k]['tgl'] = date("Y-m-d H:i:s",strtotime($data['tgl']));
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

            unset($detail[$k]['ppn']);

            $log2['inv-detail-after'][] = $detail[$k];
            $this->invoiceD->insert($detail[$k]);

            $total += floatval($detail[$k]['total']);
            $urut++;
        }

        if (count($file) > 0)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
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

        if (count($deletedfile) > 0)
        {
            foreach ($deletedfile as $key => $val)
            {
                $this->files->delete("id = {$val['id']}");
            }
        }

        unset($data['invoiceDetail']);
        unset($data['bank']);
        unset($data['cus_nama']);
        unset($data['file']);
        unset($data['deletedfile']);

        $data['bnk_norek'] = $bank['bnk_norek'];
        $data['bnk_cabang'] = $bank['bnk_cabang'];
        $data['bnk_alamat'] = $bank['bnk_alamat'];
        $data['bnk_nama'] = $bank['bnk_nama'];
        $data['bnk_noreknama'] = $bank['bnk_noreknama'];
        $data['bnk_kota'] = $bank['bnk_kota'];
        $data['bnk_kode'] = $bank['bnk_kode'];
        $data['total'] = $total;

        unset($data['tgl']);

        $dat = $this->invoice->fetchRow("trano = '$trano'");
        if ($dat)
        {
            $dat = $dat->toArray();
        }
        $log['inv-header-before'] = $dat;
        $this->invoice->update($data,"trano = '$trano'");
        $dat = $this->invoice->fetchRow("trano = '$trano'");
        $log2['inv-header-after'] = $dat;
        
        //Log Transaction
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
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
}
?>