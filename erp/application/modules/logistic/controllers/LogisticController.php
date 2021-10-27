<?php

class Logistic_LogisticController extends Zend_Controller_Action {

    private $logistic;
    private $logisticH;
    private $logisticDo;
    private $logisticDoH;
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
    private $ican;
    private $icanH;
    private $ilov;
    private $ilovH;
    private $const;
    private $supplier;
    private $isupp;
    private $isuppH;
    private $error;
    private $trans;
    private $files;
    private $counter;
    private $log;
    private $logActivity;
    private $quantity;
    private $LOGISTIC;
    private $DEFAULT;

    public function init() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
        //$this->leadHelper = $this->_helper->getHelper('chart');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->error = $this->_helper->getHelper('error');
        $this->session = new Zend_Session_Namespace('login');
        $this->request = $this->getRequest();
        $this->json = $this->request->getParam('posts');
        if (isset($this->json)) {
            //Fix unknown JSON format (Bugs on Firefox 3.6)
            $this->json = str_replace("\\", "", $this->json);
            if (substr($this->json, 0, 1) != '[') {
                $this->json = "[" . $this->json . "]";
            }
        }

        $this->logistic = new Logistic_Models_LogisticDord();
        $this->logisticH = new Logistic_Models_LogisticDorh();
        $this->logisticDo = new Logistic_Models_LogisticDod();
        $this->logisticDoH = new Logistic_Models_LogisticDoh();
        $this->ican = new Logistic_Models_LogisticMaterialCancel();
        $this->icanH = new Logistic_Models_LogisticMaterialCancelH();
        $this->ilov = new Logistic_Models_LogisticMaterialReturn();
        $this->ilovH = new Logistic_Models_LogisticMaterialReturnH();
        $this->isupp = new Logistic_Models_LogisticInputSupplier();
        $this->isuppH = new Logistic_Models_LogisticInputSupplierH();
        $this->supplier = new Default_Models_MasterSuplier();
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClass = new Admin_Models_Workflow();
        $this->files = new Default_Models_Files();
        $this->counter = new Default_Models_MasterCounter();
        $this->log = new Admin_Models_Logtransaction();
        $this->logActivity = new Admin_Models_Activitylog();


        $models = array(
            "MasterKota",
            "MasterPropinsi",
            "LogisticDoh",
            "LogisticDod",
            "LogisticDord",
            "LogisticInputSupplier"
//            "LogisticTemporaryBarang"
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);

        $models = array(
            "ProcurementPod",
            "ProcurementPoh",
            "MasterBarang"
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
            case 'dorh':
                $logistic = new Default_Models_ProcurementRequestH();
                $return['posts'] = $procurement->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
                $return['count'] = $procurement->fetchAll()->count();
                break;
            case 'nodord':
                $sql = "SELECT * FROM procurement_pointd p order by tgl desc,trano desc limit 1";
                $fetch = $this->db->query($sql);
                $return['posts'] = $fetch->fetch();
                $return['count'] = 1;
                break;
            case 'dord':
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

    public function showdorAction() {
        
    }

    public function dorAction() {
        
    }

    public function doAction() {
        
    }

    public function alldoAction() {
        
    }

    public function alldorAction() {
        
    }

    public function adddoAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->DORtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
        }
    }

    public function adddorAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->PRtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->etc = $this->getRequest()->getParam("etc");
        }
    }

    public function getlastdorAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('DOR');
        $number++;
        echo "{ dor:'$number' }";
    }

    public function appdoAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
      
        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/DO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';
        
        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $journal = $this->getRequest()->getParam("journal");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonEtc = $etc;
            $this->view->jsonResult = $json;
            $this->view->journal = $journal;

            if ($from == 'edit') {
                $this->view->edit = true;
                $this->view->trano = $this->getRequest()->getParam("trano");
                $this->view->tgl = $this->getRequest()->getParam("tgl");              
            }
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
                    $prd = $this->logisticDo->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->logisticDoH->fetchRow("trano = '$approve'");
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
                        }

                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];
                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;

                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
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

    public function appdorAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/DOR';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $this->view->approve = $approve;
        
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
            $this->view->jsonEtc = $etc;

            if ($from == 'edit') {
                $this->view->edit = true;
                $this->view->trano = $this->getRequest()->getParam("trano");
                $this->view->tgl = $this->getRequest()->getParam("tgl");
            }
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
                    $prd = $this->logistic->fetchAll("trano = '$approve'")->toArray();
                    $prh = $this->logisticH->fetchRow("trano = '$approve'");
                    if ($prd) {
                        foreach ($prd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $prd[$key]['uom'] = $barang['sat_kode'];
                            }

                            if ($val['sts_internal'] == 1)
                                $prd[$key]['qty'] = $val['qty_int'];
                        }

                        $jsonData2[0]['prj_kode'] = $prh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $prh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $prh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $prh['sit_nama'];

                        $jsonData2[0]['tgl'] = $prh['tgl'];

                        $jsonData2[0]['deliver_to'] = $prh['deliver_to'];
                        $jsonData2[0]['dest_nama'] = $prh['dest_nama'];
                        $jsonData2[0]['from_nama'] = $prh['from_nama'];
                        $jsonData2[0]['alamat'] = $prh['alamat'];
                        $jsonData2[0]['alamat1'] = $prh['alamat1'];

                        $jsonData2[0]['receiver_nama'] = $prh['receiver_nama'];
                        $jsonData2[0]['receiver_tlp'] = $prh['receiver_tlp'];

                        $cusKode = $this->project->getProjectAndCustomer($prh['prj_kode']);
                        $jsonData2[0]['cus_nama'] = $cusKode[0]['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $cusKode[0]['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;
//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getLastReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $prd;
                        $this->view->approve = true;
                        $this->view->trano = $approve;
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

    public function insertdorAction() {
        $this->_helper->viewRenderer->setNoRender();
        
        $activitylog2 = new Admin_Models_Activitylog();
        
        Zend_Loader::loadClass('Zend_Json');
        
        $comment = $this->_getParam("comment");
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);

        $tgl = date('Y-m-d');
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('DOR');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'DOR-' . $last;

        $items = $jsonData2[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        
        $params = array(
            "workflowType" => "DOR",
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

//        $result = $this->workflow->setWorkflowTrans($trano, 'DOR', '', $this->const['DOCUMENT_SUBMIT'], $items);
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
        //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $qt_ori = 0;
        $qt_ori = 0;
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            $qt_ori = $val['qty'];
            $qt_ori = $val['qty'];
            //if ($val['discount'] > 0)
                $harga = $val['harga']-($val['harga'] * $val['discount']);
            //else
                //$harga = $val['harga'];

            if ($jsonData2[0]['flag_internal'] == 1) {
                $qty_in = $val['qty'];
                $sts_internal = 1;
//                $qt_ori = 0;
            } else {
                $qty_in = 0;
                $sts_internal = 0;
            }
            if ($jsonData2[0]['flag_internal'] == 1) {
                $qty_in = $val['qty'];
                $sts_internal = 1;
//                $qt_ori = 0;
            } else {
                $qty_in = 0;
                $sts_internal = 0;
            }
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $qt_ori,
                "qty_int" => $qty_in,
                "harga" => $harga,
                "rateidr" => $rate['rateidr'],
                "harga_asli" => $val['harga'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_number'],
//                "pr_no" => $val['trano'],
                "discount" => $val['discount'],
                "sts_internal" => $sts_internal,
            );
            $urut++;
            $this->logistic->insert($arrayInsert);
            // detail
             $activityDetail['procurement_pointd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }
        $deliver_to = $jsonData2[0]['deliver_to'];
        $dest_kode = $jsonData2[0]['dest_kode'];
        $dest_nama = $jsonData2[0]['dest_nama'];
        $from_kode = $jsonData2[0]['from_kode'];
        $from_nama = $jsonData2[0]['from_nama'];
        $prno = $jsonData[0]['pr_number'];

        $recv_nama = $jsonData2[0]['receiver_nama'];
        $recv_tlp = $jsonData2[0]['receiver_tlp'];
        $alamat = $jsonData2[0]['alamat'];
        $alamat1 = $jsonData2[0]['alamat1'];

        $arrayInsert = array(
            "trano" => $trano,
            "pr_no" => $prno,
            "prj_kode" => $val['prj_kode'],
            "prj_nama" => $val['prj_nama'],
            "sit_kode" => $val['sit_kode'],
            "sit_nama" => $val['prj_nama'],
            "dest_kode" => $dest_kode,
            "dest_nama" => $dest_nama,
            "from_kode" => $from_kode,
            "from_nama" => $from_nama,
            "deliver_to" => $deliver_to,
            "receiver_nama" => $recv_nama,
            "receiver_tlp" => $recv_tlp,
            "alamat" => $alamat,
            "alamat1" => $alamat1,
            "tgl" => $tgl,
            "pr_no" => $val['pr_number']
        );

        $this->logisticH->insert($arrayInsert);
        //header
        $activityHead['procurement_pointh'][0]=$arrayInsert;
        
        //save activity
         $activityLog = array(
            "menu_name" => "Create DOR",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $val['prj_kode'],
            "sit_kode" => $val['sit_kode'],
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

    public function editdorAction() {

        $trano = $this->getRequest()->getParam("trano");
        $dord = $this->logistic->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $dorh = $this->logisticH->fetchRow("trano = '$trano'");
        if ($dorh)
            $dorh = $dorh->toArray();
        $tmp = array();

        $prArray = array();
        foreach ($dord as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $dord[$key][$key2] = '';
            }
            $dord[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $dord[$key]['trano'] = $dord[$key]['pr_no'];
            $dord[$key]['pr_number'] = $dord[$key]['pr_no'];
            if (!in_array($dord[$key]['trano'], $tmp))
                $tmp['trano'] = $dord[$key]['trano'];
            $prArray[] = $dord[$key]['pr_no'];
            unset($dord[$key]['pr_no']);
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $dord[$key]['uom'] = $barang['sat_kode'];
            }
        }
        foreach ($dorh as $key => $val) {
            if ($val == '""')
                $dorh[$key] = '';
        }
        $tmp2 = $dorh;
        unset($dorh);
        $dorh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($dord);
        $jsonData2 = Zend_Json::encode($dorh);

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
        $this->view->tgl = date('d-m-Y', strtotime($dorh[0]['tgl']));
//            $this->view->pr_no = $dorh[0]['pr_no'];
        $this->view->pr_no = $prArray;
        $dorh[0]['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $dorh[0]['ket']);
        $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $dorh[0]['ket']);
//            $this->view->ket = $dorh[0]['ket'];
    }

   public function updatedorAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        //$etc = $jsonData = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $comment = $this->_getParam("comment");
        $etc = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        

        $trano = $etc[0]['trano'];
        $items = $etc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "DOR",
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
        $trano = $this->workflow->setWorkflowTransNew($params);
        
//        $result = $this->workflow->setWorkflowTrans($trano, 'DOR', '', $this->const['DOCUMENT_SUBMIT'], $items);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//        if (is_numeric($result)) {
//            $msg = $this->error->getErrorMsg($result);
//            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
//            return false;
//        } elseif (is_array($result) && count($result) > 0) {
//            $hasil = Zend_Json::encode($result);
//            $this->getResponse()->setBody("{success: true, user:$hasil}");
//            return false;
//        }
        $tgl = $etc[0]['tgl'];
        $deliver_to = $etc[0]['deliver_to'];
        $dest_kode = $etc[0]['dest_kode'];
        $dest_nama = $etc[0]['dest_nama'];
        $from_kode = $etc[0]['from_kode'];
        $from_nama = $etc[0]['from_nama'];

        $recv_nama = $etc[0]['receiver_nama'];
        $recv_tlp = $etc[0]['receiver_tlp'];
        $alamat = $etc[0]['alamat'];
        $alamat1 = $etc[0]['alamat1'];
        
        // fetch data before
        $log['dor-detail-before'] = $this->logistic->fetchAll("trano = '$trano'")->toArray();
        $this->logistic->delete("trano='$trano'");

        $qty_ori = 0;
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        foreach ($jsonData as $key => $val) {

            //if ($val['discount'] > 0)
                $harga = $val['harga_asli'] - $val['harga_asli'] * $val['discount'];
            //else
                //$harga = $val['harga'];

            $qt_ori = $val['qty'];
            
            if ($etc[0]['flag_internal'] == 1) {
                $qty_in = $val['qty'];
                $sts_internal = 1;
//                $qt_ori = 0;
            } else {
                $qty_in = 0;
                $sts_internal = 0;
            }
            /*if ($etc[0]['flag_internal'] == 1) {
                $qty_in = $val['qty'];
                $sts_internal = 1;
                $qt_ori = 0;
            } else {
                $qty_in = 0;
                $sts_internal = 0;
            }*/

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d',strtotime($tgl)),
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $qt_ori,
                "qty_int" => $qty_in,
                "harga" => $harga,
                "harga_asli" => $val['harga_asli'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_number'],
//                "pr_no" => $val['trano'],
                "discount" => $val['discount'],
                "sts_internal" => $sts_internal,
                "rateidr" => $rate['rateidr'],
            );
            //var_dump($arrayInsert);
            $this->logistic->insert($arrayInsert);
        }
       // fetch data after
        $log2['dor-detail-after'] = $this->logistic->fetchAll("trano = '$trano'")->toArray();
        
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d',strtotime($tgl)),
            "prj_kode" => $val['prj_kode'],
            "prj_nama" => $val['prj_nama'],
            "sit_kode" => $val['sit_kode'],
            "sit_nama" => $val['prj_nama'],
            "dest_kode" => $dest_kode,
            "dest_nama" => $dest_nama,
            "from_kode" => $from_kode,
            "from_nama" => $from_nama,
            "deliver_to" => $deliver_to,
            "receiver_nama" => $recv_nama,
            "receiver_tlp" => $recv_tlp,
            "alamat" => $alamat,
            "alamat1" => $alamat1,
            "tgl" => date('Y-m-d',strtotime($tgl)),
            "pr_no" => $val['pr_number']
//            "pr_no" => $val['trano']
        );

        // fetch data before
        $log['dor-header-before'] = $this->logisticH->fetchRow("trano = '$trano'")->toArray();
        $this->logisticH->delete("trano='$trano'");
        $this->logisticH->insert($arrayInsert);
        // fetch data after
        $log2['dor-header-after'] = $this->logisticH->fetchRow("trano = '$trano'")->toArray();
        // simpan log
        
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
        
        $this->getResponse()->setBody("{success: true}");
    }

    public function insertdoAction() {
        $this->_helper->viewRenderer->setNoRender();
        
        $activitylog2 = new Admin_Models_Activitylog();
        
        Zend_Loader::loadClass('Zend_Json');      
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);
        $project = $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();
        $journal = ($this->getRequest()->getParam("journal") == 'true') ? true : false;

        $tgl = date('Y-m-d');
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('DO');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'DO01-' . $last;

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        //activity log
         $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $transnama = $jsonData2[0]['trans_nama'];
        $transkode = $jsonData2[0]['trans_kode'];
        $transalamat = $jsonData2[0]['transalamat'];
        $transtlp = $jsonData2[0]['transtlp'];
        $transfax = $jsonData2[0]['transfax'];
        $transcontact = $jsonData2[0]['transcontact'];
        $transhp = $jsonData2[0]['transhp'];
        $prjKode = $jsonData[0]['prj_kode'];
        $deliverto = $jsonData2[0]['dest_nama'] . ", " . $jsonData2[0]['alamat'];
        $deliveryfrom = $jsonData2[0]['from_nama'];
        $receivernama = $jsonData2[0]['receiver_nama'];
        $customer = $project->getProjectDetail($prjKode);
        if ($customer) {
            $cus_kode = $customer['cus_kode'];
        }

        foreach ($jsonData as $key => $val) {
            $dordata = $this->logisticH->fetchRow("trano = '{$val['dor_no']}'");
            if ($dordata) {
                if ($dordata['from_kode'] == $jsonData2[0]['gdg_kode_from'])
                    $kode_gudang = $dordata['from_kode'];
                else
                    $kode_gudang = $jsonData2[0]['gdg_kode_from'];
            }
            $kodeBrg = $val['kode_brg'];
            $cekBarang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($cekBarang) {
                $hargaAVG = $cekBarang['hargaavg'];
            } else
                $hargaAVG = 0;

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "ket" => $val['ket'],
                "qty" => $val['qty'],
                "val_kode" => $val['val_kode'],
                "harga" => $hargaAVG,
                "total" => ($hargaAVG * floatval($val['qty'])),
                "petugas" => $this->session->userName,
                "mdi_no" => $val['dor_no'],
                "gdg_kode" => $kode_gudang,
                "rateidr" => $rate['rateidr']
            );
            $this->logisticDo->insert($arrayInsert);
            // detail
             $activityDetail['procurement_whod'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;

            if ($val['with_journal'] == 'Y') {
                //Insert jurnal inventory out
                $i = new Finance_Models_AccountingInventoryOut();
                $i->insertJurnal(array(
                    "ref_number" => $trano,
                    "total" => ($hargaAVG * floatval($val['qty'])),
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "val_kode" => ($val['val_kode'] != '') ? $val['val_kode'] : 'IDR',
                    "gdg_kode_from" => $jsonData2[0]['gdg_kode_from'],
                    "gdg_kode_to" => $jsonData2[0]['gdg_kode_to']
                ));
            }
        }

        $arrayInsert = array(
            "trano" => $trano,
            "prj_kode" => $val['prj_kode'],
            "prj_nama" => $val['prj_nama'],
            "sit_kode" => $val['sit_kode'],
            "sit_nama" => $val['prj_nama'],
            "cus_kode" => $cus_kode,
            "trans_kode" => $transkode,
            "deliveryto" => $deliverto,
            "namatransporter" => $transnama,
            "transalamat" => $transalamat,
            "contactpersonto" => $receivernama,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "transtlp" => $transtlp,
            "transhp" => $transhp,
            "transfax" => $transfax,
            "transcontact" => $transcontact,
            "deliveryfrom" => $deliveryfrom,
            "tgl" => $tgl,
            "mdi_no" => $val['dor_no'],
            "gdg_kode" => $kode_gudang,
            "gdg_kode_from" => $jsonData2[0]['gdg_kode_from'],
            "gdg_kode_to" => $jsonData2[0]['gdg_kode_to'],
            "val_kode" => $val['val_kode']
        );

        $this->logisticDoH->insert($arrayInsert);
        //header
        $activityHead['procurement_whoh'][0]=$arrayInsert;
        
        //save activity
         $activityLog = array(
            "menu_name" => "Create DO",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $val['prj_kode'],
            "sit_kode" => $val['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $activitylog2->insert($activityLog);
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function editdoAction() {

        $trano = $this->getRequest()->getParam("trano");
        $dod = $this->logisticDo->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $doh = $this->logisticDoH->fetchRow("trano = '$trano'");
        if ($doh)
            $doh = $doh->toArray();
        $tmp = array();

        foreach ($dod as $key => $val) {
            foreach ($val as $key2 => $val2) {
                if ($val2 == '""')
                    $dod[$key][$key2] = '';
            }
            $dod[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $dod[$key]['trano'] = $dod[$key]['mdi_no'];
            if (!in_array($dod[$key]['trano'], $tmp))
                $tmp['trano'] = $dod[$key]['trano'];
            unset($dod[$key]['mdi_no']);
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $dod[$key]['uom'] = $barang['sat_kode'];
            }
        }
//        foreach ($doh as $key => $val) {
//            if ($val == '""')
//                $doh[$key] = '';
//        }
        $tmp2 = $doh;
        unset($doh);
        $doh[0] = $tmp2;
        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($dod);
        $jsonData2 = Zend_Json::encode($doh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }
        $this->view->dorNo = $tmp;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($doh[0]['tgl']));
        $this->view->dor_no = $doh[0]['mdi_no'];

        $doh[0]['ket'] = preg_replace("[^A-Za-z0-9-.,]", "", $doh[0]['ket']);
        $this->view->ket = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $doh[0]['ket']);
//            $this->view->ket = $doh[0]['ket'];
    }

  public function updatedoAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $project = $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $trano = $jsonData2[0]['trano'];
        $urut = 1;
        $tgl = $jsonData2[0]['tgl'];
        $transnama = $jsonData2[0]['trans_nama'];
        $transkode = $jsonData2[0]['trans_kode'];
        $transalamat = $jsonData2[0]['transalamat'];
        $transtlp = $jsonData2[0]['transtlp'];
        $transfax = $jsonData2[0]['transfax'];
        $transcontact = $jsonData2[0]['transcontact'];
        $transhp = $jsonData2[0]['transhp'];
        $prjKode = $jsonData[0]['prj_kode'];
        $deliverto = $jsonData2[0]['dest_nama'] . ", " . $jsonData2[0]['alamat'];
        $deliveryfrom = $jsonData2[0]['from_nama'];
        $receivernama = $jsonData2[0]['receiver_nama'];
        $dor_no = $jsonData[0]['trano'];
        $customer = $project->getProjectDetail($prjKode);
        if ($customer) {
            $cus_kode = $customer['cus_kode'];
        }
        // fetch data before
//        $log['do-detail-before'] = $this->logisticDo->fetchAll("trano = '$trano'")->toArray();
        
//        $this->logisticDo->delete("trano='$trano'");
        foreach ($jsonData as $key => $val) {
            $dordata = $this->logisticH->fetchRow("trano = '{$val['dor_no']}'");
            if ($dordata) {
                $kode_gudang = $dordata['from_kode'];
            }
            $kodeBrg = $val['kode_brg'];
            $cekBarang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($cekBarang) {
                $hargaAVG = $cekBarang['hargaavg'];
            } else
                $hargaAVG = 0;

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" =>$urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['prj_nama'],
                "workid" => $val['workid'],
                "workname" => $val['workname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['harga'],
                "total" => ($hargaAVG * floatval($val['qty'])),
                "harga" => $val['harga'],
                "rateidr" => $val['rateidr'],
                "val_kode" => $val['val_kode'],
                "mdi_no" => $dor_no,
                "gdg_kode" => $kode_gudang
            );
            $urut++;
            $this->logisticDo->update($arrayInsert,"trano='$trano' AND kode_brg='{$val['kode_brg']}' AND workid='{$val['workid']}'");
// fetch data after
            $log2['do-detail-after'] = $this->logisticDo->fetchAll("trano = '$trano'")->toArray();
        }
        $arrayInsert = array(
            "trano" => $trano,
            "prj_kode" => $val['prj_kode'],
            "prj_nama" => $val['prj_nama'],
            "sit_kode" => $val['sit_kode'],
            "sit_nama" => $val['prj_nama'],
            "cus_kode" => $cus_kode,
            "trans_kode" => $transkode,
            "deliveryto" => $deliverto,
            "namatransporter" => $transnama,
            "transalamat" => $transalamat,
            "contactpersonto" => $receivernama,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "transtlp" => $transtlp,
            "transhp" => $transhp,
            "transfax" => $transfax,
            "transcontact" => $transcontact,
            "deliveryfrom" => $deliveryfrom,
            "tgl" => $tgl,
            "mdi_no" => $dor_no,
            "gdg_kode" => $kode_gudang
        );
        // fetch data before
//        $log['do-header-before'] = $this->logisticDoH->fetchRow("trano = '$trano'")->toArray();
         $this->logisticDoH->update($arrayInsert,"trano = '$trano'");
        // fetch data after
        $log2['do-header-after'] = $this->logisticDoH->fetchRow("trano = '$trano'")->toArray();

        // simpan log
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
        
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true}");
    }

    public function inserticanAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $tgl = date('Y-m-d');
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('iCAN');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'i-Can01-' . $last;

        $result = $this->workflow->setWorkflowTrans($trano, 'iCAN', '', $this->const['DOCUMENT_SUBMIT']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }

        $where = $lastTrans['id'];
        $counter->update(array("urut" => $last), "id = '$where'");
        $urut = 1;

        $totals = 0;
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

        foreach ($jsonData as $key => $val) {
            $total = ($val['val_kode'] != 'IDR' ? ($val['totalPrice'] * $rate['rateidr']) : $val['totalPrice']);
            $totals += $total;
        }

        foreach ($jsonData as $key => $val) {
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
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
                "val_kode" => $val['val_kode'],
                "sat_kode" => $val['uom'],
                "gdg_kode" => $val['gdg_kode'],
                "do_no" => $val['do_no'],
                "rateidr" => $rate['rateidr']
            );
            //var_dump($arrayInsert);
            $this->ican->insert($arrayInsert);

            //Insert jurnal inventory return
            $i = new Finance_Models_AccountingInventoryReturn();
            $i->insertJurnal(array(
                "item_type" => "iCAN",
                "ref_number" => $trano,
                "total" => $val['totalPrice'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "val_kode" => ($val['val_kode'] != '') ? $val['val_kode'] : 'IDR',
                "gdg_kode_from" => "S",
                "gdg_kode_to" => $val['gdg_kode']
            ));
        }
        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "prj_kode" => $jsonData2[0]['prj_kode'],
            "prj_nama" => $jsonData2[0]['prj_nama'],
            "sit_kode" => $jsonData2[0]['sit_kode'],
            "sit_nama" => $jsonData2[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "total" => $totals,
            "cus_kode" => $cusKode,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "delivery" => $jsonData2[0]['delivery'],
            "receive" => $jsonData2[0]['receive'],
            "gdg_kode" => $jsonData[0]['gdg_kode'],
            "do_no" => $jsonData2[0]['do_no']
        );

        $this->icanH->insert($arrayInsert);

        $this->getResponse()->setBody("{success: true}");
    }

    public function updateicanAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        
        $trano = $jsonData2[0]['trano'];
        $result = $this->workflow->setWorkflowTrans($trano, 'iCAN', '', $this->const['DOCUMENT_RESUBMIT']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        $totals = 0;

        foreach ($jsonData as $key => $val) {
            $total = $val['totalPrice'];
            $totals += $total;
        }
//        fetch detail before
//        $log['ican-detail-before'] = $this->ican->fetchAll("trano = '$trano'")->toArray();
        $this->ican->delete("trano='$trano'");
        foreach ($jsonData as $key => $val) {
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
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
                "val_kode" => $val['val_kode'],
                "sat_kode" => $val['uom'],
                "gdg_kode" => $val['gdg_kode']
            );
            //var_dump($arrayInsert);
            $this->ican->insert($arrayInsert);
        }
//        fetch detail after
//        $log2['ican-detail-after'] = $this->ican->fetchAll("trano = '$trano'")->toArray();
        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonData2[0]['prj_kode'],
            "prj_nama" => $jsonData2[0]['prj_nama'],
            "sit_kode" => $jsonData2[0]['sit_kode'],
            "sit_nama" => $jsonData2[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "total" => $totals,
            "cus_kode" => $cusKode,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "delivery" => $jsonData2[0]['delivery'],
            "receive" => $jsonData2[0]['receive'],
            "gdg_kode" => $jsonData[0]['gdg_kode']
        );
//        fetch header before
//        $log['ican-header-before'] = $this->icanH->fetchRow("trano = '$trano'")->toArray();
        $this->icanH->delete("trano='$trano'");
        $this->icanH->insert($arrayInsert);
//        fetch header after
//        $log2['ican-header-after'] = $this->icanH->fetchRow("trano = '$trano'")->toArray();
          
//        simpan log ke database
//        $jsonLog = Zend_Json::encode($log);
//        $jsonLog2 = Zend_Json::encode($log2);
//        $arrayLog = array(
//            "trano" => $trano,
//            "uid" => $this->session->userName,
//            "tgl" => date('Y-m-d H:i:s'),
//            "prj_kode" => $jsonData2[0]['prj_kode'],
//            "sit_kode" => $jsonData2[0]['sit_kode'],
//            "action" => "UPDATE",
//            "data_before" => $jsonLog,
//            "data_after" => $jsonLog2,
//            "ip" => $_SERVER["REMOTE_ADDR"],
//            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
//        );
//        $this->log->insert($arrayLog);
        
      
        $this->getResponse()->setBody("{success: true}");
        
    }  

    public function insertilovAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $etc = str_replace("\\", "", $etc);

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $prj_kode = str_replace(" ", "", $jsonData2[0]['prj_kode']);
        $urut = 1;

        $items = $jsonData2[0];
        $items["prj_kode"] = $prj_kode;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "iLOV",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $prj_kode,
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $tgl = date('Y-m-d');


        $totals = 0;
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

        foreach ($jsonData as $key => $val) {
            $total = ($val['val_kode'] != 'IDR' ? ($val['totalPrice'] * $rate['rateidr']) : $val['totalPrice']);
            $totals += $total;
        }

        foreach ($jsonData as $key => $val) {
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
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
                "val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "sat_kode" => $val['uom'],
                "gdg_kode" => $val['gdg_kode'],
                "do_no" => $val['do_no'],
                "rateidr" => $rate['rateidr']
            );
            //var_dump($arrayInsert);
            $this->ilov->insert($arrayInsert);

            $i = new Finance_Models_AccountingInventoryReturn();
            $i->insertJurnal(array(
                "item_type" => "iLOV",
                "ref_number" => $trano,
                "total" => $val['totalPrice'],
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "val_kode" => ($val['val_kode'] != '') ? $val['val_kode'] : 'IDR',
                "gdg_kode_from" => "S",
                "gdg_kode_to" => $val['gdg_kode']
            ));
        }
        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "prj_kode" => $jsonData2[0]['prj_kode'],
            "prj_nama" => $jsonData2[0]['prj_nama'],
            "sit_kode" => $jsonData2[0]['sit_kode'],
            "sit_nama" => $jsonData2[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "total" => $totals,
            "cus_kode" => $cusKode,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "delivery" => $jsonData2[0]['delivery'],
            "receive" => $jsonData2[0]['receive'],
            "gdg_kode" => $jsonData[0]['gdg_kode'],
            "do_no" => $jsonData2[0]['do_no']
        );

        $this->ilovH->insert($arrayInsert);


        $this->getResponse()->setBody("{success: true}");
    }

    public function updateilovAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $trano = $jsonData2[0]['trano'];
        $result = $this->workflow->setWorkflowTrans($trano, 'iLOV', '', $this->const['DOCUMENT_RESUBMIT']);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        $totals = 0;
          
        foreach ($jsonData as $key => $val) {
            $total = $val['totalPrice'];
            $totals += $total;
        }
//        fetch detail before
//        $log['ilov-detail-before'] = $this->ilov->fetchAll("trano = '$trano'")->toArray();
        $this->ilov->delete("trano='$trano'");
        foreach ($jsonData as $key => $val) {
             $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
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
                "val_kode" => $val['val_kode'],
                "myob" => $val['net_act'],
                "sat_kode" => $val['uom'],
                "gdg_kode" => $val['gdg_kode']
            );
            //var_dump($arrayInsert);
            $this->ilov->insert($arrayInsert);
        }
//        fetch detail after
//        $log2['ilov-detail-after'] = $this->ilov->fetchAll("trano = '$trano'")->toArray();
        $cusKode = $this->project->getProjectAndCustomer($jsonData2[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonData2[0]['prj_kode'],
            "prj_nama" => $jsonData2[0]['prj_nama'],
            "sit_kode" => $jsonData2[0]['sit_kode'],
            "sit_nama" => $jsonData2[0]['sit_nama'],
            "ket" => $jsonData[0]['ket'],
            "total" => $totals,
            "cus_kode" => $cusKode,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "delivery" => $jsonData2[0]['delivery'],
            "receive" => $jsonData2[0]['receive'],
            "gdg_kode" => $jsonData[0]['gdg_kode']
        );  
//        fetch header before
//        $log['ilov-header-before'] = $this->ilovH->fetchRow("trano = '$trano'")->toArray();
        $this->ilovH->delete("trano='$trano'");
        $this->ilovH->insert($arrayInsert);
//        fetch header after
//        $log2['ilov-header-after'] = $this->ilovH->fetchRow("trano = '$trano'")->toArray();

//        simpan log ke database
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
        
        $this->getResponse()->setBody("{success: true}");
    }

    public function ilovAction() {
        
    }

    public function icanAction() {
        
    }

    public function isuppAction() {
        
    }

    public function addilovAction() {
        
    }

    public function addicanAction() {
        
    }

    public function addisuppAction() {
        
    }

    public function editicanAction() {
        $trano = $this->getRequest()->getParam("trano");
        $icand = $this->ican->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $icanh = $this->icanH->fetchRow("trano = '$trano'");

        if ($icand) {


            foreach ($icand as $key => $val) {
                $icand[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $icand[$key]['uom'] = $barang['sat_kode'];
                }
                $icand[$key]['price'] = $val['harga'];
                $icand[$key]['totalPrice'] = $val['total'];
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($icand);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $this->view->trano = $trano;
            $this->view->tgl = $icanh['tgl'];
            $this->view->prjKode = $icanh['prj_kode'];
            $this->view->prjNama = $icanh['prj_nama'];
            $this->view->sitkode = $icanh['sit_kode'];
            $this->view->sitNama = $icanh['sit_nama'];
            $this->view->delivery = $icanh['delivery'];
            $this->view->receive = $icanh['receive'];
        }
    }

    public function editilovAction() {
        $trano = $this->getRequest()->getParam("trano");
        $ilovd = $this->ilov->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $ilovh = $this->ilovH->fetchRow("trano = '$trano'");

        if ($ilovd) {
            foreach ($ilovd as $key => $val) {
                $ilovd[$key]['id'] = $key + 1;
                $kodeBrg = $val['kode_brg'];
                $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                if ($barang) {
                    $ilovd[$key]['uom'] = $barang['sat_kode'];
                }
                $ilovd[$key]['price'] = $val['harga'];
                $ilovd[$key]['totalPrice'] = $val['total'];
                $ilovd[$key]['net_act'] = $val['myob'];
            }

            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::encode($ilovd);
            $isCancel = $this->getRequest()->getParam("returnback");
            if ($isCancel) {
                $this->view->cancel = true;
                $this->view->json = $this->getRequest()->getParam("posts");
            } else
                $this->view->json = $jsonData;

            $this->view->trano = $trano;
            $this->view->tgl = $ilovh['tgl'];
            $this->view->prjKode = $ilovh['prj_kode'];
            $this->view->prjNama = $ilovh['prj_nama'];
            $this->view->sitkode = $ilovh['sit_kode'];
            $this->view->sitNama = $ilovh['sit_nama'];
            $this->view->delivery = $ilovh['delivery'];
            $this->view->receive = $ilovh['receive'];
        }
    }

    public function appicanAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/iCAN';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

        $approve = $this->getRequest()->getParam("approve");
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
            $this->view->jsonEtc = $etc;
            $this->view->rate = $rate['rateidr'];

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
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $icand = $this->ican->fetchAll("trano = '$approve'")->toArray();
                    $icanh = $this->icanH->fetchRow("trano = '$approve'");
                    if ($icand) {
                        foreach ($icand as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $icand[$key]['uom'] = $barang['sat_kode'];
                            }
                            $icand[$key]['price'] = $val['harga'];
                            $icand[$key]['totalPrice'] = $val['total'];
                        }

                        $jsonData2[0]['prj_kode'] = $icanh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $icanh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $icanh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $icanh['sit_nama'];
                        $jsonData2[0]['do_no'] = $icanh['do_no'];

                        $jsonData2[0]['tgl'] = $icanh['tgl'];
                        $jsonData2[0]['delivery'] = $icanh['delivery'];
                        $jsonData2[0]['receive'] = $icanh['receive'];
                        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $icand;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->rate = $icand[0]['rateidr'];
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function appilovAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/iLOV';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

        $approve = $this->getRequest()->getParam("approve");
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
            $this->view->jsonEtc = $etc;
            $this->view->rate = $rate['rateidr'];

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
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $ilovd = $this->ilov->fetchAll("trano = '$approve'")->toArray();
                    $ilovh = $this->ilovH->fetchRow("trano = '$approve'");
                    if ($ilovd) {
                        foreach ($ilovd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $ilovd[$key]['uom'] = $barang['sat_kode'];
                            }
                            $ilovd[$key]['price'] = $val['harga'];
                            $ilovd[$key]['totalPrice'] = $val['total'];
                        }

                        $jsonData2[0]['prj_kode'] = $ilovh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $ilovh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $ilovh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $ilovh['sit_nama'];
                        $jsonData2[0]['do_no'] = $ilovh['do_no'];

                        $jsonData2[0]['tgl'] = $ilovh['tgl'];
                        $jsonData2[0]['delivery'] = $ilovh['delivery'];
                        $jsonData2[0]['receive'] = $ilovh['receive'];
                        $jsonData2[0]['trano'] = $approve;
                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $ilovd;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->rate = $ilovd[0]['rateidr'];
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function supplierAction() {
        
    }

    public function viewsupplierAction() {
        
    }

    public function getviewsupplierAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $txtsearch = $this->getRequest()->getParam('search');
        $option = $this->getRequest()->getParam('option');
        $search = null;

        if ($txtsearch == "" || $txtsearch == null) {
            $search == null;
        } else if ($txtsearch != null && $option == 1) {
            $search = "sup_kode like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 2) {
            $search = "sup_nama like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 3) {
            $search = "alamat like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 4) {
            $search = "tlp like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 5) {
            $search = "email like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 6) {
            $search = "fax like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 7) {
            $search = "ket like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 8) {
            $search = "statussupplier like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 9) {
            $search = "jenisupplier like '%$txtsearch%' ";
        } else if ($txtsearch != null && $option == 10) {
            $search = "subjenisupplier like '%$txtsearch%' ";
        } else {
            $search == null;
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 30;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $supplierdata = $this->supplier->fetchAll($search, array($sort . " " . $dir), $limit, $offset)->toArray();
        $return['data'] = $supplierdata;
        $return['total'] = $this->supplier->fetchAll()->count();

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listmaterialAction() {
        
    }

    public function addsuppAction() {
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->file = $this->getRequest()->getParam("file");

//            if ($file)
//                $file = $file->toArray();
//            else
//                $file = array();
        }
    }

    public function insertsuppAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $jsonFile = Zend_Json::decode($file);
        $comment = $this->_getParam("comment");
        
//       var_dump($jsonData2);die;
//       var_dump($jsonData2);die;

        $prjKode = 'SUP';
        $trano = 'VDR';

        $lastTrans = $this->counter->fetchRow("tra_no = '$trano'");

        $last = intval($lastTrans['urut']);
        $last = $last + 1;
        $trano = $trano . $last;

        $insertcounter = array(
            "urut" => $last
        );

        $this->counter->update($insertcounter, "tra_no = 'VDR'");

//           $lastTrans = $this->supplier->getLastSupkode();
//           $last = abs($lastTrans['last']);
//           $last = $last + 1;
//           $trano = 'VDR' . $last;
//       $items = $jsonData2[0];
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $items['prj_kode'] = $prjKode;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
                                                                
        $result = $this->workflow->setWorkflowTrans($trano, 'SUPP', '', $this->const['DOCUMENT_SUBMIT'], $items, '', false, false, $comment);

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

        $arrayInsert = array(
            "sup_kode" => $trano,
            "sup_nama" => $jsonData2[0]['nama'],
            "alamat" => $jsonData2[0]['alamat'],
            "tlp" => $jsonData2[0]['telp'],
            "fax" => $jsonData2[0]['fax'],
            "ket" => $jsonData2[0]['ket'],
            "alamat2" => $jsonData2[0]['alamat2'],
            "master_kota" => $jsonData2[0]['city'],
            "master_propinsi" => $jsonData2[0]['province'],
            "negara" => $jsonData2[0]['country'],
            "orang" => $jsonData2[0]['contact'],
            "statussupplier" => $jsonData2[0]['status'],
            "namabank" => $jsonData2[0]['bank'],
            "rekbank" => $jsonData2[0]['account_no'],
            "reknamabank" => $jsonData2[0]['account_name'],
            "jenisupplier" => $jsonData2[0]['type'],
            "subjenisupplier" => $jsonData2[0]['spec'],
            "email" => $jsonData2[0]['email'],
            "tahunoperasi" => $jsonData2[0]['thn_operasi'],
            "tahunberdiri" => $jsonData2[0]['thn_berdiri'],
            "cabang" => $jsonData2[0]['branch_total'],
            "skala" => $jsonData2[0]['scale'],
            "daftarcabang" => $jsonData2[0]['branch_list'],
            "karyawan" => $jsonData2[0]['total_employee'],
            "aktif" => 'N',
            "npwp" => $jsonData2[0]['npwp'],
            "pkp" => $jsonData2[0]['pkp'],
            "tgl_pkp" => $jsonData2[0]['pkp_date'],
            "date" => date("Y-m-d H:i:s"),
            "uid" => $this->session->userName,
            "finance" => $jsonData2[0]['finance'],
            "direktur" => $jsonData2[0]['direktur']
        );

//            var_dump($arrayInsert);die();
        $this->supplier->insert($arrayInsert);
        $activityHead['master_suplier'][0]=$arrayInsert;

        if (count($jsonFile) > 0) {
            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $prjKode,
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
         $activityCount=0;
         $activityLog = array(
            "menu_name" => "Create Master Supplier",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $prjKode,
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "uid" => $this->session->userName,
            "header" => Zend_Json::encode($activityHead),
            "detail" =>Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
         $this->logActivity->insert($activityLog);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updatesuppAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');

        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $file = $this->getRequest()->getParam('file');
        $comment = $this->_getParam("comment");

        $jsonFile = Zend_Json::decode($file);

        $trano = $jsonData2[0]['trano'];

        $prjKode = 'SUP';
        $items = $jsonData2[0];
        $items['prj_kode'] = $prjKode;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $workTrans = new Admin_Models_Workflowtrans();
        $dataNotFinal = $workTrans->fetchRow("approve = '300' and item_id = '$trano' and final = 0","date DESC");  

//        $dataNotFinal = $this->supplier->fetchRow("sup_kode='$trano' and aktif='N'");
        

        if ($dataNotFinal) {
            $result = $this->workflow->setWorkflowTrans($trano, 'SUPP', '', $this->const['DOCUMENT_RESUBMIT'], $items, '', false, false, $comment);
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
        }



        $arrayInsert = array(
//                "sup_kode" => $trano,
            "sup_nama" => $jsonData2[0]['nama'],
            "alamat" => $jsonData2[0]['alamat'],
            "tlp" => $jsonData2[0]['telp'],
            "fax" => $jsonData2[0]['fax'],
            "ket" => $jsonData2[0]['ket'],
            "alamat2" => $jsonData2[0]['alamat2'],
            "master_kota" => $jsonData2[0]['city'],
            "master_propinsi" => $jsonData2[0]['province'],
            "negara" => $jsonData2[0]['country'],
            "orang" => $jsonData2[0]['contact'],
            "statussupplier" => $jsonData2[0]['status'],
            "namabank" => $jsonData2[0]['bank'],
            "rekbank" => $jsonData2[0]['account_no'],
            "reknamabank" => $jsonData2[0]['account_name'],
            "jenisupplier" => $jsonData2[0]['type'],
            "subjenisupplier" => $jsonData2[0]['spec'],
            "email" => $jsonData2[0]['email'],
            "tahunoperasi" => $jsonData2[0]['thn_operasi'],
            "tahunberdiri" => $jsonData2[0]['thn_berdiri'],
            "cabang" => $jsonData2[0]['branch_total'],
            "skala" => $jsonData2[0]['scale'],
            "daftarcabang" => $jsonData2[0]['branch_list'],
            "karyawan" => $jsonData2[0]['total_employee'],
            "npwp" => $jsonData2[0]['npwp'],
            "pkp" => $jsonData2[0]['pkp'],
            "tgl_pkp" => $jsonData2[0]['pkp_date'],
            "aktif" => ($jsonData2[0]['aktif']=='true' ? 'Y' : 'N'),
            "finance" => $jsonData2[0]['finance'],
            "direktur" => $jsonData2[0]['direktur'],
        );

        $dataBefore = $this->supplier->fetchAll("sup_kode='$trano'")->toArray();
        $log['Supp-detail-before'] = $dataBefore;
        
        $this->supplier->update($arrayInsert, "sup_kode='$trano'");

        $dataAfter = $this->supplier->fetchAll("sup_kode = '$trano'")->toArray();
        $log2['Supp-detail-after'] = $dataAfter;
        
        
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $prjKode,
            "sit_kode" => '',
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);
        
        
        $this->files->delete("trano='$trano'");
        if (count($jsonFile) > 0) {

            foreach ($jsonFile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $prjKode,
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }
        $this->getResponse()->setBody("{success: true , number : '$trano'}");
    }

    public function editsuppAction() {
        $trano = $this->getRequest()->getParam("sup_kode");
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        $supp = $this->supplier->fetchRow("sup_kode = '$trano'")->toArray();

        $this->view->trano = $trano;
        $this->view->supNama = $supp['sup_nama'];
        $this->view->address = $supp['alamat'];
        $this->view->telp = $supp['tlp'];
        $this->view->fax = $supp['fax'];
        $this->view->ket = $supp['ket'];
        $this->view->address2 = $supp['alamat2'];
        $this->view->city = $supp['master_kota'];
        $this->view->province = $supp['master_propinsi'];
        $this->view->country = $supp['negara'];
        $this->view->contact = $supp['orang'];
        $this->view->status = $supp['statussupplier'];
        $this->view->bank = $supp['namabank'];
        $this->view->accountName = $supp['reknamabank'];
        $this->view->accountNo = $supp['rekbank'];
        $this->view->type = $supp['jenisupplier'];
        $this->view->spec = $supp['subjenisupplier'];

        $this->view->email = $supp['email'];
        $this->view->thnOperasi = $supp['tahunoperasi'];
        $this->view->thnBerdiri = $supp['tahunberdiri'];
        $this->view->branchTotal = $supp['cabang'];
        $this->view->scale = $supp['skala'];
        $this->view->branchList = $supp['daftarcabang'];
        $this->view->totalEmployee = $supp['karyawan'];
        $this->view->finance = $supp['finance'];
        $this->view->direktur = $supp['direktur'];

        if ($supp['aktif'] == 'Y') {
            $this->view->aktif = true;
        } else {
            $this->view->aktif = false;
        }
        if ($supp['pkp'] == 'Y') {
            $this->view->pkp = true;
        } else {
            $this->view->pkp = false;
        }

        $this->view->npwp = $supp['npwp'];

        if ($supp['tgl_pkp'] == '0000-00-00') {
            $this->view->pkp_date = '';
        } else {
            $this->view->pkp_date = $supp['tgl_pkp'];
        }

        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

   public function appsuppAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/SUPP';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);

//               var_dump($jsonData2);die;

            if ($jsonData2[0]['pkp'] == 'true') {
                $jsonData2[0]['pkp'] = 'YES';
            } else {
                $jsonData2[0]['pkp'] = 'NO';
            }

            if ($jsonData2[0]['pkp_date'] == '') {
                $jsonData2[0]['pkp_date'] = '';
            } else {
                $jsonData2[0]['pkp_date'] = date('Y-m-d', strtotime($jsonData2[0]['pkp_date']));
            }

            $jsonData2[0]['tgl'] = date('d-m-Y');
            $jsonData2[0]['uid'] = $this->session->userName;


//            var_dump($jsonData2);die;

            $this->view->etc = $jsonData2;
            $this->view->jsonEtc = $etc;
            $this->view->jsonFile = $files;
            $this->view->file = $file;


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
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $supp = $this->supplier->fetchRow("sup_kode = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $jsonData2[0]['trano'] = $supp['sup_kode'];
                    $jsonData2[0]['status'] = $supp['statussupplier'];
                    $jsonData2[0]['nama'] = $supp['sup_nama'];
                    $jsonData2[0]['alamat'] = $supp['alamat'];
                    $jsonData2[0]['alamat2'] = $supp['alamat2'];
                    $jsonData2[0]['city'] = $supp['master_kota'];

                    $jsonData2[0]['country'] = $supp['negara'];
                    $jsonData2[0]['telp'] = $supp['tlp'];
                    $jsonData2[0]['fax'] = $supp['fax'];
                    $jsonData2[0]['bank'] = $supp['namabank'];
                    $jsonData2[0]['account_no'] = $supp['rekbank'];
                    $jsonData2[0]['account_name'] = $supp['reknamabank'];
                    $jsonData2[0]['type'] = $supp['jenisupplier'];
                    $jsonData2[0]['spec'] = $supp['subjenisupplier'];
                    $jsonData2[0]['contact'] = $supp['orang'];
                    $jsonData2[0]['ket'] = $supp['ket'];
                    $jsonData2[0]['sup_kode'] = $approve;

                    $jsonData2[0]['email'] = $supp['email'];
                    $jsonData2[0]['thn_operasi'] = $supp['tahunoperasi'];
                    $jsonData2[0]['thn_berdiri'] = $supp['tahunberdiri'];
                    $jsonData2[0]['branch_total'] = $supp['cabang'];
                    $jsonData2[0]['scale'] = $supp['skala'];
                    $jsonData2[0]['branch_list'] = $supp['daftarcabang'];
                    $jsonData2[0]['total_employee'] = $supp['karyawan'];

                    $jsonData2[0]['pkp'] = $supp['pkp'];
                    $jsonData2[0]['npwp'] = $supp['npwp'];
                    $jsonData2[0]['finance'] = $supp['finance'];
                    $jsonData2[0]['direktur'] = $supp['direktur'];

                    if ($supp['tgl_pkp'] == '0000-00-00') {
                        $jsonData2[0]['pkp_date'] = '';
                    } else {
                        $jsonData2[0]['pkp_date'] = $supp['tgl_pkp'];
                    }

                    $jsonData2[0]['uid'] = $supp['uid'];

//                        var_dump($supp['date']);die;

                    if ($supp['date'] == null) {
                        $jsonData2[0]['tgl'] = '';
                    } else {
                        $jsonData2[0]['tgl'] = $supp['date'];
                    }



//                        var_dump($jsonData2);die;

                    $this->view->approval = $userApp;

                    $this->view->etc = $jsonData2;

                    $this->view->file = $file;
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->trano = $approve;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function insertisuppAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        
        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonEtc = Zend_Json::decode($this->getRequest()->getParam('etc'));
        $journal = ($this->getRequest()->getParam("journal") == 'true') ? true : false;

        $tgl = date('Y-m-d');

        $items = $jsonEtc;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "iSUP",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonEtc['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        $totals = 0;
        $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

        $i = new Finance_Models_AccountingInventoryIn();

        foreach ($jsonData as $key => $val) {
            $qty = $val['qty'];
            $price = $val['price'];
            $total_item = $qty * $price;
            $total = ($val['val_kode'] != 'IDR' ? ($total_item * $rate['rateidr']) : $total_item);
            $totals += $total;
        }

        foreach ($jsonData as $key => $val) {
            $isPO = ($val["is_po"] == 'true') ? true : false;
            $isDO = ($val["is_do"] == 'true') ? true : false;
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "tglpo" => $val['tgl_po'],
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
                "rateidr" => $val['rateidr'],
                "total" => $val['totalPrice'],
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_no'],
                "tglpr" => $val['tgl_pr'],
                "sat_kode" => $val['uom']
            );

            $gdg_from = '';
            $gdg_to = '';

            if ($isPO) {
                $arrayInsert["po_no"] = $jsonEtc['po_no'];

                $gdg_from = "Supp";
                $gdg_to = $jsonEtc['wh_kode'];

                $arrayInsert["from_do"] = 1;
                $arrayInsert["gdg_kode"] = $jsonEtc['wh_kode'];
                if ($journal) {
                    $i->insertJurnal(array(
                        "item_type" => "iSUP",
                        "ref_number" => $trano,
                        "total" => $val['totalPrice'],
                        "prj_kode" => $jsonEtc['prj_kode'],
                        "sit_kode" => $jsonData[0]['sit_kode'],
                        "val_kode" => ($val['val_kode'] != '') ? $val['val_kode'] : 'IDR',
                        "gdg_kode_from" => $gdg_from,
                        "gdg_kode_to" => $gdg_to
                    ));
                }
            }
            if ($isDO) {
                $arrayInsert["do_no"] = $jsonEtc['do_no'];
                $arrayInsert["from_do"] = $jsonEtc['wh_kode_tujuan'];

                $arrayInsert["gdg_kode_tujuan"] = $jsonEtc['wh_kode'];
                $arrayInsert["gdg_kode"] = $jsonEtc['wh_kode_tujuan'];

                $gdg_from = $jsonEtc['wh_kode_tujuan'];
                $gdg_to = $jsonEtc['wh_kode'];
            }


            $this->isupp->insert($arrayInsert);                      
            $activityDetail['procurement_whsupplierd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }
        $cusKode = $this->project->getProjectAndCustomer($jsonData[0]['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => $tgl,
            "tglpo" => $jsonData[0]['tgl_po'],
            "prj_kode" => $jsonEtc['prj_kode'],
            "prj_nama" => $jsonEtc['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonEtc['ket'],
            "myob" => $jsonEtc['net_act'],
            "pomyob" => $jsonEtc['pomyob'],
            "statusppn" => $jsonData[0]['statusppn'],
            "jumlah" => $totals,
            "total" => $totals,
            "cus_kode" => $cusKode,
            "val_kode" => $jsonData[0]['val_kode'],
            "sup_kode" => $jsonEtc['sup_kode'],
            "sup_nama" => $jsonEtc['sup_nama'],
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => time('H:i:s'),
            "gdg_kode" => $jsonEtc['wh_kode_tujuan'],
            "pr_no" => $jsonData[0]['pr_no'],
            "tglpr" => $jsonData[0]['tgl_pr']
        );

        //multiwarehouse properties
        $gdg_from = '';
        $gdg_to = '';

        if ($isPO) {
            $arrayInsert["po_no"] = $jsonEtc['po_no'];
            $arrayInsert["from_do"] = 1;
            $arrayInsert["gdg_kode"] = $jsonEtc['wh_kode'];
        }
        if ($isDO) {
            $arrayInsert["do_no"] = $jsonEtc['po_no'];
            $arrayInsert["from_do"] = $jsonEtc['wh_kode_tujuan'];
            $arrayInsert["gdg_kode_tujuan"] = $jsonEtc['wh_kode'];
            $arrayInsert["gdg_kode"] = $jsonEtc['wh_kode_tujuan'];
        }

        $this->isuppH->insert($arrayInsert);
        $activityHead['procurement_whsupplierh'][0]=$arrayInsert;

         $activityLog = array(
            "menu_name" => "Create iSupp",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData[0]['prj_kode'],
            "sit_kode" => $jsonData[0]['sit_kode'],
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

    public function updateisuppAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');

        $jsonData = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $jsonEtc = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $trano = $jsonEtc['trano'];

        $items = $jsonEtc;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'iSUP', '', $this->const['DOCUMENT_RESUBMIT'], $items);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        if (is_numeric($result)) {
            $msg = $this->error->getErrorMsg($result);
            $this->getResponse()->setBody("{success: false, msg:\"$msg\"}");
            return false;
        }
        $urut = 1;

        $totals = 0;
        foreach ($jsonData as $key => $val) {
            $qty = $val['qty'];
            $price = $val['price'];
            $total = $qty * $price;
            $totals += $total;
        }
        $log['isupp-detail-before'] = $this->isupp->fetchAll("trano='$trano'")->toArray();
        $this->isupp->delete("trano='$trano'");
        foreach ($jsonData as $key => $val) {
            $isPO = ($val["is_po"] == 'true') ? true : false;
            $isDO = ($val["is_do"] == 'true') ? true : false;
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "tglpo" => $val['tgl_po'],
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
                "petugas" => $this->session->userName,
                "sup_kode" => $val['sup_kode'],
                "sup_nama" => $val['sup_nama'],
                "val_kode" => $val['val_kode'],
                "pr_no" => $val['pr_no'],
                "tglpr" => $val['tgl_pr'],
                "sat_kode" => $val['uom'],
                "gdg_kode" => $jsonEtc['wh_kode']
            );
            if ($isPO)
                $arrayInsert["po_no"] = $jsonEtc['po_no'];
            if ($isDO) {
                $arrayInsert["do_no"] = $jsonEtc['po_no'];
                $arrayInsert["from_do"] = 1;
                $arrayInsert["gdg_kode_tujuan"] = $jsonEtc['wh_kode_tujuan'];
            }
            $urut++;
            $this->isupp->insert($arrayInsert);
        }
        $log2['isupp-detail-after'] = $this->isupp->fetchAll("trano='$trano'")->toArray();
        $cusKode = $this->project->getProjectAndCustomer($jsonEtc['prj_kode']);
        $cusKode = $cusKode[0]['cus_kode'];
        $arrayInsert = array(
            "tglpo" => $jsonData[0]['tgl_po'],
            "prj_kode" => $jsonEtc['prj_kode'],
            "prj_nama" => $jsonEtc['prj_nama'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "sit_nama" => $jsonData[0]['sit_nama'],
            "ket" => $jsonEtc['ket'],
            "myob" => $jsonEtc['net_act'],
            "pomyob" => $jsonEtc['pomyob'],
            "statusppn" => $jsonData[0]['statusppn'],
            "jumlah" => $totals,
            "total" => $totals,
            "cus_kode" => $cusKode,
            "val_kode" => $jsonData[0]['val_kode'],
            "sup_kode" => $jsonEtc['sup_kode'],
            "sup_nama" => $jsonEtc['sup_nama'],
            "user" => $this->session->userName,
            "gdg_kode" => $jsonEtc['wh_kode'],
            "pr_no" => $jsonData[0]['pr_no'],
            "tglpr" => $jsonData[0]['tgl_pr']
        );

        if ($isPO)
            $arrayInsert["po_no"] = $jsonEtc['po_no'];
        if ($isDO) {
            $arrayInsert["do_no"] = $jsonEtc['po_no'];
            $arrayInsert["from_do"] = 1;
            $arrayInsert["gdg_kode_tujuan"] = $jsonEtc['wh_kode_tujuan'];
        }
        $log['isupp-header-before'] = $this->isuppH->fetchAll("trano='$trano'")->toArray();
        $this->isuppH->update($arrayInsert, "trano='$trano'");
        $log2['isupp-header-after'] = $this->isuppH->fetchAll("trano='$trano'")->toArray();
        
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
        
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function editisuppAction() {
        $tranoIsupp = $this->getRequest()->getParam("trano");
        $this->view->trano = $tranoIsupp;
        $this->view->isupph = $this->isuppH->fetchRow("trano = '$tranoIsupp'")->toArray();
        $tmp = $this->trans->getSupplierName($this->view->isupph['gdg_kode']);
        $this->view->isupph['gdg_nama'] = $tmp['gdg_nama'];
        $this->trans->getSupplierName($this->view->isupph['gdg_kode_tujuan']);
        $this->view->isupph['gdg_nama_tujuan'] = $tmp['gdg_nama'];

        $isPO = ($this->view->isupph['from_do'] == '0') ? true : false;
        $isDO = ($this->view->isupph['from_do'] == '1') ? true : false;

        $returnBack = ($this->getRequest()->getParam("returnback") == 'true') ? true : false;
        if (!$returnBack) {

            $selectisupp = $this->db->select()
                    ->from(array($this->isupp->__name()), array(
                        "prj_kode",
                        "prj_nama",
                        "sit_kode",
                        "sit_nama",
                        "trano",
                        "workid",
                        "workname",
                        "kode_brg",
                        "nama_brg",
                        "po_no",
                        "pr_no",
                        "qty",
                        "harga",
                        "from_do",
                        "do_no",
                        "gdg_kode" => 'REPLACE(gdg_kode,\'""\',\'\')',
                        "gdg_kode_tujuan" => 'REPLACE(gdg_kode_tujuan,\'""\',\'\')',
                        "ket",
                        "sup_kode",
                        "sup_nama",
                        "val_kode",
                        "tipe",
                        "uom" => "sat_kode"
                    ))
                    ->where("trano=?", $tranoIsupp);
            $isuppd = $this->db->fetchAll($selectisupp);
            foreach ($isuppd as $k => $v) {
                $prjKode = $v['prj_kode'];
                $sitKode = $v['sit_kode'];
                $workid = $v['workid'];
                $kodeBrg = $v['kode_brg'];
                $prNo = $v['pr_no'];
                if ($isPO) {
                    $trano = $v['po_no'];
                    $select = $this->DEFAULT->ProcurementPod->getSelect()->getDetail($trano, $prjKode, $sitKode, $workid, $kodeBrg);
                    $selectIsupp = $this->LOGISTIC->LogisticInputSupplier->getSelect()->getPoIsuppQuantity($trano, $prjKode, $sitKode, $workid, $kodeBrg);
                    $select->where("pr_no=?", $prNo);
                }
                if ($isDO) {
                    $trano = $v['do_no'];
                    $select = $this->LOGISTIC->LogisticDod->getSelect()->getDetail($trano, $prjKode, $sitKode, $workid, $kodeBrg);
                    $selectIsupp = $this->LOGISTIC->LogisticInputSupplier->getSelect()->getDoIsuppQuantity($trano, $prjKode, $sitKode, $workid, $kodeBrg);
                }
                $ret = $this->db->fetchRow($select);
                if ($ret) {
                    $isuppd[$k]['totalPO'] = $ret['qty'];
                    $selectisupp->where("trano != ?", $tranoIsupp);
                    $isupp = $this->db->fetchRow($selectisupp);
                    if ($isupp) {
                        $isuppd[$k]['totalISUPP'] = $isupp['total_qty'];
                    } else {
                        $isuppd[$k]['totalISUPP'] = 0;
                    }
                }
            }
        } else {
            $isuppd = ($this->_getParam("posts") != '') ? Zend_Json::decode($this->_getParam("posts")) : array();
        }
        $this->view->isuppd = $isuppd;

        if ($isPO)
            $this->view->tranoSearch = $this->view->isupph['po_no'];
        else
            $this->view->tranoSearch = $this->view->isupph['do_no'];

        $this->view->isPO = $isPO;
        $this->view->isDO = $isDO;
    }

    public function appisuppAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $isPO = ($this->_getParam("is_po") == 'true') ? true : false;
        $isDO = ($this->_getParam("is_do") == 'true') ? true : false;
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/iSUP';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $journal = $this->getRequest()->getParam("journal");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonEtc = $etc;
            $this->view->journal = $journal;

            $this->view->isPO = $isPO;
            $this->view->isDO = $isDO;

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
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $isuppd = $this->isupp->fetchAll("trano = '$approve'")->toArray();
                    $isuppH = $this->isuppH->fetchRow("trano = '$approve'");
                    $whNama = $this->trans->getSupplierName($isuppH['gdg_kode']);

                    if ($isuppd) {
                        foreach ($isuppd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $isuppd[$key]['uom'] = $barang['sat_kode'];
                            }
                        }
                    }
                    $userApp = $this->workflow->getAllApproval($approve);
                    $jsonData2['user_approval'] = $userApp;

                    $isPO = ($isuppH['from_do'] == 0) ? true : false;
                    $isDO = ($isuppH['from_do'] == 1) ? true : false;
                    $this->view->isPO = $isPO;
                    $this->view->isDO = $isDO;

                    if ($isPO)
                        $jsonData2['po_no'] = $isuppH['po_no'];
                    else
                        $jsonData2['po_no'] = $isuppH['do_no'];
                    $jsonData2['sup_kode'] = $isuppH['sup_kode'];
                    $jsonData2['sup_nama'] = $isuppH['sup_nama'];
                    $jsonData2['net_act'] = $isuppH['myob'];
                    $jsonData2['pomyob'] = $isuppH['pomyob'];

                    $jsonData2['ket'] = $isuppH['ket'];
                    $jsonData2['wh_kode'] = $isuppH['gdg_kode'];
                    $jsonData2['wh_nama'] = $whNama['gdg_nama'];
                    if ($isDO) {
                        $whNamatujuan = $this->trans->getSupplierName($isuppH['gdg_kode_tujuan']);

                        $jsonData2['wh_kode_tujuan'] = $isuppH['gdg_kode_tujuan'];
                        $jsonData2['wh_nama_tujuan'] = $whNamatujuan['gdg_nama'];
                    }
                    $this->view->trano = $approve;
                    $jsonData2['trano'] = $approve;
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $isuppd;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function addmaterialAction() {
        
    }

    public function editmaterialAction() {
        $trano = $this->getRequest()->getParam("kode_brg");
        $ToS = new Default_Models_MasterTypeOfSuply;

        $barang = $this->barang->fetchRow("kode_brg = '$trano'")->toArray();


        $tosNama = $ToS->getTosData($barang['tos_kode']);
        $ktgKode = $barang['ktg_kode'];
        $sktgKode = $barang['sktg_kode'];
        $satKode = $barang['sat_kode'];

        $sql = "SELECT ktg_nama FROM master_kategori WHERE ktg_kode ='$ktgKode'";
        $fetch = $this->db->query($sql);
        $ktgNama = $fetch->fetch();

        $sql = "SELECT sktg_nama FROM master_kategorisub WHERE ktg_kode = '$ktgKode' AND sktg_kode ='$sktgKode'";
        $fetch = $this->db->query($sql);
        $sktgNama = $fetch->fetch();

        $sql = "SELECT sat_nama FROM master_satuan WHERE sat_kode ='$satKode'";
        $fetch = $this->db->query($sql);
        $satNama = $fetch->fetch();

        $this->view->kodeBrg = $trano;
        $this->view->tosKode = $barang['tos_kode'];
        $this->view->ktgKode = $ktgKode;
        $this->view->sktgKode = $sktgKode;
        $this->view->namaBrg = $barang['nama_brg'];
        $this->view->satKode = $satKode;
        $this->view->tosNama = $tosNama['tos_nama'];
        $this->view->ktgNama = $ktgNama['ktg_nama'];
        $this->view->sktgNama = $sktgNama['sktg_nama'];
        $this->view->satNama = $satNama['sat_nama'];
        $this->view->pmeal = $barang['stspmeal'];
    }

    public function getlastmaterialAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $tos_kode = $request->getParam('tos_kode');

        $sql = "SELECT geno2009 FROM master_typeofsuply WHERE tos_kode='$tos_kode'";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();

        $last = abs($data['geno2009']);
        $last = $last + 1;
        $trano = $tos_kode . substr($last, 1, 5);
        $return = array("success" => true, "kode_brg" => $trano);

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function insertmaterialAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

//        var_dump($jsonData2);die;

        $cek = false;
        $kode = $jsonData2[0]['kode_brg'];
        while (!$cek) {
            $fetch = $this->barang->fetchRow("kode_brg = '$kode'");
            if (!$fetch) {
                $cek = true;
            } else
                $kode++;
        }

        $counter = new Default_Models_MasterTypeOfSuply;
        $lastTrans = $counter->getTosData($jsonData2[0]['tos_kode']);
        $last = abs($lastTrans['geno2009']);
        $last = $last + 1;

        $where = "tos_kode=" . $jsonData2[0]['tos_kode'];
        $counter->update(array("geno2009" => $last), $where);

        $arrayInsert = array(
            "kode_brg" => $jsonData2[0]['kode_brg'],
            "nama_brg" => $jsonData2[0]['ket'],
            "sat_kode" => $jsonData2[0]['uom'],
            "ktg_kode" => $jsonData2[0]['ktg_kode'],
            "sktg_kode" => $jsonData2[0]['sktg_kode'],
            "tos_kode" => $jsonData2[0]['tos_kode'],
            "stspmeal" => $jsonData2[0]['stspmeal'],
            "harga_borong" => floatval($jsonData2[0]['price'])
        );

//            var_dump($arrayInsert);die();
        $this->barang->insert($arrayInsert);
//                $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true}");
    }

    public function updatematerialAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');

        $jsonData2 = Zend_Json::decode($this->getRequest()->getParam('etc'));

        $kodeBrg = $jsonData2[0]['kode_brg'];

        $arrayInsert = array(
//                "kode_brg" => $jsonData2[0]['kode_brg'],
            "nama_brg" => $jsonData2[0]['ket'],
            "sat_kode" => $jsonData2[0]['uom'],
            "ktg_kode" => $jsonData2[0]['ktg_kode'],
            "sktg_kode" => $jsonData2[0]['sktg_kode'],
//                "tos_kode" => $jsonData2[0]['tos_kode']
            "stspmeal" => $jsonData2[0]['stspmeal']
        );

        $this->barang->update($arrayInsert, "kode_brg = '$kodeBrg'");
//	        $arrayInsert = $this->workflow->convertFormat($arrayInsert,'sup_kode');
//            $result = $this->workflow->setWorkflowTrans($arrayInsert,'SUPP');
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{success: true}");
    }

    public function getPropinsiAction() {
        $this->_helper->viewRenderer->setNoRender();

        $start = $this->_getParam('start');
        $limit = $this->_getParam('limit');

        $select = $this->db->select()
                ->from(array($this->LOGISTIC->MasterPropinsi->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->order(array("nama ASC"))
                ->limit($limit, $start);

        $tmp = $this->db->fetchAll($select);
        $data['posts'] = $tmp;

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data['total'] = $count;

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($data);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getKabkotaAction() {
        $this->_helper->viewRenderer->setNoRender();

        $start = $this->_getParam('start');
        $limit = $this->_getParam('limit');
        $id_propinsi = $this->_getParam('id_propinsi');

        $select = $this->db->select()
                ->from(array($this->LOGISTIC->MasterKota->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->where("id_propinsi = $id_propinsi")
                ->order(array("nama ASC"))
                ->limit($limit, $start);

        $tmp = $this->db->fetchAll($select);
        $data['posts'] = $tmp;

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        $data['total'] = $count;

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($data);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cekIsuppAction() {
        $this->_helper->viewRenderer->setNoRender();

        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $workid = $this->_getParam("workid");
        $kodeBrg = $this->_getParam("kode_brg");
        $namaBrg = $this->_getParam("nama_brg");
        $tranoPO = $this->_getParam("po_no");
        $tranoPR = $this->_getParam("pr_no");
        $qtyReq = $this->_getParam("qty_request");
        $isPO = ($this->_getParam("is_po") == 'true') ? true : false;
        $isDO = ($this->_getParam("is_do") == 'true') ? true : false;

        $edit = ($this->getRequest()->getParam('for_edit') == 'true') ? true : false;
        $tranoExclude = $this->_getParam("trano_exclude");
        if ($isPO) {
            $po = new Default_Models_ProcurementPod();
            $subselect = $this->db->select()
                    ->from(array($po->__name()))
                    ->where("prj_kode = ?", $prjKode)
                    ->where("sit_kode = ?", $sitKode)
                    ->where("workid = ?", $workid)
                    ->where("kode_brg = ?", $kodeBrg)
                    ->where("trano = ?", $tranoPO)
                    ->where("pr_no = ?", $tranoPR);

            $select = $this->db->select()
                    ->from(
                            array("a" => $subselect), array(
                        "total" => "COALESCE(SUM(a.qtyspl))",
                        "totalISUPP" => "COALESCE(SUM(b.qty))",
                            )
                    )
                    ->joinLeft(
                    array("b" => $this->isupp->__name()), "a.prj_kode = b.prj_kode
                    AND a.sit_kode = b.sit_kode
                    AND a.workid = b.workid
                    AND a.kode_brg = b.kode_brg
                    AND a.trano = b.po_no
                    AND a.pr_no = b.pr_no", array(
                "qty"
                    )
            );
        } elseif ($isDO) {
            $do = $this->LOGISTIC->LogisticDod;
            $selectDO = $do->getSelect()->getDetail();
            $selectDO->where("prj_kode = ?", $prjKode)
                    ->where("sit_kode = ?", $sitKode)
                    ->where("workid = ?", $workid)
                    ->where("kode_brg = ?", $kodeBrg)
                    ->where("trano = ?", $tranoPO);

            $dor = $this->LOGISTIC->LogisticDord;
            $selectDOR = $this->db->select()
                    ->from(array("x" => $selectDO))
                    ->joinleft(
                    array("y" => $dor->__name()), "x.mdi_no = y.trano
                    AND x.prj_kode = y.prj_kode
                    AND x.sit_kode = y.sit_kode
                    AND x.workid = y.workid
                    AND x.kode_brg = y.kode_brg", array(
                "y.pr_no"
                    )
            );

            $subselect = $this->db->select()
                    ->from(array($selectDOR))
                    ->where("pr_no = ?", $tranoPR);

            $select = $this->db->select()
                    ->from(
                            array("a" => $subselect), array(
                        "total" => "COALESCE(SUM(a.qty))",
                        "totalISUPP" => "COALESCE(SUM(b.qty))",
                            )
                    )
                    ->joinLeft(
                    array("b" => $this->isupp->__name()), "a.prj_kode = b.prj_kode
                    AND a.sit_kode = b.sit_kode
                    AND a.workid = b.workid
                    AND a.kode_brg = b.kode_brg
                    AND a.trano = b.do_no
                    AND a.pr_no = b.pr_no", array(
                "qty"
                    )
            );
        }
        if ($edit && $tranoExclude) {
            $select2 = $this->db->select()
                    ->from(array($this->isupp->__name()))
                    ->where("trano != ?", $tranoExclude);

            $tmp = $this->db->select()
                    ->from(
                            array("a" => $subselect), array(
                        "qty"
                            )
                    )
                    ->joinLeft(
                    array("b" => $select2), "a.prj_kode = b.prj_kode
                    AND a.sit_kode = b.sit_kode
                    AND a.workid = b.workid
                    AND a.kode_brg = b.kode_brg
                    AND a.trano = b.po_no
                    AND a.pr_no = b.pr_no", array(
                "b.trano",
                "isupp_qty" => "COALESCE(b.qty,0)"
                    )
            );
            $select = $this->db->select()
                    ->from(array("x" => $tmp), array(
                "total" => "COALESCE(SUM(x.qty))",
                "totalISUPP" => "COALESCE(SUM(x.isupp_qty))",
            ));
        }
        $data = $this->db->fetchRow($select);

        $return = array("success" => false);
        if ($data) {
            if ($data['total'] == '')
                $data['total'] = 0;

            if ($data['total'] < ($qtyReq + $data['totalISUPP'])) {
                $return['msg'] = "Your Request QTY is greater than Existing i-Supp for this item : <b>" . $namaBrg . "</b>.";
            } else {
                $return['success'] = true;
                $return['total'] = $data['total'];
                $return['total_isupp'] = $data['totalISUPP'];
            }
        } else {
            $return['success'] = true;
        }

        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getDoSummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $workid = $this->_getParam("workid");
        $kodeBrg = $this->_getParam("kode_brg");
        $trano = $this->_getParam("trano");

        $data = $this->LOGISTIC->LogisticDoh->getDetail($trano, $prjKode, $sitKode, $workid, $kodeBrg);

        $return = array(
            "posts" => $data
        );
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
    
    public function getdodetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $trano = $this->_getParam("do_number");

        $data = $this->logisticDo->getDetail($trano, $prjKode, $sitKode);
        
        foreach($data as $key => $val)
        {
            $data[$key]['uom'] = $this->quantity->getUOMByProductID($data[$key]['kode_brg']);
        }

        $return['posts'] = $data;
        $return['count'] = count($data);
        $json = Zend_Json::encode($return);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getPoIsuppSummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $tranoEx = $this->getRequest()->getParam('trano_exclude');
        $edit = ($this->_getParam("edit") == 'true') ? true : false;

        $return = $this->DEFAULT->ProcurementPod->getDetail($trano);

        $id = 1;
        $result = array();
        foreach ($return as $key => $value) {
            $return[$key]['id'] = $id;
            $id++;
            foreach ($value as $key2 => $val2) {
                if ($val2 == "\"\"") {
                    $return[$key][$key2] = '';
                }
            }
            if ($value['tglpr'] != '' || $value['tglpr'] != "\"\"") {
                $return[$key]['tglpr'] = date('d-m-Y', strtotime($value['tglpr']));
            }
            $return[$key]['uom'] = $this->DEFAULT->MasterBarang->getUom($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\"", " inch", $return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'", " inch", $return[$key]['nama_brg']);

            $return[$key]['price'] = $return[$key]['hargaspl'];
            $return[$key]['totalPrice'] = $return[$key]['hargaspl'] * $return[$key]['qtyspl'];
            $return[$key]['qty'] = $return[$key]['qtyspl'];
            if ($edit) {
                $select = $this->LOGISTIC->LogisticInputSupplier->getSelect()->getPoIsuppQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                $select->where("po_no != '$tranoEx'");
                $isupp = $this->db->fetchRow($select);
            } else
                $isupp = $this->LOGISTIC->LogisticInputSupplier->getPoIsuppQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);

            if ($isupp != '') {
                $return[$key]['totalISUPP'] = $isupp['total_qty'];
                $return[$key]['totalPriceISUPP'] = $isupp['total_harga'];
                $return[$key]['balanceISUPP'] = $return[$key]['qtyspl'] - $isupp['total_qty'];
            } else {
                $return[$key]['totalISUPP'] = 0;
                $return[$key]['balanceISUPP'] = 0;
                $return[$key]['totalPriceISUPP'] = 0;
            }

            $poh = $this->DEFAULT->ProcurementPoh->getDetail($return[$key]['trano']);
            if ($poh != '') {
                $return[$key]['statusppn'] = $poh[0]['statusppn'];
            }

            $result[] = $return[$key];
        }
        $hasil['posts'] = $result;
        $hasil['count'] = count($return);
        $json = Zend_Json::encode($hasil);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getDoIsuppSummaryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam('trano');
        $tranoEx = $this->getRequest()->getParam('trano_exclude');
        $edit = ($this->_getParam("edit") == 'true') ? true : false;

        $return = $this->LOGISTIC->LogisticDod->getDetail($trano);

        $id = 1;
        $result = array();
        foreach ($return as $key => $value) {
            $return[$key]['id'] = $id;
            $id++;
            foreach ($value as $key2 => $val2) {
                if ($val2 == "\"\"") {
                    $return[$key][$key2] = '';
                }
            }
            if ($value['tglpr'] != '' || $value['tglpr'] != "\"\"") {
                $return[$key]['tglpr'] = date('d-m-Y', strtotime($value['tglpr']));
            }
            $return[$key]['uom'] = $this->DEFAULT->MasterBarang->getUom($return[$key]['kode_brg']);
            $return[$key]['nama_brg'] = str_replace("\"", " inch", $return[$key]['nama_brg']);
            $return[$key]['nama_brg'] = str_replace("'", " inch", $return[$key]['nama_brg']);

            $return[$key]['price'] = $return[$key]['harga'];
            $return[$key]['totalPrice'] = $return[$key]['harga'] * $return[$key]['qty'];

            if ($edit) {
                $select = $this->LOGISTIC->LogisticInputSupplier->getSelect()->getDoIsuppQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);
                $select->where("do_no != '$tranoEx'");
                $isupp = $this->db->fetchRow($select);
            } else
                $isupp = $this->LOGISTIC->LogisticInputSupplier->getDoIsuppQuantity($return[$key]['trano'], $return[$key]['prj_kode'], $return[$key]['sit_kode'], $return[$key]['workid'], $return[$key]['kode_brg']);

            if ($isupp != '') {
                $return[$key]['totalISUPP'] = $isupp['total_qty'];
                $return[$key]['totalPriceISUPP'] = $isupp['total_harga'];
                $return[$key]['balanceISUPP'] = $return[$key]['qty'] - $isupp['total_qty'];
            } else {
                $return[$key]['totalISUPP'] = 0;
                $return[$key]['balanceISUPP'] = 0;
                $return[$key]['totalPriceISUPP'] = 0;
            }

            //Get PR No from DOR
            $do = $this->LOGISTIC->LogisticDod;
            $selectDO = $do->getSelect()->getDetail();
            $selectDO->where("prj_kode = ?", $return[$key]['prj_kode'])
                    ->where("sit_kode = ?", $return[$key]['sit_kode'])
                    ->where("workid = ?", $return[$key]['workid'])
                    ->where("kode_brg = ?", $return[$key]['kode_brg'])
                    ->where("trano = ?", $return[$key]['trano']);

            $dor = $this->LOGISTIC->LogisticDord;
            $selectDOR = $this->db->select()
                    ->from(array("x" => $selectDO))
                    ->joinleft(
                    array("y" => $dor->__name()), "x.mdi_no = y.trano
                    AND x.prj_kode = y.prj_kode
                    AND x.sit_kode = y.sit_kode
                    AND x.workid = y.workid
                    AND x.kode_brg = y.kode_brg", array(
                "y.pr_no"
                    )
            );

            $dorData = $this->db->fetchRow($selectDOR);
            if ($dorData) {
                $return[$key]['pr_no'] = $dorData['pr_no'];
            }

            $result[] = $return[$key];
        }
        $hasil['posts'] = $result;
        $hasil['count'] = count($return);
        $json = Zend_Json::encode($hasil);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function updatedordetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $deliver_to = $this->_getParam("deliverto");
        $destkode = $this->_getParam("destkode");
        $fromkode = $this->_getParam("fromkode");
        $destnama = $this->_getParam("destnama");
        $fromnama = $this->_getParam("fromnama");
        $trano = $this->_getParam("trano");
        $recv_nama = $this->_getParam("recv_nama");
        $recv_tlp = $this->_getParam("recv_tlp");
        $alamat = $this->_getParam("alamat");
        $alamat1 = $this->_getParam("alamat1");

        $arrayInsert = array(
            "dest_kode" => $destkode,
            "dest_nama" => $destnama,
            "from_kode" => $fromkode,
            "from_nama" => $fromnama,
            "deliver_to" => $deliver_to,
            "receiver_nama" => $recv_nama,
            "receiver_tlp" => $recv_tlp,
            "alamat" => $alamat,
            "alamat1" => $alamat1
        );

        $update = $this->logisticH->update($arrayInsert, "trano = '$trano'");

        if ($update)
            $success = true;
        else
            $success = false;

        $return = array(
            "success" => $success
        );
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function checkinventorymappingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $deliver_to = $this->_getParam("deliverto");
        $destkode = $this->_getParam("destkode");
        $fromkode = $this->_getParam("fromkode");

        $jurnalinventory = new Finance_Models_MasterJurnalInventory();

        $coamapping = $jurnalinventory->fetchRow("type ='$deliver_to' and gdg_kode_from ='$fromkode' and gdg_kode_to ='$destkode' ");

        if ($coamapping)
            $success = true;
        else
            $success = false;

        $return = array(
            "success" => $success
        );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function checkAveragePriceAction() {
        $this->_helper->viewRenderer->setNoRender();
        $kode_brg = $this->_getParam("kode_brg");

        $master_barang = new Default_Models_MasterBarang();
        $harga = $master_barang->getAvgPrice($kode_brg);

        if ($harga)
            $success = true;
        else
            $success = false;

        $return = array(
            "success" => $success,
            "price" => $harga
        );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function checkQuantityFromDoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $kode_brg = $this->_getParam("kode_brg");
        $gdg_kode = $this->_getParam("gdg_kode");

        $saldo = QDC_Inventory_Stock::factory()->getStockForDo(array(
            "kode_brg" => $kode_brg,
            "gdg_kode" => $gdg_kode,
            "tgl_current" => date('Y-m-d')
        ));


        $return = array(
            "saldo" => $saldo,
            "success" => true
        );

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>