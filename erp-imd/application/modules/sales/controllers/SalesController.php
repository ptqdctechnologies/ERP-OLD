<?php

class Sales_SalesController extends Zend_Controller_Action {

    /**
     * The default action - show the home page
     */
    private $boq2;
    private $addco;
    private $util;
    private $utility;
    private $session;
    private $workflow;
    private $const;
    private $workflowTrans;
    private $database;
    private $token;
    private $creTrans;
    private $log;
    private $praco;
    private $customer;
    private $regisco;
    private $files;
    private $kboq2;
    private $addpraco;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->boq2 = new Default_Models_MasterBoq2();
        $this->addco = new Default_Models_MasterAddco();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->utility = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->session = new Zend_Session_Namespace('login');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->database = Zend_Registry::get('db');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->log = new Admin_Models_Logtransaction();
        $this->praco = new Sales_Models_Praco();
        $this->addpraco = new Sales_Models_AddPraco();
        $this->customer = new Default_Models_MasterCustomer();
        $this->regisco = new Sales_Models_Regisco();
        $this->files = new Default_Models_Files();
        $this->kboq2 = new Default_Models_MasterCboq3H();
    }

    public function indexAction() {
        // TODO Auto-generated SalesController::indexAction() default action
    }

    public function coAction() {
        
    }

    public function createboq2Action() {
        
    }

    public function addcoAction() {
        
    }

    public function gridinfoAction() {
        $etc = $this->getRequest()->getParam('etc');
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($etc);
//        var_dump($jsonData); die();

        $this->view->prjKode = $jsonData[0]['prj_kode'];
        $this->view->prjNama = $jsonData[0]['prj_nama'];
    }

    public function insertboq2Action() {
        $this->_helper->viewRenderer->setNoRender();
        $posts = $this->getRequest()->getParam('posts');
//         $etc = $this->getRequest()->getParam('etc');
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);
//        $jsonEtc = Zend_Json::decode($etc);

        $items = $jsonData;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PRACO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
//        $trano = $this->workflow->setWorkflowTransNew($params);

        $counter = new Default_Models_MasterCounter();
        $trano = $counter->setNewTrans('BOQ2');

        $urut = 1;

        $prj_kode = $jsonData[0]['prj_kode'];
        $tgl = date('Y-m-d');

        $query = "SELECT cus_kode FROM master_project WHERE prj_kode ='$prj_kode'";
        $ambil = $this->db->query($query);
        $cus = $ambil->fetch();
        $cus_kode = $cus['cus_kode'];

        if ($jsonData[0]['totalusd'] > 0) {
            if ($jsonData[0]['rateidr'] > 0)
                $rate = $jsonData[0]['rateidr'];
            else {
                $rate = $this->utility->getExchangeRate();
            }
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "tglinput" => $tgl,
            "jam" => date('H:i:s'),
            "user" => $this->session->userName,
            "petugas" => $this->session->userName,
            "prj_kode" => $prj_kode,
            "prj_nama" => $jsonData[0]['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "cus_kode" => $cus_kode,
            "total" => $jsonData[0]['total'],
            "totalusd" => $jsonData[0]['totalusd'],
            "rateidr" => $rate,
            "pocustomer" => $jsonData[0]['pocustomer'],
            "rateidr" => $jsonData[0]['rateidr'],
            "statusestimate" => $jsonData[0]['costat']
        );

        $log['boq2-detail-after'] = $arrayInsert;

        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $prj_kode,
            "sit_kode" => $jsonData[0]['sit_kode'],
            "action" => "INSERT",
            "data_before" => "",
            "data_after" => Zend_Json::encode($log),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $this->boq2->insert($arrayInsert);
//            $this->praco->insert($arrayInsert);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true}");
    }

    public function insertaddcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $posts = $this->getRequest()->getParam('posts');
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);

//        var_dump($jsonData);die();

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('ABOQ2');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ABOQ2-' . $last;
        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);
        $urut = 1;

        $prj_kode = $jsonData[0]['prj_kode'];
        $tgl = date('Y-m-d');
        $tglInput = date('Y-m-d');
        $jamInput = date('H:i:s');
        $prev = $jsonData[0]['adtotal'] + $jsonData[0]['totaltambah'];
        $prevusd = $jsonData[0]['adtotalusd'] + $jsonData[0]['totaltambahusd'];

        $query = "SELECT cus_kode FROM master_project WHERE prj_kode ='$prj_kode'";
        $ambil = $this->db->query($query);
        $cus = $ambil->fetch();
        $cus_kode = $cus['cus_kode'];

        if ($jsonData[0]['totaltambahusd'] > 0) {
            if ($jsonData[0]['rateidr'] > 0)
                $rate = $jsonData[0]['rateidr'];
            else {
                $rate = $this->utility->getExchangeRate();
            }
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "tglinput" => $tglInput,
            "jam" => $jamInput,
            "petugas" => $this->session->userName,
            "prj_kode" => $prj_kode,
            "prj_nama" => $jsonData[0]['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "cus_kode" => $cus_kode,
            "total" => $jsonData[0]['total'],
            "totalusd" => $jsonData[0]['totalusd'],
            "adtotal" => $prev,
            "totaltotal" => $jsonData[0]['totaltotal'],
            "totaltambah" => $jsonData[0]['totaltambah'],
            "adtotalusd" => $prevusd,
            "totaltotalusd" => $jsonData[0]['totaltotalusd'],
            "totaltambahusd" => $jsonData[0]['totaltambahusd'],
            "rateidr" => $rate,
            "user" => $this->session->userName
        );


//			var_dump($arrayInsert);die();
        $lastID = $this->addco->insert($arrayInsert);
        $jsonData['id'] = $lastID;
//    		$return = array('success' => true,'message' => 'Additional Customer Order has been created','posts' => $jsonData);
//      		$json = Zend_Json::encode($return);
        //result encoded in JSON
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true}");
    }

    public function getlasttranoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('ABOQ2');
        $number++;
        echo "{ aboq2:'$number' }";
    }

    public function appboq2Action() {
        $approve = $this->getRequest()->getParam('approve');

        if ($approve != '') {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
        }

        $trano = $docs['item_id'];
        $statApprove = $docs['approve'];

        $cocustomer = $this->praco->fetchRow("trano = '$trano'");

        if ($cocustomer['statusestimate'] == 1) {
            $cocustomer['statusestimate'] = 'Estimate';
        } else {
            $cocustomer['statusestimate'] = 'Original';
        }

        if ($statApprove == $this->const['DOCUMENT_REJECT']) {
            $this->view->reject = true;
        }

        $userapp = $this->workflow->getAllApproval($trano);

        $allReject = $this->workflow->getAllReject($trano);
        $lastReject = $this->workflow->getLastReject($trano);
        $this->view->lastReject = $lastReject;
        $this->view->allReject = $allReject;

        $this->view->boq2 = $cocustomer;
        $this->view->approval = $userapp;
        $this->view->uid = $this->session->userName;
        $this->view->userID = $this->session->idUser;
        $this->view->ID = $approve;
    }

    public function editboq2Action() {
        $trano = $this->getRequest()->getParam('trano');

        $this->view->trano = $trano;
    }

    public function getcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
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
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $return['data'] = $this->boq2->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $return['total'] = $this->boq2->fetchAll()->count();

//        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_boq2h b LEFT JOIN workflow_trans w on b.trano = w.item_id
//                WHERE $search AND approve = 300 group by trano order by $sort $dir LIMIT $offset,$limit";
//        $fetch = $this->database->query($query);
//
//        $return['data'] = $fetch->fetchAll();
//        $return['total'] = $this->database->fetchOne('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getaddcodataAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option != null) {
            $search = "AND $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'y.trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = "select SQL_CALC_FOUND_ROWS * FROM (select * FROM (select a.*,b.approve,b.final FROM transengineer_addpraco a left join workflow_trans b on a.trano = b.item_id order by b.item_id,b.date desc) z
                group by z.trano ) y where ((y.approve = 300 AND y.final = 0) OR y.approve is null ) $search order by $sort $dir LIMIT $offset,$limit";

        $fetch = $this->database->query($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

//        $data['data'] = $this->praco->fetchAll()->toArray();
//        $data['total'] = $this->praco->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getformcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

//        var_dump($trano);die;

        $codata = $this->boq2->fetchAll("trano = '$trano'")->toArray();

//        var_dump($data);die;

        $return = array("success" => true, "codata" => $codata);

//        var_dump($return);die;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatecoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $posts = $this->getRequest()->getParam('posts');
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($posts);

        $trano = $jsonData[0]['cotrano'];

        $items = $jsonData[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "BOQ2",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $jsonData,
            "itemID" => $trano,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );

//        $myUid = $this->session->userName;
//
//        $token = $this->token->getDocumentSignatureByUserID($myUid);
//        $tgl = $token['date'];
//        $sign = $token['signature'];
//        $fetch = $this->workflowTrans->fetchAll("item_id = '$trano'");
//
//        if($fetch)
//        {
//            $hasil = $fetch->toArray();
//            $prjKode = $hasil[0]['prj_kode'];
//            Zend_Loader::loadClass('Zend_Json');
//            $json = Zend_Json::encode($hasil);
//
//            $arrayInsert = array(
//                "trano" => $trano,
//                "tgl" => $tgl,
//                "prj_kode" => $prjKode,
//                "uid" => $myUid,
//                "uid_requestor" => $myUid,
//                "sign" => $sign,
//                "reason" => 'UPDATE CO',
//                "data" => $json,
//                "ip" => $_SERVER["REMOTE_ADDR"],
//                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
//            );
//            $this->creTrans->insert($arrayInsert);
//            $this->workflowTrans->delete("item_id = '$trano'");
        $this->workflow->setWorkflowTransNew($params);

        $log['boq2-header-before'] = $this->boq2->fetchRow("trano = '$trano'")->toArray();
//        $this->boq2->delete("trano = '$trano'");

        $prj_kode = $jsonData[0]['prj_kode'];
        $tgl = date('Y-m-d');

        $query = "SELECT cus_kode FROM master_project WHERE prj_kode ='$prj_kode'";
        $ambil = $this->db->query($query);
        $cus = $ambil->fetch();
        $cus_kode = $cus['cus_kode'];

        if ($jsonData[0]['totalusd'] > 0) {
            if ($jsonData[0]['rateidr'] > 0)
                $rate = $jsonData[0]['rateidr'];
            else {
                $rate = $this->utility->getExchangeRate();
            }
        }

        $arrayInsert = array(
//				"trano" => $trano,
//				"tgl" => $tgl,
//				"tglinput" => $tgl,
//				"jam" => date('H:i:s'),
            "user" => $this->session->userName,
            "petugas" => $this->session->userName,
            "prj_kode" => $prj_kode,
            "prj_nama" => $jsonData[0]['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "cus_kode" => $cus_kode,
            "total" => $jsonData[0]['total'],
            "totalusd" => $jsonData[0]['totalusd'],
            "rateidr" => $rate,
            "pocustomer" => $jsonData[0]['pocustomer'],
            "rateidr" => $jsonData[0]['rateidr'],
            "statusestimate" => $jsonData[0]['costat']
        );

//			var_dump($arrayInsert);die();
        $this->boq2->update($arrayInsert, "trano = '$trano'");
        $log2['boq2-header-after'] = $this->boq2->fetchRow("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData[0]['prj_kode'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createcoAction() {
        
    }

    public function getcustomerAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option != null) {
            $search = " $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $customer['data'] = $this->customer->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $customer['total'] = $this->customer->fetchAll()->count();

        $json = Zend_Json::encode($customer);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getregiscoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $uid = $this->session->userName;

//        $query = "SELECT SQL_CALC_FOUND_ROWS RC.* FROM transengineer_registerco RC LEFT JOIN transengineer_praco PC ON RC.trano = PC.regis_no WHERE PC.trano IS NULL AND stsproses = 1 AND assignto = '$uid' ";

        $query = "SELECT SQL_CALC_FOUND_ROWS RC.* FROM (transengineer_registerco RC LEFT JOIN transengineer_praco PC ON RC.trano = PC.regis_no)
LEFT JOIN transengineer_addpraco AP ON AP.regis_no = RC.trano WHERE PC.trano IS NULL AND AP.trano IS NULL AND stsproses = 1 AND assignto = '$uid'";

        $fetch = $this->db->query($query);
        $regisco['data'] = $fetch->fetchAll();
        $regisco['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');


//        $regisco['data'] = $this->regisco->fetchAll()->toArray();

        $json = Zend_Json::encode($regisco);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function appcoAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PRACO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

//        $codata = Zend_Json::decode($this->getRequest()->getParam('codata'));

        if ($approve == '') {
            $codata = Zend_Json::decode($this->getRequest()->getParam('codata'));
            $codata[0]['user'] = $this->session->userName;
            $codata[0]['tgl'] = date('d-m-Y');
            $num_regisco = $codata[0]['regisco'];

            if ($from == 'edit') {
                $num_regisco = $codata[0]['regis_no'];
            }

            $jsonFile = $this->getRequest()->getParam('file');
            $file = Zend_Json::decode($this->getRequest()->getParam('file'));

            $regcodata = $this->regisco->fetchAll("trano = '$num_regisco'")->toArray();

            $this->view->json = Zend_Json::encode($codata);
            $this->view->codata = $codata;
            $this->view->uid = $this->session->userName;
            $this->view->file = $file;
            $this->view->regcodata = $regcodata;
            $this->view->jsonFile = $jsonFile;

            if ($from == 'edit') {
                $this->view->edit = true;
                $this->view->trano = $codata[0]['trano'];
                $this->view->registrano = $num_regisco;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $trano = $docs['item_id'];
                $codata = $this->praco->fetchAll("trano = '$trano'");

                if ($codata)
                    $codata = $codata->toArray();

                $num_regisco = $codata[0]['regis_no'];

                $file_temp = $codata[0]['trano'];
//                if ($codata[0]['statusco'] == 'estimate') {
//                    $file_temp = $codata[0]['trano'];
//                } else {
//                    $file_temp = $codata[0]['regis_no'];
//                }

                $file = $this->files->fetchAll("trano = '$file_temp'")->toArray();
                $regcodata = $this->regisco->fetchAll("trano = '$num_regisco'")->toArray();

//                var_dump($num_praco,$num_regisco);die;

                $allReject = $this->workflow->getAllReject($trano);
                $lastReject = $this->workflow->getLastReject($trano);

                $userapp = $this->workflow->getAllApproval($trano);
                $this->view->approval = $userapp;

                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;

                $this->view->lastReject = $lastReject;
                $this->view->allReject = $allReject;
                $this->view->codata = $codata;
                $this->view->approve = true;
                $this->view->uid = $this->session->userName;
                $this->view->userID = $this->session->idUser;
                $this->view->docsID = $approve;
                $this->view->trano = $trano;
                $this->view->file = $file;
                $this->view->regcodata = $regcodata;
            }
        }
    }

    public function doinsertcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));

//        var_dump($jsonData);die;

        $items = $jsonData[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PRACO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d H:i:s');

        foreach ($jsonData as $key => $val) {
            $insertarray = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "statusco" => $val['statusco'],
                "regis_no" => $val['regisco'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "ket" => $val['ket'],
                "cus_kode" => $val['cus_kode'],
                "cus_nama" => $val['cus_nama'],
                "total" => floatval($val['total']),
                "totalusd" => floatval($val['totalusd']),
                "pocustomer" => $val['pocustomer'],
                "user" => $this->session->userName,
                "rateidr" => floatval($val['rate']),
                "type" => $val['type'],
                "top" => $val['top'],
            );

            $this->praco->insert($insertarray);
        }

        if ($jsonData[0]['statusco'] == 'estimate') {
            if (count($jsonFile) > 0) {
                foreach ($jsonFile as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }
        }

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editcoAction() {
        $trano = $this->getRequest()->getParam('trano');
        $registrano = $this->getRequest()->getParam('registrano');

        $this->view->cotrano = $trano;
        $this->view->registrano = $registrano;
    }

    public function getcodataAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option != null) {
            $search = "AND $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'y.trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM transengineer_praco $search GROUP BY trano ORDER BY $sort $dir LIMIT $offset,$limit" ;
        //$sql = "select * FROM (select a.* FROM transengineer_praco a left join workflow_trans b on a.trano = b.item_id WHERE approve = '300' order by b.date desc) z group by z.trano ORDER BY $sort $dir LIMIT $offset,$limit";

        //$sql = "select SQL_CALC_FOUND_ROWS * FROM (select * FROM (select a.*,b.approve FROM transengineer_praco a left join workflow_trans b on a.trano = b.item_id order by b.item_id,b.date desc) z
        //        group by z.trano ) y where (y.approve = 300 OR y.approve is null) $search order by $sort $dir LIMIT $offset,$limit";
        
        $sql = "select SQL_CALC_FOUND_ROWS * FROM (select * FROM (select a.*,b.approve as approve2 FROM transengineer_praco a left join workflow_trans b on a.trano = b.item_id order by b.item_id,b.date desc) z
                group by z.trano ) y where (y.approve = 300 OR y.approve2 is null) $search order by $sort $dir LIMIT $offset,$limit";

        $fetch = $this->database->query($sql);
        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

//        $data['data'] = $this->praco->fetchAll()->toArray();
//        $data['total'] = $this->praco->fetchAll()->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getstorecoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->praco->fetchAll("trano = '$trano'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getstoreaddcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data = $this->addpraco->fetchAll("trano = '$trano'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatepracoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));

//        var_dump($jsonFile);die;

        $data = '';

        $trano = $jsonData[0]['trano'];

        $items = $jsonData[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PRACO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $old = $this->praco->fetchAll("trano = '$trano'")->toArray();
        $log['praco-detail-before'] = $old;
        $log2['praco-detail-after'] = $jsonData;

        $this->praco->delete("trano = '$trano'");

        $tgl = date('Y-m-d H:i:s');

        foreach ($jsonData as $key => $val) {
            $insertarray = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "statusco" => $val['statusco'],
                "regis_no" => $val['regis_no'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "ket" => $val['ket'],
                "cus_kode" => $val['cus_kode'],
                "cus_nama" => $val['cus_nama'],
                "total" => floatval($val['total']),
                "totalusd" => floatval($val['totalusd']),
                "pocustomer" => $val['pocustomer'],
                "user" => $this->session->userName,
                "rateidr" => floatval($val['rateidr']),
                "type" => $val['type'],
                "top" => $val['top'],
            );

            $this->praco->insert($insertarray);
        }

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData[0]['prj_kode'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        if ($jsonData[0]['statusco'] == 'estimate') {
            $this->files->delete("trano = '$trano'");

            if (count($jsonFile) > 0) {
                foreach ($jsonFile as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }
        }


        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdateaddpracoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));

        $data = '';

        $trano = $jsonData[0]['trano'];

        $items = $jsonData[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "APRACO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $old = $this->addpraco->fetchRow("trano = '$trano'")->toArray();
        $log['addpraco-detail-before'] = $old;
        $log2['addpraco-detail-after'] = $jsonData;

        $this->addpraco->delete("trano = '$trano'");

        $tgl = date('Y-m-d H:i:s');

        foreach ($jsonData as $key => $val) {
            $insertarray = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "statusco" => $val['statusco'],
                "regis_no" => $val['regis_no'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "ket" => $val['ket'],
                "cus_kode" => $val['cus_kode'],
                "cus_nama" => $val['cus_nama'],
                "total" => floatval($val['total']),
                "totalusd" => floatval($val['totalusd']),
                "pocustomer" => $val['pocustomer'],
                "user" => $this->session->userName,
                "rateidr" => floatval($val['rateidr']),
                "type" => $val['type']
            );

            $this->addpraco->insert($insertarray);
        }

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData[0]['prj_kode'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        if ($jsonData[0]['statusco'] == 'estimate') {
            $this->files->delete("trano = '$trano'");

            if (count($jsonFile) > 0) {
                foreach ($jsonFile as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }
        }

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function estimatetooriginAction() {
        
    }

    public function cekboq2Action() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $status = $this->getRequest()->getParam('status');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $type = $this->getRequest()->getParam('type');
        $tranoedit = $this->getRequest()->getParam('tranoedit');

        if ($tranoedit != '')
            $where = " AND trano != '$tranoedit'";
        $cekpraco = $this->praco->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type' $where");

        if ($cekpraco) {
            $estimatepraco = false;
            if ($cekpraco['statusco'] == 'estimate')
                $estimatepraco = true;

//            if ($status == "original" AND !$estimatepraco)
//            {
//
//                    echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Original Praco'}";
//                die;
//            }
//            else
            if ($status == "estimate" AND $estimatepraco) {

                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Estimate Praco'}";
                die;
            } else if ($status == "estimate" AND !$estimatepraco) {

                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Original Praco'}";
                die;
            }
        }

        $cek = $this->boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type'");
        if ($cek) {
            $estimate = false;
            if ($cek['statusestimate'] == 1)
                $estimate = true;
            $old = false;
            if ($cek['old'] == 1)
                $old = true;
        }
        else {
            echo "{success: true}";
            die;
        }

        if ($status == "original" AND !$estimate) {
            if ($old)
                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> is Exists, Please contact IT Support'}";
            else
                echo "{success: true}";
//                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Original Customer Order'}";
            die;
        }
        else if ($status == "estimate" AND $estimate) {
            if ($old)
                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> is Exists, Please contact IT Support'}";
            else
                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Estimate Customer Order'}";
            die;
        }else if ($status == "estimate" AND !$estimate) {
            if ($old)
                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> is Exists, Please contact IT Support'}";
            else
                echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Has been created Original Customer Order'}";
            die;
        }


        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getregisAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $dataregis = $this->regisco->fetchRow("trano = '$trano'");


        if ($dataregis) {
            $dataregis = $dataregis->toArray();
        }

        $return = array("success" => true, "dataregis" => $dataregis);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getboq2Action() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $prj_kode = $this->getRequest()->getParam('prj_kode');

        $data['data'] = $this->boq2->fetchAll("prj_kode = '$prj_kode'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function createaddcoAction() {
        
    }

    public function editaddcoAction() {
        $trano = $this->getRequest()->getParam('trano');
        $registrano = $this->getRequest()->getParam('registrano');

        $this->view->cotrano = $trano;
        $this->view->registrano = $registrano;
    }

    public function cekaddboq2Action() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $status = $this->getRequest()->getParam('status');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $type = $this->getRequest()->getParam('type');

        $cek = $this->boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type'");

        If (!$cek) {
            echo "{success: false,message : ' Project <b><font color=red>$prj_kode</font></b> - Site  <b><font color=red>$sit_kode</font></b> - <b><font color=red>$type</font></b> Customer Order Has not been created '}";
            die;
        } else {
            echo "{success: true}";
            die;
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function appaddcoAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PRACO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

//        $codata = Zend_Json::decode($this->getRequest()->getParam('codata'));

        if ($approve == '') {
            $codata = Zend_Json::decode($this->getRequest()->getParam('codata'));
            $codata[0]['user'] = $this->session->userName;
            $codata[0]['tgl'] = date('d-m-Y');
            $prj_kode = $codata[0]['prj_kode'];
            $type = $codata[0]['type'];
            $num_regisco = $codata[0]['regisco'];

            foreach ($codata as $key => $val) {
                $sit_kode = $val['sit_kode'];

                $tmp = $this->boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type' ");
                if ($tmp) {
                    $tmp2 = $tmp->toArray();
                    if ($tmp2['statusestimate'] == 0)
                        $tmp2['statusco'] = 'Original';
                    else
                        $tmp2['statusco'] = 'Estimate';

                    $no_praco = $tmp2['praco'];
                    $tmp3 = $this->praco->fetchRow("trano = '$no_praco'");
                    if ($tmp3) {
                        $tmp3 = $tmp3->toArray();
                        $regis_no = $tmp3['regis_no'];
                        $tmp4 = $this->files->fetchAll("trano = '$regis_no'");
                        if ($tmp4) {
                            $tmp4 = $tmp4->toArray();
                            $tmp2['files'] = $tmp4;
                        }
                    }

                    $boq2data[] = $tmp2;
                }
            }

            if ($from == 'edit') {
                $num_regisco = $codata[0]['regis_no'];
            }

            $jsonFile = $this->getRequest()->getParam('file');
            $file = Zend_Json::decode($this->getRequest()->getParam('file'));

            $regcodata = $this->regisco->fetchAll("trano = '$num_regisco'")->toArray();

            $this->view->json = Zend_Json::encode($codata);
            $this->view->codata = $codata;
            $this->view->uid = $this->session->userName;
            $this->view->file = $file;
            $this->view->regcodata = $regcodata;
            $this->view->boq2data = $boq2data;
            $this->view->jsonFile = $jsonFile;

            if ($from == 'edit') {
                $this->view->edit = true;
                $this->view->trano = $codata[0]['trano'];
                $this->view->registrano = $num_regisco;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $trano = $docs['item_id'];
                $codata = $this->addpraco->fetchAll("trano = '$trano'");

                if ($codata) {
                    $codata = $codata->toArray();
                    foreach ($codata as $key => $val) {
                        $prj_kode = $val['prj_kode'];
                        $sit_kode = $val['sit_kode'];
                        $type = $val['type'];

                        $tmp = $this->boq2->fetchRow("prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND type = '$type' ");
                        if ($tmp) {
                            $tmp2 = $tmp->toArray();
                            if ($tmp2['statusestimate'] == 0)
                                $tmp2['statusco'] = 'Original';
                            else
                                $tmp2['statusco'] = 'Estimate';

                            $no_praco = $tmp2['praco'];
                            $tmp3 = $this->praco->fetchRow("trano = '$no_praco'");
                            if ($tmp3) {
                                $tmp3 = $tmp3->toArray();
                                $regis_no = $tmp3['regis_no'];
                                $tmp4 = $this->files->fetchAll("trano = '$regis_no'");
                                if ($tmp4) {
                                    $tmp4 = $tmp4->toArray();
                                    $tmp2['files'] = $tmp4;
                                }
                            }

                            $boq2data[] = $tmp2;
                        }
                    }
                }

//                var_dump($codata[0]['statusco']);die;

                if ($codata[0]['statusco'] == 'estimate') {
                    $file_temp = $codata[0]['trano'];
                } else {
                    $file_temp = $codata[0]['regis_no'];
                }

//                $num_regisco = $codata[0]['regis_no'];
//                $trano_addraco = $codata[0]['trano'];
//                var_dump($trano_addraco);die;
//                if ($tmp2['statusco'] = 'Original'){
//                    $file = $this->files->fetchAll("trano = '$num_regisco'")->toArray();
//                }else
//                {
                $file = $this->files->fetchAll("trano = '$file_temp'")->toArray();
//                }
//                var_dump($file);die;

                $regcodata = $this->regisco->fetchAll("trano = '$num_regisco'")->toArray();

//                var_dump($num_praco,$num_regisco);die;

                $allReject = $this->workflow->getAllReject($trano);
                $lastReject = $this->workflow->getLastReject($trano);

                $userapp = $this->workflow->getAllApproval($trano);
                $this->view->approval = $userapp;

                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;

                $this->view->lastReject = $lastReject;
                $this->view->allReject = $allReject;
                $this->view->codata = $codata;
                $this->view->approve = true;
                $this->view->uid = $this->session->userName;
                $this->view->userID = $this->session->idUser;
                $this->view->docsID = $approve;
                $this->view->trano = $trano;
                $this->view->file = $file;
                $this->view->regcodata = $regcodata;
                $this->view->boq2data = $boq2data;
            }
        }
    }

    public function doinsertaddcoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));

//        var_dump($jsonFile);die;

        $items = $jsonData[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "APRACO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d H:i:s');

        foreach ($jsonData as $key => $val) {
            $insertarray = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "statusco" => $val['statusco'],
                "regis_no" => $val['regisco'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "ket" => $val['ket'],
                "cus_kode" => $val['cus_kode'],
                "cus_nama" => $val['cus_nama'],
                "total" => floatval($val['total']),
                "totalusd" => floatval($val['totalusd']),
                "pocustomer" => $val['pocustomer'],
                "user" => $this->session->userName,
                "rateidr" => floatval($val['rate']),
                "type" => $val['type']
            );

            $this->addpraco->insert($insertarray);
        }

        if ($jsonData[0]['statusco'] == 'estimate') {
            if (count($jsonFile) > 0) {
                foreach ($jsonFile as $key => $val) {
                    $arrayInsert = array(
                        "trano" => $trano,
//                        "prj_kode" => $prjKode,
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => $this->session->userName,
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->files->insert($arrayInsert);
                }
            }
        }

        $json = "{success: true, number : '$trano'}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function transferbudgetmenuAction() {
//         $tes = new Zend_Session_Namespace('login');
//         var_dump($tes->name);
//         die;
    }

    public function addtransferbudgetAction() {
        
    }

    public function apptransferbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/TBOQ';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve != '') {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $tempTboq = new Sales_Models_TemporaryTransferBudget();
                    $data = $tempTboq->fetchAll("trano = '$approve'")->toArray();
                    if ($data) {
                        $ref_number = $data[0]['ref_number'];

                        if ($data[0]['trano_type'] == 'PR' || $data[0]['trano_type'] == 'PRO') {

                            $query = $this->db->query("CALL get_data_project(\"$ref_number\")");
                            $query->closeCursor();

                            $sql = 'Select * from pr';
                            try {
                                $ref_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $ref_data = null;
                            }

                            $sql = 'Select * from po';
                            try {
                                $po_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $po_data = null;
                            }

                            $sql = 'Select * from rpi';
                            try {
                                $rpi_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $rpi_data = null;
                            }

                            $this->view->poData = ($po_data ? $po_data : false);
                            $this->view->rpiData = ($rpi_data ? $rpi_data : false);
                        } else {
                            $query = $this->db->query("CALL get_data_advance(\"$ref_number\")");
                            $query->closeCursor();

                            $sql = 'Select * from arf';
                            try {
                                $ref_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $ref_data = null;
                            }

                            $sql = 'Select * from asf';
                            try {
                                $asf = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $asf = null;
                            }

                            $sql = 'Select * from asfcancel';
                            try {
                                $asfcancel = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                            } catch (Exception $e) {
                                $asfcancel = null;
                            }

                            $this->view->asfData = ($asf ? $asf : false);

                            $this->view->asfCancelData = ($asfcancel ? $asfcancel : false);
                        }


                        $sql = 'Select * from bpv_data';
                        try {
                            $bpv_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                        } catch (Exception $e) {
                            $bpv_data = null;
                        }

                        $sql = 'Select * from pay_data';
                        try {
                            $pay_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
                        } catch (Exception $e) {
                            $pay_data = null;
                        }



                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;

                        $this->view->prj_kode = $data[0]['prj_kode'];
                        $this->view->sit_kode = $data[0]['sit_kode'];
                        $this->view->workid = $data[0]['workid'];
                        $this->view->kode_brg = $data[0]['kode_brg'];

                        $kodeBrg = $data[0]['kode_brg'];
                        $modelBarang = new Default_Models_MasterBarang();
                        $dataBarang = $modelBarang->fetchRow("kode_brg = '$kodeBrg'");
                        $this->view->nama_brg = $dataBarang['nama_brg'];

                        $this->view->trano = $approve;
                        $this->view->ref_no = $ref_number;
                        $this->view->ref_data = ($ref_data ? $ref_data : false);
                        $this->view->bpvData = ($bpv_data ? $bpv_data : false);
                        $this->view->payData = ($pay_data ? $pay_data : false);
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function edittransferbudgetAction() {

        $tranoType = $this->getRequest()->getParam('trano_type');
        $trano = $this->getRequest()->getParam('trano');

        $tempTboq = new Sales_Models_TemporaryTransferBudget();
        $data = $tempTboq->fetchAll("trano = '$trano'")->toArray();

        if (!$data) {
            $result = Zend_Json::encode(array("success" => false));
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($result);
        }

        $refData = $data[0]['ref_number'];
        $itemType = $data[0]['trano_type'];


        if ($itemType == 'PR' || $itemType == 'PRO') {

            $query = $this->db->query("CALL get_data_project(\"$refData\")");
            $query->closeCursor();

            $sql = 'Select * from pr';
            try {
                $ref_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $ref_data = null;
            }

            $sql = 'Select * from po';
            $po_data = $this->db->fetchAll($sql);
            try {
                $po_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $po_data = null;
            }
            $sql = 'Select * from rpi';
            $rpi_data = $this->db->fetchAll($sql);
            try {
                $rpi_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $rpi_data = null;
            }
            $this->view->poData = ($po_data ? $po_data : false);
            $this->view->rpiData = ($rpi_data ? $rpi_data : false);
        } else {
            $query = $this->db->query("CALL get_data_advance(\"$refData\")");
            $query->closeCursor();

            $sql = 'Select * from arf';
            $ref_data = $this->db->fetchAll($sql);
            try {
                $ref_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $ref_data = null;
            }
            $sql = 'Select * from asf';
            $asf = $this->db->fetchAll($sql);
            try {
                $asf = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $asf = null;
            }
            $sql = 'Select * from asfcancel';
            $asfcancel = $this->db->fetchAll($sql);
            try {
                $asfcancel = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
            } catch (Exception $e) {
                $asfcancel = null;
            }
            $this->view->asfData = ($asf ? $asf : false);
            $this->view->asfCancelData = ($asfcancel ? $asfcancel : false);
        }


        $sql = 'Select * from bpv_data';
        $bpv_data = $this->db->fetchAll($sql);
        try {
            $bpv_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
        } catch (Exception $e) {
            $bpv_data = null;
        }
        $sql = 'Select * from pay_data';
        $pay_data = $this->db->fetchAll($sql);
        try {
            $pay_data = ($this->db->fetchAll($sql) ? $this->db->fetchAll($sql) : null);
        } catch (Exception $e) {
            $pay_data = null;
        }
        $this->view->prj_kode = $data[0]['prj_kode'];
        $this->view->prj_kode_from = $ref_data[0]['prj_kode'];
        $this->view->sit_kode_from = $ref_data[0]['sit_kode'];
        $this->view->sit_kode = $data[0]['sit_kode'];
        $this->view->workid = $data[0]['workid'];
        $this->view->kode_brg = $data[0]['kode_brg'];
        $this->view->trano_type = $data[0]['trano_type'];

        $kodeBrg = $data[0]['kode_brg'];
        $modelBarang = new Default_Models_MasterBarang();
        $dataBarang = $modelBarang->fetchRow("kode_brg = '$kodeBrg'");
        $this->view->nama_brg = $dataBarang['nama_brg'];

        $this->view->trano = $trano;
        $this->view->ref_no = $refData;
        $this->view->ref_data = ($ref_data ? $ref_data : false);
        $this->view->bpvData = ($bpv_data ? $bpv_data : false);
        $this->view->payData = ($pay_data ? $pay_data : false);
    }

    public function dotransferbudgetAction() {

        $this->_helper->viewRenderer->setNoRender();

        $refData = $this->getRequest()->getParam('refData');


        $projectTo = $this->getRequest()->getParam('projectTo');
        $siteTo = $this->getRequest()->getParam('siteTo');
        $workidTo = $this->getRequest()->getParam('workidTo');
        $kodebrgTo = $this->getRequest()->getParam('kodebrgTo');
        $ref_number = $this->getRequest()->getParam('ref_number');
        $tranoType = $this->getRequest()->getParam('tranoType');


        $items = Zend_Json::decode($refData);
        $items['prj_kode'] = $projectTo;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;

        $params = array(
            "workflowType" => "TBOQ",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $projectTo,
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "useOverride" => $useOverride
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $tempTboq = new Sales_Models_TemporaryTransferBudget();

        $insertArray = array(
            "trano" => $trano,
            "prj_kode" => $projectTo,
            "sit_kode" => $siteTo,
            "workid" => $workidTo,
            "kode_brg" => $kodebrgTo,
            "tgl" => date("Y-m-d"),
            "ref_number" => $ref_number,
            "trano_type" => $tranoType
        );

        $tempTboq->insert($insertArray);


        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function doupdatetransferbudgetAction() {

        $this->_helper->viewRenderer->setNoRender();

        $tranoEdit = $this->getRequest()->getParam('tranoEdit');
        $refData = $this->getRequest()->getParam('refData');

        $projectTo = $this->getRequest()->getParam('projectTo');
        $siteTo = $this->getRequest()->getParam('siteTo');
        $workidTo = $this->getRequest()->getParam('workidTo');
        $kodebrgTo = $this->getRequest()->getParam('kodebrgTo');
        $ref_number = $this->getRequest()->getParam('ref_number');
        $tranoType = $this->getRequest()->getParam('tranoType');


        $items = Zend_Json::decode($refData);
        $items['prj_kode'] = $projectTo;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;

        $params = array(
            "workflowType" => "TBOQ",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "itemID" => $tranoEdit,
            "items" => $items,
            "prjKode" => $projectTo,
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "useOverride" => $useOverride
        );
        $this->workflow->setWorkflowTransNew($params);

        $tempTboq = new Sales_Models_TemporaryTransferBudget();

        $old = $tempTboq->fetchAll("trano = '$tranoEdit'")->toArray();
        $log['tboq-detail-before'] = $old;
        $tempTboq->delete("trano = '$tranoEdit'");

        $insertArray = array(
            "trano" => $tranoEdit,
            "prj_kode" => $projectTo,
            "sit_kode" => $siteTo,
            "workid" => $workidTo,
            "kode_brg" => $kodebrgTo,
            "tgl" => date("Y-m-d"),
            "ref_number" => $ref_number,
            "trano_type" => $tranoType
        );

        $tempTboq->insert($insertArray);

        $new = $tempTboq->fetchRow("trano = '$tranoEdit'")->toArray();
        $log2['tboq-detail-after'] = $new;

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $tranoEdit,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $projectTo,
            "sit_kode" => $siteTo,
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);


        $result = Zend_Json::encode(array("success" => true, "number" => $tranoEdit));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

}
