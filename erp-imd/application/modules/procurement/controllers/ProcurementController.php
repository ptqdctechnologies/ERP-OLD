<?php

class Procurement_ProcurementController extends Zend_Controller_Action {

    private $procurement;
    private $procurementH;
    private $db;
    private $request;
    private $json;
    private $util;
    private $token;
    private $session;
    private $workflow;
    private $workflowClass;
    private $workflowTrans;
    private $project;
    private $barang;
    private $const;
    private $purchase;
    private $purchaseH;
    private $rpi;
    private $rpiH;
    private $asf;
    private $asfc;
    private $asfD;
    private $asfH;
    private $trans;
    private $arfh;
    private $arfd;
    private $pmeal;
    private $pmealH;
    private $error;
    private $upload;
    private $files;
    private $budget;
    private $quantity;
    private $reimbursH;
    private $reimbursD;
    private $log;
    private $logActivity;
    private $paymentreimbursH;
    private $requestcancel;
    private $temporary_jurnal_ap;
    private $temporary_bpv;
    private $DEFAULT;
    private $ADMIN;
    private $coa;
    private $miscWorkid;
    private $cost;
    private $paymentArf;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
        $this->cost = new Default_Models_Cost();
        $this->upload = $this->_helper->getHelper('uploadfile');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->session = new Zend_Session_Namespace('login');
        $this->error = $this->_helper->getHelper('error');
        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('posts');
        if (isset($this->json)) {
            //Fix unknown JSON format (Bugs on Firefox 3.6)
            $this->json = str_replace("\\", "", $this->json);
            if (substr($this->json, 0, 1) != '[') {
                $this->json = "[" . $this->json . "]";
            }
        }
        $this->arfh = new Default_Models_AdvanceRequestFormH();
        $this->arfd = new Default_Models_AdvanceRequestFormD();
        $this->reimbursH = new Default_Models_ReimbursH();
        $this->reimbursD = new Default_Models_ReimbursD();
        $this->procurement = new Default_Models_ProcurementRequest();
        $this->procurementH = new Default_Models_ProcurementRequestH();
        $this->purchase = new Default_Models_ProcurementPod();
        $this->purchaseH = new Default_Models_ProcurementPoh();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->rpi = new Default_Models_RequestPaymentInvoice();
        $this->rpiH = new Default_Models_RequestPaymentInvoiceH();
        $this->asf = new Default_Models_AdvanceSettlementForm();
        $this->asfc = new Default_Models_AdvanceSettlementFormCancel();
        $this->asfD = new Default_Models_AdvanceSettlementFormD();
        $this->asfH = new Default_Models_AdvanceSettlementFormH();
        $this->pmeal = new Default_Models_PieceMeal();
        $this->pmealH = new Default_Models_PieceMealH();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->files = new Default_Models_Files();
        $this->budget = new Default_Models_Budget();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->log = new Admin_Models_Logtransaction();
        $this->logActivity = new Admin_Models_Activitylog();
        $this->paymentreimbursH = new Procurement_Model_PaymentReimbursH();
        $this->requestcancel = new Procurement_Model_RequestCancel();
        $this->temporary_jurnal_ap = new Finance_Models_AccountingTemporaryJurnalAP();
        $this->temporary_bpv = new Finance_Models_AccountingTemporaryBPV();
        $this->coa = new Finance_Models_MasterCoa();
        $this->paymentArf = new Finance_Models_PaymentARF();

        $this->miscWorkid = Zend_Registry::get('misc');

        $this->miscWorkid = Zend_Registry::get('misc');

        $models = array(
            "ProcurementPoh",
            "ProcurementPod",
            "MasterSite",
            "MasterProject",
            "Files"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
        $models = array(
            "Logtransaction"
        );
        $this->ADMIN = QDC_Model_Admin::init($models);
    }

    public function indexAction() {
        
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        switch ($listType) {
            case 'poh':
                $procurement = new Default_Models_ProcurementPoh();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
                break;
            case 'pod':
                $trano = $request->getParam('trano');
                $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_pod WHERE trano='$trano' ORDER BY urut";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetchAll();
                $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                break;
            case 'prh':
                $procurement = new Default_Models_ProcurementRequestH();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
                break;
            case 'noprd':
                $sql = "SELECT * FROM procurement_prd p order by tgl desc,trano desc limit 1";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetch();
                $return['count'] = 1;
                break;
            case 'prd':
                //$trano = $request->getParam('trano');
                //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano='$trano' ORDER BY urut";

                $prj_kode = $request->getParam('prj_kode');
                $sit_kode = $request->getParam('sit_kode');
                $workid = $request->getParam('workid');
                $kode_brg = $request->getParam('kode_brg');

                //$sql = "call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')";
                //$fetch = $this->db->query($sql);
                //$return['posts'] = $fetch->fetchAll();
                //$return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                //break;
                // call store procedure
                $sp = $this->db->prepare("call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')");
                $sp->execute();
                $return['posts'] = $sp->fetchAll();
                $return['count'] = count($return['posts']);
                $sp->closeCursor();

                break;
            case 'arfd':
                //$trano = $request->getParam('trano');
                //$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM procurement_prd WHERE trano='$trano' ORDER BY urut";

                $prj_kode = $request->getParam('prj_kode');
                $sit_kode = $request->getParam('sit_kode');
                $workid = $request->getParam('workid');
                $kode_brg = $request->getParam('kode_brg');

                //$sql = "call sp_boq3pr('$prj_kode','$sit_kode','$workid','$kode_brg')";
                //$fetch = $this->db->query($sql);
                //$return['posts'] = $fetch->fetchAll();
                //$return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                //break;
                // call store procedure
                $sp = $this->db->prepare("call sp_boq3arf('$prj_kode','$sit_kode','$workid','$kode_brg')");
                $sp->execute();
                $return['posts'] = $sp->fetchAll();
                $return['count'] = count($return['posts']);
                $sp->closeCursor();

                break;
        }

        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');
        $joinToPod = $request->getParam('joinToPod');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        if ($joinToPod) {
            $prj_kode = $request->getParam('Prj_Kode');
            $sql = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS a.trano,a.prj_kode,a.tgl,a.tglpr FROM procurement_poh a INNER JOIN procurement_pod b ON a.prj_kode = b.prj_kode WHERE b.' . $columnName . ' LIKE \'%' . $columnValue . '%\' AND b.Prj_Kode LIKE \'%' . $prj_kode . '%\' ORDER BY a.' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;
        } else
            $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_poh WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit;

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

    public function pohAction() {
        
    }

    public function chartAction() {
        $request = $this->getRequest();

//        $chartType = $request->getParam('type');
//        if ($chartType == 'byProject')
//        {
//            $this->render('chart');
//        }
    }

    public function getchartAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $chartType = $request->getParam('data');
        if (isset($chartType)) {
            $sql = "SELECT SUM(a.total) AS total,a.tgl FROM procurement_poh a WHERE a.prj_kode='$chartType' GROUP BY CONCAT(a.tgl);";

            $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0; $i < count($return); $i++) {
                foreach ($return[$i] as $key => $value) {
                    $nama_key = $return[$i]['tgl'];
                    $data[$nama_key] = ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml = $this->leadHelper->buildChart($data, 'Detail Nilai Project', 'Tgl', 'Nilai Project');
        } else {
            $sql = "SELECT SUM(a.total) AS total,a.prj_kode,b.prj_nama FROM procurement_poh a LEFT JOIN master_project b on a.prj_kode = b.prj_kode GROUP BY prj_kode;";

            $fetch = $this->db->query($sql);
            $return = $fetch->fetchAll();
            $data = array();
            error_reporting(0);
            for ($i = 0; $i < count($return); $i++) {
                foreach ($return[$i] as $key => $value) {
                    $nama_key = $return[$i]['prj_kode'] . "-" . $return[$i]['prj_nama'];
                    $data[$nama_key] = ceil($return[$i]['total']);
                }
            }

            error_reporting(E_ALL ^ E_NOTICE);
            $xml = $this->leadHelper->buildChart($data, 'Total Nilai Project', 'Project Name', 'Nilai Project', 'JavaScript:showDrillDown', true);
        }
        $this->getResponse()->setHeader('Content-Type', 'text/xml');
        $this->getResponse()->setBody($xml);
    }

    public function showpoAction() {
        
    }

    public function showprAction() {
        
    }

    public function getpoAction() {
        
    }

    public function deletepoAction() {
        
    }

    public function insertprAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $file = $this->getRequest()->getParam('file');
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PR",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "rateidr" => $rate['rateidr']
            );
            
            $this->procurement->insert($arrayInsert);                        
            $activityDetail['procurement_prd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }
        
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "cus_kode" => $cusKode[0]['cus_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s')
        );
        
        $this->procurementH->insert($arrayInsert);
        $activityHead['procurement_prh'][0]=$arrayInsert;
        
        $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        
        $activityLog = array(
            "menu_name" => "Create PR",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        
        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateprAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $urut = 1;
        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => 'PR',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "rateidr" => $rate['rateidr']
            );
            $urut++;

            $this->procurement->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['workid']}'");
        }
        $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "cus_kode" => $cusKode[0]['cus_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s')
        );
        $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'");
        $this->procurementH->update($arrayInsert,"trano = '$trano'");
        $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'");

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        
        // Delete data file di Table File WHERE trano=...
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatefinalprAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        Zend_Loader::loadClass('Zend_Json');
        
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $urut = 1;
        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $this->workflowTrans->delete("item_id = '$trano'");
        
        $params = array(
            "workflowType" => 'PR',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "rateidr" => $rate['rateidr']
            );
            $urut++;

            $this->procurement->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['workid']}'");
        }
        $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "cus_kode" => $cusKode[0]['cus_kode'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "revisi" => $jsonEtc[0]['rev'],
        );
        $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'");
        $this->procurementH->update($arrayInsert,"trano = '$trano'");
        $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'");

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        
        // Delete data file di Table File WHERE trano=...
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    
     public function updatefinalprohAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $urut = 1;

        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $this->workflowTrans->delete("item_id = '$trano'");
        
        $params = array(
            "workflowType" => 'PRO',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {
            
            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe,
                "rateidr" => $rate['rateidr']
            );
            $urut++;

            $this->procurement->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['budgetid']}'");
        }
        $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "tipe" => $tipe
        );

        $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'");
        $this->procurementH->update($arrayInsert, "trano = '$trano'");
        $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'");
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        // Delete data file di Table File WHERE trano=...
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    
    public function insertprbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $sales = $this->getRequest()->getParam('sales');
        $etc = str_replace("\\", "", $etc);

        $this->json = str_replace("\r", "", $this->json);
        $this->json = str_replace("\n", "", $this->json);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

         $params = array(
            "workflowType" => "PRO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe,
                "rateidr" => $rate['rateidr']
            );
            $urut++;

            $this->procurement->insert($arrayInsert);
            $activityDetail['procurement_prd'][$activityCount]=$arrayInsert;
            $activityCount++;
        }
        
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "tipe" => $tipe
        );
        $this->procurementH->insert($arrayInsert);
        $activityHead['procurement_prh'][0]=$arrayInsert;

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
         if ($sales) {
            $activityLog = array(
            "menu_name" => "Create PR Sales",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        } else {
            $activityLog = array(
            "menu_name" => "Create PR Overhead",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        }
        

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateprbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $urut = 1;

        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => 'PRO',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        $log['pr-detail-before'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {
            
            $total = floatval($val['qty']) * floatval($val['harga']);
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe,
                "rateidr" => $rate['rateidr']
            );
            $urut++;

            $this->procurement->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['budgetid']}'");
        }
        $log2['pr-detail-after'] = $this->procurement->fetchAll("trano = '$trano'")->toArray();

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "petugas" => $this->session->userName,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "tipe" => $tipe
        );

        $log['pr-header-before'] = $this->procurementH->fetchRow("trano = '$trano'");
        $this->procurementH->update($arrayInsert, "trano = '$trano'");
        $log2['pr-header-after'] = $this->procurementH->fetchRow("trano = '$trano'");
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        // Delete data file di Table File WHERE trano=...
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updaterpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        
        $properties = str_replace("\\", "", $this->getRequest()->getParam('etc'));
        
        //parse newline,\\ char
        $etc = preg_replace("~[\r\nØ]~", "", $properties);        
        $dataJson = preg_replace("~[\r\nØ]~", "", $this->json);
      
        $jsonData = Zend_Json::decode($dataJson);
        $jsonEtc = Zend_Json::decode($etc);
        
        $jsonJurnal = $this->getRequest()->getParam('jurnal');
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));
        $jsonDeletedFile = Zend_Json::decode($this->getRequest()->getParam('deletefile'));

//       var_dump($jsonData);die;

        $trano = $jsonEtc[0]['trano'];

        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $items['prj_kode'] = $jsonEtc[0]['prj_kode'];

        $result = $this->workflow->setWorkflowTrans($trano, 'RPI', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {
        
            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $urut = 1;
        $gtotal = 0;
        $totalPPN = 0;

        //insert to Log....
        $log['rpi-detail-before'] = array();
        $fetch = $this->rpi->fetchAll("trano = '$trano'");
        if ($fetch) {
            $fetch = $fetch->toArray();
            $log['rpi-detail-before'] = $fetch;
        }
        //done

        $this->rpi->delete("trano = '$trano'");
        $log2['rpi-detail-after'] = array();

        $supplier2 = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $totalPPH = 0;
        $totalGrossup = 0;
        $totalDeduction = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['harga'];
            $total = $val['qty'] * $harga;
            $arrayInsert = array();
            $totalPPN += $val['ppn'];
            $gtotal += $total;
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "cus_kode" => $jsonEtc[0]['cus_kode'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "po_no" => $val['po_no'],
                "pr_no" => $val['pr_no'],
                "ppn" => $val['ppn'],
                "qty" => $val['qty'],
                "harga" => $harga,
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
            );
            $log2['rpi-detail-after'][] = $arrayInsert;
            $deduct = floatval(str_replace(",", "", $val['deduction']));
            if ($deduct >= 0) {
                $totalDeduction += $deduct;
                $arrayInsert['total_deduction'] = $deduct;
            }
            if ($val['holding_tax_status'] == 'Y') {
                $totalPPH += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['totalwht'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['wht'] = floatval(str_replace(",", "", $val['holding_tax']));
            }

            if ($val['grossup_status'] == 'Y') {
                $totalGrossup += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['total_grossup'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['grossup'] = floatval(str_replace(",", "", $val['holding_tax']));
            }
            $this->rpi->insert($arrayInsert);

            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];

            if ($statusppn == 'Y') {
                $coa_ppn = '1-4400';
//                $adaPPN = true;
            } else {
                $coa_ppn = '';
            }

            if ($statustax == 'Y') {
                $coa_tax = '2-2100';
            } else {
                $coa_tax = '';
            }

            $total_bayar = str_replace(",", "", $val['total_value']);
            $ht_value = str_replace(",", "", $val['holding_tax_val']);
            $grossup = $val['grossup_status'];
            if ($grossup == 'Y') {
//                $total_bayar = floatval($total_bayar) + floatval($ht_value);
            }

            $arrayBPV[] = array(
                "trano" => '',
                "tgl" => '',
                "item_type" => 'RPI',
                "total_value" => floatval(str_replace(",", "", $val['value'])),
                "total_bayar" => floatval(str_replace(",", "", $val['value'])),
                "statusppn" => $val['statusppn'],
                "valueppn" => floatval(str_replace(",", "", $val['valueppn'])),
                "coa_ppn" => $coa_ppn,
                "grossup_status" => $val['grossup_status'],
                "holding_tax_status" => $val['holding_tax_status'],
                "holding_tax" => floatval(str_replace(",", "", $val['holding_tax'])),
                "holding_tax_val" => floatval(str_replace(",", "", $val['holding_tax_val'])),
                "holding_tax_text" => $val['holding_tax_text'],
                "coa_holding_tax" => $coa_tax,
                "deduction" => floatval(str_replace(",", "", $val['deduction'])),
                "total" => $total_bayar,
                "valuta" => $val['val_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $trano,
                "coa_kode" => $val['coa_kode'],
                "ketin" => $val['nama_brg'],
                "requester" => $supplier2['sup_nama'],
                "uid" => $this->session->userName,
                "statuspulsa" => '',
                "trano_ppn" => '',
                "ppn_ref_number" => $val['ppn_ref_number'],
                "status_bpv_ppn" => '',
                "productid" => $val['kode_brg'],
                "type" => $jsonEtc[0]['voc_type'],
                "pr_no" => $val['pr_no']
            );

            $urut++;
        }

        $radio = array();
        $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
        $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
        $radio['do-radio'] = $jsonEtc[0]['do_radio'];
        $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

        $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

        $radios = Zend_Json::encode($radio);
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        if ($jsonEtc[0]['ppn'] > 0)
            $ppn = $gtotal * 0.1;
        else
            $ppn = 0;

        $result = $this->rpiH->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['rpi-header-before'] = $result;
        }

        $statusppn = 'N';
        $ppn = $totalPPN;
        if ($ppn > 0)
            $statusppn = 'Y';

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "tglinvoice" => date('Y-m-d'),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "rateidr" => $jsonEtc[0]['rate_idr'],
            "val_kode" => $jsonData[0]['val_kode'],
            "namabank" => $supplier['namabank'],
            "rekbank" => $supplier['rekbank'],
            "reknamabank" => $supplier['reknamabank'],
            "ket" => $jsonEtc[0]['rpi_ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "po_no" => $jsonEtc[0]['po_no'],
            "totalpo" => $jsonEtc[0]['totalPO'],
            "petugas" => $this->session->userName,
            "invoice_no" => $jsonEtc[0]['sup_invoice'],
            "ppn" => $ppn,
            "ppnpo" => $jsonEtc[0]['ppn'],
            "total" => $gtotal,
            "gtotal" => $gtotal + ($ppn),
            "cus_kode" => $cusKode,
            "document_valid" => $radios,
            "with_materai" => $jsonEtc[0]['with_materai'],
            "materai" => $jsonEtc[0]['materai'],
            "statusbrg" => $jsonEtc[0]['statusbrg']
        );

        $log2['rpi-header-after'] = $arrayInsert;

        if ($totalPPH >= 0) {
            $arrayInsert['totalwht'] = $totalPPH;
        }
        if ($totalGrossup >= 0) {
            $arrayInsert['total_grossup'] = $totalGrossup;
        }
        if ($totalDeduction >= 0) {
            $arrayInsert['total_deduction'] = $totalDeduction;
        }

        $this->rpiH->delete("trano = '$trano'");
        $this->rpiH->insert($arrayInsert);

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        //Cek kalau RPI yg diupdate adalah RPI lama (sebelum adanya UI BPV)
        $cek_bpv = $this->temporary_bpv->fetchRow("trano = '$trano'");

        $this->temporary_bpv->delete("trano = '$trano'");
        $this->temporary_jurnal_ap->delete("trano = '$trano'");

        $tempJurnalAP = array(
            "trano" => $trano,
            "jurnal" => $jsonJurnal
        );

        if ($cek_bpv) {
            $this->temporary_bpv->insert(
                    array(
                        "trano" => $trano,
                        "data" => Zend_Json::encode($arrayBPV)
            ));
            $this->temporary_jurnal_ap->insert($tempJurnalAP);
        }
        $this->files->delete("trano = '$trano'");

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

//            if (count($jsonDeletedFile) > 0)
//            {
//                foreach ($jsonDeletedFile as $key => $val)
//                {
//                    $this->files->delete("id = {$val['id']}");
//                }
//            }

        $this->getResponse()->setBody("{success: true}");
    }

    public function updaterpibudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $sales = $this->getRequest()->getParam('sales');
                
        $properties = str_replace("\\", "", $this->getRequest()->getParam('etc'));
        
        //parse newline,\\ char
        $etc = preg_replace("~[\r\nØ]~", "", $properties);        
        $dataJson = preg_replace("~[\r\nØ]~", "", $this->json);
      
        $jsonData = Zend_Json::decode($dataJson);
        $jsonEtc = Zend_Json::decode($etc);
        
        $jsonJurnal = $this->getRequest()->getParam('jurnal');
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));
//       var_dump($jsonFile);die;

        $trano = $jsonEtc[0]['trano'];
        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $items['prj_kode'] = $jsonEtc[0]['prj_kode'];
        
        $result = $this->workflow->setWorkflowTrans($trano, 'RPIO', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $urut = 1;
        $gtotal = 0;
        $totalPPN = 0;

        //insert to Log....
        $log['rpi-detail-before'] = array();
        $fetch = $this->rpi->fetchAll("trano = '$trano'");
        if ($fetch) {
            $fetch = $fetch->toArray();
            $log['rpi-detail-before'] = $fetch;
        }
        //done

        $this->rpi->delete("trano = '$trano'");
        $log2['rpi-detail-after'] = array();

        $supplier2 = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $totalPPH = 0;
        $totalGrossup = 0;
        $totalDeduction = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['harga'];
            $total = $val['qty'] * $harga;
            $arrayInsert = array();
            $gtotal += $total;
            $totalPPN += $val['ppn'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "cus_kode" => $jsonEtc[0]['cus_kode'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "po_no" => $val['po_no'],
                "pr_no" => $val['pr_no'],
                "ppn" => $val['ppn'],
                "qty" => $val['qty'],
                "harga" => $harga,
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "tipe" => $tipe
            );
            $log2['rpi-detail-after'][] = $arrayInsert;
            $deduct = floatval(str_replace(",", "", $val['deduction']));
            if ($deduct >= 0) {
                $totalDeduction += $deduct;
                $arrayInsert['total_deduction'] = $deduct;
            }
            if ($val['holding_tax_status'] == 'Y') {
                $totalPPH += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['totalwht'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['wht'] = floatval(str_replace(",", "", $val['holding_tax']));
            }
            if ($val['grossup_status'] == 'Y') {
                $totalGrossup += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['total_grossup'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['grossup'] = floatval(str_replace(",", "", $val['holding_tax']));
            }
            $this->rpi->insert($arrayInsert);

            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];

            if ($statusppn == 'Y') {
                $coa_ppn = '1-4400';
            } else {
                $coa_ppn = '';
            }

            if ($statustax == 'Y') {
                $coa_tax = '2-2100';
            } else {
                $coa_tax = '';
            }

            $total_bayar = str_replace(",", "", $val['total_value']);
            $ht_value = str_replace(",", "", $val['holding_tax_val']);
            $grossup = $val['grossup_status'];

            $arrayBPV[] = array(
                "trano" => '',
                "tgl" => '',
                "item_type" => 'RPI',
                "total_value" => floatval(str_replace(",", "", $val['value'])),
                "total_bayar" => floatval(str_replace(",", "", $val['value'])),
                "statusppn" => $val['statusppn'],
                "valueppn" => floatval(str_replace(",", "", $val['valueppn'])),
                "coa_ppn" => $coa_ppn,
                "grossup_status" => $val['grossup_status'],
                "holding_tax_status" => $val['holding_tax_status'],
                "holding_tax" => floatval(str_replace(",", "", $val['holding_tax'])),
                "holding_tax_val" => floatval(str_replace(",", "", $val['holding_tax_val'])),
                "holding_tax_text" => $val['holding_tax_text'],
                "coa_holding_tax" => $coa_tax,
                "deduction" => floatval(str_replace(",", "", $val['deduction'])),
                "total" => $total_bayar,
                "valuta" => $val['val_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $trano,
                "coa_kode" => $val['coa_kode'],
                "ketin" => $val['nama_brg'],
                "requester" => $supplier2['sup_nama'],
                "uid" => $this->session->userName,
                "statuspulsa" => '',
                "trano_ppn" => '',
                "ppn_ref_number" => $val['ppn_ref_number'],
                "status_bpv_ppn" => '',
                "productid" => $val['kode_brg'],
                "type" => $jsonEtc[0]['voc_type'],
                "pr_no" => $val['pr_no']
            );

            $urut++;
        }

        $radio = array();
        $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
        $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
        $radio['do-radio'] = $jsonEtc[0]['do_radio'];
        $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];

        $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

        $radios = Zend_Json::encode($radio);
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        if ($jsonEtc[0]['ppn'] > 0)
            $ppn = $gtotal * 0.1;
        else
            $ppn = 0;

        $result = $this->rpiH->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['rpi-header-before'] = $result;
        }

        $statusppn = 'N';
        $ppn = $totalPPN;
        if ($ppn > 0)
            $statusppn = 'Y';

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "tglinvoice" => date('Y-m-d'),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "rateidr" => $jsonEtc[0]['rate_idr'],
            "val_kode" => $jsonData[0]['val_kode'],
            "namabank" => $supplier['namabank'],
            "rekbank" => $supplier['rekbank'],
            "reknamabank" => $supplier['reknamabank'],
            "ket" => $jsonEtc[0]['rpi_ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "po_no" => $jsonEtc[0]['po_no'],
            "totalpo" => $jsonEtc[0]['totalPO'],
            "petugas" => $this->session->userName,
            "invoice_no" => $jsonEtc[0]['sup_invoice'],
            "ppn" => $ppn,
            "ppnpo" => $jsonEtc[0]['ppn'],
            "total" => $gtotal,
            "gtotal" => $gtotal + ($ppn),
            "cus_kode" => $cusKode,
            "document_valid" => $radios,
            "tipe" => $tipe,
            "with_materai" => $jsonEtc[0]['with_materai'],
            "materai" => $jsonEtc[0]['materai'],
            "statusbrg" => $jsonEtc[0]['statusbrg']
        );

        $log2['rpi-header-after'] = $arrayInsert;
        if ($totalPPH > 0)
            $arrayInsert['totalwht'] = $totalPPH;

        if ($totalGrossup >= 0) {
            $arrayInsert['total_grossup'] = $totalGrossup;
        }
        if ($totalDeduction >= 0) {
            $arrayInsert['total_deduction'] = $totalDeduction;
        }
        $this->rpiH->delete("trano = '$trano'");
        $this->rpiH->insert($arrayInsert);

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        //Cek kalau RPI yg diupdate adalah RPI lama (sebelum adanya UI BPV)
        $cek_bpv = $this->temporary_bpv->fetchRow("trano = '$trano'");

        $this->temporary_bpv->delete("trano = '$trano'");
        $this->temporary_jurnal_ap->delete("trano = '$trano'");

        $tempJurnalAP = array(
            "trano" => $trano,
            "jurnal" => $jsonJurnal
        );


        if ($cek_bpv) {
            $this->temporary_bpv->insert(
                    array(
                        "trano" => $trano,
                        "data" => Zend_Json::encode($arrayBPV)
            ));
            $this->temporary_jurnal_ap->insert($tempJurnalAP);
        }

        $this->files->delete("trano = '$trano'");

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true}");
    }

    public function insertpoAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
        $total = 0;
        $totalSupp = 0;

        $grandtotals = 0;
        $grandtotalsSupp = 0;

        $totalppn = 0;
        $totalppnSupp = 0;
        $tax = '';

        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totals += $total;
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];
            $totalsSupp += $totalSupp;

            if ($val['statusppn'] == 'Y') {
                $totalppn += $val['valueppn'];
                $totalppnSupp += $val['valueppnSupp'];
            }
        }

        if ($totalppn != 0)
            $tax = 'Y';

        $grandtotals = $totals + $totalppn;
        $grandtotalsSupp = $totalsSupp + $totalppnSupp;


        $counter = new Default_Models_MasterCounter();

        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        if ($jsonEtc[0]['tgldeliesti'] != '')
            $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
        else
            $tgldeliesti = $tgl;

        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "pr_no" => $val['pr_number'],
                "tglpr" => $val['tgl_pr'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "qtyspl" => $val['qtySupp'],
                "harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
                "statusppn" => $tax,
                "ppn" => $val['valueppn'],
                "ppnspl" => $val['valueppnSupp'],
                "total" => $total,
                "totalspl" => $totalSupp,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['currency'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act']
            );
            $urut++;

            $harga = floatval($val['price']);
            $kode_brg = $val['kode_brg'];

            $this->purchase->insert($arrayInsert);
            $activityDetail['procurement_pod'][$activityCount]=$arrayInsert;
            $activityCount++;
        }
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "pr_no" => $jsonData[0]['pr_number'],
            "tglpr" => $jsonData[0]['tgl_pr'],
            "prj_kode" => $jsonData[0]['prj_kode'],
            "prj_nama" => $jsonData[0]['prj_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $this->session->userName,
            "myob" => $jsonData[0]['net_act'],
            "statusppn" => $tax,
            "jumlah" => $totals,
            "jumlahspl" => $totalsSupp,
            "ppn" => $totalppn,
            "ppnspl" => $totalppnSupp,
            "total" => $grandtotals,
            "totalspl" => $grandtotalsSupp,
            "val_kode" => $jsonEtc[0]['val_kode'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "rateidr" => $jsonEtc[0]['rateidr'],
            "deliverytext" => $jsonEtc[0]['tujuan'],
            "paymentterm" => $jsonEtc[0]['payterm'],
            "top" => $jsonEtc[0]['top'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "tgldeliesti" => $tgldeliesti,
            "invoiceto" => $jsonEtc[0]['invoiceto'],
            "ketin" => $jsonEtc[0]['ketin'],
            "typepo2" => $jsonData[0]['po_type'],
            "cod" => $jsonEtc[0]['cod']
                //"cus_kode" => $cusKode,
        );
        $this->purchaseH->insert($arrayInsert);
        $activityHead['procurement_poh'][0]=$arrayInsert;
        
        $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        $activityLog = array(
            "menu_name" => "Create PO",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);


        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertpobudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $sales = $this->getRequest()->getParam('sales');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
        $total = 0;
        $totalSupp = 0;

        $grandtotals = 0;
        $grandtotalsSupp = 0;

        $totalppn = 0;
        $totalppnSupp = 0;
        $tax = '';

        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totals += $total;
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];
            $totalsSupp += $totalSupp;

            if ($val['statusppn'] == 'Y') {
                $totalppn += $val['valueppn'];
                $totalppnSupp += $val['valueppnSupp'];
            }
        }

        if ($totalppn != 0)
            $tax = 'Y';

        $grandtotals = $totals + $totalppn;
        $grandtotalsSupp = $totalsSupp + $totalppnSupp;



        $counter = new Default_Models_MasterCounter();

        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        
        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        if ($sales) {
            $params = array(
                "workflowType" => "PO",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $jsonEtc[0]['prj_kode'],
                "generic" => false,
                "revisi" => false,
                "returnException" => false,
                "comment" => $comment
            );
            $tipe = 'S';
        } else {
            $params = array(
                "workflowType" => "POO",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $jsonEtc[0]['prj_kode'],
                "generic" => false,
                "revisi" => false,
                "returnException" => false,
                "comment" => $comment
            );
            $tipe = 'O';
        }

        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        if ($jsonEtc[0]['tgldeliesti'] != '')
            $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
        else
            $tgldeliesti = $jsonEtc[0]['tgldeliesti'];


        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "pr_no" => $val['pr_number'],
                "tglpr" => $val['tgl_pr'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "qtyspl" => $val['qtySupp'],
                "harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
                "statusppn" => $tax,
                "ppn" => $val['valueppn'],
                "ppnspl" => $val['valueppnSupp'],
                "total" => $total,
                "totalspl" => $totalSupp,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['currency'],
//				"typepo" => $val['po_type'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe
            );
            $urut++;
            $harga = $val['price'];
            $kode_brg = $val['kode_brg'];
//            $this->barang->update(array("harga_beli" => $harga, "hargaavg" => $harga), "kode_brg = '$kode_brg'");
            $this->purchase->insert($arrayInsert);
            $activityDetail['procurement_pod'][$activityCount]=$arrayInsert;
            $activityCount++;
        }
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "pr_no" => $jsonData[0]['pr_number'],
            "tglpr" => $jsonData[0]['tgl_pr'],
            "prj_kode" => $jsonData[0]['prj_kode'],
            "prj_nama" => $jsonData[0]['prj_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $this->session->userName,
            "myob" => $jsonData[0]['net_act'],
            "statusppn" => $tax,
            "jumlah" => $totals,
            "jumlahspl" => $totalsSupp,
            "ppn" => $totalppn,
            "ppnspl" => $totalppnSupp,
            "total" => $grandtotals,
            "totalspl" => $grandtotalsSupp,
            "val_kode" => $jsonEtc[0]['val_kode'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "rateidr" => $jsonEtc[0]['rateidr'],
            "deliverytext" => $jsonEtc[0]['tujuan'],
            "paymentterm" => $jsonEtc[0]['payterm'],
            "top" => $jsonEtc[0]['top'],
            "budgettype" => $jsonEtc[0]['budgettype'],
            "tgldeliesti" => $tgldeliesti,
            "invoiceto" => $jsonEtc[0]['invoiceto'],
            "ketin" => $jsonEtc[0]['ketin'],
            "typepo2" => $jsonData[0]['po_type'],
            "tipe" => $tipe
                //"cus_kode" => $cusKode,
        );
        $this->purchaseH->insert($arrayInsert);
        $activityHead['procurement_poh'][0]=$arrayInsert;

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
         if ($sales) {
            $activityLog = array(
            "menu_name" => "Create PO Sales",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        } else {
            $activityLog = array(
            "menu_name" => "Create PO Overhead",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        }
        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatepoAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
        $total = 0;
        $totalSupp = 0;

        $grandtotals = 0;
        $grandtotalsSupp = 0;

        $totalppn = 0;
        $totalppnSupp = 0;
        $tax = '';

        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totals += $total;
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];
            $totalsSupp += $totalSupp;

            if ($val['statusppn'] == 'Y') {
                $totalppn += $val['valueppn'];
                $totalppnSupp += $val['valueppnSupp'];
            }
        }

        if ($totalppn != 0)
            $tax = 'Y';

        $grandtotals = $totals + $totalppn;
        $grandtotalsSupp = $totalsSupp + $totalppnSupp;


        $trano = $jsonData[0]['po_number'];
        $urut = 1;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');



        $params = array(
            "workflowType" => "PO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
//        var_dump($params);die;

        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        if ($jsonEtc[0]['tgldeliesti'] != '')
            $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
        else
            $tgldeliesti = $jsonEtc[0]['tgldeliesti'];

        //insert to Log....
        $log['po-detail-before'] = array();
        $fetch = $this->purchase->fetchAll("trano = '$trano'");
        if ($fetch) {
            $fetch = $fetch->toArray();
            $log['po-detail-before'] = $fetch;
        }
        
        $log['po-header-before'] = array();
        $result = $this->purchaseH->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['po-header-before'] = $result;
        }
        //done

        $this->purchase->delete("trano = '$trano'");
        $log2['po-detail-after'] = array();
        
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            if ($val['qtySupp'] == '')
                $val['qtySupp'] = $val['qty'];
            if ($val['priceSupp'] == '')
                $val['priceSupp'] = $val['harga'];
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];



            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "pr_no" => $val['pr_number'],
                "tglpr" => $val['tgl_pr'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "qtyspl" => $val['qtySupp'],
                "harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
                "statusppn" => $tax,
                "ppn" => $val['valueppn'],
                "ppnspl" => $val['valueppnSupp'],
                "total" => $total,
                "totalspl" => $totalSupp,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['currency'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act']
            );
            $urut++;

            $workid = $val['workid'];
            $kode_brg = $val['kode_brg'];
            $harga = floatval($val['price']);

            $this->purchase->insert($arrayInsert);

            //$log2['po-detail-after'][] = $this->purchase->fetchRow("trano = '$trano'")->toArray();
        }
        
        $log2['po-detail-after'] = $this->purchase->fetchAll("trano = '$trano'")->toArray();
       
        $arrayInsert = array(
            "revisi" => $jsonEtc[0]['rev'],
            "statusppn" => $tax,
            "jumlah" => $totals,
            "jumlahspl" => $totalsSupp,
            "ppn" => $totalppn,
            "ppnspl" => $totalppnSupp,
            "total" => $grandtotals,
            "totalspl" => $grandtotalsSupp,
//                "val_kode" => $jsonData[0]['val_kode'],
            "tgldeliesti" => $tgldeliesti,
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "paymentterm" => $jsonEtc[0]['payterm'],
            "top" => $jsonEtc[0]['top'],
            "deliverytext" => $jsonEtc[0]['tujuan'],
            "ket" => $jsonEtc[0]['ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "cod" => $jsonEtc[0]['cod']
            
        );

        //$result = $this->purchaseH->update($arrayInsert, "trano = '$trano'");
        //$result = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $log2['po-header-after']= array();
        $this->purchaseH->update($arrayInsert, "trano = '$trano'");
        $log2['po-header-after'] = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->log->insert($arrayLog);

        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatepobudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $totals = 0;
        $totalsSupp = 0;
        $total = 0;
        $totalSupp = 0;

        $grandtotals = 0;
        $grandtotalsSupp = 0;

        $totalppn = 0;
        $totalppnSupp = 0;
        $tax = '';


        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totals += $total;
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];
            $totalsSupp += $totalSupp;

            if ($val['statusppn'] == 'Y') {
                $totalppn += $val['valueppn'];
                $totalppnSupp += $val['valueppnSupp'];
            }
        }

        if ($totalppn != 0)
            $tax = 'Y';

        $grandtotals = $totals + $totalppn;
        $grandtotalsSupp = $totalsSupp + $totalppnSupp;

        $trano = $jsonEtc[0]['trano'];


        $urut = 1;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        if ($sales) {
            $params = array(
                "workflowType" => "PO",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_RESUBMIT'],
                "items" => $items,
                "itemID" => $trano,
                "prjKode" => $jsonData['prj_kode'],
                "generic" => false,
                "revisi" => false,
                "returnException" => false,
                "comment" => $comment
            );
            $tipe = 'S';
        } else {
            $params = array(
                "workflowType" => "POO",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_RESUBMIT'],
                "items" => $items,
                "itemID" => $trano,
                "prjKode" => $jsonData['prj_kode'],
                "generic" => false,
                "revisi" => false,
                "returnException" => false,
                "comment" => $comment
            );
            $tipe = 'O';
        }

        $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        if ($jsonEtc[0]['tgldeliesti'] != '')
            $tgldeliesti = date('Y-m-d', strtotime($jsonEtc[0]['tgldeliesti']));
        else
            $tgldeliesti = $jsonEtc[0]['tgldeliesti'];

        //insert to Log....
        $log['po-detail-before'] = array();
        $fetch = $this->purchase->fetchAll("trano = '$trano'");
        if ($fetch) {
            $fetch = $fetch->toArray();
            $log['po-detail-before'] = $fetch;
        }
        $log['po-header-before'] = array();
        $result = $this->purchaseH->fetchRow("trano = '$trano'");
        if ($result) {
            $result = $result->toArray();
            $log['po-header-before'] = $result;
        }
        //done
        $this->purchase->delete("trano = '$trano'");

        $log2['po-detail-after'] = array();
        foreach ($jsonData as $key => $val) {
            $total = $val['qty'] * $val['price'];
            $totalSupp = $val['qtySupp'] * $val['priceSupp'];

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "pr_no" => $val['pr_number'],
                "tglpr" => $val['tgl_pr'],
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "qtyspl" => $val['qtySupp'],
                "harga" => $val['price'],
                "hargaspl" => $val['priceSupp'],
                "statusppn" => $tax,
                "ppn" => $val['valueppn'],
                "ppnspl" => $val['valueppnSupp'],
                "total" => $total,
                "totalspl" => $totalSupp,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $val['currency'],
                "myob" => $val['net_act'],
                "cfs_kode" => $val['net_act']
            );
            $urut++;

            $workid = $val['workid'];
            $kode_brg = $val['kode_brg'];
            $harga = $val['price'];


            $this->purchase->insert($arrayInsert);
//            $this->purchase->update($arrayInsert, "trano = '$trano' AND workid = '$workid'  AND kode_brg = '$kode_brg'");
//            $log2['po-detail-after'][] = $this->purchase->fetchRow("trano = '$trano'")->toArray();
        }
        
        $log2['po-detail-after'] = $this->purchase->fetchAll("trano = '$trano'")->toArray();
//        $result = $this->purchaseH->fetchRow("trano = '$trano'");
//        if ($result) {
//            $result = $result->toArray();
//            $log['po-header-before'] = $result;
//        }

        $arrayInsert = array(
            "revisi" => $jsonEtc[0]['rev'],
            "statusppn" => $tax,
            "jumlah" => $totals,
            "jumlahspl" => $totalsSupp,
            "ppn" => $totalppn,
            "ppnspl" => $totalppnSupp,
            "total" => $grandtotals,
            "totalspl" => $grandtotalsSupp,
//                "val_kode" => $jsonData[0]['val_kode'],
            "tgldeliesti" => $tgldeliesti,
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "paymentterm" => $jsonEtc[0]['payterm'],
            "top" => $jsonEtc[0]['top'],
            "deliverytext" => $jsonEtc[0]['tujuan'],
            "ket" => $jsonEtc[0]['ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s')
        );

//        $result = $this->purchaseH->update($arrayInsert, "trano = '$trano'");
        //$result = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $log2['po-header-after']= array();
        $this->purchaseH->update($arrayInsert, "trano = '$trano'");
        $log2['po-header-after'] = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        
        $properties = str_replace("\\", "", $this->getRequest()->getParam('etc'));

        $etc = preg_replace("~[\r\nØ]~", "", $properties);        
        $dataJson = preg_replace("~[\r\nØ]~", "", $this->json);
      
        $jsonData = Zend_Json::decode($dataJson);
        $jsonEtc = Zend_Json::decode($etc);
                  
        $jsonJurnal = $this->getRequest()->getParam('jurnal');
        $filedata = Zend_Json::decode($this->getRequest()->getParam('file'));

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('RPI');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'RPI01-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

         $params = array(
            "workflowType" => "RPI",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);
        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $gtotal = 0;
        $totalPPN = 0;

        $supplier2 = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);

        $totalPPH = 0;
        $totalGrossup = 0;
        $totalDeduction = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['harga'];
            $total = $val['qty'] * $harga;
            $arrayInsert = array();
            $gtotal += $total;
            $totalPPN += $val['valueppn'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "cus_kode" => $jsonEtc[0]['cus_kode'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "po_no" => $val['po_no'],
                "pr_no" => $val['pr_no'],
                "ppn" => $val['valueppn'],
                "qty" => $val['qty'],
                "harga" => $harga,
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "rateidr" => $jsonEtc[0]['rate_idr']
            );

            $deduct = floatval(str_replace(",", "", $val['deduction']));
            if ($deduct > 0) {
                $totalDeduction += $deduct;
                $arrayInsert['total_deduction'] = $deduct;
            }

            if ($val['holding_tax_status'] == 'Y') {
                $totalPPH += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['totalwht'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['wht'] = floatval(str_replace(",", "", $val['holding_tax']));
            }

            if ($val['grossup_status'] == 'Y') {
                $totalGrossup += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['total_grossup'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['grossup'] = floatval(str_replace(",", "", $val['holding_tax']));
            }

            $this->rpi->insert($arrayInsert);
            $activityDetail['procurement_rpid'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;

            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];

            if ($statusppn == 'Y') {
                $coa_ppn = '1-4400';
            } else {
                $coa_ppn = '';
            }

            if ($statustax == 'Y') {
                $coa_tax = '2-2100';
            } else {
                $coa_tax = '';
            }

            $total_bayar = str_replace(",", "", $val['total_value']);
            $ht_value = str_replace(",", "", $val['holding_tax_val']);
            $grossup = $val['grossup_status'];

            $arrayBPV[] = array(
                "trano" => '',
                "tgl" => '',
                "item_type" => 'RPI',
                "total_value" => floatval(str_replace(",", "", $val['value'])),
                "total_bayar" => floatval(str_replace(",", "", $val['value'])),
                "statusppn" => $val['statusppn'],
                "valueppn" => floatval(str_replace(",", "", $val['valueppn'])),
                "coa_ppn" => $coa_ppn,
                "grossup_status" => $val['grossup_status'],
                "holding_tax_status" => $val['holding_tax_status'],
                "holding_tax" => floatval(str_replace(",", "", $val['holding_tax'])),
                "holding_tax_val" => floatval(str_replace(",", "", $val['holding_tax_val'])),
                "holding_tax_text" => $val['holding_tax_text'],
                "coa_holding_tax" => $coa_tax,
                "deduction" => floatval(str_replace(",", "", $val['deduction'])),
                "total" => $total_bayar,
                "valuta" => $val['val_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $trano,
                "coa_kode" => $val['coa_kode'],
                "ketin" => $val['nama_brg'],
                "requester" => $supplier2['sup_nama'],
                "uid" => $this->session->userName,
                "statuspulsa" => '',
                "trano_ppn" => '',
                "ppn_ref_number" => $val['ppn_ref_number'],
                "status_bpv_ppn" => '',
                "productid" => $val['kode_brg'],
                "type" => $jsonEtc[0]['voc_type'],
                "pr_no" => $val['pr_no']
            );




            $urut++;
        }

        $radio = array();
        $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
        $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
        $radio['do-radio'] = $jsonEtc[0]['do_radio'];
        $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];
        $radio['with_materai'] = $jsonEtc[0]['with_materai'];

        $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

        $radios = Zend_Json::encode($radio);
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];

        $statusppn = 'N';
        $ppn = $totalPPN;
        if ($ppn > 0)
            $statusppn = 'Y';

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "tglinvoice" => date('Y-m-d'),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "rateidr" => $jsonEtc[0]['rate_idr'],
            "val_kode" => $jsonData[0]['val_kode'],
            "namabank" => $supplier['namabank'],
            "rekbank" => $supplier['rekbank'],
            "reknamabank" => $supplier['reknamabank'],
            "ket" => $jsonEtc[0]['rpi_ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "po_no" => $jsonEtc[0]['po_no'],
            "totalpo" => $jsonEtc[0]['totalPO'],
            "petugas" => $this->session->userName,
            "invoice_no" => $jsonEtc[0]['sup_invoice'],
            "ppn" => $ppn,
            "statusppn" => $statusppn,
            "ppnpo" => $jsonEtc[0]['ppn'],
            "total" => $gtotal,
            "gtotal" => $gtotal + ($ppn),
            "cus_kode" => $cusKode,
            "document_valid" => $radios,
            "with_materai" => $jsonEtc[0]['with_materai'],
            "materai" => $jsonEtc[0]['materai'],
            "statusbrg" => $jsonEtc[0]['statusbrg'],
        );

        if ($jsonEtc[0]['with_materai'] == 'Y') {
            $coa = $this->coa->fetchRow("coa_kode= '5-3110'");
            $coaAp = $this->coa->fetchRow("coa_kode= '2-1110'");
            $stamp = Zend_Json::decode($jsonJurnal);

            $stamp[] = array(
                "coa_kode" => "5-3110",
                "coa_nama" => $coa['coa_nama'],
                "debit" => $jsonEtc[0]['materai'],
                "credit" => 0,
                "tipe" => "AP",
                "urut" => "1",
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "trano" => $trano,
                "ref_number" => "",
                "ref_number2" => "",
                "is_minus" => false
            );

            $stamp[] = array(
                "coa_kode" => "2-1110",
                "coa_nama" => $coaAp['coa_nama'],
                "debit" => 0,
                "credit" => $jsonEtc[0]['materai'],
                "tipe" => "AP",
                "urut" => "1",
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "trano" => $trano,
                "ref_number" => "",
                "ref_number2" => "",
                "is_minus" => false
            );

            $jsonJurnal = Zend_Json::encode($stamp);
        }
        $tempJurnalAP = array(
            "trano" => $trano,
            "jurnal" => $jsonJurnal
        );

        if ($totalPPH > 0) {
            $arrayInsert['totalwht'] = $totalPPH;
        }

        if ($totalGrossup > 0) {
            $arrayInsert['total_grossup'] = $totalGrossup;
        }

        if ($totalDeduction > 0) {
            $arrayInsert['total_deduction'] = $totalDeduction;
        }

        $this->rpiH->insert($arrayInsert);
        $activityHead['procurement_rpih'][0]=$arrayInsert;

        $this->temporary_bpv->insert(
                array(
                    "trano" => $trano,
                    "data" => Zend_Json::encode($arrayBPV)
        ));
        $this->temporary_jurnal_ap->insert($tempJurnalAP);

        $activityCount=0;
        if (count($filedata) > 0) {
            foreach ($filedata as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
         $activityLog = array(
            "menu_name" => "Create RPI",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertrpibudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $sales = $this->getRequest()->getParam('sales');
             
        $properties = str_replace("\\", "", $this->getRequest()->getParam('etc'));
        
        //parse newline,\\ char
        $etc = preg_replace("~[\r\nØ]~", "", $properties);        
        $dataJson = preg_replace("~[\r\nØ]~", "", $this->json);
      
        $jsonData = Zend_Json::decode($dataJson);
        $jsonEtc = Zend_Json::decode($etc);
        
        
        $jsonJurnal = $this->getRequest()->getParam('jurnal');
        $decodejurnal = Zend_Json::decode($jsonJurnal);
        $filedata = Zend_Json::decode($this->getRequest()->getParam('file'));

//       var_dump($jsonData,$decodejurnal);die;

        $counter = new Default_Models_MasterCounter();

        if ($sales) {
            $lastTrans = $counter->getLastTrans('RPI');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'RPI01-' . $last;
            $tipe = 'S';
        } else {
            $lastTrans = $counter->getLastTrans('RPIO');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'RPI02-' . $last;
            $tipe = 'O';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

         $params = array(
            "workflowType" => "RPIO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'RPIO', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);
        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $gtotal = 0;
        $totalPPN = 0;

        $supplier2 = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $totalPPH = 0;
        $totalGrossup = 0;
        $totalDeduction = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['harga'];
            $total = $val['qty'] * $harga;
            $arrayInsert = array();
            $gtotal += $total;
            $totalPPN += $val['valueppn'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "sup_kode" => $jsonEtc[0]['sup_kode'],
                "sup_nama" => $jsonEtc[0]['sup_nama'],
                "cus_kode" => $jsonEtc[0]['cus_kode'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "po_no" => $val['po_no'],
                "pr_no" => $val['pr_no'],
                "ppn" => $val['ppn'],
                "qty" => $val['qty'],
                "harga" => $harga,
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "rateidr" => $jsonEtc[0]['rate_idr'],
                "tipe" => $tipe
            );

            $deduct = floatval(str_replace(",", "", $val['deduction']));
            if ($deduct > 0) {
                $totalDeduction += $deduct;
                $arrayInsert['total_deduction'] = $deduct;
            }

            if ($val['holding_tax_status'] == 'Y') {
                $totalPPH += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['totalwht'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['wht'] = floatval(str_replace(",", "", $val['holding_tax']));
            }

            if ($val['grossup_status'] == 'Y') {
                $totalGrossup += floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['total_grossup'] = floatval(str_replace(",", "", $val['holding_tax_val']));
                $arrayInsert['grossup'] = floatval(str_replace(",", "", $val['holding_tax']));
            }

            $this->rpi->insert($arrayInsert);
            $activityDetail['procurement_rpid'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;

            $statusppn = $val['statusppn'];
            $statustax = $val['holding_tax_status'];

            if ($statusppn == 'Y') {
                $coa_ppn = '1-4400';
            } else {
                $coa_ppn = '';
            }

            if ($statustax == 'Y') {
                $coa_tax = '2-2100';
            } else {
                $coa_tax = '';
            }

            $total_bayar = str_replace(",", "", $val['total_value']);
            $ht_value = str_replace(",", "", $val['holding_tax_val']);
            $grossup = $val['grossup_status'];

            $arrayBPV[] = array(
                "trano" => '',
                "tgl" => '',
                "item_type" => 'RPI',
                "total_value" => floatval(str_replace(",", "", $val['value'])),
                "total_bayar" => floatval(str_replace(",", "", $val['value'])),
                "statusppn" => $val['statusppn'],
                "valueppn" => floatval(str_replace(",", "", $val['valueppn'])),
                "coa_ppn" => $coa_ppn,
                "grossup_status" => $val['grossup_status'],
                "holding_tax_status" => $val['holding_tax_status'],
                "holding_tax" => floatval(str_replace(",", "", $val['holding_tax'])),
                "holding_tax_val" => floatval(str_replace(",", "", $val['holding_tax_val'])),
                "holding_tax_text" => $val['holding_tax_text'],
                "coa_holding_tax" => $coa_tax,
                "deduction" => floatval(str_replace(",", "", $val['deduction'])),
                "total" => $total_bayar,
                "valuta" => $val['val_kode'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "ref_number" => $trano,
                "coa_kode" => $val['coa_kode'],
                "ketin" => $val['nama_brg'],
                "requester" => $supplier2['sup_nama'],
                "uid" => $this->session->userName,
                "statuspulsa" => '',
                "trano_ppn" => '',
                "ppn_ref_number" => $val['ppn_ref_number'],
                "status_bpv_ppn" => '',
                "productid" => $val['kode_brg'],
                "id" => $val['id'],
                "type" => $jsonEtc[0]['voc_type'],
                "pr_no" => $val['pr_no']
            );



            $urut++;
        }
        $radio = array();
        $radio['invoice-radio'] = $jsonEtc[0]['invoice_radio'];
        $radio['vat-radio'] = $jsonEtc[0]['vat_radio'];
        $radio['do-radio'] = $jsonEtc[0]['do_radio'];
        $radio['sign-radio'] = $jsonEtc[0]['sign_radio'];
        $radio['with_materai'] = $jsonEtc[0]['with_materai'];

        $supplier = $this->trans->getSupplierDetail($jsonEtc[0]['sup_kode']);
        $bank = $supplier['reknamabank'] . "\n" . $supplier['rekbank'] . "\n" . $supplier['namabank'];

        $radios = Zend_Json::encode($radio);
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $ppn = $totalPPN;
        if ($ppn > 0)
            $statusppn = 'Y';

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "tglinvoice" => date('Y-m-d'),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "sup_kode" => $jsonEtc[0]['sup_kode'],
            "sup_nama" => $jsonEtc[0]['sup_nama'],
            "rateidr" => $jsonEtc[0]['rate_idr'],
            "val_kode" => $jsonData[0]['val_kode'],
            "namabank" => $supplier['namabank'],
            "rekbank" => $supplier['rekbank'],
            "reknamabank" => $supplier['reknamabank'],
            "ket" => $jsonEtc[0]['rpi_ket'],
            "ketin" => $jsonEtc[0]['ketin'],
            "po_no" => $jsonEtc[0]['po_no'],
            "totalpo" => $jsonEtc[0]['totalPO'],
            "petugas" => $this->session->userName,
            "invoice_no" => $jsonEtc[0]['sup_invoice'],
            "ppn" => $ppn,
            "ppnpo" => $jsonEtc[0]['ppn'],
            "total" => $gtotal,
            "gtotal" => $gtotal + ($ppn),
            "cus_kode" => $cusKode,
            "document_valid" => $radios,
            "tipe" => $tipe,
            "with_materai" => $jsonEtc[0]['with_materai'],
            "materai" => $jsonEtc[0]['materai'],
            "statusbrg" => $jsonEtc[0]['statusbrg']
        );

        if ($totalPPH > 0) {
            $arrayInsert['totalwht'] = $totalPPH;
        }
        if ($totalGrossup > 0) {
            $arrayInsert['total_grossup'] = $totalGrossup;
        }
        if ($totalDeduction > 0) {
            $arrayInsert['total_deduction'] = $totalDeduction;
        }

        if ($jsonEtc[0]['with_materai'] == 'Y') {
            $coa = $this->coa->fetchRow("coa_kode= '6-1200'");
            $coaAp = $this->coa->fetchRow("coa_kode= '2-1110'");
            $stamp = Zend_Json::decode($jsonJurnal);

            $stamp[] = array(
                "coa_kode" => "6-1200",
                "coa_nama" => $coa['coa_nama'],
                "debit" => $jsonEtc[0]['materai'],
                "credit" => 0,
                "tipe" => "AP",
                "urut" => "1",
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "trano" => $trano,
                "ref_number" => "",
                "ref_number2" => "",
                "is_minus" => false
            );

            $stamp[] = array(
                "coa_kode" => "2-1110",
                "coa_nama" => $coaAp['coa_nama'],
                "debit" => 0,
                "credit" => $jsonEtc[0]['materai'],
                "tipe" => "AP",
                "urut" => "1",
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "trano" => $trano,
                "ref_number" => "",
                "ref_number2" => "",
                "is_minus" => false
            );

            $jsonJurnal = Zend_Json::encode($stamp);
        }

        $tempJurnalAP = array(
            "trano" => $trano,
            "jurnal" => $jsonJurnal
        );

        $this->temporary_bpv->insert(
                array(
                    "trano" => $trano,
                    "data" => Zend_Json::encode($arrayBPV)
        ));

        $this->rpiH->insert($arrayInsert);
        $activityHead['procurement_rpih'][0]=$arrayInsert;
        $this->temporary_jurnal_ap->insert($tempJurnalAP);

        $activityCount=0;
        if (count($filedata) > 0) {
            foreach ($filedata as $key => $val) {
                $arrayInsertfile = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsertfile);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        if ($sales) {
            $activityLog = array(
            "menu_name" => "Create RPI Sales",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        } else {
            $activityLog = array(
            "menu_name" => "Create RPI Overhead",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        }
        

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertasfAction() {
        $this->_helper->viewRenderer->setNoRender();
         $comment = $this->_getParam("comment");

        
         $activitylog2 = new Admin_Models_Activitylog();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('ASF');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ASF01-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        
        $params = array(
            "workflowType" => "ASF",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'ASF', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }

        $ada = false;
        while (!$ada) {
            $cek = $this->asf->fetchRow("trano = '$trano'");
            if ($cek) {
                $lastTrans = $counter->getLastTrans('ASF');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ASF01-' . $last;
            } elseif (!$cek)
                $ada = true;
        }

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;
        
        //activity log
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceArf = 0;

        $temp = array();
        if ($jsonData) {
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut++;
                $this->asf->insert($arrayInsert);
            }
        }


        if ($jsonData2) {
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut2++;
                $this->asfc->insert($arrayInsert);
            }
        }

        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
            );
            $this->asfD->insert($arrayD);
              // detail
             $activityDetail['procurement_asfd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }
//         $this->asfD->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc[0]['tgl_arf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalarfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
                //"cus_kode" => $cusKode,
        );
        $this->asfH->insert($arrayInsert);
          //header
        $activityHead['procurement_asfh'][0]=$arrayInsert;

         $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        $activityLog = array(
            "menu_name" => "Create ASF",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);
        


        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertasfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $comment = $this->_getParam("comment");

        
         $activitylog2 = new Admin_Models_Activitylog();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $sales = $this->getRequest()->getParam('sales');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $counter = new Default_Models_MasterCounter();

        if ($sales) {
            $lastTrans = $counter->getLastTrans('ASF');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ASF01-' . $last;
            $tipe = 'S';
        } else {
            $lastTrans = $counter->getLastTrans('ASFO');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ASF02-' . $last;
            $tipe = 'O';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "ASFO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'ASFO', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;
        //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceArf = 0;

        $temp = array();
        if ($jsonData) {
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "tipe" => $tipe
                );
                $urut++;
                $this->asf->insert($arrayInsert);
            }
        }


        if ($jsonData2) {
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "tipe" => $tipe
                );
                $urut2++;
                $this->asfc->insert($arrayInsert);
            }
        }

        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfD->insert($arrayD);
              // detail
             $activityDetail['procurement_asfd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }
//         $this->asfD->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc[0]['tgl_arf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalarfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe
                //"cus_kode" => $cusKode,
        );
        $this->asfH->insert($arrayInsert);
        //header
        $activityHead['procurement_asfh'][0]=$arrayInsert;


        $file = $this->getRequest()->getParam('file');
        $jsonFile = Zend_Json::decode($file);
        
         $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
          $activityLog = array(
            "menu_name" => "Create ASF Overhead",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $file = $this->getRequest()->getParam('file');
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        
         $params = array(
            "workflowType" => 'ASF',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

//        $result = $this->workflow->setWorkflowTrans($trano, 'ASF', '', $this->const['DOCUMENT_RESUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $temp = array();
        if ($jsonData) {
            $log['asfdd-detail-before'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
            $this->asf->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut++;

                $this->asf->insert($arrayInsert);
            }
            $log2['asfdd-detail-after'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
        }

        if ($jsonData2) {
            $log['asfddcancel-detail-before'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
            $this->asfc->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut2++;

                $this->asfc->insert($arrayInsert);
            }
            $log2['asfddcancel-detail-after'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
        }
        $log['asfd-detail-before'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();
        $this->asfD->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
            );
            $this->asfD->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc[0]['tgl_arf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalarfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
                //"cus_kode" => $cusKode,
        );
        $log['asf-header-before'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $this->asfH->delete("trano = '$trano'");
        $this->asfH->insert($arrayInsert);
        $log2['asf-header-after'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        if (count($jsonFile) > 0) {
            $this->files->delete("trano = '$trano'");
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateasfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => 'ASFO',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'ASFO', '', $this->const['DOCUMENT_RESUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $temp = array();
        if ($jsonData) {
            $log['asfdd-detail-before'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
            $this->asf->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "tipe" => $tipe
                );
                $urut++;

                $this->asf->insert($arrayInsert);
            }
            $log2['asfdd-detail-after'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
        }

        if ($jsonData2) {
            $log['asfddcancel-detail-before'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
            $this->asfc->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "tipe" => $tipe
                );
                $urut2++;

                $this->asfc->insert($arrayInsert);
            }
            $log2['asfddcancel-detail-after'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
        }
        $log['asfd-detail-before'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();
        $this->asfD->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfD->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc[0]['tgl_arf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalarfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe

                //"cus_kode" => $cusKode,
        );
        $log['asf-header-before'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $this->asfH->delete("trano = '$trano'");
        $this->asfH->insert($arrayInsert);
        $log2['asf-header-after'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $file = $this->getRequest()->getParam('file');
        $jsonFile = Zend_Json::decode($file);
        if (count($jsonFile) > 0) {
            $this->files->delete("trano = '$trano'");
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateasfsalesAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $file = $this->getRequest()->getParam('file');
        $jsonFile = Zend_Json::decode($file);

        $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');


        $result = $this->workflow->setWorkflowTrans($trano, 'ASF', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
        $temp = array();
        if ($jsonData) {
              $log['asfdd-detail-before'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
            $this->asf->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "tipe" => $tipe
                );
                $urut++;

                $this->asf->insert($arrayInsert);
            }
              $log2['asfdd-detail-after'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
        }

        if ($jsonData2) {
             $log['asfddcancel-detail-before'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
            $this->asfc->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
//				"petugas" => $this->session->userName,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "tipe" => $tipe
                );
                $urut2++;

                $this->asfc->insert($arrayInsert);
            }
               $log2['asfddcancel-detail-after'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
        }
        
         $log['asfd-detail-before'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();
        $this->asfD->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfD->insert($arrayD);
        }
//         $this->asfD->insert($arrayD);
                $log2['asfd-detail-after'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc[0]['tgl_arf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalarfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe

                //"cus_kode" => $cusKode,
        );
         $log['asf-header-before'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $this->asfH->delete("trano = '$trano'");
        $this->asfH->insert($arrayInsert);
         $log2['asf-header-after'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
           $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        $this->files->delete("trano = '$trano'");
        $file = $this->getRequest()->getParam('file');
        $jsonFile = Zend_Json::decode($file);
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function prAction() {
        
    }

    public function poAction() {
        
    }

    public function rpiAction() {
        
    }

    public function arfAction() {
        
    }

    public function asfAction() {
        
    }

    public function afeAction() {
        
    }

    public function addasfAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addasfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addasfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addprAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addnewprAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function getboq3summarybyoneAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $workid = $this->getRequest()->getParam("workid");
        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $trano = $this->getRequest()->getParam("trano");

        if ($prjKode != '') {
            $boq3 = $this->budget->getBoq3ByOne($prjKode, $sitKode, $workid, $kodeBrg);
            $boq3['uom'] = $this->quantity->getUOMByProductID($boq3['kode_brg']);
            $boq3['nama_brg'] = str_replace("\"", "'", $boq3['nama_brg']);

            foreach ($boq3 as $key => $val) {
                $boq3[$key] = str_replace("\"\"", "", $boq3[$key]);
                $boq3[$key] = str_replace("\"", " inch", $boq3[$key]);
                $boq3[$key] = str_replace("'", " inch", $boq3[$key]);
            }

            if ($boq3['val_kode'] == 'IDR') {
                $boq3['price'] = $boq3['hargaIDR'];
                $boq3['priceavg'] = $boq3['hargaavg'];
                $boq3['totalPrice'] = $boq3['totalIDR'];
            } else {
                $boq3['price'] = $boq3['hargaUSD'];
                $boq3['priceavg'] = $boq3['hargaavg'];
                $boq3['totalPrice'] = $boq3['totalHargaUSD'];
            }

            //$newQtyPR = 0;
            //$newPriceIDRPR = 0;
            //$newPriceUSDPR = 0;
            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
            $pr2 = $this->quantity->getPrQuantity($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg'],$trano);
            if ($pr != '') {
                /*if ($trano != '') {
                    $isPO = $this->trans->isPRExecuted($trano);
                    $thisPR = $this->quantity->getPrQuantityByTrano($trano);
                    if ($isPO == '') {
                        $newQtyPR = $thisPR['qty'];
                        $newPriceIDRPR = $thisPR['totalIDR'];
                        $newPriceUSDPR = $thisPR['totalHargaUSD'];
                    }
                }*/
                $boq3['totalPR'] = $pr['qty'];// - $newQtyPR;
                if ($boq3['val_kode'] == 'IDR')
                    $boq3['totalPricePR_'] = $pr['totalIDR']-$pr2['totalIDR'];// - $newPriceIDRPR;
                else
                    $boq3['totalPricePR_'] = $pr['totalHargaUSD']-$pr2['totalHargaUSD'];// - $newPriceUSDPR;
                //$boq3['balancePR'] = $boq3['qty'] - $pr['qty'] - $newQtyPR;
            }
            else {
                $boq3['totalPR'] = 0;
                $boq3['balancePR'] = 0;
                $boq3['totalPricePR_'] = 0;
            }
            if (in_array($boq3['workid'], $this->miscWorkid)) {
                
                //$pod = $this->quantity->getPoQuantity($prjKode, $sitKode, $boq3['workid']);
                $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $boq3['workid']);
                //$asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $boq3['workid']);
                
                //Material return, cancel
                $LeftOver = $this->quantity->getLeftOverQty($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                $Cancel = $this->quantity->getCancelQty($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                $boq3['materialReturn'] = $LeftOver['qty'] + $Cancel['qty'];
                            
                if ($boq3['val_kode'] == 'IDR')
                    $boq3['materialPrice'] = $LeftOver['totalIDR'] + $Cancel['totalIDR'];// - $newPriceIDRPR;
                else
                   $boq3['materialPrice'] = $LeftOver['totalHargaUSD'] + $Cancel['totalHargaUSD'];
                
                /*if ($pod != '') {
                    $boq3['totalqtyPOD'] = $pod['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalPOD'] = $pod['totalIDR'];
                    else
                        $boq3['totalPOD'] = $pod['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyPOD'] = 0;
                    $boq3['totalPOD'] = 0;
                }*/
                if ($arf != '') {
                    $boq3['totalqtyARF'] = $arf['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalInARF'] = $arf['totalIDR'];
                    else
                        $boq3['totalInARF'] = $arf['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyARF'] = 0;
                    $boq3['totalARF'] = 0;
                }
                /*if ($asfcancel != '') {
                    $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                    else
                        $boq3['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyASFCancel'] = 0;
                    $boq3['totalASFCancel'] = 0;
                }*/

//                            $totalpoarf = ($boq3[$key]['totalPOD'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel']);
                //$boq3['totalPriceMIP'] = $boq3['totalPOD'] + $boq3['totalInARF'] - $boq3['totalASFCancel'];
                //if ($boq3['totalPricePR'] < $boq3['totalPriceMIP'])
                //    $boq3['totalPricePR'] = $boq3['totalPriceMIP'];
                $boq3['totalPricePR'] = $boq3['totalPricePR_'] +  $boq3['totalInARF'] - $boq3['materialPrice'];
            }
            else {
                ///$newPriceIDRPR = 0;
                //$newPriceUSDPR = 0;
                //$pod = $this->quantity->getPoQuantity($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                //$asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                
                //Material return, cancel
                $LeftOver = $this->quantity->getLeftOverQty($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                $Cancel = $this->quantity->getCancelQty($prjKode, $sitKode, $boq3['workid'], $boq3['kode_brg']);
                $boq3['materialReturn'] = $LeftOver['qty'] + $Cancel['qty'];

                if ($boq3['val_kode'] == 'IDR')
                    $boq3['materialPrice'] = $LeftOver['totalIDR'] + $Cancel['totalIDR'];// - $newPriceIDRPR;
                else
                   $boq3['materialPrice'] = $LeftOver['totalHargaUSD'] + $Cancel['totalHargaUSD'];

                /*if ($pod != '') {
                    $boq3['totalqtyPOD'] = $pod['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalPOD'] = $pod['totalIDR'];
                    else
                        $boq3['totalPOD'] = $pod['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyPOD'] = 0;
                    $boq3['totalPOD'] = 0;
                }*/
                if ($arf != '') {
                    $boq3['totalqtyARF'] = $arf['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalInARF'] = $arf['totalIDR'];
                    else
                        $boq3['totalInARF'] = $arf['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyARF'] = 0;
                    $boq3['totalARF'] = 0;
                }
                /*if ($asfcancel != '') {
                    $boq3['totalqtyASFCancel'] = $asfcancel['qty'];
                    if ($boq3['val_kode'] == 'IDR')
                        $boq3['totalASFCancel'] = $asfcancel['totalIDR'];
                    else
                        $boq3['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                }
                else {
                    $boq3['totalqtyASFCancel'] = 0;
                    $boq3['totalASFCancel'] = 0;
                }*/
                //$boq3['totalPriceMIP'] = $boq3['totalPOD'] + $boq3['totalInARF'] - $boq3['totalASFCancel'];
                /*if ($boq3['totalPriceMIP'] > 0 && $boq3[$key]['totalPrice'] > 0) {
//                                $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR'] + $boq3[$key]['totalPriceMIP'];
                    $mipQty = $boq3['qty'] * (1 - (($boq3['totalPrice'] - $boq3['totalPriceMIP']) / $boq3['totalPrice']));
                    if ($boq3['totalPR'] < $mipQty) {
                        $boq3['totalPR'] = $mipQty;
                        if ($boq3['totalPricePR'] < $boq3['totalPriceMIP'])
                            $boq3['totalPricePR'] = $boq3['totalPriceMIP'];
                    }
                }*/
                
                $boq3['totalPR'] = $boq3['totalPR'] + $boq3['totalqtyARF']-$boq3['materialReturn'];   
                $boq3['totalPricePR'] = $boq3['totalPricePR_'] +  $boq3['totalInARF'] - $boq3['materialPrice'];
            }
        }

        $return['posts'] = $boq3;
        $return['count'] = 1;
        $json = Zend_Json::encode($return);
//result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getbarangdetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $workid = $this->getRequest()->getParam("workid");
        $brgKode = $this->getRequest()->getParam("kode_brg");
        $trano = $this->getRequest()->getParam("trano");
        $prNo = $this->getRequest()->getParam("pr_no");

        $type = $this->getRequest()->getParam("type");
        $result = array();
        if ($type != '') {
            switch ($type) {
                case 'PR' :
                    $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workid, $brgKode,$prNo);
                    if ($pr == '') {
                        $pr['qty'] = 0;
                        $pr['totalIDR'] = 0;
                        $pr['totalUSD'] = 0;
                    }
                    $pr2 = $this->quantity->getPrQuantityByTrano($trano);
                    if ($pr2 == '') {
                        $pr2['qty'] = 0;
                        $pr2['totalIDR'] = 0;
                        $pr2['totalUSD'] = 0;
                    }
                    $selisihPr['qty'] = $pr['qty'] - $pr2['qty'];
                    $selisihPr['totalIDR'] = $pr['totalIDR'] - $pr2['totalIDR'];
                    $selisihPr['totalUSD'] = $pr['totalUSD'] - $pr2['totalUSD'];
                    
                    $po = $this->quantity->getPoQuantityByPrno($prNo, $prjKode, $sitKode, $workid, $brgKode);
                    if ($po == '') {
                        $po['qty'] = 0;
                        $po['totalIDR'] = 0;
                        $po['totalHargaUSD'] = 0;
                    }
                    $return = array(
                        'success' => true,
                        'type' => 'PR',
                        'qty' => $pr['qty'],
                        'totalIDR' => $pr['totalIDR'],
                        'totalUSD' => $pr['totalHargaUSD'],
                        'qtyPO' => $po['qty'],
                        'totalPOIDR' => $po['totalIDR'],
                        'totalPOUSD' => $po['totalHargaUSD'],
                        'qtySelisih' => $selisihPr['qty'],
                        'totalSelisihIDR' => $selisihPr['totalIDR'],
                        'totalSelisihUSD' => $selisihPr['totalUSD'],
                    );
                    break;
                case 'ARF' :
                    $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid, $brgKode);
                    if ($arf == '') {
                        $arf['qty'] = 0;
                        $arf['totalIDR'] = 0;
                        $arf['totalUSD'] = 0;
                    }
                    $return = array('success' => true, 'type' => 'ARF', 'qty' => $arf['qty'], 'totalIDR' => number_format($arf['totalIDR']), 'totalUSD' => number_format($arf['totalHargaUSD']));
                    break;
                case 'RPI' :
                    if ($trano != '') {
                        $rpi = $this->quantity->getPoRpiQuantity($trano, $prjKode, $sitKode, $workid, $brgKode, $prNo);
                        if ($rpi == '') {
                            $rpi['qty'] = 0;
                            $rpi['totalIDR'] = 0;
                            $rpi['totalUSD'] = 0;
                        }
                        $return = array('success' => true, 'type' => 'RPI-Po_no', 'qty' => $rpi['qty'], 'totalIDR' => $rpi['totalIDR'], 'totalUSD' => $rpi['totalHargaUSD']);
                    } else {
                        $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode, $workid, $brgKode);
                        if ($rpi == '') {
                            $rpi['qty'] = 0;
                            $rpi['totalIDR'] = 0;
                            $rpi['totalUSD'] = 0;
                        }
                        $return = array('success' => true, 'type' => 'RPI', 'qty' => $rpi['qty'], 'totalIDR' => number_format($rpi['totalIDR']), 'totalUSD' => number_format($rpi['totalHargaUSD']));
                    }
                    break;
            }
        } else {
            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workid, $brgKode);
            if ($pr != '')
                $result[] = array('type' => 'PR', 'qty' => $pr['qty'], 'totalIDR' => number_format($pr['totalIDR']), 'totalUSD' => number_format($pr['totalHargaUSD']));
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $brgKode);
            if ($po != '')
                $result[] = array('type' => 'PO', 'qty' => $po['qty'], 'totalIDR' => number_format($po['totalIDR']), 'totalUSD' => number_format($po['totalHargaUSD']));

            $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode, $workid, $brgKode);
            if ($rpi != '')
                $result[] = array('type' => 'RPI', 'qty' => $rpi['qty'], 'totalIDR' => number_format($rpi['totalIDR']), 'totalUSD' => number_format($rpi['totalHargaUSD']));

            $mdi = $this->quantity->getMdiQuantity($prjKode, $sitKode, $workid, $brgKode);
            if ($mdi != '')
                $result[] = array('type' => 'DO Request', 'qty' => $mdi['qty'], 'totalIDR' => '0', 'totalUSD' => '0');

            $mdo = $this->quantity->getMdoQuantity($prjKode, $sitKode, $workid, $brgKode);
            if ($mdo != '')
                $result[] = array('type' => 'DO', 'qty' => $mdo['qty'], 'totalIDR' => '0', 'totalUSD' => '0');
            $return['posts'] = $result;
            $return['count'] = count($result);
        }
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getbudgetsummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");

        $trano = $this->getRequest()->getParam("trano");

        $current = 0;

        if ($prjKode != '') {
            $budget = $this->budget->getBudgetOverhead($prjKode, $sitKode);
            $i = 1;
            $limit = count($budget);

            $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
            $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;


            $result = array();
            foreach ($budget as $key => $val) {
                $budget[$key]['id'] = $i;

                if ($budget[$key]['val_kode'] == 'IDR') {

                    $budget[$key]['totalPrice'] = $budget[$key]['totalIDR'];
                } else {

                    $budget[$key]['totalPrice'] = $budget[$key]['totalHargaUSD'];
                }

                if ($current < ($limit + $offset) && $current >= $offset) {
                    $pr = $this->quantity->getPrOverheadQuantity($prjKode, $sitKode, $budget[$key]['budgetid']);
                    
                    //Material return, cancel
                    $LeftOver = $this->quantity->getLeftOverQty($prjKode, $sitKode, $budget[$key]['budgetid']);
                    $Cancel = $this->quantity->getCancelQty($prjKode, $sitKode, $budget[$key]['budgetid']);
                    $boq3[$key]['materialReturn'] = $LeftOver['qty'] + $Cancel['qty'];
                            
                    if ($boq3[$key]['val_kode'] == 'IDR')
                        $boq3[$key]['materialPrice'] = $LeftOver['totalIDR'] + $Cancel['totalIDR'];// - $newPriceIDRPR;
                    else
                        $boq3[$key]['materialPrice'] = $LeftOver['totalHargaUSD'] + $Cancel['totalIDR'];
                    
                    if ($pr != '') {

                        $budget[$key]['totalPRraw'] = $pr['qty'];

                        $budget[$key]['totalPricePRraw'] = $pr['total'];
                    } else {
                        $budget[$key]['totalPRraw'] = 0;

                        $budget[$key]['totalPricePRraw'] = 0;
                    }

                    $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $budget[$key]['budgetid']);
                    if ($arf != '') {

                        $budget[$key]['totalARF'] = $arf['qty'];

                        if ($budget[$key]['val_kode'] == 'IDR') {

                            $budget[$key]['totalPriceARF'] = $arf['totalIDR'];
                        } else {

                            $budget[$key]['totalPriceARF'] = $arf['totalHargaUSD'];
                        }
                    } else {
                        $budget[$key]['totalARF'] = 0;

                        $budget[$key]['totalPriceARF'] = 0;
                    }
                    $totalQtyPRARF = $budget[$key]['totalPRraw'] + $budget[$key]['totalARF'] - $boq3[$key]['materialReturn'];
                    $totalPricePRARF = $budget[$key]['totalPricePRraw'] + $budget[$key]['totalPriceARF'] - $boq3[$key]['materialPrice'];

                    $budget[$key]['totalPR'] = $totalQtyPRARF;
                    $budget[$key]['totalPricePR'] = $totalPricePRARF;

                    $result[] = $budget[$key];
                }

                $current++;
                $i++;
            }
        }
        $return['posts'] = $result;
        $return['count'] = count($budget);
        $json = Zend_Json::encode($return);
//result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getboq3nonprojectAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $budgetId = $this->getRequest()->getParam("workid");
        $budgetName = $this->getRequest()->getParam("budgetname");
        $trano = $this->getRequest()->getParam("trano");
        
        $boq3 = $this->budget->getBudgetNonProject($prjKode, $sitKode,$budgetId,$budgetName);
        $this->budget->createOnGoingAFESaving($prjKode);

        $current = 0;
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
                
        $result = array();

        foreach ($boq3 as $key => $val) {

            $boq3[$key]['id'] = $val['id'];        
            $boq3[$key]['totalPrice'] = $val['val_kode']=='USD' ? $val['totalHargaUSD'] : $val['totalIDR'];
            $rate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $boq3[$key]['rateidr'] = $rate['rateidr'];
            
            // Get All Requests of The Item
            $requests=$this->cost->totalRequestsV2($prjKode, $sitKode,$val['budgetid'],'',$trano);
            $boq3[$key]['totalRequests']= $requests['amount']==null ? 0 : $requests['amount'];
                       
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['budgetid'],null,$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            if ($current < ($limit + $offset) && $current >= $offset) {
              $result[] = $boq3[$key];
            }
            
            $current++;
            
        }
        
        $return['posts'] = $result;
        $return['count'] = count($boq3);
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function getboq3forprojectAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        $kodeBrg = $this->getRequest()->getParam("kode_brg");
        $workId = $this->getRequest()->getParam("workid");
        $trano = $this->getRequest()->getParam("trano");
        
        $boq3 = $this->budget->getBOQ3CurrentPerItemNonPeacemeal($prjKode, $sitKode,$valKode,$workId,$kodeBrg);
        
        $this->budget->createOnGoingAFESaving($prjKode);


        $current = 0;
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
                
        $result = array();

        foreach ($boq3 as $key => $val) {
            
            foreach ($val as $key2 => $val2) {
                if ($val2 == "\"\"")
                    $boq3[$key][$key2] = '';
                if (strpos($val2, "\"") !== false)
                    $boq3[$key][$key2] = str_replace("\"", " inch", $boq3[$key][$key2]);
                if (strpos($val2, "'") !== false)
                    $boq3[$key][$key2] = str_replace("'", " inch", $boq3[$key][$key2]);
            }

            $boq3[$key]['id'] = $val['id'];
            $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
            $boq3[$key]['nama_brg'] = str_replace("\"", "'", $this->quantity->getProductName($boq3[$key]['kode_brg']));
            $boq3[$key]['price'] = $val['harga'];
            $boq3[$key]['totalPrice'] = $val['val_kode']=='USD' ? $val['totalUSD'] : $val['totalIDR'];
            $rate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $boq3[$key]['rateidr'] = $rate['rateidr'];
            
            // Get All Requests of The Item
            $requests=$this->cost->totalRequestsV2($prjKode, $sitKode,$val['workid'], $val['kode_brg'],$trano);
            $boq3[$key]['totalRequests']= $requests['amount']==null ? 0 : $requests['amount'];
            
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg'],$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            if ($current < ($limit + $offset) && $current >= $offset) {
              $result[] = $boq3[$key];
            }
            
            $current++;

        }

        $return['posts'] = $result;
        $return['count'] = count($boq3);
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function getboq3forarfAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        
        $boq3 = $this->budget->getBOQ3CurrentPerItem($prjKode, $sitKode,$valKode);
        $this->budget->createOnGoingAFESaving($prjKode);

        $i = 1;
        $current = 0;
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
                
        $result = array();

        foreach ($boq3 as $key => $val) {
            
            foreach ($val as $key2 => $val2) {
                if ($val2 == "\"\"")
                    $boq3[$key][$key2] = '';
                if (strpos($val2, "\"") !== false)
                    $boq3[$key][$key2] = str_replace("\"", " inch", $boq3[$key][$key2]);
                if (strpos($val2, "'") !== false)
                    $boq3[$key][$key2] = str_replace("'", " inch", $boq3[$key][$key2]);
            }

            $boq3[$key]['id'] = $i;
            $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
            $boq3[$key]['nama_brg'] = str_replace("\"", "'", $boq3[$key]['nama_brg']);
            $boq3[$key]['price'] = $val['harga'];
            $boq3[$key]['totalPrice'] = $val['val_kode']=='USD' ? $val['totalUSD'] : $val['totalIDR'];
            
            // Get All Related OCA
            $oca = $this->cost->totalOCA($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $boq3[$key]['totalOCA']= $val['val_kode']=='USD' ? $oca['totalUSD'] : $oca['totalIDR'];
            
            // Get All Requests of The Item PR + ARF - (Material Return + ASF Cancel)
            $requests=$this->cost->totalRequests($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $boq3[$key]['totalRequests']= $val['val_kode']=='USD' ? $requests['totalUSD'] : $requests['totalIDR'];
            
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg'],$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            if ($current < ($limit + $offset) && $current >= $offset) {
              $result[] = $boq3[$key];
            }
            
            $current++;
            $i++; 
        }

        $return['posts'] = $result;
        $return['count'] = count($boq3);
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
        
    }

    public function getboq3summaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $sumPR = $this->getRequest()->getParam("sumpr");
        $trano = $this->getRequest()->getParam("trano");

        $isPR = $this->getRequest()->getParam("pr");
        $current = 0;

        if ($prjKode != '') {
            $boq3 = $this->budget->getBoq3PR('all-current', $prjKode, $sitKode);
            $i = 1;
            $limit = count($boq3);
            if ($isPR) {
                $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
                $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
            } else
                $offset = 0;

            $result = array();
            foreach ($boq3 as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $boq3[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $boq3[$key][$key2] = str_replace("\"", " inch", $boq3[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $boq3[$key][$key2] = str_replace("'", " inch", $boq3[$key][$key2]);
                }

                $boq3[$key]['id'] = $i;
                $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
                $boq3[$key]['nama_brg'] = str_replace("\"", "'", $boq3[$key]['nama_brg']);
                if ($boq3[$key]['val_kode'] == 'IDR') {
                    $boq3[$key]['price'] = $boq3[$key]['hargaIDR'];
                    $boq3[$key]['priceavg'] = $boq3[$key]['hargaavg'];
                    $boq3[$key]['totalPrice'] = $boq3[$key]['totalIDR'];
                } else {
                    $boq3[$key]['price'] = $boq3[$key]['hargaUSD'];
                    $boq3[$key]['priceavg'] = $boq3[$key]['hargaavg'];
                    $boq3[$key]['totalPrice'] = $boq3[$key]['totalHargaUSD'];
                }
                if ($current < ($limit + $offset) && $current >= $offset) {
                    if ($sumPR) {
                        if (in_array($boq3[$key]['workid'], $this->miscWorkid)) {

                            //$pod = $this->quantity->getPoQuantity($prjKode, $sitKode, $boq3[$key]['workid']);
                            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $boq3[$key]['workid']);
                            //$asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $boq3[$key]['workid']);
                            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $boq3[$key]['workid']);
                            
                            //Material return, cancel
                            $LeftOver = $this->quantity->getLeftOverQty($prjKode, $sitKode, $boq3[$key]['workid']);
                            $Cancel = $this->quantity->getCancelQty($prjKode, $sitKode, $boq3[$key]['workid']);
                            $boq3[$key]['materialReturn'] = $LeftOver['qty'] + $Cancel['qty'];
                            
                            if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['materialPrice'] = $LeftOver['totalIDR'] + $Cancel['totalIDR'];// - $newPriceIDRPR;
                            else
                                    $boq3[$key]['materialPrice'] = $LeftOver['totalHargaUSD'] + $Cancel['totalHargaUSD'];
                            
                            if ($pr != '') {
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPricePR_'] = $pr['totalIDR'];
                                else
                                    $boq3[$key]['totalPricePR_'] = $pr['totalHargaUSD'];
                            } else
                                $boq3[$key]['totalPricePR_'] = 0;
                            /*if ($pod != '') {
                                $boq3[$key]['totalqtyPOD'] = $pod['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPOD'] = $pod['totalIDR'];
                                else
                                    $boq3[$key]['totalPOD'] = $pod['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyPOD'] = 0;
                                $boq3[$key]['totalPOD'] = 0;
                            }*/
                            if ($arf != '') {
                                $boq3[$key]['totalqtyARF'] = $arf['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalInARF'] = $arf['totalIDR'];
                                else
                                    $boq3[$key]['totalInARF'] = $arf['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyARF'] = 0;
                                $boq3[$key]['totalARF'] = 0;
                            }
                            /*if ($asfcancel != '') {
                                $boq3[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                                else
                                    $boq3[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyASFCancel'] = 0;
                                $boq3[$key]['totalASFCancel'] = 0;
                            }*/

//                            $totalpoarf = ($boq3[$key]['totalPOD'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel']);
                            //$boq3[$key]['totalPriceMIP'] = $boq3[$key]['totalPOD'] + $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel'];
                            //if ($boq3[$key]['totalPricePR'] < $boq3[$key]['totalPriceMIP'])
                            //    $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPriceMIP'];
                            $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR_'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['materialPrice'] ;
                        }
                        else  {
                            //$newQtyPR = 0;
                            //$newPriceIDRPR = 0;
                            //$newPriceUSDPR = 0;
                            //$pod = $this->quantity->getPoQuantity($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            //$asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            
                            //Material return, cancel
                            $LeftOver = $this->quantity->getLeftOverQty($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            $Cancel = $this->quantity->getCancelQty($prjKode, $sitKode, $boq3[$key]['workid'], $boq3[$key]['kode_brg']);
                            $boq3[$key]['materialReturn'] = $LeftOver['qty'] + $Cancel['qty'];
                            
                            if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['materialPrice'] = $LeftOver['totalIDR'] + $Cancel['totalIDR'];// - $newPriceIDRPR;
                            else
                                    $boq3[$key]['materialPrice'] = $LeftOver['totalHargaUSD'] + $Cancel['totalHargaUSD'];
                            
                            if ($pr != '') {
                                /*if ($trano != '') {
                                    $newPR = $this->quantity->getPrQuantityByTrano($trano);
                                    $isPO = $this->trans->isPRExecuted($trano);
                                    if ($isPO == '') {
                                        $newQtyPR = $newPR['qty'];
                                        $newPriceIDRPR = $newPR['totalIDR'];
                                        $newPriceUSDPR = $newPR['totalHargaUSD'];
                                    }
                                }*/
                                $boq3[$key]['totalPR'] = $pr['qty'];// - $newQtyPR;
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPricePR_'] = $pr['totalIDR'];// - $newPriceIDRPR;
                                else
                                    $boq3[$key]['totalPricePR_'] = $pr['totalHargaUSD'];// - $newPriceUSDPR;
                                //$boq3[$key]['balancePR'] = $boq3[$key]['qty'] - $boq3[$key]['totalPR'];
                            }
                            else {
                                $boq3[$key]['totalPR'] = 0;
                                $boq3[$key]['balancePR'] = 0;
                                $boq3[$key]['totalPricePR_'] = 0;
                            }
                            /*if ($pod != '') {
                                $boq3[$key]['totalqtyPOD'] = $pod['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalPOD'] = $pod['totalIDR'];
                                else
                                    $boq3[$key]['totalPOD'] = $pod['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyPOD'] = 0;
                                $boq3[$key]['totalPOD'] = 0;
                            }*/
                            if ($arf != '') {
                                $boq3[$key]['totalqtyARF'] = $arf['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalInARF'] = $arf['totalIDR'];
                                else
                                    $boq3[$key]['totalInARF'] = $arf['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyARF'] = 0;
                                $boq3[$key]['totalARF'] = 0;
                            }
                            /*if ($asfcancel != '') {
                                $boq3[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                                if ($boq3[$key]['val_kode'] == 'IDR')
                                    $boq3[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                                else
                                    $boq3[$key]['totalASFCancel'] = $asfcancel['totalHargaUSD'];
                            }
                            else {
                                $boq3[$key]['totalqtyASFCancel'] = 0;
                                $boq3[$key]['totalASFCancel'] = 0;
                            }*/
                            /*$boq3[$key]['totalPriceMIP'] = $boq3[$key]['totalPOD'] + $boq3[$key]['totalInARF'] - $boq3[$key]['totalASFCancel'];
                            if ($boq3[$key]['totalPriceMIP'] > 0 && $boq3[$key]['totalPrice'] > 0) {
//                                $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR'] + $boq3[$key]['totalPriceMIP'];
                                $mipQty = $boq3[$key]['qty'] * (1 - (($boq3[$key]['totalPrice'] - $boq3[$key]['totalPriceMIP']) / $boq3[$key]['totalPrice']));
                                if ($boq3[$key]['totalPR'] < $mipQty) {
                                    $boq3[$key]['totalPR'] = $mipQty;
                                    if ($boq3[$key]['totalPricePR'] < $boq3[$key]['totalPriceMIP'])
                                        $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPriceMIP'];
                                }
                            }*/
                            
                            $boq3[$key]['totalPR'] = $boq3[$key]['totalPR'] + $boq3[$key]['totalqtyARF'] -$boq3[$key]['materialReturn'];   
                            $boq3[$key]['totalPricePR'] = $boq3[$key]['totalPricePR_'] +  $boq3[$key]['totalInARF'] - $boq3[$key]['materialPrice'];
                        }
                    }

                    $result[] = $boq3[$key];
                }
                $current++;
                $i++;
            }
        }
        $return['posts'] = $result;
        $return['count'] = count($boq3);
        $json = Zend_Json::encode($return);
//result encoded in JSON
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function addprbudgetAction() {
        
    }

    public function addnewprbudgetAction() {
        
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
    }

    }

    public function addprsalesAction() {
        
    }

    public function addnewprsalesAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
    }
    }

    public function addarfAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }
    
    public function addnewarfAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->ARFtrano  = $this->getRequest()->getParam("trano");

        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }
    
    public function editnewarfAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        
        $trano  = $this->getRequest()->getParam("trano");
        
        $savePath = Zend_Registry::get('uploadPath') . 'files';
        
        $posts  =array();
        $etc    =array();
        $file   =array();
        
        if (!$isCancel) {
            
            $paymentArf = $this->paymentArf->getPayment($trano);
            $totalPayment = $paymentArf == null ? 0 : $paymentArf;
            
            $arfh = $this->arfh->fetchRow("trano = '$trano'")->toArray();
            $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $file = $this->files->fetchAll("trano = '$trano'")->toArray();
                      
            foreach($arfh as $index => $value){     
                $etc[0]["prj_kode"] = $arfh["prj_kode"];
                $etc[0]["prj_nama"] = $arfh["prj_nama"];
                $etc[0]["sit_kode"] = $arfh["sit_kode"];
                $etc[0]["sit_nama"] = $arfh["sit_nama"];
                $etc[0]["requester2"] = $arfh["request"];
                $etc[0]["penerima"] = $arfh["penerima"];
                $etc[0]["bank"] = $arfh["namabank"];
                $etc[0]["bankaccountname"] = $arfh["reknamabank"];
                $etc[0]["bankaccountno"] = $arfh["rekbank"];
                $etc[0]["valuta"] = $arfh["val_kode"];
                $etc[0]["pic_kode"] = $arfh["orangpic"];
                $etc[0]["pic_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangpic"]))->getName();;
                $etc[0]["mgr_kode"]= $arfh["request"];
                $etc[0]["mgr_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["request"]))->getName();;
                $etc[0]["finance"]= $arfh["orangfinance"];
                $etc[0]["financeName"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangfinance"]))->getName();
                $etc[0]["budgettype"]= $arfh["budgettype"];
                $etc[0]["ketin"]= $arfh["ketin"];
                $etc[0]["trano"]= $trano;
            }
            
            foreach($arfd as $index => $value){
                $posts[$index]["id"] = $value["id"];
                $boq3 = $this->budget->getBOQ3CurrentPerItemNonPeacemeal($value["prj_kode"], $value["sit_kode"],$value["val_kode"],$value["workid"],$value["kode_brg"]);
                $posts[$index]["boq_id"] = $boq3[0]["id"];
                $posts[$index]["prj_kode"] = $value["prj_kode"];
                $posts[$index]["prj_nama"] = $value["prj_nama"];
                $posts[$index]["sit_kode"] = $value["sit_kode"];
                $posts[$index]["sit_nama"] = $value["sit_nama"];
                $posts[$index]["workid"] = $value["workid"];
                $posts[$index]["workname"] = $value["workname"];
                $posts[$index]["kode_brg"] = $value["kode_brg"];
                $posts[$index]["nama_brg"] = str_replace("\"", "'", $value["nama_brg"]);
                $posts[$index]["qty"] = $totalPayment > 0 ? $value["qty"] : 0;
                $posts[$index]["harga"] = $value["harga"];
                $posts[$index]["ket"] = $value["ket"];
                $posts[$index]["val_kode"] = $value["val_kode"];
                $posts[$index]["net_act"]= $value["cfs_kode"];
                $posts[$index]["uom"]= $this->quantity->getUOMByProductID($value["kode_brg"]);
                $posts[$index]["requester"]= $value["requester"];
                $posts[$index]["requesterName"]= QDC_User_Ldap::factory(array("uid" => $value["requester"]))->getName();
                $posts[$index]["approve"] = $value["approve"];
                
            }
            
            foreach($file as $index => $value){
                $files[$index]["id"]=$value["id"];
                $files[$index]["filename"]=$value["filename"];
                $files[$index]["savename"]=$value["savename"];
                $files[$index]["status"]='edit';
                $files[$index]["path"]=$savePath . "/" . $value["savename"];
            }
            Zend_Loader::loadClass('Zend_Json');
            $files = Zend_Json::encode($files);
            $posts = Zend_Json::encode($posts);
            $etc = Zend_Json::encode($etc);
            
        }else{
            
            $posts  =   $this->getRequest()->getParam("posts");
            $etc    =   $this->getRequest()->getParam("etc");
            $files   =   $this->getRequest()->getParam("file");
            
            $etc2 = Zend_Json::decode($etc);
            
            $totalPayment = $etc2[0]["payment"] ; 
            $trano = $etc2[0]["trano"] ; 
        }
        
        $this->view->payment = $totalPayment;
        $this->view->json = $posts;
        $this->view->etc = $etc;
        $this->view->file = $files;
        $this->view->trano = $trano;
       
        
    }
    
    public function editnewarfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        
        $trano  = $this->getRequest()->getParam("trano");
        
        $savePath = Zend_Registry::get('uploadPath') . 'files';
        
        $posts  =array();
        $etc    =array();
        $file   =array();
               
        if (!$isCancel) {
            $arfh = $this->arfh->fetchRow("trano = '$trano'")->toArray();
            $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $file = $this->files->fetchAll("trano = '$trano'")->toArray();
            
            $paymentArf = $this->paymentArf->getPayment($trano);
            $totalPayment = $paymentArf == null ? 0 : $paymentArf;
            
            foreach($arfh as $index => $value){     
                $etc[0]["prj_kode"] = $arfh["prj_kode"];
                $etc[0]["prj_nama"] = $arfh["prj_nama"];
                $etc[0]["sit_kode"] = $arfh["sit_kode"];
                $etc[0]["sit_nama"] = $arfh["sit_nama"];
                $etc[0]["requester2"] = $arfh["request"];
                $etc[0]["penerima"] = $arfh["penerima"];
                $etc[0]["bank"] = $arfh["namabank"];
                $etc[0]["bankaccountname"] = $arfh["reknamabank"];
                $etc[0]["bankaccountno"] = $arfh["rekbank"];
                $etc[0]["valuta"] = $arfh["val_kode"];
                $etc[0]["pic_kode"] = $arfh["orangpic"];
                $etc[0]["pic_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangpic"]))->getName();;
                $etc[0]["mgr_kode"]= $arfh["request"];
                $etc[0]["mgr_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["request"]))->getName();;
                $etc[0]["finance"]= $arfh["orangfinance"];
                $etc[0]["financeName"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangfinance"]))->getName();
                $etc[0]["budgettype"]= $arfh["budgettype"];
                $etc[0]["ketin"]= $arfh["ketin"];
                $etc[0]["trano"]= $trano;
            }
            
            foreach($arfd as $index => $value){
                $posts[$index]["id"] = $value["id"];      
                $posts[$index]["budgetid"] = $value["workid"];
                $posts[$index]["budgetname"] = $value["workname"];
                $posts[$index]["kode_brg"] = $value["kode_brg"];
                $posts[$index]["nama_brg"] = str_replace("\"", "'", $value["nama_brg"]);
                $posts[$index]["qty"] = $totalPayment > 0 ? $value["qty"] : 0;
                $posts[$index]["harga"] = $value["harga"];
                $posts[$index]["ket"] = $value["ket"];
                $posts[$index]["val_kode"] = $value["val_kode"];
                $posts[$index]["net_act"]= $value["cfs_kode"];
                $posts[$index]["uom"]= $this->quantity->getUOMByProductID($value["kode_brg"]);
                $posts[$index]["requester"]= $value["requester"];
                $posts[$index]["requesterName"]= QDC_User_Ldap::factory(array("uid" => $value["requester"]))->getName();
                $totalTransaksi += $value["qty"]*$value["harga"];
                $posts[$index]["approve"] = $value["approve"];
                
            }
            
            foreach($file as $index => $value){
                $files[$index]["id"]=$value["id"];
                $files[$index]["filename"]=$value["filename"];
                $files[$index]["savename"]=$value["savename"];
                $files[$index]["status"]='edit';
                $files[$index]["path"]=$savePath . "/" . $value["savename"];
            }
            Zend_Loader::loadClass('Zend_Json');
            $files = Zend_Json::encode($files);
            $posts = Zend_Json::encode($posts);
            $etc = Zend_Json::encode($etc);
            
        }else{
            
            $posts  =   $this->getRequest()->getParam("posts");
            $etc    =   $this->getRequest()->getParam("etc");
            $files   =   $this->getRequest()->getParam("file");
            
            $etc2 = Zend_Json::decode($etc);
            
            $totalPayment = $etc2[0]["payment"] ;
            $trano = $etc2[0]["trano"] ; 
        }
        
        $this->view->payment = $totalPayment;
        $this->view->json = $posts;
        $this->view->etc = $etc;
        $this->view->file = $files;
        $this->view->trano = $trano;
        
    }
    
    public function editnewarfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        
        $trano  = $this->getRequest()->getParam("trano");
        
        $savePath = Zend_Registry::get('uploadPath') . 'files';
        
        $posts  =array();
        $etc    =array();
        $file   =array();
         
        if (!$isCancel) {
            $arfh = $this->arfh->fetchRow("trano = '$trano'")->toArray();
            $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $file = $this->files->fetchAll("trano = '$trano'")->toArray();
            
            $paymentArf = $this->paymentArf->getPayment($trano);
            $totalPayment = $paymentArf == null ? 0 : $paymentArf;
            
            foreach($arfh as $index => $value){     
                $etc[0]["prj_kode"] = $arfh["prj_kode"];
                $etc[0]["prj_nama"] = $arfh["prj_nama"];
                $etc[0]["sit_kode"] = $arfh["sit_kode"];
                $etc[0]["sit_nama"] = $arfh["sit_nama"];
                $etc[0]["requester2"] = $arfh["request"];
                $etc[0]["penerima"] = $arfh["penerima"];
                $etc[0]["bank"] = $arfh["namabank"];
                $etc[0]["bankaccountname"] = $arfh["reknamabank"];
                $etc[0]["bankaccountno"] = $arfh["rekbank"];
                $etc[0]["valuta"] = $arfh["val_kode"];
                $etc[0]["pic_kode"] = $arfh["orangpic"];
                $etc[0]["pic_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangpic"]))->getName();
                $etc[0]["mgr_kode"]= $arfh["request"];
                $etc[0]["mgr_nama"]= QDC_User_Ldap::factory(array("uid" => $arfh["request"]))->getName();
                $etc[0]["finance"]= $arfh["orangfinance"];
                $etc[0]["financeName"]= QDC_User_Ldap::factory(array("uid" => $arfh["orangfinance"]))->getName();
                $etc[0]["budgettype"]= $arfh["budgettype"];
                $etc[0]["ketin"]= $arfh["ketin"];
                $etc[0]["trano"]= $trano;
            }
            
            foreach($arfd as $index => $value){
                $posts[$index]["id"] = $value["id"];
                $posts[$index]["budgetid"] = $value["workid"];
                $posts[$index]["budgetname"] = $value["workname"];
                $posts[$index]["kode_brg"] = $value["kode_brg"];
                $posts[$index]["nama_brg"] = str_replace("\"", "'", $value["nama_brg"]);
                $posts[$index]["qty"] = $totalPayment > 0 ? $value["qty"] : 0;
                $posts[$index]["harga"] = $value["harga"];
                $posts[$index]["ket"] = $value["ket"];
                $posts[$index]["val_kode"] = $value["val_kode"];
                $posts[$index]["net_act"]= $value["cfs_kode"];
                $posts[$index]["uom"]= $this->quantity->getUOMByProductID($value["kode_brg"]);
                $posts[$index]["requester"]= $value["requester"];
                $posts[$index]["requesterName"]= QDC_User_Ldap::factory(array("uid" => $value["requester"]))->getName();
                $posts[$index]["approve"] = $value["approve"];
                
            }
            
            foreach($file as $index => $value){
                $files[$index]["id"]=$value["id"];
                $files[$index]["filename"]=$value["filename"];
                $files[$index]["savename"]=$value["savename"];
                $files[$index]["status"]='edit';
                $files[$index]["path"]=$savePath . "/" . $value["savename"];
            }
            Zend_Loader::loadClass('Zend_Json');
            $files = Zend_Json::encode($files);
            $posts = Zend_Json::encode($posts);
            $etc = Zend_Json::encode($etc);
            
        }else{

            $posts  =   $this->getRequest()->getParam("posts");
            $etc    =   $this->getRequest()->getParam("etc");
            $files   =   $this->getRequest()->getParam("file");
            
            $etc2 = Zend_Json::decode($etc);
            
            $totalPayment = $etc2[0]["payment"] ;
            $trano = $etc2[0]["trano"] ; 
        }
        
        $this->view->payment = $totalPayment;
        $this->view->json = $posts;
        $this->view->etc = $etc;
        $this->view->file = $files;
        $this->view->trano = $trano;
        
    }
    
    public function addnewarfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->ARFtrano  = $this->getRequest()->getParam("trano");

        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function addnewarfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->ARFtrano  = $this->getRequest()->getParam("trano");

        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }
    

    public function addarfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

    public function addarfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

    public function addrpiAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->trano = $this->getRequest()->getParam("trano");
            $this->view->jurnal = $this->getRequest()->getParam("jurnal");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function addrpibudgetAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->trano = $this->getRequest()->getParam("trano");
            $this->view->jurnal = $this->getRequest()->getParam("jurnal");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function addrpisalesAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $this->view->no_po = $this->getRequest()->getParam("no_po");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->trano = $this->getRequest()->getParam("trano");
        }
    }

    public function addpoAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addpobudgetAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function addposalesAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function editprAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }


        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
//   		if ($prd)
//   		{
//   			foreach($prd as $key => $val)
//   			{
//   				$prd[$key]['id'] = $key + 1;
//   				$kodeBrg = $val['kode_brg'];
//   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
//   				if ($barang)
//   				{
//   					$prd[$key]['uom'] = $barang['sat_kode'];
//   				}
//   				if ($val['val_kode'] == 'IDR')
//   					$prd[$key]['hargaIDR'] = $val['harga'];
//   				elseif ($val['val_kode'] == 'USD')
//   					$prd[$key]['hargaUSD'] = $val['harga'];
//
//                $prd[$key]['net_act'] = $val['myob'];
//                $prd[$key]['fromBoq3'] = 1;
//   			}
//
//   			Zend_Loader::loadClass('Zend_Json');
//   			$jsonData = Zend_Json::encode($prd);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->jsonCancel = $this->getRequest()->getParam("posts");
        }
//	   		else
//	   			$this->view->json = $jsonData;
        $this->view->json = true;
        $this->view->trano = $trano;
        $this->view->tgl = $prh['tgl'];
        $this->view->revisi = $prh['revisi'];
        $this->view->budgetType = $prh['budgettype'];
        $this->view->prj_nama = $prh['prj_nama'];
        $this->view->sit_nama = $prh['sit_nama'];
        $this->view->prj_kode = $prh['prj_kode'];
        $this->view->sit_kode = $prh['sit_kode'];
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
//   		}
    }
    
        public function prrevisiAction (){
      $isCancel = $this->getRequest()->getParam("returnback");
          
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");
        
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }
        
        foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                
            $prd[$key2]['id'] = $val2['id'];
            $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
            $prd[$key2]['nama_brg'] = str_replace("\"", "'", $val2['nama_brg']);
                
            // Get Related PO
            $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);               
            $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
            // Get Related DOR Qty            
            $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
            $prd[$key2]['dorqty'] = $dor['qty'];
            $prd[$key2]['dortotal'] = $dor['total'];
            
            $prd[$key2]['qty'] = $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] > 0 || $prd[$key2]['pototal'] > 0 ? $val2['qty'] : $prd[$key2]['qty'];
                
        }
        
        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
        
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);

            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
            
        }
        
        Zend_Loader::loadClass('Zend_Json');
        
    }
    
     public function appprrevisiAction ()
     {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $lastReject=array();
        
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PR_Revisi';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
//                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$userid);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }
                            if ($val['val_kode'] == 'IDR')
                                $prd[$key]['hargaIDR'] = $val['harga'];
                            elseif ($val['val_kode'] == 'USD')
                                $prd[$key]['hargaUSD'] = $val['harga'];

                            $prd[$key]['net_act'] = $val['myob'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                         $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
//                        $this->view->uid = $uid;
                        $this->view->userID = $this->session->idUser;
//                        $this->view->userID = $userid;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
                    }
                }
                else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }
    
      public function prrevisiohAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
            $trano = $this->getRequest()->getParam("trano");
            $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $file = $this->files->fetchAll("trano = '$trano'");
        
            if ($file)
                $file = $file->toArray();
            else
                $file = array();

            foreach ($prh as $key => $val) {
                if ($val == '""')
                    $prh[$key] = '';
            }

            foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                $prd[$key2]['budgetid'] = $val2['workid'];
                $prd[$key2]['budgetname'] = $val2['workname'];
                $prd[$key2]['dep_kode'] =$val2['prj_kode'];
                $prd[$key2]['dep_nama'] = $val2['prj_nama'];
                $prd[$key2]['per_kode'] = $val2['sit_kode'];
                $prd[$key2]['per_nama'] = $val2['sit_nama'];

                // Get Related PO
                $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
                // Get Related DOR Qty            
                $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['dorqty'] = $dor['qty'];
                $prd[$key2]['dortotal'] = $dor['total'];
                
                // Get Uom
                $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
                
                $prd[$key2]['qty'] = $prd[$key2]['pototal'] > 0 || $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] >0 ? $val2['qty'] : $prd[$key2]['qty'];
            }

            if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
                $prh['revisi'] = 1;
            } else
                $prh['revisi'] = abs($prh['revisi']) + 1;
            
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);
            
            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
        }
        
        Zend_Loader::loadClass('Zend_Json');
    }
    
     public function appprrevisiohAction ()
     {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PRO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }

//	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }

            if ($sales == 'true') {
                $this->view->sales = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $potong = substr($approve, 0, 3);
                    if ($potong == 'PRF')
                        $this->view->sales = true;

                    $prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $prd[$key]['totalPrice'] = $val['jumlah'];
                            $prd[$key]['budgetid'] = $val['workid'];
                            $prd[$key]['budgetname'] = $val['workname'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
//		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
//				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
//				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }
    
    public function prrevisisalesAction (){
      $isCancel = $this->getRequest()->getParam("returnback");
        
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
            $trano = $this->getRequest()->getParam("trano");
            $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $file = $this->files->fetchAll("trano = '$trano'");
        
            if ($file)
                $file = $file->toArray();
            else
                $file = array();

            foreach ($prh as $key => $val) {
                if ($val == '""')
                    $prh[$key] = '';
            }

            foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                $prd[$key2]['budgetid'] = $val2['workid'];
                $prd[$key2]['budgetname'] = $val2['workname'];
                $prd[$key2]['dep_kode'] =$val2['prj_kode'];
                $prd[$key2]['dep_nama'] = $val2['prj_nama'];
                $prd[$key2]['per_kode'] = $val2['sit_kode'];
                $prd[$key2]['per_nama'] = $val2['sit_nama'];

                // Get Related PO
                $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
                // Get Related DOR Qty            
                $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['dorqty'] = $dor['qty'];
                $prd[$key2]['dortotal'] = $dor['total'];
                
                // Get Uom
                $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
                
                $prd[$key2]['qty'] = $prd[$key2]['pototal'] > 0 || $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] >0 ? $val2['qty'] : $prd[$key2]['qty'];
            }

            if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
                $prh['revisi'] = 1;
            } else
                $prh['revisi'] = abs($prh['revisi']) + 1;
            
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);
            
            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
        }
        
        Zend_Loader::loadClass('Zend_Json');
    }
    
     public function appprrevisisalesAction ()
     {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $lastReject=array();
        
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PR';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
//                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$userid);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }
                            if ($val['val_kode'] == 'IDR')
                                $prd[$key]['hargaIDR'] = $val['harga'];
                            elseif ($val['val_kode'] == 'USD')
                                $prd[$key]['hargaUSD'] = $val['harga'];

                            $prd[$key]['net_act'] = $val['myob'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
//                        $jsonData2[0]['petugas'] = $prh['petugas'];
                        $jsonData2[0]['pic_kode'] = $prh['orangpic'];
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                         $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
//                        $this->view->uid = $uid;
                        $this->view->userID = $this->session->idUser;
//                        $this->view->userID = $userid;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
                    }
                }
                else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }
    
    public function neweditprAction() {
        
        $isCancel = $this->getRequest()->getParam("returnback");
          
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");
        
        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }
        
        foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                
            $prd[$key2]['id'] = $val2['id'];
            $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
            $prd[$key2]['nama_brg'] = str_replace("\"", "'", $val2['nama_brg']);
                
            // Get Related PO
            $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);               
            $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
            // Get Related DOR Qty            
            $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
            $prd[$key2]['dorqty'] = $dor['qty'];
            $prd[$key2]['dortotal'] = $dor['total'];
            
            $prd[$key2]['qty'] = $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] > 0 || $prd[$key2]['pototal'] > 0 ? $val2['qty'] : 0;
                
        }
        
        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
        
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);

            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
            
        }
        
        Zend_Loader::loadClass('Zend_Json');
        
    }
    
    public function neweditprbudgetAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
            $trano = $this->getRequest()->getParam("trano");
            $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $file = $this->files->fetchAll("trano = '$trano'");
        
            if ($file)
                $file = $file->toArray();
            else
                $file = array();

            foreach ($prh as $key => $val) {
                if ($val == '""')
                    $prh[$key] = '';
            }

            foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                $prd[$key2]['budgetid'] = $val2['workid'];
                $prd[$key2]['budgetname'] = $val2['workname'];
                $prd[$key2]['dep_kode'] =$val2['prj_kode'];
                $prd[$key2]['dep_nama'] = $val2['prj_nama'];
                $prd[$key2]['per_kode'] = $val2['sit_kode'];
                $prd[$key2]['per_nama'] = $val2['sit_nama'];

                // Get Related PO
                $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
                // Get Related DOR Qty            
                $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['dorqty'] = $dor['qty'];
                $prd[$key2]['dortotal'] = $dor['total'];
                
                // Get Uom
                $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
                
                $prd[$key2]['qty'] = $prd[$key2]['pototal'] > 0 || $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] >0 ? $val2['qty'] : 0;
            }

            if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
                $prh['revisi'] = 1;
            } else
                $prh['revisi'] = abs($prh['revisi']) + 1;
            
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);
            
            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
        }
        
        Zend_Loader::loadClass('Zend_Json');
    }
    
    public function neweditprsalesAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        
        if ($isCancel) {
            $this->view->pr = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file"); 
        }
        else
        {
            $trano = $this->getRequest()->getParam("trano");
            $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
            $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
            $file = $this->files->fetchAll("trano = '$trano'");
        
            if ($file)
                $file = $file->toArray();
            else
                $file = array();

            foreach ($prh as $key => $val) {
                if ($val == '""')
                    $prh[$key] = '';
            }

            foreach ($prd as $key2 => $val2) {
            
                $prd[$key2]['net_act'] = $val2['cfs_kode'];
                $prd[$key2]['budgetid'] = $val2['workid'];
                $prd[$key2]['budgetname'] = $val2['workname'];
                $prd[$key2]['dep_kode'] =$val2['prj_kode'];
                $prd[$key2]['dep_nama'] = $val2['prj_nama'];
                $prd[$key2]['per_kode'] = $val2['sit_kode'];
                $prd[$key2]['per_nama'] = $val2['sit_nama'];

                // Get Related PO
                $po = $this->cost->getPO($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['pototal'] = $po['totalUSD'] > 0 ? $po['totalUSD'] : $po['totalIDR'];
                
                // Get Related DOR Qty            
                $dor = $this->cost->getDOR($trano,$val2['workid'],$val2['kode_brg']);
                $prd[$key2]['dorqty'] = $dor['qty'];
                $prd[$key2]['dortotal'] = $dor['total'];
                
                // Get Uom
                $prd[$key2]['uom'] = $this->quantity->getUOMByProductID($val2['kode_brg']);
                
                $prd[$key2]['qty'] = $prd[$key2]['pototal'] > 0 || $prd[$key2]['dorqty'] > 0 || $prd[$key2]['dortotal'] >0 ? $val2['qty'] : 0;
            }

            if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
                $prh['revisi'] = 1;
            } else
                $prh['revisi'] = abs($prh['revisi']) + 1;
            
            $file = Zend_Json::encode($file);
            $prd = Zend_Json::encode($prd);
            $prh = Zend_Json::encode($prh);
            
            $this->view->pr = $prd;
            $this->view->etc = '['.$prh.']';
            $this->view->file = $file;
        }
        
        Zend_Loader::loadClass('Zend_Json');
    }

    public function editprbudgetAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchRow("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }


        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
        if ($prd) {
//   			foreach($prd as $key => $val)
//   			{
//   				$prd[$key]['id'] = $key + 1;
//   				$kodeBrg = $val['kode_brg'];
//                $workid = $val['workid'];
//                $sitKode = $val['sit_kode'];
//                $prjKode = $val['prj_kode'];
//
//   				$barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
//   				if ($barang)
//   				{
//   					$prd[$key]['uom'] = $barang['sat_kode'];
//   				}
//   				if ($val['val_kode'] == 'IDR')
//   					$prd[$key]['hargaIDR'] = $val['harga'];
//   				elseif ($val['val_kode'] == 'USD')
//   					$prd[$key]['hargaUSD'] = $val['harga'];
//
//                $prd[$key]['dep_kode'] = $val['prj_kode'];
//                $prd[$key]['dep_nama'] = $val['prj_nama'];
//                $prd[$key]['per_kode'] = $val['sit_kode'];
//                $prd[$key]['per_nama'] = $val['sit_nama'];
//                $prd[$key]['budgetid'] = $val['workid'];
//                $prd[$key]['budgetname'] = $val['workname'];
//                $prd[$key]['totalPrice'] = $val['jumlah'];
//
//                $boq3 = $this->budget->getBudgetOverhead($prjKode,$sitKode,$workid);
//                if ($prd[$key]['val_kode'] == 'IDR')
//                {
//                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalIDR'];
//                }
//                else
//                {
//                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalHargaUSD'];
//                }
//
//                $pr = $this->quantity->getPrOverheadQuantity($prjKode,$sitKode,$workid);
//                    if ($pr != '')
//                    {
//
//                        $prd[$key]['totalPRraw'] = $pr['qty'];
//
//                        $prd[$key]['totalPricePRraw'] = $pr['total'];
//
//                    }
//
//                    else
//                    {
//                        $prd[$key]['totalPRraw'] = 0;
//
//                        $prd[$key]['totalPricePRraw'] = 0;
//                    }
//
//                    $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workid);
//                    if ($arf != '')
//                    {
//
//                        $prd[$key]['totalARF'] = $arf['qty'];
//
//                        if ($prd[$key]['val_kode'] == 'IDR')
//                        {
//
//                            $prd[$key]['totalPriceARF'] = $arf['totalIDR'];
//                        }
//                        else
//                        {
//
//                            $prd[$key]['totalPriceARF'] = $arf['totalUSD'];
//                        }
//
//                    }
//
//                    else
//                    {
//                        $prd[$key]['totalARF'] = 0;
//
//                        $prd[$key]['totalPriceARF'] = 0;
//                    }
//                    $totalQtyPRARF = $prd[$key]['totalPRraw']+ $prd[$key]['totalARF'];
//                    $totalPricePRARF = $prd[$key]['totalPricePRraw']+ $prd[$key]['totalPriceARF'];
//
//                    $prd[$key]['totalPR'] = $totalQtyPRARF;
//                    $prd[$key]['totalPricePR'] = $totalPricePRARF;
//
//
//                $prd[$key]['net_act'] = $val['myob'];
//                $prd[$key]['fromBoq3'] = 1;
//   			}
//   			Zend_Loader::loadClass('Zend_Json');
//   			$jsonData = Zend_Json::encode($prd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->jsonCancel = $this->getRequest()->getParam("posts");
            }
//	   		else
//	   			$this->view->json = $jsonData;
            $this->view->json = true;
            $this->view->trano = $trano;
            $this->view->tgl = $prh['tgl'];
            $this->view->revisi = $prh['revisi'];
            $this->view->budgetType = $prh['budgettype'];
            $this->view->prj_nama = $prh['prj_nama'];
            $this->view->sit_nama = $prh['sit_nama'];
            $this->view->prj_kode = $prh['prj_kode'];
            $this->view->sit_kode = $prh['sit_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editprsalesAction() {
        $trano = $this->getRequest()->getParam("trano");
        $prd = $this->procurement->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->procurementH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($prh as $key => $val) {
            if ($val == '""')
                $prh[$key] = '';
        }


        if ($prh['revisi'] == '' || $prh['revisi'] == '""') {
            $prh['revisi'] = 1;
        } else
            $prh['revisi'] = abs($prh['revisi']) + 1;
        if ($prd) {
            foreach ($prd as $key => $val) {
                $prd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $workid = $val['workid'];
                $sitKode = $val['sit_kode'];
                $prjKode = $val['prj_kode'];

                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $prd[$key]['uom'] = $barang['sat_kode'];
                }
                if ($val['val_kode'] == 'IDR')
                    $prd[$key]['hargaIDR'] = $val['harga'];
                elseif ($val['val_kode'] == 'USD')
                    $prd[$key]['hargaUSD'] = $val['harga'];

                $prd[$key]['dep_kode'] = $val['prj_kode'];
                $prd[$key]['dep_nama'] = $val['prj_nama'];
                $prd[$key]['per_kode'] = $val['sit_kode'];
                $prd[$key]['per_nama'] = $val['sit_nama'];
                $prd[$key]['budgetid'] = $val['workid'];
                $prd[$key]['budgetname'] = $val['workname'];
                $prd[$key]['totalPrice'] = $val['jumlah'];

                $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode, $workid);
                if ($prd[$key]['val_kode'] == 'IDR') {
                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalIDR'];
                } else {
                    $prd[$key]['totalPriceBudget'] = $boq3[0]['totalHargaUSD'];
                }

                $pr = $this->quantity->getPrOverheadQuantity($prjKode, $sitKode, $workid);
                if ($pr != '') {

                    $prd[$key]['totalPRraw'] = $pr['qty'];

                    $prd[$key]['totalPricePRraw'] = $pr['total'];
                } else {
                    $prd[$key]['totalPRraw'] = 0;

                    $prd[$key]['totalPricePRraw'] = 0;
                }

                $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid);
                if ($arf != '') {

                    $prd[$key]['totalARF'] = $arf['qty'];

                    if ($prd[$key]['val_kode'] == 'IDR') {

                        $prd[$key]['totalPriceARF'] = $arf['totalIDR'];
                    } else {

                        $prd[$key]['totalPriceARF'] = $arf['totalUSD'];
                    }
                } else {
                    $prd[$key]['totalARF'] = 0;

                    $prd[$key]['totalPriceARF'] = 0;
                }
                $totalQtyPRARF = $prd[$key]['totalPRraw'] + $prd[$key]['totalARF'];
                $totalPricePRARF = $prd[$key]['totalPricePRraw'] + $prd[$key]['totalPriceARF'];

                $prd[$key]['totalPR'] = $totalQtyPRARF;
                $prd[$key]['totalPricePR'] = $totalPricePRARF;


                $prd[$key]['net_act'] = $val['myob'];
                $prd[$key]['fromBoq3'] = 1;
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($prd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;
            $this->view->trano = $trano;
            $this->view->tgl = $prh['tgl'];
            $this->view->revisi = $prh['revisi'];
            $this->view->budgetType = $prh['budgettype'];
            $this->view->prj_nama = $prh['prj_nama'];
            $this->view->sit_nama = $prh['sit_nama'];
            $this->view->prj_kode = $prh['prj_kode'];
            $this->view->sit_kode = $prh['sit_kode'];
        }
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editarf2Action() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;

        $trano = $this->getRequest()->getParam("trano");
        $arfh = $this->arfh->fetchRow("trano = '$trano'");
        $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($arfh)
            $arfh = $arfh->toArray();
        $tmp = array();

        foreach ($arfd as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $arfd[$key][$key2] = '';
            }
            $arfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $arfd[$key]['priceArf'] = $val['harga'];
            $arfd[$key]['totalARF'] = $val['total'];

            if (QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName() != '')
                $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
            else
                $arfd[$key]['requesterName'] = $val['requester'];
//        $arfd[$key]['trano'] = $arfd[$key]['pr_no'];
//        if(!in_array($arfd[$key]['trano'],$tmp))
//          $tmp['trano'] = $arfd[$key]['trano'];
//        unset($arfd[$key]['pr_no']);
//        unset($arfd[$key]['harga']);
//        unset($arfd[$key]['total']);
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $arfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBoq3ByOne($prjKode, $sitKode, $workid, $kodeBrg);
            if ($arfd[$key]['val_kode'] == 'IDR') {
//            $arfd[$key]['priceBOQ3'] = $boq3['hargaIDR'];
                $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
            } else {
//            $arfd[$key]['priceBOQ3'] = $boq3['hargaUSD'];
                $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workid, $kodeBrg);
//        $reimburs = $this->quantity->getReimbursementQuantity($prjKode,$sitKode,$workid,$kodeBrg);
//                var_dump($po);die;
            if ($po != '') {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
            }
            if ($arf != '') {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
            }

            if ($asfcancel != '') {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
            }

//        if ($reimburs != '' )
//                {
//                        $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
//                        if ($arfd[$key]['val_kode'] == 'IDR')
//                            $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
//                        else
//                            $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
//                }
//                else
//                {
//                        $arfd[$key]['totalqtyReimburs'] = 0;
//                        $arfd[$key]['totalReimburs'] = 0;
//                }
            $totalpoarfasfc = (($arfd[$key]['totalPO'] + $arfd[$key]['totalInARF']) - $arfd[$key]['totalASFCancel'] );
            $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;
        }
//                 var_dump($arfd);die;

        foreach ($arfh as $key => $val) {
            if ($val == '""')
                $arfh[$key] = '';
        }

        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
        $tmp2 = $arfh;
        unset($arfh);
        $arfh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $jsonData2 = Zend_Json::encode($arfh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }
        $this->view->prNo = $tmp;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($arfh[0]['tgl']));
        $this->view->pr_no = $arfh[0]['pr_no'];
        $this->view->val_kode = $arfh[0]['val_kode'];
        $this->view->request = $arfh[0]['request'];
        $this->view->orangfinance = $arfh[0]['orangfinance'];
        $this->view->ket = $arfh[0]['ket'];
        $this->view->ketin = $arfh[0]['ketin'];

        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editarfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;

        $trano = $this->getRequest()->getParam("trano");
        $arfh = $this->arfh->fetchRow("trano = '$trano'");
        $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($arfh)
            $arfh = $arfh->toArray();
        $tmp = array();

        foreach ($arfd as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $arfd[$key][$key2] = '';
            }
            $arfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $arfd[$key]['priceArf'] = $val['harga'];
            $arfd[$key]['totalARF'] = $val['total'];
            $arfd[$key]['budgetid'] = $val['workid'];
            $arfd[$key]['budgetname'] = $val['workname'];
            $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $arfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode, $workid);

            if ($arfd[$key]['val_kode'] == 'IDR') {
                $arfd[$key]['totalBOQ3'] = $boq3[0]['totalIDR'];
            } else {
                $arfd[$key]['totalBOQ3'] = $boq3[0]['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid);
            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid);
            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workid);
            $reimburs = $this->quantity->getReimbursementQuantity($prjKode, $sitKode, $workid);

            if ($po != '') {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
            }
            if ($arf != '') {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
            }

            if ($asfcancel != '') {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
            }

            if ($reimburs != '') {
                $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                else
                    $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyReimburs'] = 0;
                $arfd[$key]['totalReimburs'] = 0;
            }
            $totalpoarfasfc = (($arfd[$key]['totalPO'] + $arfd[$key]['totalInARF']) - $arfd[$key]['totalASFCancel'] );
            $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;
        }

        foreach ($arfh as $key => $val) {
            if ($val == '""')
                $arfh[$key] = '';
        }
        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
        $tmp2 = $arfh;
        unset($arfh);
        $arfh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $jsonData2 = Zend_Json::encode($arfh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }

        $this->view->prNo = $tmp;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($arfh[0]['tgl']));
        $this->view->pr_no = $arfh[0]['pr_no'];
        $this->view->val_kode = $arfh[0]['val_kode'];
        $this->view->request = $arfh[0]['request'];
        $this->view->orangfinance = $arfh[0]['orangfinance'];
        $this->view->ket = $arfh[0]['ket'];
        $this->view->ketin = $arfh[0]['ketin'];

        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editarfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;

        $trano = $this->getRequest()->getParam("trano");
        $arfh = $this->arfh->fetchRow("trano = '$trano'");
        $arfd = $this->arfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($arfh)
            $arfh = $arfh->toArray();
        $tmp = array();

        foreach ($arfd as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $arfd[$key][$key2] = '';
            }
            $arfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $arfd[$key]['priceArf'] = $val['harga'];
            $arfd[$key]['totalARF'] = $val['total'];
            $arfd[$key]['budgetid'] = $val['workid'];
            $arfd[$key]['budgetname'] = $val['workname'];
            $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $arfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBoq3ByOne($prjKode, $sitKode, $workid, $kodeBrg);
            if ($arfd[$key]['val_kode'] == 'IDR') {
                $arfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
            } else {
                $arfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $asfcancel = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $reimburs = $this->quantity->getReimbursementQuantity($prjKode, $sitKode, $workid, $kodeBrg);

            if ($po != '') {
                $arfd[$key]['totalqtyPO'] = $po['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $arfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyPO'] = 0;
                $arfd[$key]['totalPO'] = 0;
            }
            if ($arf != '') {
                $arfd[$key]['totalqtyARF'] = $arf['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalInARF'] = $arf['totalIDR'];
                else
                    $arfd[$key]['totalInARF'] = $arf['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyARF'] = 0;
                $arfd[$key]['totalARF'] = 0;
            }

            if ($asfcancel != '') {
                $arfd[$key]['totalqtyASFCancel'] = $asfcancel['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalIDR'];
                else
                    $arfd[$key]['totalASFCancel'] = $asfcancel['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyASFCancel'] = 0;
                $arfd[$key]['totalASFCancel'] = 0;
            }

            if ($reimburs != '') {
                $arfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                if ($arfd[$key]['val_kode'] == 'IDR')
                    $arfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                else
                    $arfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
            }
            else {
                $arfd[$key]['totalqtyReimburs'] = 0;
                $arfd[$key]['totalReimburs'] = 0;
            }
            $totalpoarfasfc = (($arfd[$key]['totalPO'] + $arfd[$key]['totalInARF']) - $arfd[$key]['totalASFCancel'] );
            $arfd[$key]['totalPoArfAsfc'] = $totalpoarfasfc;
        }

        foreach ($arfh as $key => $val) {
            if ($val == '""')
                $arfh[$key] = '';
        }
        if ($arfh['bt'] == 'Y')
            $arfh['bt'] = 1;
        else
            $arfh['bt'] = 0;
        $tmp2 = $arfh;
        unset($arfh);
        $arfh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($arfd);
        $jsonData2 = Zend_Json::encode($arfh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }
        $this->view->prNo = $tmp;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($arfh[0]['tgl']));
        $this->view->pr_no = $arfh[0]['pr_no'];
        $this->view->val_kode = $arfh[0]['val_kode'];
        $this->view->request = $arfh[0]['request'];
        $this->view->orangfinance = $arfh[0]['orangfinance'];
        $this->view->ket = $arfh[0]['ket'];

        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editpoAction() {
        $trano = $this->getRequest()->getParam("trano");

        $pod = $this->purchase->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($poh as $key => $val) {
            if ($val == "\"\"")
                $poh[$key] = '';
        }

        if ($poh['revisi'] == '' || $poh['revisi'] == '""') {
            $poh['revisi'] = 1;
        } else
            $poh['revisi'] = abs($poh['revisi']) + 1;


        if ($pod) {
            foreach ($pod as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $pod[$key][$key2] = str_replace("\"", " inch", $pod[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $pod[$key][$key2] = str_replace("'", " inch", $pod[$key][$key2]);
                }
                $pod[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $pod[$key]['uom'] = $barang['sat_kode'];
                }
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($pod);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;
        }

        if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
            $poh['budgettype'] = 'Project';

        $this->view->trano = $trano;
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->tgl = $poh['tgl'];
        $this->view->revisi = $poh['revisi'];
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->ppn = $poh['ppn'];
        $this->view->sup_kode = $poh['sup_kode'];
        $this->view->sup_nama = $poh['sup_nama'];
        $this->view->val_kode = $poh['val_kode'];

        $this->view->rateidr = $poh['rateidr'];
        $this->view->type_po = $poh['typepo2'];
        $this->view->paymentterm = trim($poh['paymentterm']);
        $this->view->top = $poh['top'];
        $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['deliverytext']);
        $this->view->tujuan = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
        $this->view->tgl_kirim = trim($poh['tgldeliesti']);
        $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['ket']);
        $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
        $this->view->ketin = trim($poh['ketin']);

//            $this->view->ket = trim($poh['ket']);


        $this->view->oripo = trim($poh['budgettype']);
        $this->view->invoiceto = trim($poh['invoiceto']);
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editpobudgetAction() {
        $trano = $this->getRequest()->getParam("trano");

        $pod = $this->purchase->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($poh as $key => $val) {
            if ($val == "\"\"")
                $poh[$key] = '';
        }

        if ($poh['revisi'] == '' || $poh['revisi'] == '""') {
            $poh['revisi'] = 1;
        } else
            $poh['revisi'] = abs($poh['revisi']) + 1;


        if ($pod) {
            foreach ($pod as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $pod[$key][$key2] = str_replace("\"", " inch", $pod[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $pod[$key][$key2] = str_replace("'", " inch", $pod[$key][$key2]);
                }
                $pod[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $pod[$key]['uom'] = $barang['sat_kode'];
                }
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($pod);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;
        }

        if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
            $poh['budgettype'] = 'Project';

        $this->view->trano = $trano;
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->tgl = $poh['tgl'];
        $this->view->revisi = $poh['revisi'];
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->ppn = $poh['ppn'];
        $this->view->sup_kode = $poh['sup_kode'];
        $this->view->sup_nama = $poh['sup_nama'];
        $this->view->val_kode = $poh['val_kode'];

        $this->view->rateidr = $poh['rateidr'];
        $this->view->type_po = $poh['typepo2'];
        $this->view->paymentterm = trim($poh['paymentterm']);
        $this->view->top = $poh['top'];
        $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['deliverytext']);
        $this->view->tujuan = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
        $this->view->tgl_kirim = trim($poh['tgldeliesti']);
        $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['ket']);
        $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
        $this->view->ketin = trim($poh['ketin']);


        $this->view->oripo = trim($poh['budgettype']);
        $this->view->invoiceto = trim($poh['invoiceto']);
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editposalesAction() {
        $trano = $this->getRequest()->getParam("trano");

        $pod = $this->purchase->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $poh = $this->purchaseH->fetchRow("trano = '$trano'")->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        foreach ($poh as $key => $val) {
            if ($val == "\"\"")
                $poh[$key] = '';
        }

        if ($poh['revisi'] == '' || $poh['revisi'] == '""') {
            $poh['revisi'] = 1;
        } else
            $poh['revisi'] = abs($poh['revisi']) + 1;


        if ($pod) {
            foreach ($pod as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $pod[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $pod[$key][$key2] = str_replace("\"", " inch", $pod[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $pod[$key][$key2] = str_replace("'", " inch", $pod[$key][$key2]);
                }
                $pod[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $pod[$key]['uom'] = $barang['sat_kode'];
                }
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($pod);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;
        }

        if ($poh['budgettype'] == '' || $poh['budgettype'] == '""')
            $poh['budgettype'] = 'Project';

        $this->view->trano = $trano;
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->tgl = $poh['tgl'];
        $this->view->revisi = $poh['revisi'];
        $this->view->prjKode = $poh['prj_kode'];
        $this->view->ppn = $poh['ppn'];
        $this->view->sup_kode = $poh['sup_kode'];
        $this->view->sup_nama = $poh['sup_nama'];
        $this->view->val_kode = $poh['val_kode'];

        $this->view->rateidr = $poh['rateidr'];
        $this->view->type_po = $poh['typepo2'];
        $this->view->paymentterm = trim($poh['paymentterm']);
        $this->view->top = $poh['top'];
        $poh['deliverytext'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['deliverytext']);
        $this->view->tujuan = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['deliverytext']);
        $this->view->tgl_kirim = trim($poh['tgldeliesti']);
        $poh['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $poh['ket']);
        $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $poh['ket']);
        $this->view->ketin = trim($poh['ketin']);

//            $this->view->ket = trim($poh['ket']);


        $this->view->oripo = trim($poh['budgettype']);
        $this->view->invoiceto = trim($poh['invoiceto']);
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editrpiAction() {
        $trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

        $prd = $this->rpi->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->rpiH->fetchRow("trano = '$trano'");

        $temp_bpv = $this->temporary_bpv->fetchRow("trano = '$trano'");
        $bpv = Zend_Json::decode($temp_bpv['data']);

        $query = "SELECT * FROM procurement_rpid rpi LEFT JOIN finance_payment_voucher bpv
                ON (rpi.trano = bpv.ref_number AND bpv.deleted=0) where rpi.trano = '$trano' ";
        $fetch = $this->db->query($query);
        $hasil = $fetch->fetchAll();

//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
        if ($prd) {
            foreach ($prd as $key => $val) {
                foreach ($val as $key2 => $val2) {

                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $prd[$key][$key2] = str_replace("\"", " inch", $prd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $prd[$key][$key2] = str_replace("'", " inch", $prd[$key][$key2]);
                }

                $prd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $pr_no = $val['pr_no'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $prd[$key]['uom'] = $barang['sat_kode'];
                }
                if ($val['val_kode'] == 'IDR')
                    $prd[$key]['hargaIDR'] = $val['harga'];
                elseif ($val['val_kode'] == 'USD')
                    $prd[$key]['hargaUSD'] = $val['harga'];

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($pod != '') {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty'] * $pod['harga'];
                } else {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0;
                }

                $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($rpid != '') {
                    $prd[$key]['totalRPI'] = $rpid['qty'];
                    if ($prd[$key]['val_kode'] == 'IDR')
                        $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                    else
                        $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];
                }
                else {
                    $prd[$key]['totalRPI'] = 0;
                    $prd[$key]['totalPriceRPI'] = 0;
                }

                if ($bpv) {
                    foreach ($bpv as $key2 => $val2) {
                        if ($kodeBrg == $val2['productid'] && $pr_no == $val2['pr_no']) {
                            $prd[$key]['value'] = $val2['total_bayar'];
                            $prd[$key]['statusppn'] = $val2['statusppn'];
                            $prd[$key]['valueppn'] = $val2['valueppn'];
                            $prd[$key]['coa_ppn'] = $val2['coa_ppn'];
                            $prd[$key]['grossup_status'] = $val2['grossup_status'];
                            $prd[$key]['holding_tax_status'] = $val2['holding_tax_status'];
                            $prd[$key]['holding_tax'] = $val2['holding_tax'];
                            $prd[$key]['holding_tax_val'] = $val2['holding_tax_val'];
                            $prd[$key]['holding_tax_text'] = $val2['holding_tax_text'];
                            $prd[$key]['coa_holding_tax'] = $val2['coa_holding_tax'];
                            $prd[$key]['deduction'] = $val2['deduction'];
                            $prd[$key]['total_value'] = $val2['total'];
                            $prd[$key]['valuta'] = $val2['valuta'];
                            $prd[$key]['prj_kode'] = $val2['prj_kode'];
                            $prd[$key]['sit_kode'] = $val2['sit_kode'];
                            $prd[$key]['ref_number'] = $val2['ref_number'];
                            $prd[$key]['coa_kode'] = $val2['coa_kode'];
                            $prd[$key]['ketin'] = $val2['ketin'];
                            $prd[$key]['requester'] = $val2['requester'];
                            $prd[$key]['voc_type'] = $val2['type'];
                        }
                    }
                }
            }
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($prd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $radio = Zend_Json::decode($prh['document_valid']);

            if ($radio != '' && count($radio) > 0) {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            } else {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['vat_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['with_materai'] = $radio['with_materai'];
            $etc[0]['materai'] = $prh['materai'];
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $etc[0]['statusbrg'] = $prh['statusbrg'];


//            var_dump(Zend_Json::decode($jsonData));die;
//               var_dump($bpv);die;


            $jsonjurnal = $this->temporary_jurnal_ap->fetchRow("trano = '$trano'");
            if (!$jsonjurnal) {
                $jurnal = "[]";
//               $this->view->old = true;
            } else
                $jurnal = $jsonjurnal['jurnal'];

            $files = $this->files->fetchAll("trano = '$trano'")->toArray();

            $JsonFile = Zend_Json::encode($files);

            $this->view->jurnal = $jurnal;
            $this->view->etc = Zend_Json::encode($etc);
            $this->view->trano = $trano;
            $this->view->po_no = $prh['po_no'];
            $this->view->tgl = $prh['tgl'];
            $this->view->prj_nama = $prh['prj_nama'];
            $this->view->sit_nama = $prh['sit_nama'];
            $this->view->jsonfile = $JsonFile;
        }
    }

    public function editrpibudgetAction() {
        $trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

        $prd = $this->rpi->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->rpiH->fetchRow("trano = '$trano'");

        $temp_bpv = $this->temporary_bpv->fetchRow("trano = '$trano'");
        $bpv = Zend_Json::decode($temp_bpv['data']);
//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
        if ($prd) {
            foreach ($prd as $key => $val) {
                foreach ($val as $key2 => $val2) {

                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $prd[$key][$key2] = str_replace("\"", " inch", $prd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $prd[$key][$key2] = str_replace("'", " inch", $prd[$key][$key2]);
                }

                $prd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                $pr_no = $val['pr_no'];
                if ($barang) {
                    $prd[$key]['uom'] = $barang['sat_kode'];
                }
                if ($val['val_kode'] == 'IDR')
                    $prd[$key]['hargaIDR'] = $val['harga'];
                elseif ($val['val_kode'] == 'USD')
                    $prd[$key]['hargaUSD'] = $val['harga'];

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($pod != '') {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty'] * $pod['harga'];
                } else {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0;
                }

                $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($rpid != '') {
                    $prd[$key]['totalRPI'] = $rpid['qty'];
                    if ($prd[$key]['val_kode'] == 'IDR')
                        $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                    else
                        $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];
                }
                else {
                    $prd[$key]['totalRPI'] = 0;
                    $prd[$key]['totalPriceRPI'] = 0;
                }


                if ($bpv) {
                    foreach ($bpv as $key2 => $val2) {
                        if ($kodeBrg == $val2['productid'] && $pr_no == $val2['pr_no']) {
                            $prd[$key]['value'] = $val2['total_bayar'];
                            $prd[$key]['statusppn'] = $val2['statusppn'];
                            $prd[$key]['valueppn'] = $val2['valueppn'];
                            $prd[$key]['coa_ppn'] = $val2['coa_ppn'];
                            $prd[$key]['grossup_status'] = $val2['grossup_status'];
                            $prd[$key]['holding_tax_status'] = $val2['holding_tax_status'];
                            $prd[$key]['holding_tax'] = $val2['holding_tax'];
                            $prd[$key]['holding_tax_val'] = $val2['holding_tax_val'];
                            $prd[$key]['holding_tax_text'] = $val2['holding_tax_text'];
                            $prd[$key]['coa_holding_tax'] = $val2['coa_holding_tax'];
                            $prd[$key]['deduction'] = $val2['deduction'];
                            $prd[$key]['total_value'] = $val2['total'];
                            $prd[$key]['valuta'] = $val2['valuta'];
                            $prd[$key]['prj_kode'] = $val2['prj_kode'];
                            $prd[$key]['sit_kode'] = $val2['sit_kode'];
                            $prd[$key]['ref_number'] = $val2['ref_number'];
                            $prd[$key]['coa_kode'] = $val2['coa_kode'];
                            $prd[$key]['ketin'] = $val2['ketin'];
                            $prd[$key]['requester'] = $val2['requester'];
                            $prd[$key]['voc_type'] = $val2['type'];
                        }
                    }
                }
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($prd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $radio = Zend_Json::decode($prh['document_valid']);
            if ($radio != '' && count($radio) > 0) {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            } else {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['var_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $etc[0]['with_materai'] = $radio['with_materai'];
            $etc[0]['materai'] = $prh['materai'];
            $etc[0]['statusbrg'] = $prh['statusbrg'];

//           $jsonjurnal = $this->temporary_jurnal_ap->fetchRow("trano = '$trano'");
//           $jurnal = $jsonjurnal['jurnal'];
//
            $jsonjurnal = $this->temporary_jurnal_ap->fetchRow("trano = '$trano'");
            if (!$jsonjurnal) {
                $jurnal = "[]";
                //              $this->view->old = true;
            } else
                $jurnal = $jsonjurnal['jurnal'];

            $files = $this->files->fetchAll("trano = '$trano'")->toArray();

            $JsonFile = Zend_Json::encode($files);

            $this->view->jurnal = $jurnal;
            $this->view->etc = Zend_Json::encode($etc);
            $this->view->trano = $trano;
            $this->view->po_no = $prh['po_no'];
            $this->view->tgl = $prh['tgl'];
            $this->view->prj_nama = $prh['prj_nama'];
            $this->view->sit_nama = $prh['sit_nama'];
            $this->view->jsonfile = $JsonFile;
        }
    }

    public function editrpisalesAction() {
        $trano = $this->getRequest()->getParam("trano");

//        $finance = new Finance_Models_Payment();

        $prd = $this->rpi->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $prh = $this->rpiH->fetchRow("trano = '$trano'");

        $temp_bpv = $this->temporary_bpv->fetchRow("trano = '$trano'");
        $bpv = Zend_Json::decode($temp_bpv['data']);

//   		if ($prh['revisi'] == '' || $prh['revisi'] == '""')
//   		{
//   			$prh['revisi'] = 1;
//   		}
//   		else
//	   		$prh['revisi'] = abs($prh['revisi']) + 1;
        if ($prd) {
            foreach ($prd as $key => $val) {
                foreach ($val as $key2 => $val2) {

                    if ($val2 == "\"\"")
                        $prd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $prd[$key][$key2] = str_replace("\"", " inch", $prd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $prd[$key][$key2] = str_replace("'", " inch", $prd[$key][$key2]);
                }

                $prd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                $pr_no = $val['pr_no'];
                if ($barang) {
                    $prd[$key]['uom'] = $barang['sat_kode'];
                }
                if ($val['val_kode'] == 'IDR')
                    $prd[$key]['hargaIDR'] = $val['harga'];
                elseif ($val['val_kode'] == 'USD')
                    $prd[$key]['hargaUSD'] = $val['harga'];

                $pod = $this->quantity->getPoSummary($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($pod != '') {
                    $prd[$key]['qtyPO'] = $pod['qty'];
                    $prd[$key]['pricePO'] = $pod['harga'];
                    $prd[$key]['totalPricePO'] = $pod['qty'] * $pod['harga'];
                } else {
                    $prd[$key]['qtyPO'] = 0;
                    $prd[$key]['pricePO'] = 0;
                    $prd[$key]['totalPricePO'] = 0;
                }

                $rpid = $this->quantity->getPoRPIQuantity($prd[$key]['po_no'], $prd[$key]['prj_kode'], $prd[$key]['sit_kode'], $prd[$key]['workid'], $prd[$key]['kode_brg'], $prd[$key]['pr_no']);
                if ($rpid != '') {
                    $prd[$key]['totalRPI'] = $rpid['qty'];
                    if ($prd[$key]['val_kode'] == 'IDR')
                        $prd[$key]['totalPriceRPI'] = $rpid['totalIDR'];
                    else
                        $prd[$key]['totalPriceRPI'] = $rpid['totalUSD'];
                }
                else {
                    $prd[$key]['totalRPI'] = 0;
                    $prd[$key]['totalPriceRPI'] = 0;
                }

                if ($bpv) {
                    foreach ($bpv as $key2 => $val2) {
                        if ($kodeBrg == $val2['productid'] && $pr_no == $val2['pr_no']) {
                            $prd[$key]['value'] = $val2['total_bayar'];
                            $prd[$key]['statusppn'] = $val2['statusppn'];
                            $prd[$key]['valueppn'] = $val2['valueppn'];
                            $prd[$key]['coa_ppn'] = $val2['coa_ppn'];
                            $prd[$key]['grossup_status'] = $val2['grossup_status'];
                            $prd[$key]['holding_tax_status'] = $val2['holding_tax_status'];
                            $prd[$key]['holding_tax'] = $val2['holding_tax'];
                            $prd[$key]['holding_tax_val'] = $val2['holding_tax_val'];
                            $prd[$key]['holding_tax_text'] = $val2['holding_tax_text'];
                            $prd[$key]['coa_holding_tax'] = $val2['coa_holding_tax'];
                            $prd[$key]['deduction'] = $val2['deduction'];
                            $prd[$key]['total_value'] = $val2['total'];
                            $prd[$key]['valuta'] = $val2['valuta'];
                            $prd[$key]['prj_kode'] = $val2['prj_kode'];
                            $prd[$key]['sit_kode'] = $val2['sit_kode'];
                            $prd[$key]['ref_number'] = $val2['ref_number'];
                            $prd[$key]['coa_kode'] = $val2['coa_kode'];
                            $prd[$key]['ketin'] = $val2['ketin'];
                            $prd[$key]['requester'] = $val2['requester'];
                            $prd[$key]['id'] = $val2['id'];
                            $prd[$key]['voc_type'] = $val2['type'];
                        }
                    }
                }
            }



            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($prd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $radio = Zend_Json::decode($prh['document_valid']);
            if ($radio != '' && count($radio) > 0) {
//                $etc[0] = $radio;
                $etc[0]['invoice_radio'] = $radio['invoice-radio'];
                $etc[0]['vat_radio'] = $radio['vat-radio'];
                $etc[0]['do_radio'] = $radio['do-radio'];
                $etc[0]['sign_radio'] = $radio['sign-radio'];
            } else {
                $etc[0]['invoice_radio'] = 1;
                $etc[0]['var_radio'] = 1;
                $etc[0]['do_radio'] = 1;
                $etc[0]['sign_radio'] = 1;
            }
            $etc[0]['sup_invoice'] = $prh['invoice_no'];
            $etc[0]['ketin'] = $prh['ketin'];
            $etc[0]['rpi_ket'] = $prh['ket'];
            $etc[0]['with_materai'] = $radio['with_materai'];
            $etc[0]['materai'] = $prh['materai'];
            $etc[0]['statusbrg'] = $prh['statusbrg'];

//           $jsonjurnal = $this->temporary_jurnal_ap->fetchRow("trano = '$trano'");
//           $jurnal = $jsonjurnal['jurnal'];

            $jsonjurnal = $this->temporary_jurnal_ap->fetchRow("trano = '$trano'");
            if (!$jsonjurnal) {
                $jurnal = "[]";
                //              $this->view->old = true;
            } else
                $jurnal = $jsonjurnal['jurnal'];

            $files = $this->files->fetchAll("trano = '$trano'")->toArray();

            $JsonFile = Zend_Json::encode($files);

            $this->view->jurnal = $jurnal;
            $this->view->etc = Zend_Json::encode($etc);
            $this->view->trano = $trano;
            $this->view->po_no = $prh['po_no'];
            $this->view->tgl = $prh['tgl'];
            $this->view->prj_nama = $prh['prj_nama'];
            $this->view->sit_nama = $prh['sit_nama'];
            $this->view->jsonfile = $JsonFile;
        }
    }

    public function editasfAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();
        
        if ($return) {
            foreach ($return as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $return[$key][$key2] = str_replace("\"", " inch", $return[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $return[$key][$key2] = str_replace("'", " inch", $return[$key][$key2]);
                }

                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asf != '') {
                    $asfqty = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asftotal = $asf['totalIDR'];
                    else
                        $asftotal = $asf['totalUSD'];
                }
                else {
                    $asfqty = 0;
                    $asftotal = 0;
                }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asfcancel != '') {

                    $asfcancelqty = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asfcanceltotal = $asfcancel['totalIDR'];
                    else
                        $asfcanceltotal = $asfcancel['totalUSD'];
                }
                else {

                    $asfcancelqty = 0;
                    $asfcanceltotal = 0;
                }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                if ($arfh != '')
                    $inarfhtotal = $arfh['total'];
                else
                    $inarfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;
            }
        } else
            $asfddcancel = Array();


        foreach ($asfh as $key => $val) {
            if ($val == "\"\"")
                $asfh[$key] = '';
        }

        if ($asfdd) {
            foreach ($asfdd as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfdd[$key][$key2] = str_replace("\"", " inch", $asfdd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfdd[$key][$key2] = str_replace("'", " inch", $asfdd[$key][$key2]);
                }

                $asfdd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfdd[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfdd = Array();

        if ($asfddcancel) {
            foreach ($asfddcancel as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfddcancel[$key][$key2] = str_replace("\"", " inch", $asfddcancel[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfddcancel[$key][$key2] = str_replace("'", " inch", $asfddcancel[$key][$key2]);
                }

                $asfddcancel[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $arf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        } else
            $this->view->json = $jsonData;
        $this->view->json2 = $jsonData2;
        $this->view->arf = $arf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];

        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editasfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        if ($return) {
            foreach ($return as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $return[$key][$key2] = str_replace("\"", " inch", $return[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $return[$key][$key2] = str_replace("'", " inch", $return[$key][$key2]);
                }

                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asf != '') {
                    $asfqty = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asftotal = $asf['totalIDR'];
                    else
                        $asftotal = $asf['totalUSD'];
                }
                else {
                    $asfqty = 0;
                    $asftotal = 0;
                }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asfcancel != '') {

                    $asfcancelqty = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asfcanceltotal = $asfcancel['totalIDR'];
                    else
                        $asfcanceltotal = $asfcancel['totalUSD'];
                }
                else {

                    $asfcancelqty = 0;
                    $asfcanceltotal = 0;
                }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                if ($arfh != '')
                    $inarfhtotal = $arfh['total'];
                else
                    $inarfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;
            }
        } else
            $asfddcancel = Array();


        foreach ($asfh as $key => $val) {
            if ($val == "\"\"")
                $asfh[$key] = '';
        }

        if ($asfdd) {
            foreach ($asfdd as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfdd[$key][$key2] = str_replace("\"", " inch", $asfdd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfdd[$key][$key2] = str_replace("'", " inch", $asfdd[$key][$key2]);
                }

                $asfdd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfdd[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfdd = Array();

        if ($asfddcancel) {
            foreach ($asfddcancel as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfddcancel[$key][$key2] = str_replace("\"", " inch", $asfddcancel[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfddcancel[$key][$key2] = str_replace("'", " inch", $asfddcancel[$key][$key2]);
                }

                $asfddcancel[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $arf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        } else
            $this->view->json = $jsonData;
        $this->view->json2 = $jsonData2;
        $this->view->arf = $arf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function editasfsalesAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfD->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfH->fetchRow("trano = '$trano'")->toArray();

        $sql = "SELECT a.* FROM procurement_arfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
        $fetch = $this->db->query($sql);
        $return = $fetch->fetchAll();

        if ($return) {
            foreach ($return as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $return[$key][$key2] = str_replace("\"", " inch", $return[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $return[$key][$key2] = str_replace("'", " inch", $return[$key][$key2]);
                }

                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $return[$key]['uom'] = $barang['sat_kode'];
                }

                $asf = $this->quantity->getArfAsfQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asf != '') {
                    $asfqty = $asf['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asftotal = $asf['totalIDR'];
                    else
                        $asftotal = $asf['totalUSD'];
                }
                else {
                    $asfqty = 0;
                    $asftotal = 0;
                }

                $asfcancel = $this->quantity->getArfAsfcancelQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                if ($asfcancel != '') {

                    $asfcancelqty = $asfcancel['qty'];
                    if ($return[$key]['val_kode'] == 'IDR')
                        $asfcanceltotal = $asfcancel['totalIDR'];
                    else
                        $asfcanceltotal = $asfcancel['totalUSD'];
                }
                else {

                    $asfcancelqty = 0;
                    $asfcanceltotal = 0;
                }

                $arfh = $this->quantity->getArfhTotal($return[$key]['trano']);

                if ($arfh != '')
                    $inarfhtotal = $arfh['total'];
                else
                    $inarfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalASF'] = $asfqty;
                $return[$key]['totalPriceASF'] = $asftotal;
                $return[$key]['totalASFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceASFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInArfh'] = $inarfhtotal;
            }
        } else
            $asfddcancel = Array();


        foreach ($asfh as $key => $val) {
            if ($val == "\"\"")
                $asfh[$key] = '';
        }

        if ($asfdd) {
            foreach ($asfdd as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfdd[$key][$key2] = str_replace("\"", " inch", $asfdd[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfdd[$key][$key2] = str_replace("'", " inch", $asfdd[$key][$key2]);
                }

                $asfdd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfdd[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfdd = Array();

        if ($asfddcancel) {
            foreach ($asfddcancel as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $asfddcancel[$key][$key2] = str_replace("\"", " inch", $asfddcancel[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $asfddcancel[$key][$key2] = str_replace("'", " inch", $asfddcancel[$key][$key2]);
                }

                $asfddcancel[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                }
            }
        } else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $arf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        } else
            $this->view->json = $jsonData;
        $this->view->json2 = $jsonData2;
        $this->view->arf = $arf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

    public function getlastprAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('PR');
        $number++;
        echo "{ pr:'$number' }";
    }

    public function getlastarfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('ARF');
        $number++;
        echo "{ ARF:'$number' }";
    }

    public function getlastpoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('PO');
        $number++;
        echo "{ po:'$number' }";
    }

    public function appprAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $userid = $this->getRequest()->getParam("userid");
        $uid = $this->getRequest()->getParam("uid");
        $userid = $userid !='' || $userid != null ? $userid : $this->session->idUser;
        $uid = $uid !='' || $uid!= null ? $uid : $this->session->userName;
        $lastReject=array();
        
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PR';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                //$user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],$userid);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }
                            if ($val['val_kode'] == 'IDR')
                                $prd[$key]['hargaIDR'] = $val['harga'];
                            elseif ($val['val_kode'] == 'USD')
                                $prd[$key]['hargaUSD'] = $val['harga'];

                            $prd[$key]['net_act'] = $val['myob'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        //$this->view->uid = $this->session->userName;
                        $this->view->uid = $uid;
                        //$this->view->userID = $this->session->idUser;
                        $this->view->userID = $userid;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
                    }
                }
                else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function appprbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PRO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }

//	        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = Zend_Json::encode($jsonData);
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }

            if ($sales == 'true') {
                $this->view->sales = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $potong = substr($approve, 0, 3);
                    if ($potong == 'PRF')
                        $this->view->sales = true;

                    $prd = $this->procurement->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->procurementH->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $prd[$key]['totalPrice'] = $val['jumlah'];
                            $prd[$key]['budgetid'] = $val['workid'];
                            $prd[$key]['budgetname'] = $val['workname'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
//		   				$cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
//				        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['budgettype'] = $prh['budgettype'];
//				        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $file;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apprpiAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/RPI';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $trano = $jsonData2[0]['po_no'];
            $rpino = $jsonData2[0]['trano'];
            $quantity = $this->_helper->getHelper('quantity');
            $jsonJurnal = $this->getRequest()->getParam("jsonJurnal");
            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');

//            var_dump($jsonData);die;

            if ($from == 'edit') {
                $this->view->edit = true;
                $po = $quantity->getDetailPoRPIQuantity($rpino, $trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '') {
                    $tmp = array();
                    foreach ($po as $key => $val) {
                        $tmp[$key]['total'] = number_format($val['total'], 2);
                        $tmp[$key]['val_kode'] = $val['val_kode'];
                        $jsonData2[0]['po_invoice'] += $val['total'];
                    }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
            } else {
                $po = $quantity->getDetailPoRPIQuantity('', $trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '') {
                    $tmp = array();
                    foreach ($po as $key => $val) {
                        $tmp[$key]['total'] = number_format($val['total'], 2);
                        $tmp[$key]['val_kode'] = $val['val_kode'];
                        $jsonData2[0]['po_invoice'] += $val['total'];
                    }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
            }
            $po = $this->purchase->fetchRow("trano='$trano'");
            $poH = $this->purchaseH->fetchRow("trano='$trano'");
            $cusKode = $this->project->getProjectAndCustomer($po['prj_kode']);
            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $jsonData2[0]['prj_nama'] = $po['prj_nama'];
            $jsonData2[0]['prj_kode'] = $po['prj_kode'];
            $jsonData2[0]['sit_nama'] = $po['sit_nama'];
            $jsonData2[0]['sit_kode'] = $po['sit_kode'];
            $jsonData2[0]['sup_nama'] = $poH['sup_nama'];
            $jsonData2[0]['sup_kode'] = $poH['sup_kode'];
            $jsonData2[0]['val_kode'] = $poH['val_kode'];
            $jsonData2[0]['totalPO'] = $poH['jumlahspl'];
            $jsonData2[0]['po_no'] = $poH['trano'];
            $pono = substr($poH['trano'], 0, 4);
            switch ($pono) {
                case 'PO01':
                    $this->view->poType = 'PO';
                    break;
                case 'PO02':
                    $this->view->poType = 'POO';
                    break;
            }

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonEtc = Zend_Json::encode($jsonData2);
            $this->view->jsonJurnal = $jsonJurnal;
            $this->view->file = $filedata;
            $this->view->JsonFile = $this->getRequest()->getParam('filedata');


            //PPh stuff...
            $totalPPH = 0;
            $totalGrossup = 0;
            $totalPPN = 0;
            $totalDeduction = 0;
            foreach ($jsonData as $k => $v) {
                if ($v['holding_tax_status'] == 'Y') {
                    $totalPPH += floatval(str_replace(",", "", $v['holding_tax_val']));
                }
                if ($v['grossup_status'] == 'Y') {
                    $totalGrossup += floatval(str_replace(",", "", $v['holding_tax_val']));
                }
                if (floatval(str_replace(",", "", $v['valueppn'])) >= 0) {
                    $totalPPN += floatval(str_replace(",", "", $v['valueppn']));
                }
                if (floatval(str_replace(",", "", $v['deduction'])) >= 0) {
                    $totalDeduction += floatval(str_replace(",", "", $v['deduction']));
                }
            }


            if ($totalPPN > 0) {
                $this->view->isPPn = true;
                $this->view->ppnValue = $totalPPN;
            }
            $this->view->totalPPH = $totalPPH;
            $this->view->totalGrossup = $totalGrossup;
            $this->view->totalDeduction = $totalDeduction;
        } elseif ($approve || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->workflowTrans->fetchRow("item_id = '$tranoShow'", array("date DESC"));
            } else
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $prd = $this->rpi->fetchAll("trano = '$approve' AND deleted=0")->toArray();
                    $prh = $this->rpiH->fetchRow("trano = '$approve' AND deleted=0")->toArray();
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }
                            if ($val['val_kode'] == 'IDR')
                                $prd[$key]['hargaIDR'] = $val['harga'];
                            elseif ($val['val_kode'] == 'USD')
                                $prd[$key]['hargaUSD'] = $val['harga'];

                            if ($statApprove == $this->const['DOCUMENT_FINAL']) {
                                $tempBPV = new Finance_Models_BankPaymentVoucher;
                                $bpv = $tempBPV->fetchAll("ref_number = '$approve'");
                                if ($bpv) {
                                    $bpv = $bpv->toArray();
                                    foreach ($bpv as $k => $v) {
                                        if ($v['status_bpv_ppn'] == 1) {
                                            $prd[$key]['ppn_ref_number'] = $v['ppn_ref_number'];
                                        }
                                    }
                                }
                            } else {
                                $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                                $bpv = $tempBPV->fetchRow("trano = '$approve'");
                                if ($bpv) {
                                    $bpv = $bpv->toArray();
                                    $bpv = Zend_Json::decode($bpv['data']);
                                    foreach ($bpv as $k => $v) {
                                        if (floatval(str_replace(",", "", $v['valueppn'])) >= 0) {
                                            $prd[$key]['ppn_ref_number'] = $v['ppn_ref_number'];
                                        }
                                    }
                                }
                            }
                        }
                        $po_no = $prh['po_no'];
                        $quantity = $this->_helper->getHelper('quantity');
                        $po = $quantity->getDetailPoRPIQuantity($approve, $po_no);
                        $jsonData2[0]['po_invoice'] = 0;
                        $radio = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0] = $radio;
                        if ($po != '') {
                            $tmp = array();
                            foreach ($po as $key => $val) {
                                $tmp[$key]['total'] = number_format($val['total'], 2);
                                $tmp[$key]['val_kode'] = $val['val_kode'];
                                $jsonData2[0]['po_invoice'] += $val['total'];
                            }
                            $jsonData2[0]['po_invoice_detail'] = $tmp;
                        }
                        $totalPO = 0;
                        $poH = $this->purchaseH->fetchRow("trano='$po_no'");
                        if ($poH) {
                            $poH = $poH->toArray();
//                            if ($poH['ppn'] > 0) {
//                                $this->view->isPPn = true;
//                                $this->view->ppnValue = $prh['ppn'];
//                            } else {
//                                $this->view->isPPn = false;
//                                $this->view->ppnValue = 0;
//                            }
                            $totalPO = $poH['jumlahspl'];
                            $valKode = $poH['val_kode'];
                        }

                        //update ppn stuff...

                        if ($prh['ppn'] > 0) {
                            $this->view->isPPn = true;
                            $this->view->ppnValue = $prh['ppn'];
                        } else {
                            $this->view->isPPn = false;
                            $this->view->ppnValue = 0;
                        }
                        $userApp = $this->workflow->getAllApproval($approve);

                        $filedata = $this->files->fetchAll("trano = '$approve'");

                        $radios = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0]['invoice_radio'] = $radios['invoice-radio'];
                        $jsonData2[0]['vat_radio'] = $radios['vat-radio'];
                        $jsonData2[0]['do_radio'] = $radios['do-radio'];
                        $jsonData2[0]['sign_radio'] = $radios['sign-radio'];
                        $jsonData2[0]['with_materai'] = $radios['with_materai'];

                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $jsonData2[0]['sup_kode'] = $prh['sup_kode'];
                        $jsonData2[0]['sup_nama'] = $prh['sup_nama'];
                        $jsonData2[0]['sup_invoice'] = $prh['invoice_no'];
                        $jsonData2[0]['materai'] = $prh['materai'];
                        $jsonData2[0]['val_kode'] = $valKode;
                        $jsonData2[0]['totalPO'] = $totalPO;
                        $jsonData2[0]['balance'] = $totalPO - $jsonData2[0]['po_invoice'];
                        $jsonData2[0]['po_no'] = $prh['po_no'];
                        $jsonData2[0]['ppn'] = $prh['ppn'];
                        $jsonData2[0]['statusbrg'] = $prh['statusbrg'];
                        if ($jsonData2[0]['ppn'] > 0) {
                            $this->view->isPPn = true;
                        }
                        $jsonData2[0]['rpi_ket'] = $prh['ket'];
                        $jsonData2[0]['ketin'] = $prh['ketin'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];

                        $pono = substr($prh['po_no'], 0, 4);
                        switch ($pono) {
                            case 'PO01':
                                $this->view->poType = 'PO';
                                break;
                            case 'PO02':
                                $this->view->poType = 'POO';
                                break;
                        }

//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->createdDate = date('d-m-Y', strtotime($prh['tgl']));
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->trano = $approve;
                        $this->view->result = $prd;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $filedata;

                        //PPh stuff...
                        $totalPPH = 0;
                        $totalGrossup = 0;
                        $totalDeduction = 0;
                        if ($prh['totalwht'] == 0 || $prh['totalwht'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    if ($v['holding_tax_status'] == 'Y') {
                                        $totalPPH += $v['holding_tax_val'];
                                    }
                                }
                            }
                        } else {
                            $totalPPH = $prh['totalwht'];
                        }

                        if ($prh['total_grossup'] == 0 || $prh['total_grossup'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    if ($v['grossup_status'] == 'Y') {
                                        $totalGrossup += $v['holding_tax_val'];
                                    }
                                }
                            }
                        } else {
                            $totalGrossup = $prh['total_grossup'];
                        }

                        if ($prh['total_deduction'] == 0 || $prh['total_deduction'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    $totalDeduction += $v['deduction'];
                                }
                            }
                        } else {
                            $totalDeduction = $prh['total_deduction'];
                        }

                        $this->view->totalPPH = $totalPPH;
                        $this->view->totalGrossup = $totalGrossup;
                        $this->view->totalDeduction = $totalDeduction;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apprpibudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $sales = $this->getRequest()->getParam("sales");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/RPIO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $trano = $jsonData2[0]['po_no'];
            $rpino = $jsonData2[0]['trano'];
            $quantity = $this->_helper->getHelper('quantity');
            $jsonJurnal = $this->getRequest()->getParam("jsonJurnal");
            $decodejurnal = Zend_Json::decode($jsonJurnal);
            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));

//           var_dump($jsonData,$decodejurnal);die;

            if ($from == 'edit') {
                $this->view->edit = true;
                $po = $quantity->getDetailPoRPIQuantity($rpino, $trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '') {
                    $tmp = array();
                    foreach ($po as $key => $val) {
                        $tmp[$key]['total'] = number_format($val['total'], 2);
                        $tmp[$key]['val_kode'] = $val['val_kode'];
                        $jsonData2[0]['po_invoice'] += $val['total'];
                    }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
            } else {
                $po = $quantity->getDetailPoRPIQuantity('', $trano);
                $jsonData2[0]['po_invoice'] = 0;
                if ($po != '') {
                    $tmp = array();
                    foreach ($po as $key => $val) {
                        $tmp[$key]['total'] = number_format($val['total'], 2);
                        $tmp[$key]['val_kode'] = $val['val_kode'];
                        $jsonData2[0]['po_invoice'] += $val['total'];
                    }
                    $jsonData2[0]['po_invoice_detail'] = $tmp;
                }
            }

            if ($sales == 'true') {
                $this->view->sales = true;
            }
            $po = $this->purchase->fetchRow("trano='$trano'");
            $poH = $this->purchaseH->fetchRow("trano='$trano'");
//	        $cusKode = $this->project->getProjectAndCustomer($po['prj_kode']);
//
//	        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//	        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $jsonData2[0]['prj_nama'] = $poH['prj_nama'];
            $jsonData2[0]['prj_kode'] = $poH['prj_kode'];
            $jsonData2[0]['sit_nama'] = $poH['sit_nama'];
            $jsonData2[0]['sit_kode'] = $poH['sit_kode'];
            $jsonData2[0]['sup_nama'] = $poH['sup_nama'];
            $jsonData2[0]['sup_kode'] = $poH['sup_kode'];
            $jsonData2[0]['val_kode'] = $poH['val_kode'];
            $jsonData2[0]['totalPO'] = $poH['jumlahspl'];
            $jsonData2[0]['po_no'] = $poH['trano'];
//           if ($jsonData2[0]['ppn'] > 0)
//           {
//               $this->view->isPPn = true;
//               $this->view->ppnValue = $jsonData2[0]['ppn'];
//           }

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonEtc = Zend_Json::encode($jsonData2);
            $this->view->jsonJurnal = $jsonJurnal;
            $this->view->file = $filedata;
            $this->view->JsonFile = $this->getRequest()->getParam('filedata');

            //PPh stuff...
            $totalPPH = 0;
            $totalGrossup = 0;
            $totalPPN = 0;
            $totalDeduction = 0;
            foreach ($jsonData as $k => $v) {
                if ($v['holding_tax_status'] == 'Y') {
                    $totalPPH += floatval(str_replace(",", "", $v['holding_tax_val']));
                }
                if ($v['grossup_status'] == 'Y') {
                    $totalGrossup += floatval(str_replace(",", "", $v['holding_tax_val']));
                }
                if (floatval(str_replace(",", "", $v['valueppn'])) >= 0) {
                    $totalPPN += floatval(str_replace(",", "", $v['valueppn']));
                }
                if (floatval(str_replace(",", "", $v['deduction'])) >= 0) {
                    $totalDeduction += floatval(str_replace(",", "", $v['deduction']));
                }
            }

            if ($totalPPN > 0) {
                $this->view->isPPn = true;
                $this->view->ppnValue = $totalPPN;
            }

            $this->view->totalPPH = $totalPPH;
            $this->view->totalGrossup = $totalGrossup;
            $this->view->totalDeduction = $totalDeduction;
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $potong = substr($approve, 0, 5);
                    if ($potong == 'RPI01')
                        $this->view->sales = true;

                    $prd = $this->rpi->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->rpiH->fetchRow("trano = '$approve'")->toArray();
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }
                            if ($val['val_kode'] == 'IDR')
                                $prd[$key]['hargaIDR'] = $val['harga'];
                            elseif ($val['val_kode'] == 'USD')
                                $prd[$key]['hargaUSD'] = $val['harga'];

                            if ($statApprove == $this->const['DOCUMENT_FINAL']) {
                                $tempBPV = new Finance_Models_BankPaymentVoucher;
                                $bpv = $tempBPV->fetchAll("ref_number = '$approve'");
                                if ($bpv) {
                                    $bpv = $bpv->toArray();
                                    foreach ($bpv as $k => $v) {
                                        if ($v['status_bpv_ppn'] == 1) {
                                            $prd[$key]['ppn_ref_number'] = $v['ppn_ref_number'];
                                        }
                                    }
                                }
                            } else {

                                $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                                $bpv = $tempBPV->fetchRow("trano = '$approve'");
                                if ($bpv) {
                                    $bpv = $bpv->toArray();
                                    $bpv = Zend_Json::decode($bpv['data']);
                                    foreach ($bpv as $k => $v) {
                                        if (floatval(str_replace(",", "", $v['valueppn'])) >= 0) {
                                            $prd[$key]['ppn_ref_number'] = $v['ppn_ref_number'];
                                        }
                                    }
                                }
                            }
                        }

                        $po_no = $prh['po_no'];
                        $quantity = $this->_helper->getHelper('quantity');
                        $po = $quantity->getDetailPoRPIQuantity($approve, $po_no);
                        $jsonData2[0]['po_invoice'] = 0;
                        $radio = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0] = $radio;
                        if ($po != '') {
                            $tmp = array();
                            foreach ($po as $key => $val) {
                                $tmp[$key]['total'] = number_format($val['total'], 2);
                                $tmp[$key]['val_kode'] = $val['val_kode'];
                                $jsonData2[0]['po_invoice'] += $val['total'];
                            }
                            $jsonData2[0]['po_invoice_detail'] = $tmp;
                        }
                        $totalPO = 0;
                        $poH = $this->purchaseH->fetchRow("trano='$po_no'");
                        if ($poH) {
                            $poH = $poH->toArray();
//                            if ($poH['ppn'] > 0) {
//                                $this->view->isPPn = true;
//                                $this->view->ppnValue = $prh['ppn'];
//                            } else {
//                                $this->view->isPPn = false;
//                                $this->view->ppnValue = 0;
//                            }
                            $totalPO = $poH['jumlahspl'];
                            $valKode = $poH['val_kode'];
                        }
                        //ppn stuff update..

                        if ($prh['ppn'] > 0) {
                            $this->view->isPPn = true;
                            $this->view->ppnValue = $prh['ppn'];
                        } else {
                            $this->view->isPPn = false;
                            $this->view->ppnValue = 0;
                        }
                        $userApp = $this->workflow->getAllApproval($approve);

                        $filedata = $this->files->fetchAll("trano = '$approve'");

                        $radios = Zend_Json::decode($prh['document_valid']);
                        $jsonData2[0]['invoice_radio'] = $radios['invoice-radio'];
                        $jsonData2[0]['vat_radio'] = $radios['vat-radio'];
                        $jsonData2[0]['do_radio'] = $radios['do-radio'];
                        $jsonData2[0]['sign_radio'] = $radios['sign-radio'];
                        $jsonData2[0]['with_materai'] = $radios['with_materai'];

                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $jsonData2[0]['sup_kode'] = $prh['sup_kode'];
                        $jsonData2[0]['sup_nama'] = $prh['sup_nama'];
                        $jsonData2[0]['sup_invoice'] = $prh['invoice_no'];
                        $jsonData2[0]['materai'] = $prh['materai'];
                        $jsonData2[0]['val_kode'] = $valKode;
                        $jsonData2[0]['totalPO'] = $totalPO;
                        $jsonData2[0]['balance'] = $totalPO - $jsonData2[0]['po_invoice'];
                        $jsonData2[0]['po_no'] = $prh['po_no'];
                        $jsonData2[0]['ppn'] = $prh['ppn'];
                        if ($jsonData2[0]['ppn'] > 0)
                            $this->view->isPPn = true;
                        $jsonData2[0]['rpi_ket'] = $prh['ket'];
                        $jsonData2[0]['ketin'] = $prh['ketin'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
                        $jsonData2[0]['petugas'] = $prh['petugas'];
                        $jsonData2[0]['statusbrg'] = $prh['statusbrg'];

                        $pono = substr($prh['po_no'], 0, 4);
                        switch ($pono) {
                            case 'PO01':
                                $this->view->poType = 'PO';
                                break;
                            case 'PO02':
                                $this->view->poType = 'POO';
                                break;
                        }

//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->createdDate = date('d-m-Y', strtotime($prh['tgl']));
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->trano = $approve;
                        $this->view->result = $prd;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                        $this->view->file = $filedata;

                        //PPh stuff...
                        $totalPPH = 0;
                        $totalGrossup = 0;
                        $totalDeduction = 0;
                        if ($prh['totalwht'] == 0 || $prh['totalwht'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    if ($v['holding_tax_status'] == 'Y') {
                                        $totalPPH += $v['holding_tax_val'];
                                    }
                                }
                            }
                        } else {
                            $totalPPH = $prh['totalwht'];
                        }

                        if ($prh['total_grossup'] == 0 || $prh['total_grossup'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    if ($v['grossup_status'] == 'Y') {
                                        $totalGrossup += $v['holding_tax_val'];
                                    }
                                }
                            }
                        } else {
                            $totalGrossup = $prh['total_grossup'];
                        }

                        if ($prh['total_deduction'] == 0 || $prh['total_deduction'] == '') {
                            $tempBPV = new Finance_Models_AccountingTemporaryBPV();
                            $bpv = $tempBPV->fetchRow("trano = '$approve'");
                            if ($bpv) {
                                $bpv = $bpv->toArray();
                                $bpv = Zend_Json::decode($bpv['data']);
                                foreach ($bpv as $k => $v) {
                                    $totalDeduction += $v['deduction'];
                                }
                            }
                        } else {
                            $totalDeduction = $prh['total_deduction'];
                        }

                        $this->view->totalPPH = $totalPPH;
                        $this->view->totalGrossup = $totalGrossup;
                        $this->view->totalDeduction = $totalDeduction;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apppoAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');

            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            $sup = $this->trans->getAlamatSup($jsonData2[0]['sup_kode']);
            $alamat = $sup['alamat'] . " " . $sup['alamat2'];
            $alamat = str_replace('""', "", $alamat);
            $tlpsup = $sup['tlp'];
            $faxsup = $sup['fax'];
            $jsonData2[0]['alamat_sup'] = $alamat;
            $jsonData2[0]['tlp_sup'] = $tlpsup;
            $jsonData2[0]['fax_sup'] = $faxsup;
            $jsonData2[0]['ketin'] = trim($jsonData2[0]['ketin']);

            $temp = array();
            $total = 0;
            foreach ($jsonData as $key => $val) {
                $prjKode = $val['prj_kode'];
                $jsonData[$key]['pr_no'] = $val['pr_number'];
                if ($jsonData[$key]['qtySupp'] == '')
                    $jsonData[$key]['qtySupp'] = $jsonData[$key]['qty'];
                if ($jsonData[$key]['priceSupp'] == '')
                    $jsonData[$key]['priceSupp'] = $jsonData[$key]['price'];
                if ($jsonData[$key]['totalPriceSupp'] == '' || $jsonData[$key]['totalPriceSupp'] == 0)
                    $jsonData[$key]['totalPriceSupp'] = $jsonData[$key]['price'] * $jsonData[$key]['qty'];
                $temp[$prjKode][] = $jsonData[$key];
                $total += $jsonData[$key]['totalPriceSupp'];
            }
            $jsonData = $temp;


            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;
            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $this->view->approve = false;

            if (is_numeric($approve)) {
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
                if ($docs) {
                    $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                    if ($user || $show) {
                        $id = $docs['workflow_trans_id'];
                        $approve = $docs['item_id'];

                        $this->view->approve = true;
                    }
                }
            } else {
                $pod = $this->purchase->fetchRow("trano = '$approve'");
                if ($pod) {
                    $docs = $this->workflow->getDocumentLastStatusAll($approve);
                    $this->view->approve = true;
                } else
                    $this->view->approve = false;
            }

            if ($this->view->approve) {
                $userApp = $this->workflow->getAllApproval($approve);
                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;
                $pod = $this->purchase->fetchAll("trano = '$approve'")->toArray();
                $poh = $this->purchaseH->fetchRow("trano = '$approve'");
                $file = $this->files->fetchAll("trano = '$approve'");
                if ($pod) {
                    foreach ($pod as $key => $val) {
                        $kodeBrg = $val['kode_brg'];
                        $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                        if ($barang) {
                            $pod[$key]['uom'] = $barang['sat_kode'];
                        }

                        $pod[$key]['price'] = $val['harga'];
                        $pod[$key]['net_act'] = $val['myob'];
                        if ($val['qtyspl'] == '')
                            $pod[$key]['qtyspl'] = $val['qty'];
                        if ($val['hargaspl'] == '')
                            $pod[$key]['hargaspl'] = $val['harga'];

                        if ($val['qtyspl'] == '')
                            $pod[$key]['totalspl'] = $val['harga'] * $val['qty'];
                        $pod[$key]['qtySupp'] = $pod[$key]['qtyspl'];
                        $pod[$key]['priceSupp'] = $pod[$key]['hargaspl'];
                        $pod[$key]['totalPriceSupp'] = $pod[$key]['qtyspl'] * $pod[$key]['hargaspl'];

                        $pod[$key]['valueppnSupp'] = $val['ppnspl'];

                        if ($val['ppnspl'] > 0)
                            $pod[$key]['statusppn'] = 'Y';

                        //get Remark from PR
                        $cekPr = $this->procurement->fetchRow("trano = '" . $val['pr_no'] . "' AND prj_kode = '" . $val['prj_kode'] . "' AND sit_kode = '" . $val['sit_kode'] . "' AND workid = '" . $val['workid'] . "' AND kode_brg = '" . $val['kode_brg'] . "'");
                        if ($cekPr) {
                            $cekPr = $cekPr->toArray();
                            $pod[$key]['ket_pr'] = $cekPr['ket'];
                        }
                    }

                    $sup = $this->trans->getAlamatSup($poh['sup_kode']);

                    $jsonData2[0]['user_approval'] = $userApp;
                    $alamat = $sup['alamat'] . " " . $sup['alamat2'];
                    $tlpsup = $sup['tlp'];
                    $faxsup = $sup['fax'];
                    $jsonData2[0]['alamat_sup'] = $alamat;
                    $jsonData2[0]['tlp_sup'] = $tlpsup;
                    $jsonData2[0]['fax_sup'] = $faxsup;

                    $jsonData2[0]['prj_kode'] = $poh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $poh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $poh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $poh['sit_nama'];
                    $jsonData2[0]['sup_kode'] = $poh['sup_kode'];
                    $jsonData2[0]['sup_nama'] = $poh['sup_nama'];

                    $jsonData2[0]['tgl'] = $poh['tgl'];
                    $jsonData2[0]['tgldeliesti'] = $poh['tgldeliesti'];
                    $jsonData2[0]['tujuan'] = $poh['deliverytext'];
                    $jsonData2[0]['payterm'] = $poh['paymentterm'];
                    $jsonData2[0]['ket'] = $poh['ket'];
                    $jsonData2[0]['rev'] = $poh['revisi'];
                    $jsonData2[0]['tax'] = $poh['statusppn'];
                    $jsonData2[0]['invoiceto'] = $poh['invoiceto'];
                    $jsonData2[0]['petugas'] = $poh['petugas'];
                    $jsonData2[0]['ketin'] = trim($poh['ketin']);
                    $jsonData2[0]['tax_val'] = $poh['ppnspl'];
                    $jsonData2[0]['cod'] = $poh['cod'];

                    $temp = array();
                    foreach ($pod as $key => $val) {
                        $prjKode = $val['prj_kode'];
                        $temp[$prjKode][] = $val;
                    }
                    $pod = $temp;
                    $jsonData2[0]['trano'] = $approve;
//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $pod;
                    $this->view->trano = $approve;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->file = $file;
                    $this->view->preview = $preview;
                }
            }
        }


        if ($poh['revisi'] > 0)
            $this->view->isRevisi = true;
        $json = $this->getRequest()->getParam("posts");


        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
        $this->view->jsonResult = $json;
    }

    public function apppobudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $sales = $this->getRequest()->getParam("sales");
        $this->view->show = $show;
        $lastReject = array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/POO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');

            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            $sup = $this->trans->getAlamatSup($jsonData2[0]['sup_kode']);
            $alamat = $sup['alamat'] . " " . $sup['alamat2'];
            $alamat = str_replace('""', "", $alamat);
            $tlpsup = $sup['tlp'];
            $faxsup = $sup['fax'];
            $jsonData2[0]['alamat_sup'] = $alamat;
            $jsonData2[0]['tlp_sup'] = $tlpsup;
            $jsonData2[0]['fax_sup'] = $faxsup;
            $jsonData2[0]['ketin'] = trim($jsonData2[0]['ketin']);

            $temp = array();
            $total = 0;
            foreach ($jsonData as $key => $val) {
                $prjKode = $val['prj_kode'];
                $jsonData[$key]['pr_no'] = $val['pr_number'];
                if ($jsonData[$key]['qtySupp'] == '')
                    $jsonData[$key]['qtySupp'] = $jsonData[$key]['qty'];
                if ($jsonData[$key]['priceSupp'] == '')
                    $jsonData[$key]['priceSupp'] = $jsonData[$key]['price'];
                if ($jsonData[$key]['totalPriceSupp'] == '' || $jsonData[$key]['totalPriceSupp'] == 0)
                    $jsonData[$key]['totalPriceSupp'] = $jsonData[$key]['price'] * $jsonData[$key]['qty'];
                $temp[$prjKode][] = $jsonData[$key];
                $total += $jsonData[$key]['totalPriceSupp'];
            }
            $jsonData = $temp;


            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;
            if ($from == 'edit') {
                $this->view->edit = true;
            }
            if ($sales == 'true') {
                $this->view->sales = true;
            }
        } else {

            $this->view->approve = false;

            if (is_numeric($approve)) {
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
                if ($docs) {
                    $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                    if ($user || $show) {
                        $id = $docs['workflow_trans_id'];
                        $approve = $docs['item_id'];

                        $this->view->approve = true;
                    }
                }
            } else {
                $pod = $this->purchase->fetchRow("trano = '$approve'");
                if ($pod) {
                    $docs = $this->workflow->getDocumentLastStatusAll($approve);
                    $this->view->approve = true;
                } else
                    $this->view->approve = false;
            }

            if ($this->view->approve) {
                $userApp = $this->workflow->getAllApproval($approve);
                $statApprove = $docs['approve'];
                if ($statApprove == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;
                $potong = substr($approve, 0, 4);
                if ($potong == 'PO01')
                    $this->view->sales = true;

                $pod = $this->purchase->fetchAll("trano = '$approve'")->toArray();
                $poh = $this->purchaseH->fetchRow("trano = '$approve'");
                $file = $this->files->fetchAll("trano = '$approve'");
                if ($pod) {
                    foreach ($pod as $key => $val) {
                        $kodeBrg = $val['kode_brg'];
                        $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                        if ($barang) {
                            $pod[$key]['uom'] = $barang['sat_kode'];
                        }

                        $pod[$key]['price'] = $val['harga'];
                        $pod[$key]['net_act'] = $val['myob'];
                        if ($val['qtyspl'] == '')
                            $pod[$key]['qtyspl'] = $val['qty'];
                        if ($val['hargaspl'] == '')
                            $pod[$key]['hargaspl'] = $val['harga'];

                        if ($val['qtyspl'] == '')
                            $pod[$key]['totalspl'] = $val['harga'] * $val['qty'];
                        $pod[$key]['qtySupp'] = $pod[$key]['qtyspl'];
                        $pod[$key]['priceSupp'] = $pod[$key]['hargaspl'];
                        $pod[$key]['totalPriceSupp'] = $pod[$key]['qtyspl'] * $pod[$key]['hargaspl'];
                        $pod[$key]['valueppnSupp'] = $val['ppnspl'];
                        if ($val['ppnspl'] > 0)
                            $pod[$key]['statusppn'] = 'Y';

                        //get Remark from PR
                        $cekPr = $this->procurement->fetchRow("trano = '" . $val['pr_no'] . "' AND prj_kode = '" . $val['prj_kode'] . "' AND sit_kode = '" . $val['sit_kode'] . "' AND workid = '" . $val['workid'] . "' AND kode_brg = '" . $val['kode_brg'] . "'");
                        if ($cekPr) {
                            $cekPr = $cekPr->toArray();
                            $pod[$key]['ket_pr'] = $cekPr['ket'];
                        }
                    }

                    $sup = $this->trans->getAlamatSup($poh['sup_kode']);

                    $jsonData2[0]['user_approval'] = $userApp;
                    $alamat = $sup['alamat'] . " " . $sup['alamat2'];
                    $tlpsup = $sup['tlp'];
                    $faxsup = $sup['fax'];
                    $jsonData2[0]['alamat_sup'] = $alamat;
                    $jsonData2[0]['tlp_sup'] = $tlpsup;
                    $jsonData2[0]['fax_sup'] = $faxsup;

                    $jsonData2[0]['prj_kode'] = $poh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $poh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $poh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $poh['sit_nama'];
                    $jsonData2[0]['sup_kode'] = $poh['sup_kode'];
                    $jsonData2[0]['sup_nama'] = $poh['sup_nama'];

                    $jsonData2[0]['tgl'] = $poh['tgl'];
                    $jsonData2[0]['tgldeliesti'] = $poh['tgldeliesti'];
                    $jsonData2[0]['tujuan'] = $poh['deliverytext'];
                    $jsonData2[0]['payterm'] = $poh['paymentterm'];
                    $jsonData2[0]['ket'] = $poh['ket'];
                    $jsonData2[0]['rev'] = $poh['revisi'];
                    $jsonData2[0]['tax'] = $poh['statusppn'];
                    $jsonData2[0]['invoiceto'] = $poh['invoiceto'];
                    $jsonData2[0]['petugas'] = $poh['petugas'];
                    $jsonData2[0]['ketin'] = trim($poh['ketin']);


                    $temp = array();
                    foreach ($pod as $key => $val) {
                        $prjKode = $val['prj_kode'];
                        $temp[$prjKode][] = $val;
                    }
                    $pod = $temp;
                    $jsonData2[0]['trano'] = $approve;
//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $pod;
                    $this->view->trano = $approve;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->file = $file;
                    $this->view->preview = $preview;
                }
            }
        }
        
        if ($poh['revisi'] > 0)
            $this->view->isRevisi = true;
        $json = $this->getRequest()->getParam("posts");


        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
    }

    public function allprAction() {
        
    }

    public function allpoAction() {
        
    }

    public function allarfAction() {
        
    }

    public function insertarfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $comment = $this->_getParam("comment");
        
        $activitylog2 = new Admin_Models_Activitylog();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $urut = 1;
        //activity log
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
//    var_dump($items);die;
        $params = array(
            "workflowType" => "ARF",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
//         var_dump($params);die;
        $trano = $this->workflow->setWorkflowTransNew($params);
        
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "total" => $total,
                "ket" => $val['ket'],
                "cfs_kode" => $val['net_act'],
                "petugas" => $this->session->userName,
                "requester" => $val['requester'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $rate['rateidr']
            );
            $totals = $totals + $total;

            $this->arfd->insert($arrayInsert);
            // detail
            $activityDetail['procurement_arfd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['bank'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
        );
        $this->arfh->insert($arrayInsert);
        //header
        $activityHead['procurement_arfh'][0]=$arrayInsert;

        $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
          $activityLog = array(
            "menu_name" => "Create ARF",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertarfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
         $comment = $this->_getParam("comment");
        
        $activitylog2 = new Admin_Models_Activitylog();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
        
        //activity log
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $counter = new Default_Models_MasterCounter();

        if ($sales) {
            $menu_name='Sales';
            $tipe = 'S';
        } else {
            $menu_name='Over Head';
            $tipe = 'O';
        }

        $urut = 1;
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "ARFO",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "requester" => $val['requester'],
                "tipe" => $tipe,
                "rateidr" => $rate['rateidr']
            );
            $urut++;
            $totals = $totals + $total;

            $this->arfd->insert($arrayInsert);
            
            // detail
            $activityDetail['procurement_arfd'][$activityCount]=$arrayInsert;
            $activityCount++;
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['bank'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tipe" => $tipe
        );
        $this->arfh->insert($arrayInsert);
        
        //header
        $activityHead['procurement_arfh'][0]=$arrayInsert;
        
        $activityCount=0;
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                
                $activityFile['file'][$activityCount]=$arrayInsert;
                $activityCount++;
            }
        }
        
        $activityLog = array(
            "menu_name" => "Create ARF ".$menu_name,
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatearfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = array(
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "trano" => $jsonEtc[0]['trano']
        );
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => 'ARF',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);
        $urut = 1;
        $log['arf-detail-before'] = $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "total" => $total,
                "ket" => $val['ket'],
                "cfs_kode" => $val['net_act'],
                "petugas" => $this->session->userName,
                "requester" => $val['requester'],
                "val_kode" => $val['val_kode'],
                "rateidr" => $rate['rateidr']
            );
            $urut++;
            $totals = $totals + $total;

            $this->arfd->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['workid']}'");
        }
        $log2['arf-detail-after'] = $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['bank'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
        );
        $log['arf-header-before'] = $this->arfh->fetchRow("trano = '$trano'")->toArray();
        $this->arfh->update($arrayInsert, "trano = '$trano'");
        $log2['arf-header-after'] = $this->arfh->fetchRow("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatearfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        if ($sales) {
            $tipe = 'S';
        } else {
            $tipe = 'O';
        }

        $items = array(
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "trano" => $jsonEtc[0]['trano'],
            "rev" => 0,
            "budgettype" => $jsonEtc[0]['budgettype']
        );
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        
        $params = array(
            "workflowType" => 'ARFO',
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        $urut = 1;
        $log['arf-detail-before'] = $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {
            
            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "total" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "requester" => $val['requester'],
                "tipe" => $tipe,
                "rateidr" => $rate['rateidr']
            );
            $urut++;
            $totals = $totals + $total;

            $this->arfd->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['budgetid']}'");
        }
        $log2['arf-detail-after'] = $this->arfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['bank'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tipe" => $tipe
        );
        $log['arf-header-before'] = $this->arfh->fetchRow("trano = '$trano'")->toArray();
        $this->arfh->update($arrayInsert, "trano = '$trano'");
        $log2['arf-header-after'] = $this->arfh->fetchRow("trano = '$trano'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function apparfAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;
        $doc_file = $this->getRequest()->getParam("doc_file");
        $this->view->doc_file = $doc_file;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ARF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $k => $v) {
                $jsonData[$k]['cfs_kode'] = $v['net_act'];
                $jsonData[$k]['cfs_nama'] = $v['net_act'];
            }

            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etcResult = $etc;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } elseif ($approve || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->workflowTrans->fetchRow("item_id = '$tranoShow'", array("date DESC"));
            } else
                $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                    $arfh = $this->arfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($arfd) {
                        foreach ($arfd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $arfd[$key]['uom'] = $barang['sat_kode'];
                            }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                            $arfd[$key]['priceArf'] = $val['harga'];
                            $arfd[$key]['totalARF'] = $val['total'];
                            $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                        $jsonData2[0]['valuta'] = $arfh['val_kode'];
                        $jsonData2[0]['mgr_kode'] = $arfh['request'];
                        $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                        $jsonData2[0]['ketin'] = $arfh['ketin'];

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

                        $cusKode = $this->project->getProjectAndCustomer($arfh['prj_kode']);
//                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;

//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];

                        if ($arfh['bt'] == 'Y' && $arfh['trano_ref']) {
                            $this->view->isBT = true;
                            $this->view->bt_trano = $arfh['trano_ref'];
                        }

                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $arfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apparfbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject = array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ARFO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

            foreach ($jsonData as $key => $val) {
                $jsonData[$key]['cfs_kode'] = $val['net_act'];
                $jsonData[$key]['cfs_nama'] = $val['net_act'];
                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $jsonData[$key][$key2] = '';
                    if (strpos($val2, "\"") !== false)
                        $jsonData[$key][$key2] = str_replace("\"", " inch", $jsonData[$key][$key2]);
                    if (strpos($val2, "'") !== false)
                        $jsonData[$key][$key2] = str_replace("'", " inch", $jsonData[$key][$key2]);
                }
            }
            
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;

            if ($from == 'edit') {
                $this->view->edit = true;
            }

            if ($sales == 'true') {
                $this->view->sales = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $potong = substr($approve, 0, 5);

                    $arfd = $this->arfd->fetchAll("trano = '$approve'")->toArray();
                    $arfh = $this->arfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($arfd) {
                        foreach ($arfd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $arfd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $arfd[$key]['priceArf'] = $val['harga'];
                            $arfd[$key]['totalARF'] = $val['total'];
                            $arfd[$key]['budgetid'] = $val['workid'];
                            $arfd[$key]['budgetname'] = $val['workname'];
                            $arfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $arfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $arfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $arfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $arfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $arfh['budgettype'];
                        $jsonData2[0]['valuta'] = $arfh['val_kode'];

                        $jsonData2[0]['mgr_kode'] = $arfh['request'];
                        $jsonData2[0]['pic_kode'] = $arfh['orangpic'];
                        $jsonData2[0]['ketin'] = $arfh['ketin'];

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;
                        $jsonData2[0]['trano'] = $approve;

                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->sales = ($sales == 'true' || $arfh['tipe']=='S') ? true : false;
                        $this->view->lastReject = $lastReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $arfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
            
               
        if ($arfh['statrevisi'] > 0 || $this->isUpdate($approve)!=0){
        $this->view->isRevisi = true;
        }
        }
    }
    
    public function isUpdate($trano)
    {
            
        $sql = "SELECT COUNT(*) As exist FROM erpdb.log_transaction WHERE trano='$trano' ";
      
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();    

        return $data['exist'];

    }

    public function appasfAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ASF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $approve2 = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");

            $etc = str_replace("\\", "", $etc);
            $etc = preg_replace('/(\'|&#0*39;)/', '', $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);
            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            $this->view->jsonFile = $this->getRequest()->getParam('filedata');
            $this->view->file = $filedata;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $asfdd = $this->asf->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->asfc->fetchAll("trano = '$approve'")->toArray();
                    $asfh = $this->asfH->fetchRow("trano = '$approve'");

                    if ($asfdd) {
                        foreach ($asfdd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfdd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfdd[$key]['price'] = $val['harga'];
                            $asfdd[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];

                            $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();

                            foreach ($asfD as $keys => $vals) {
                                $asfdd[$key]['totalPriceInArfh'] = $asfdd[$key]['totalPriceInArfh'] + $vals['requestv'];
                            }

                            foreach ($val as $key2 => $val2) {
                                if ($val2 == "\"\"")
                                    $asfdd[$key][$key2] = '';
                                if ($val2 == '""')
                                    $asfdd[$key][$key2] = '';
                            }
                        }
                    }

                    if ($asfddcancel) {
                        foreach ($asfddcancel as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfddcancel[$key]['price'] = $val['harga'];
                            $asfddcancel[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];

                            $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();
                            foreach ($asfD as $keys => $vals) {
                                $asfddcancel[$key]['totalPriceInArfh'] = $asfddcancel[$key]['totalPriceInArfh'] + $vals['requestv'];
                            }

                            foreach ($val as $key2 => $val2) {
                                if ($val2 == "\"\"")
                                    $asfddcancel[$key][$key2] = '';
                                if ($val2 == '""')
                                    $asfddcancel[$key][$key2] = '';
                            }
                        }
                    }
                    $jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
                    $jsonData2[0]['ket'] = $asfh['ket'];
                    $jsonData2[0]['petugas'] = $asfh['petugas'];
                    $jsonData2[0]['requester'] = $asfh['request2'];
                    $jsonData2[0]['arf_no'] = $asfh['arf_no'];

                    $jsonData2[0]['tgl'] = $asfh['tgl'];
                    $jsonData2[0]['tgl_arf'] = $asfh['tglarf'];
                    $jsonData2[0]['pic'] = $asfh['orangpic'];
                    $jsonData2[0]['finance'] = $asfh['orangfinance'];

                    $jsonData2[0]['trano'] = $approve;
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];

                    Zend_Loader::loadClass('Zend_Json');
                    $jsonRev = Zend_Json::encode($asfdd);
                    $jsonRev2 = Zend_Json::encode($asfddcancel);


                    $filedata = $this->files->fetchAll("trano = '$approve'");
                    $this->view->lastReject = $lastReject;
                    $this->view->etc = $jsonData2;
                    unset($jsonData2[0]['user_approval']);
                    $this->view->revetc = $jsonData2;

                    $this->view->result = $asfdd;
                    $this->view->trano = $approve;
                    $this->view->result2 = $asfddcancel;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->file = $filedata;
                    $this->view->jsonRev = $jsonRev;
                    $this->view->jsonRev2 = $jsonRev2;
                    if ($filedata == '')
                        $filedata = array();
                    else
                        $filedata = $filedata->toArray();

                    $this->view->jsonFile = Zend_Json::encode(array('data' => $filedata, 'count' => count($filedata)));
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
        
        $this->view->approve = $approve2;

        $json = $this->getRequest()->getParam("posts");


        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
    }
    
    public function getasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");       
        $asfdd = $this->asf->fetchAll("trano = '$trano'")->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'")->toArray();
        $asfh = $this->asfH->fetchRow("trano = '$trano'");
        $filedata = $this->files->fetchAll("trano = '{$asfh['caption_id']}'");
        $jsonData2 = array();

       

        if ($asfdd) {
            foreach ($asfdd as $key => $val) {
                $barang = $this->barang->fetchRow("kode_brg = '{$val['kode_brg']}'");
                
                if ($barang) {
                    $asfdd[$key]['uom'] = $barang['sat_kode'];
                }

                $asfdd[$key]['price'] = $val['harga'];
                $asfdd[$key]['totalPrice'] = $val['total'];

                $arf = $val['arf_no'];

                $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();

                foreach ($asfD as $keys => $vals) {
                    $asfdd[$key]['totalPriceInArfh'] = $asfdd[$key]['totalPriceInArfh'] + $vals['requestv'];
                }

                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfdd[$key][$key2] = '';
                    if ($val2 == '""')
                        $asfdd[$key][$key2] = '';
                }
            }
        }

        if ($asfddcancel) {
            foreach ($asfddcancel as $key => $val) {
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                }

                $asfddcancel[$key]['price'] = $val['harga'];
                $asfddcancel[$key]['totalPrice'] = $val['total'];

                $arf = $val['arf_no'];

                $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();
                foreach ($asfD as $keys => $vals) {
                    $asfddcancel[$key]['totalPriceInArfh'] = $asfddcancel[$key]['totalPriceInArfh'] + $vals['requestv'];
                }

                foreach ($val as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $asfddcancel[$key][$key2] = '';
                    if ($val2 == '""')
                        $asfddcancel[$key][$key2] = '';
                }
            }
        }
        
        $jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
        $jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
        $jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
        $jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
        $jsonData2[0]['ket'] = $asfh['ket'];
        $jsonData2[0]['petugas'] = $asfh['petugas'];
        $jsonData2[0]['requester'] = $asfh['request2'];
        $jsonData2[0]['arf_no'] = $asfh['arf_no'];
        $jsonData2[0]['tgl'] = $asfh['tgl'];
        $jsonData2[0]['tgl_arf'] = $asfh['tglarf'];
        $jsonData2[0]['pic'] = $asfh['orangpic'];
        $jsonData2[0]['finance'] = $asfh['orangfinance'];
        $jsonData2[0]['trano'] = $trano;
        
        if ($filedata == '')
            $filedata = array();
        else
            $filedata = $filedata->toArray();

        Zend_Loader::loadClass('Zend_Json');
        $json['posts'] = Zend_Json::encode($asfdd);
        $json['posts2'] = Zend_Json::encode($asfddcancel);
        $json['etc'] = Zend_Json::encode($jsonData2);
        $json['file'] = Zend_Json::encode(array('data' => $filedata, 'count' => count($filedata)));
        $jsonData = Zend_Json::encode($json);
        echo $jsonData;
    }

    public function appasfbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ASFO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
            $etc = str_replace("\\", "", $etc);
            $etc = preg_replace('/(\'|&#0*39;)/', '', $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

//            $files = $this->getRequest()->getParam("file");
            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');
            $this->view->jsonFile = $this->getRequest()->getParam('filedata');
            $this->view->file = $filedata;


            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            
            if ($from == 'edit') {
                $this->view->edit = true;
            }

            if ($sales) {
                $this->view->sales = true;
            }
        } else {
            $docs = $this->workflowTrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $potong = substr($approve, 0, 5);
                    if ($potong == 'ASF01')
                        $this->view->sales = true;

                    $asfdd = $this->asf->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->asfc->fetchAll("trano = '$approve'")->toArray();
                    $asfh = $this->asfH->fetchRow("trano = '$approve'");
                    if ($asfdd) {
                        foreach ($asfdd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfdd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfdd[$key]['price'] = $val['harga'];
                            $asfdd[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];
                            $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();

                            foreach ($asfD as $keys => $vals) {
                                $asfdd[$key]['totalPriceInArfh'] = $asfdd[$key]['totalPriceInArfh'] + $vals['requestv'];
                            }

                            foreach ($val as $key2 => $val2) {
                                if ($val2 == "\"\"")
                                    $asfdd[$key][$key2] = '';
                                if ($val2 == '""')
                                    $asfdd[$key][$key2] = '';
                            }
                        }
                    }

                    if ($asfddcancel) {
                        foreach ($asfddcancel as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfddcancel[$key]['price'] = $val['harga'];
                            $asfddcancel[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];

                            $asfD = $this->asfD->fetchAll("arf_no = '$arf'")->toArray();
                            foreach ($asfD as $keys => $vals) {
                                $asfddcancel[$key]['totalPriceInArfh'] = $asfddcancel[$key]['totalPriceInArfh'] + $vals['requestv'];
                            }

                            foreach ($val as $key2 => $val2) {
                                if ($val2 == "\"\"")
                                    $asfddcancel[$key][$key2] = '';
                                if ($val2 == '""')
                                    $asfddcancel[$key][$key2] = '';
                            }
                        }
                    }
                    $jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
                    $jsonData2[0]['ket'] = $asfh['ket'];
                    $jsonData2[0]['petugas'] = $asfh['petugas'];
                    $jsonData2[0]['requester'] = $asfh['request2'];
                    $jsonData2[0]['arf_no'] = $asfh['arf_no'];

                    $jsonData2[0]['tgl'] = $asfh['tgl'];
                    $jsonData2[0]['tgl_arf'] = $asfh['tglarf'];
                    $jsonData2[0]['pic'] = $asfh['orangpic'];
                    $jsonData2[0]['finance'] = $asfh['orangfinance'];
//		   				$jsonData2[0]['payterm'] = $asfh['paymentterm'];

                    Zend_Loader::loadClass('Zend_Json');
                    $jsonRev = Zend_Json::encode($asfdd);
                    $jsonRev2 = Zend_Json::encode($asfddcancel);


                    $jsonData2[0]['trano'] = $approve;
//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    unset($jsonData2[0]['user_approval']);
                    $this->view->revetc = $jsonData2;

                    $this->view->result = $asfdd;
                    $this->view->trano = $approve;
                    $this->view->result2 = $asfddcancel;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->jsonRev = $jsonRev;
                    $this->view->jsonRev2 = $jsonRev2;

                    $filedata = $this->files->fetchAll("trano = '$approve'");
                    $this->view->file = $filedata;
                    if ($file == '')
                        $file = array();
                    else
                        $file = $file->toArray();

                    $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }

        $json = $this->getRequest()->getParam("posts");


        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::decode($json);
    }

    public function pmealAction() {
        
    }

    public function addpmealAction() {
        
    }

    public function editpmealAction() {
        $trano = $this->getRequest()->getParam("trano");
        $pmeald = $this->pmeal->fetchAll("notran = '$trano'", array("urut ASC"))->toArray();
        $pmealh = $this->pmealH->fetchRow("notran = '$trano'");

        if ($pmeald) {
            foreach ($pmeald as $key => $val) {
                $pmeald[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $pmeald[$key]['uom'] = $barang['sat_kode'];
                    $pmeald[$key]['nama_brg'] = $barang['nama_brg'];
                }

                $pmeald[$key]['boq_no'] = $val['boqtran'];
                $pmeald[$key]['price'] = $val['harga_borong'];
                $pmeald[$key]['totalPrice'] = $val['qty'] * $val['harga_borong'];
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($pmeald);

            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $this->view->trano = $trano;
            $this->view->tgl = $pmealh['tgl'];

            $pmealh['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $pmealh['ket']);
            $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $pmealh['ket']);

//	       	$this->view->ket = $pmealh['ket'];
            $this->view->prj_nama = $pmealh['prj_nama'];
            $this->view->sit_nama = $pmealh['sit_nama'];
        }
    }

    public function apppmealAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/PMEAL';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;

            if ($from == 'edit') {
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
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $pmeald = $this->pmeal->fetchAll("notran = '$approve'")->toArray();
                    $pmealh = $this->pmealH->fetchRow("notran = '$approve'");
                    if ($pmeald) {
                        foreach ($pmeald as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $pmeald[$key]['uom'] = $barang['sat_kode'];
                                $pmeald[$key]['nama_brg'] = $barang['nama_brg'];
                            }
                            $pmeald[$key]['totalPrice'] = $pmeald[$key]['harga_borong'] * $pmeald[$key]['qty'];
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $pmealh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $pmealh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $pmealh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $pmealh['sit_nama'];

                        $jsonData2[0]['ket'] = $pmealh['ket'];

                        $jsonData2[0]['trano'] = $approve;
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $pmeald;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->preview = $preview;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function insertpmealAction() {
        $this->_helper->viewRenderer->setNoRender();
        $comment = $this->_getParam("comment");

        
        $activitylog2 = new Admin_Models_Activitylog();
        
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('PBOQ3');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'PBQ110-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

         $params = array(
            "workflowType" => "PBOQ3",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'PBOQ3', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);
        $urut = 1;
         //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        
        $totals = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['price'];

            $total = $val['qty'] * $harga;
            $arrayInsert = array(
                "notran" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "kode_brg" => $val['kode_brg'],
                "harga_borong" => $harga,
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "qty" => $val['qty'],
                "boqtran" => $val['boq_no'],
                "stspmeal" => $val['stspmeal']
            );
            $urut++;
            $totals = $totals + $total;
//                var_dump($arrayInsert);die;
            $this->pmeal->insert($arrayInsert);
             // detail
             $activityDetail['boq_dboqpasang'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }

        $arrayInsert = array(
            "notran" => $trano,
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "tgl" => date('Y-m-d'),
            "jumlah" => $totals,
            "boqtran" => $jsonEtc[0]['boq_no'],
        );
        $this->pmealH->insert($arrayInsert);
         //header
        $activityHead['boq_hboqpasang'][0]=$arrayInsert;
        
        //save activity
         $activityLog = array(
            "menu_name" => "Create pmeal",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

 public function updatepmealAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
//       $json = $this->getRequest()->getParam('posts');
        $etc = str_replace("\\", "", $etc);
//       $json = str_replace("\\","",$json);
        $jsonData = Zend_Json::decode($this->json);
//        $jsonData = Zend_Json::decode($json);
        $jsonEtc = Zend_Json::decode($etc);

        $trano = $jsonEtc[0]['trano'];

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'PBOQ3', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        } elseif (is_array($result) && count($result) > 0) {

            $hasil = Zend_Json::encode($result);
            $this->getResponse()->setBody("{success: true, user:$hasil}");
            return false;
        }
   // fetch data before simpan di aaray log
//      $log['pmeal-detail-before'] = $this->pmeal->fetchAll("trano = '$trano'")->toArray();
        $totals = 0;
        foreach ($jsonData as $key => $val) {
            $harga = $val['price'];

            $total = $val['qty'] * $harga;
       
            $arrayInsert = array(
                "qty" => $val['qty'],
                "harga_borong" => $harga,
            );
            $totals = $totals + $total;

            $kode_brg = $val['kode_brg'];

            $this->pmeal->update($arrayInsert, "notran = '$trano' AND kode_brg = '$kode_brg'");
           
        }
        
 // fetch data after simpan di array log  
//        $log2['pmeal-detail-after'] = $this->pmeal->fetchAll("trano = '$trano'")->toArray();
        
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "jumlah" => $totals,
        );
// simpan array log before dan after ke database  
//        $log['pmeal-header-before'] = $this->pmealH->fetchRow("trano = '$trano'")->toArray();
        $result = $this->pmealH->update($arrayInsert, "notran = '$trano'");
        
//        $log2['pmeal-header-after'] = $this->pmealH->fetchRow("trano = '$trano'")->toArray();
        
//        
//        $jsonLog = Zend_Json::encode($log);
//        $jsonLog2 = Zend_Json::encode($log2);
//        $arrayLog = array(
//            "trano" => $trano,
//            "uid" => $this->session->userName,
//            "tgl" => date('Y-m-d H:i:s'),
//            "prj_kode" => $jsonEtc[0]['prj_kode'],
//            "sit_kode" => $jsonEtc[0]['sit_kode'],
//            "action" => "UPDATE",
//            "data_before" => $jsonLog,
//            "data_after" => $jsonLog2,
//            "ip" => $_SERVER["REMOTE_ADDR"],
//            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
//        );
//        $this->log->insert($arrayLog);
//        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    public function deletefileAction() {        
        $this->_helper->viewRenderer->setNoRender();
        $fileName = $this->getRequest()->getParam('filename');
        $id = $this->getRequest()->getParam('id');
        
        if(is_null($id) || $id == 1){
            $savePath = Zend_Registry::get('uploadPath') . 'files';
            $myFiles = $savePath . "/" . $fileName;
            if (file_exists($myFiles)) {
                unlink($myFiles);
                $return = array('success' => true);
            } else {
                $return = array('success' => false, 'msg' => 'File Not Found!');
            }

            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($return);
            echo $json;
        } else {
            $savePath = Zend_Registry::get('uploadPath');
            $result = $this->files->fetchRow("id = $id");
            if ($result) {
                $result = $result->toArray();
                $path = "files";
                $myFiles = $this->savePath . "/files/" . $result['savename'];
                if (file_exists($myFiles)) {
                    unlink($myFiles);
                    $return['success'] = true;
                } else {
                    $return['success'] = true;
                }
                $this->files->delete("id = $id");
            } else {
                $return['success'] = false;
                $return['msg'] = "File not found!!";
            }

            $return = Zend_Json::encode($return);
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($return);
        }   
        
    }
    
    public function uploadfileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $type = $this->getRequest()->getParam('type');

        $success = 'false';
        $msg = '';

        if ($_FILES['file-path']['name'] != '' && $_FILES['file-path']['size'] != '') {
            if ($_FILES['file-path']['error'] === UPLOAD_ERR_INI_SIZE) {
                $return = array('success' => false, 'msg' => 'Error while uploading Your file, Your file is too large..');
            } else {

                $result = $this->upload->uploadFile($_FILES, 'file-path', true, 'files');
                if ($result) {
                    $savePath = Zend_Registry::get('uploadPath') . 'files';
                    $myFiles = $savePath . "/" . $result['save_name'];

                    $name = explode(".", $result['origin_name']);
                    $fileName = $name[0];
                    $fileName = preg_replace("/[^a-zA-Z0-9\s]/", "_", $fileName);
                    $name[0] = $fileName;
                    $newName = implode(".", $name);
                    $return = array('success' => true, 'filename' => $newName, 'path' => $myFiles, 'savename' => $result['save_name']);
                } else
                    $return = array('success' => false, 'msg' => 'Error while uploading Your file, Please contact Administrator or Support Team.');
            }
        }
        else {
            $return = array('success' => false);
        }
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        echo $json;
//        switch($type)
//        {
//            case 'PO':
//
//                break;
//            default:
//                $msg = 'No Such Transaction!';
//                break;
//        }
    }

    public function reimbursAction() {
        
    }

    public function addreimbursAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
              $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->reimburs = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function appreimbursAction() {

        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/Reimburs';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));


            $cus = new Default_Models_MasterCustomer();
            $jsonData2[0]['cus_nama'] = $cus->getName($jsonData2[0]['cus_kode']);

//            $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
//            $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//            $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->file = $filedata;
            $this->view->JsonFile = $this->getRequest()->getParam('filedata');
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');

            if ($from == 'edit') {
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
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    $this->workflowTrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $reimbursd = $this->reimbursD->fetchAll("trano = '$approve'")->toArray();
                    $reimbursh = $this->reimbursH->fetchRow("trano = '$approve'");
                    if ($reimbursd) {
                        foreach ($reimbursd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $reimbursd[$key]['uom'] = $barang['sat_kode'];
                            }
//                                $arfd[$key]['priceArf'] = $val['priceArf'];
                            $reimbursd[$key]['harga'] = $val['harga'];
                        }

                        $jsonData2[0] = $reimbursh->toArray();

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['valuta'] = $reimbursh['val_kode'];
                        $jsonData2[0]['requester'] = $reimbursh['request2'];
                        $jsonData2[0]['mgr_kode'] = $reimbursh['request'];
                        $jsonData2[0]['pic_kode'] = $reimbursh['orangpic'];

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

//                        $cusKode = $this->project->getProjectAndCustomer($reimbursh['prj_kode']);
//                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
//                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];

                        $cus = new Default_Models_MasterCustomer();
                        $jsonData2[0]['cus_nama'] = $cus->getName($jsonData2[0]['cus_kode']);
//                        $jsonData2[0]['trano'] = $approve;

                        $filedata = $this->files->fetchAll("trano = '$approve'");

//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $reimbursd;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->file = $filedata;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function insertreimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");

        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $counter = new Default_Models_MasterCounter();
        $filedata = Zend_Json::decode($this->getRequest()->getParam('file'));
        $jsonFile = Zend_Json::decode($file);



        $lastTrans = $counter->getLastTrans('REM');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'REM01-' . $last;


        $ada = false;
        while (!$ada) {
            $cek = $this->reimbursD->fetchRow("trano = '$trano'");
            if ($cek) {
                $lastTrans = $counter->getLastTrans('REM');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'REM01-' . $last;
            } elseif (!$cek)
                $ada = true;
        }

        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);
        $urut = 1;
        $total = 0;
        $totals = 0;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "REM",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'REM', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }

        foreach ($jsonData as $key => $val) {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "cus_kode" => $jsonEtc[0]['cus_kode'],
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "type" => 'P'
            );
            $urut++;
            $totals = $totals + $total;

//                var_dump($arrayInsert);die;
            $this->reimbursD->insert($arrayInsert);
            $activityDetail['procurement_reimbursementd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }

        $cusKode = $this->project->getProjectAndCustomer($jsonEtc[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "cus_kode" => $jsonEtc[0]['cus_kode'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['namabank'],
            "rekbank" => $jsonEtc[0]['rekbank'],
            "reknamabank" => $jsonEtc[0]['reknamabank'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "request2" => $jsonEtc[0]['requester'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "type" => 'P'
        );
        $this->reimbursH->insert($arrayInsert);
        $activityHead['procurement_reimbursementh'][0]=$arrayInsert;

        $activityCount=0;
        if (count($filedata) > 0) {
            foreach ($filedata as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
                $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
   $activityLog = array(
            "menu_name" => "Create Reimbursement to Customer",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

public function updatereimbursAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($this->getRequest()->getParam('file'));
        $jsonDeletedFile = Zend_Json::decode($this->getRequest()->getParam('deletefile'));

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

         $params = array(
            "workflowType" => "REM",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "itemID" => $trano,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);
//        $result = $this->workflow->setWorkflowTrans($trano, 'REM', '', $this->const['DOCUMENT_RESUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $urut = 1;
        // fetch data before
      $log['rem-detail-before'] = $this->reimbursD->fetchAll("trano = '$trano'")->toArray();
        
        $this->reimbursD->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

            $total = $val['qty'] * $val['harga'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "jumlah" => $total,
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "type" => 'P'
            );
            $urut++;
            $totals = $totals + $total;

            $this->reimbursD->insert($arrayInsert);
        }
        // fetch data after
         $log2['rem-detail-after'] = $this->reimbursD->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
//                 "trano" => $trano,
//                 "tgl" => date('Y-m-d'),
//                 "prj_kode" => $jsonEtc[0]['prj_kode'],
//                 "prj_nama" => $jsonEtc[0]['prj_nama'],
//                 "sit_kode" => $jsonEtc[0]['sit_kode'],
//                 "sit_nama" => $jsonEtc[0]['sit_nama'],
            "namabank" => $jsonEtc[0]['bank'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
//                 "val_kode" => $jsonEtc[0]['valuta'],
            "penerima" => $jsonEtc[0]['penerima'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "request2" => $jsonEtc[0]['requester'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "type" => 'P'
        );
        // fetch data before
        $log['rem-header-before'] = $this->reimbursH->fetchRow("trano = '$trano'")->toArray();
        $this->reimbursH->update($arrayInsert, "trano = '$trano'");
//             $this->arfh->insert($arrayInsert);
        // fetch data after
        $log2['rem-header-after'] = $this->reimbursH->fetchRow("trano = '$trano'")->toArray();
      
        // save data log
             $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);
        
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonEtc[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function editreimbursAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;

        $trano = $this->getRequest()->getParam("trano");
        $reimbursh = $this->reimbursH->fetchRow("trano = '$trano'");
        $reimbursd = $this->reimbursD->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();

        $where = "rem_no = '$trano'";
        $paymentquery = $this->paymentreimbursH->fetchAll($where)->toArray();
        if ($paymentquery) {

            foreach ($paymentquery as $key => $val) {
                $total += $val['total'];
                $paymenttrano[] = $val['trano'];
            }
            $paymenttrano = implode(',', $paymenttrano);
        } else {
            $total = 0;
            $paymenttrano = '';
        }

        $this->view->totalpayment = $total;
        $this->view->tranopayment = $paymenttrano;
        if ($reimbursh)
            $reimbursh = $reimbursh->toArray();
        $tmp = array();

        foreach ($reimbursd as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $reimbursd[$key][$key2] = '';
            }
            $reimbursd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];


            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $reimbursd[$key]['uom'] = $barang['sat_kode'];
            }
        }
        foreach ($reimbursh as $key => $val) {
            if ($val == '""')
                $reimbursh[$key] = '';
        }
        $tmp2 = $reimbursh;
        unset($reimbursh);
        $reimbursh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($reimbursd);
        $jsonData2 = Zend_Json::encode($reimbursh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }

        $files = $this->files->fetchAll("trano = '$trano'")->toArray();

        $JsonFile = Zend_Json::encode($files);

        $this->view->prNo = $tmp;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($reimbursh[0]['tgl']));
        $this->view->pr_no = $reimbursh[0]['pr_no'];
        $this->view->val_kode = $reimbursh[0]['val_kode'];
        $this->view->request = $reimbursh[0]['request'];
        $this->view->orangfinance = $reimbursh[0]['orangfinance'];
        $this->view->ket = $reimbursh[0]['ket'];
        $this->view->jsonfile = $JsonFile;
    }

    public function cancelpoAction() {
        $this->view->user = $this->session->userName;
    }

    public function cancelrpiAction() {
        $this->view->user = $this->session->userName;
    }

    public function dorequestcancelpoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $user = $this->getRequest()->getParam('user');
        $ponumber = $this->getRequest()->getParam('po-number');
        $reason = $this->getRequest()->getParam('cancel-reason');
        
        $activityHead=array();

        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER["REMOTE_ADDR"];
        $hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $insertcancel = array(
            "uid" => $user,
            "date" => $date,
            "trano" => $ponumber,
            "reason" => $reason,
            "ip" => $ip,
            "hostname" => $hostname,
            "type" => 'PO',
        );

        $this->requestcancel->insert($insertcancel);
        $activityHead['request_cancel'][0]=$insertcancel;

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        
        $activityLog = array(
            "menu_name" => "Request Cancel PO",
            "trano" => $ponumber,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" =>'',
            "file" => '',
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $this->logActivity->insert($activityLog);
         
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dorequestcancelrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $user = $this->getRequest()->getParam('user');
        $rpinumber = $this->getRequest()->getParam('rpi-number');
        $reason = $this->getRequest()->getParam('cancel-reason');

        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER["REMOTE_ADDR"];
        $hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $insertcancel = array(
            "uid" => $user,
            "date" => $date,
            "trano" => $rpinumber,
            "reason" => $reason,
            "ip" => $ip,
            "hostname" => $hostname,
            "type" => 'RPI'
        );

        $this->requestcancel->insert($insertcancel);

        $return = array('success' => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function historyPoRevisiAction() {
        $trano = $this->getRequest()->getParam("trano");

        $cek = $this->ADMIN->Logtransaction->fetchAll("trano = '$trano'", array("tgl ASC"))->toArray();
        
        if (count($cek)) 
        {
            $count = 1;
            $arrayBefore = array();
            $arrayAfter = array();
            
            foreach ($cek as $k => $v) {
                
                $before = Zend_Json::decode($v['data_before']);
                $after = Zend_Json::decode($v['data_after']);
                            
                $arrayAfter[$count]['header'] = $after['po-header-after'];
                $arrayAfter[$count]['detail'] = $after['po-detail-after'];
                
                $arrayBefore[$count]['header'] = $before['po-header-before'];
                $arrayBefore[$count]['detail'] = $before['po-detail-before'];
                
                switch ($v['action']) {
                    case 'UPDATE':
                        $action = 'Revisi';
                        break;
                    case 'CANCEL':
                        $action = 'Cancel PO';
                        break;
                    default:
                        $action = $v['action'];
                        break;
                }
                
                $arrayAfter[$count]['action'] = $action;
                $arrayAfter[$count]['uid'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrayAfter[$count]['tgl'] = date("d M Y H:i:s", strtotime($v['tgl']));
                
                $arrayCombo[] = array(
                    "id" => $count,
                    "name" => "$count".' - '."{$v['tgl']}"
                );

                $count++;
            }
            
            $this->view->jsonCombo = Zend_Json::encode(array("posts" => $arrayCombo));
            $this->view->json = Zend_Json::encode($arrayBefore);
            $this->view->json2 = Zend_Json::encode($arrayAfter);
            $this->view->trano = $trano;
            $this->view->lastRev = ($count-1);
            
            
        } else
            $this->view->noData = true;

        
    }
    
    public function oldHistoryPoRevisiAction() {
        $trano = $this->getRequest()->getParam("trano");

        $po = $this->DEFAULT->ProcurementPoh->fetchRow("trano = '$trano'");
        if ($po)
            $po = $po->toArray();
        $pod = $this->DEFAULT->ProcurementPod->fetchAll("trano = '$trano'");
        if ($pod)
            $pod = $pod->toArray();
        $cek = $this->ADMIN->Logtransaction->fetchAll("trano = '$trano'", array("tgl ASC"))->toArray();

        $arrayRev = array();
        $arrayCombo = array();
        $arrayRev[0]['header'] = $po;
        $arrayRev[0]['detail'] = $pod;
        $arrayRev[0]['uid'] = $po['petugas'];
        $arrayRev[0]['tgl'] = date("d M Y H:i:s", strtotime($pod[0]['tgl']));

        if ($cek) {
            
            $count = 1;
            foreach ($cek as $k => $v) {
                
                $before = Zend_Json::decode($v['data_before']);
                $after = Zend_Json::decode($v['data_after']);
                
                if(count($after['po-header-after']) > 0)
                {
                    foreach ($after['po-header-after'] as $k2 => $v2) {
                        if ($v2 == '""')
                            $after['po-header-after'][$k2] = '';
                    }
                }
                
                if(count($after['po-detail-after']) > 0)
                {
                    foreach ($after['po-detail-after'] as $k2 => $v2) {
                        $after['po-detail-after'][$k2]['totalspl'] = $v2['qtyspl'] * $v2['hargaspl'];
                        foreach ($v2 as $k3 => $v3) {
                            if ($v3 == '""')
                                $after['po-detail-after'][$k2][$k3] = '';
                        }
                    }
                }

                $arrayRev[$count]['header'] = $after['po-header-after'];
                $arrayRev[$count]['detail'] = $after['po-detail-after'];
                switch ($v['action']) {
                    case 'UPDATE':
                        $action = 'Revisi';
                        break;
                    case 'CANCEL':
                        $action = 'Cancel PO';
                        break;
                    default:
                        $action = $v['action'];
                        break;
                }
                $arrayRev[$count]['action'] = $action;
                $arrayRev[$count]['uid'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrayRev[$count]['tgl'] = date("d M Y H:i:s", strtotime($v['tgl']));

                $count++;
            }

            foreach ($arrayRev as $k => $v) {
                if ($k == 0)
                    $text = 'Current';
                else
                    $text = $k;
                $arrayCombo[] = array(
                    "id" => $k,
                    "name" => "$text - {$v['tgl']}"
                );
            }


            $this->view->jsonCombo = Zend_Json::encode(array("posts" => $arrayCombo));
            $this->view->dataRev = $arrayRev;
            $this->view->json = Zend_Json::encode($arrayRev);
            $this->view->trano = $trano;
            $this->view->lastRev = ($count - 1);
        } else
            $this->view->noData = true;
    }

    public function orderRevisiAction() {
        $data = $this->getRequest()->getParam("data_first");
        $data2 = $this->getRequest()->getParam("data_second");

        if ($data)
            $data = Zend_Json::decode($data);
        if ($data2)
            $data = Zend_Json::decode($data2);
    }

    public function getPoSummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");

        $return = array('success' => true);
        $data = $this->purchaseH->fetchRow("trano='$trano'");
        if ($data) {
            $data = $data->toArray();
            $return['posts'][] = $data;
        } else
            $return['success'] = false;

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateAsfFileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");
        $file = $this->_getParam("file");

        if ($file)
            $file = Zend_Json::decode($file);

        $return['success'] = true;
        if ($file) {
            foreach ($file as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => '',
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                if ($val['status'] == 'new')
                    $this->DEFAULT->Files->insert($arrayInsert);
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updateasfvalueAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $revisi = ($this->getRequest()->getParam("revisi") == 'true') ? true : false;

        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        
        $arf_trano_ref= '';
        $arf_caption_id= '';
        $caption_id='';
        $trano_ref= '';


        $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $trano = $jsonEtc[0]['trano'];

        $temp = array();
        if ($jsonData) {
            $log['asfdd-detail-before'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
            $this->asf->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];
                
                $arf_trano_ref= $val['arf_trano_ref'];
                $arf_caption_id= $val['arf_caption_id'];
                $caption_id=$val['caption_id'];
                $trano_ref= $val['trano_ref'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "arf_trano_ref" => $arf_trano_ref,
                    "arf_caption_id" => $arf_caption_id,
                    "caption_id" => $caption_id,
                    "trano_ref" => $trano_ref
                );                               
                $urut++;
                $this->asf->insert($arrayInsert);
            }
            $log2['asfdd-detail-after'] = $this->asf->fetchAll("trano = '$trano'")->toArray();
        }

        if ($jsonData2) {
            $log['asfddcancel-detail-before'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
            $this->asfc->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {

                $tranotemp = $val['arf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_arf'];
                    $temp[$tranotemp]['totalPriceInArfh'] = $val['totalPriceInArfh'];
                    $temp[$tranotemp]['totalPriceArf'] = $val['totalPriceArf'];
                } else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['arf_no'],
                    "tglarf" => $val['tgl_arf'],
                    "urut" => $urut2,
                    "prj_kode" => $val['prj_kode'],
                    "prj_nama" => $val['prj_nama'],
                    "sit_kode" => $val['sit_kode'],
                    "sit_nama" => $val['sit_nama'],
                    "workid" => $val['workid'],
                    "workname" => $val['workname'],
                    "kode_brg" => $val['kode_brg'],
                    "nama_brg" => $val['nama_brg'],
                    "qty" => $val['qty'],
                    "harga" => $val['price'],
                    "total" => $val['totalPrice'],
                    "ket" => $val['ket'],
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode'],
                    "arf_trano_ref" => $arf_trano_ref,
                    "arf_caption_id" => $arf_caption_id,
                    "caption_id" => $caption_id,
                    "trano_ref" => $trano_ref
                );         
                $urut2++;
                $this->asfc->insert($arrayInsert);
            }

            $log2['asfddcancel-detail-after'] = $this->asfc->fetchAll("trano = '$trano'")->toArray();
        }

        $log['asfd-detail-before'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();
        $this->asfD->delete("trano = '$trano'");

        $totalrevisi = 0;

        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceInArfh'];
            $totalrevisi = $totalrevisi + $val['total'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc[0]['prj_kode'],
                "prj_nama" => $jsonEtc[0]['prj_nama'],
                "sit_kode" => $jsonEtc[0]['sit_kode'],
                "sit_nama" => $jsonEtc[0]['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc[0]['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "arf_trano_ref" => $arf_trano_ref,
                "arf_caption_id" => $arf_caption_id,
                "caption_id" => $caption_id
            );                   
            $this->asfD->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->asfD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $totalheader = 0;
        if ($revisi)
            $totalheader = $totalrevisi;
        else
            $totalheader = $jsonEtc[0]['totalarfh'];


        $arrayInsert = array(
            "tgl" => date('Y-m-d'),
            "total" => $totalheader,
            "requestv" => $totalPriceArf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s')
        );
        $log['asf-header-before'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $this->asfH->update($arrayInsert, "trano = '$trano'");
        $log2['asf-header-after'] = $this->asfH->fetchRow("trano = '$trano'")->toArray();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log->insert($arrayLog);

        $return['success'] = true;
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getpolistAction() {
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
            $search = "tgl like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "prj_nama like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->purchaseH->finalpolist($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getrpilistAction() {
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
            $search = "tgl like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 3) {
            $search = "prj_kode like '%$textsearch%' ";
        } else if ($textsearch != null && $option == 4) {
            $search = "prj_nama like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $data = $this->rpiH->finalpolist($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getsupplierInvoiceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $textsearch = $this->getRequest()->getParam('search');


        $cekSuppInvExists = $this->rpiH->fetchRow("invoice_no LIKE '%$textsearch%'");
        if ($cekSuppInvExists)
            $return = array('success' => true);
        else
            $return = array('success' => false);


        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cekWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $item_type = $this->_getParam("item_type");
        $finalOnly = ($this->_getParam("final") !== 'true') ? false : true;

        $isFinal = QDC_Workflow_Transaction::factory()->isDocumentFinal($trano);
        $isReject = QDC_Workflow_Transaction::factory()->isDocumentReject($trano);
        $isExpired = QDC_Workflow_Transaction::factory()->isDocumentExpired($trano);
        $valid = true;
        if ($finalOnly) {
            if (!$isFinal) {
                $msg = "This Document is not Final Approval yet.";
                $valid = false;
            }
        } else {
            if (!$isReject || $isFinal) {
                $msg = "This Document is already Final Approval or not Rejected yet.";
                $valid = false;
            }
        }
        if($isExpired) {
                $msg = "Cann't Access! This Document more than 3 months didn't Resubmitted";
                $valid = false;
        }
        
        $result = Zend_Json::encode(array("success" => $valid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
    
    public function historyArfAction() {
        
        
        $trano = $this->getRequest()->getParam("trano");
        $cek = $this->ADMIN->Logtransaction->fetchAll("trano = '$trano'", array("tgl ASC"))->toArray();
       
     
        if (count($cek)) 
        {
            $count = 1;
            $arrayBefore = array();
            $arrayAfter = array();
            
            foreach ($cek as $k => $v) {
                
                $before = Zend_Json::decode($v['data_before']);
                $after = Zend_Json::decode($v['data_after']);
                            
                $arrayAfter[$count]['header'] = $after['arf-header-after'];
                $arrayAfter[$count]['detail'] = $after['arf-detail-after'];
                
                $arrayBefore[$count]['header'] = $before['arf-header-before'];
                $arrayBefore[$count]['detail'] = $before['arf-detail-before'];
                
                switch ($v['action']) {
                    case 'EDIT':
                        $action = 'Edit';
                        break;
                    case 'REVISI':
                        $action = 'Revisi';
                        break;
                }
                
                $arrayAfter[$count]['action'] = $action;
                $arrayAfter[$count]['uid'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
                $arrayAfter[$count]['tgl'] = date("d M Y H:i:s", strtotime($v['tgl']));
                
                $arrayCombo[] = array(
                    "id" => $count,
                    "name" => "$count".' - '."{$v['tgl']}"
                );

                $count++;
            }
            
            $this->view->jsonCombo = Zend_Json::encode(array("posts" => $arrayCombo));
            $this->view->json = Zend_Json::encode($arrayBefore);
            $this->view->json2 = Zend_Json::encode($arrayAfter);
            $this->view->trano = $trano;
            $this->view->lastRev = ($count-1);
            
            
        } else
            $this->view->noData = true;

        
    }
    
    
}

?>
