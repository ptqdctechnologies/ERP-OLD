<?php

class Finance_ChargingController extends Zend_Controller_Action {

    private $ADMIN;
    private $DEFAULT;
    private $FINANCE;
    private $PROC;
    private $workflow;
    private $const;
    private $db;

    public function init() {
        $this->ADMIN = QDC_Model_Admin::init(array(
                    "Workflowtrans"
        ));

        $this->FINANCE = QDC_Model_Finance::init(array(
                    "TemporaryCharging"
        ));

        $this->DEFAULT = QDC_Model_Default::init(array(
                    "MasterProject",
                    "MasterSite"
        ));

        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get("db");
    }

    public function menuAction() {
        
    }

    public function addAction() {
        
    }

    public function editAction() {
        $asfh = new Default_Models_AdvanceSettlementFormH();
        $asfd = new Default_Models_AdvanceSettlementFormD();
        $asfdd = new Default_Models_AdvanceSettlementForm();
        $bankOut = new Finance_Models_BankSpendMoney();

        $trano = $this->_getParam("trano");

        $firstData = $asfdd->fetchAll("trano = '$trano'");
        $bankData = $bankOut->fetchAll("ref_number = '$trano'");

        $result = array();

        if ($bankData) {
            $bankData = $bankData->toArray();
            $firstData = $firstData->toArray();

            foreach ($bankData as $key => $v) {

                foreach ($firstData as $key2 => $v2) {
                    $total = ($v['debit'] == 0 ? $v['credit'] : $v['debit']);

                    //debit section
                    if ($v['debit'] != 0) {
                        if ($v['prj_kode'] == $v2['prj_kode'] && $v['sit_kode'] == $v2['sit_kode'] && $total == $v2['total']) {
                            $bankData[$key]['prj_kode'] = $v2['prj_kode'];
                            $bankData[$key]['prj_nama'] = $v2['prj_nama'];
                            $bankData[$key]['sit_kode'] = $v2['sit_kode'];
                            $bankData[$key]['sit_nama'] = $v2['sit_nama'];
                            $bankData[$key]['workid'] = $v2['workid'];
                            $bankData[$key]['workname'] = $v2['workname'];
                            $bankData[$key]['kode_brg'] = $v2['kode_brg'];
                            $bankData[$key]['nama_brg'] = $v2['nama_brg'];
                            $bankData[$key]['ket'] = $v2['ket'];
                            $bankData[$key]['qty'] = $v2['qty'];
                            $bankData[$key]['harga'] = $v2['harga'];
                            $bankData[$key]['total'] = $v2['total'];
                            $bankData[$key]['val_kode'] = $v2['val_kode'];
                            $bankData[$key]['debit'] = $v['debit'];
                            $bankData[$key]['debit_coa'] = $v['coa_kode'];
                            $bankData[$key]['debit_coa_nama'] = $v['coa_nama'];
                            $bankData[$key]['credit'] = 0;
                            $bankData[$key]['credit_coa'] = '';
                            $bankData[$key]['credit_coa_nama'] = '';
                            $bankData[$key]['bank_type'] = substr($v['trano'], 0, 3);
                            $bankData[$key]['ref_number'] = str_replace('""',"",$v2['arf_no']);
                            break;
                        }
                    } else {
                        $bankData[$key]['prj_kode'] = $v['prj_kode'];
                        $bankData[$key]['prj_nama'] = '';
                        $bankData[$key]['sit_kode'] = $v['sit_kode'];
                        $bankData[$key]['sit_nama'] = '';
                        $bankData[$key]['workid'] = '';
                        $bankData[$key]['workname'] = '';
                        $bankData[$key]['kode_brg'] = '';
                        $bankData[$key]['nama_brg'] = '';
                        $bankData[$key]['ket'] = '';
                        $bankData[$key]['qty'] = '';
                        $bankData[$key]['harga'] = '';
                        $bankData[$key]['total'] = $total;
                        $bankData[$key]['val_kode'] = $v['val_kode'];
                        $bankData[$key]['debit'] = 0;
                        $bankData[$key]['debit_coa'] = '';
                        $bankData[$key]['debit_coa_nama'] = '';
                        $bankData[$key]['credit'] = $v['credit'];
                        $bankData[$key]['credit_coa'] = $v['coa_kode'];
                        $bankData[$key]['credit_coa_nama'] = $v['coa_nama'];
                        $bankData[$key]['bank_type'] = substr($v['trano'], 0, 3);
                        $bankData[$key]['ref_number'] = str_replace('""',"",$v2['arf_no']);
                        break;
                    }
                }
            }
        }

        Zend_Loader::loadClass('Zend_Json');
        $this->view->tgl = date("Y-m-d",  strtotime($bankData[0]['tgl']));
        $jsonData = Zend_Json::encode($bankData);
        $this->view->json = $jsonData;
        $this->view->trano = $trano;
    }

    public function insertChargingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = ($this->_getParam("data")) ? Zend_Json::decode($this->_getParam("data")) : array();
        $tgl = $this->_getParam("tgl");

        $prjKode = '1000'; //Overhead

        $items = $data[0];
        $comment = $this->_getParam("comment");
        $items["prj_kode"] = $prjKode;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "OCA",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $prjKode,
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        if ($tgl != '')
            $tgl = date("Y-m-d", strtotime($tgl));
        else
            $tgl = date("Y-m-d H:i:s");
        
        $uid = QDC_User_Session::factory()->getCurrentUID();
        $data = $this->_getParam("data");
        $arrayInsert = array(
            "trano" => $trano,
            "prj_kode" => $prjKode,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date("Y-m-d H:i:s"),
            "data" => $this->_getParam("data"),
            "tgl_change" => $tgl
        );
        
        $this->FINANCE->TemporaryCharging->insert($arrayInsert);

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function updateChargingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data = ($this->_getParam("data")) ? Zend_Json::decode($this->_getParam("data")) : array();
        $tgl = $this->_getParam("tgl");

        $prjKode = '1000'; //Overhead

        $trano = $this->_getParam("trano");
        $tranoWorkflow = $trano;

        //Bypass workflow
//        $items = $data;
//        $comment = $this->_getParam("comment");
//        $items["prj_kode"] = $prjKode;
//        $items['next'] = $this->getRequest()->getParam('next');
//        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
//        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
//        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
//        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
//        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
//        $params = array(
//            "workflowType" => "OCA",
//            "paramArray" => '',
//            "approve" => $this->const['DOCUMENT_RESUBMIT'],
//            "itemID" => $tranoWorkflow,
//            "items" => $items,
//            "prjKode" => $prjKode,
//            "generic" => false,
//            "revisi" => false,
//            "returnException" => false,
//            "comment" => $comment
//        );
//        $this->workflow->setWorkflowTransNew($params);

        if ($tgl != '')
            $tgl = date("Y-m-d", strtotime($tgl));
        else
            $tgl = date("Y-m-d H:i:s");

        $before = $this->FINANCE->TemporaryCharging->fetchAll("trano = '$trano'");
        if ($before) {
            $log['temporary-detail-before'] = $before->toArray();
            $this->FINANCE->TemporaryCharging->delete("trano = '$trano'");

            $arrayInsert = array(
                "trano" => $trano,
                "prj_kode" => $prjKode,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date("Y-m-d H:i:s"),
                "data" => $this->_getParam("data"),
                "tgl_change" => $tgl,
                "edit" => 1
            );

            $this->FINANCE->TemporaryCharging->insert($arrayInsert);

            $log2 = $this->FINANCE->TemporaryCharging->fetchAll("trano = '$trano'")->toArray();

            $arrayLog = array(
                "trano" => $trano,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => $prjKode,
                "sit_kode" => '',
                "action" => "UPDATE",
                "data_before" => Zend_Json::encode($log),
                "data_after" => Zend_Json::encode($log2),
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $log = new Admin_Models_Logtransaction();
            $log->insert($arrayLog);

            $o = new Finance_Models_TemporaryCharging();
            $tranoJurnal = $o->transferOverheadCost($trano);

            $result = Zend_Json::encode(array("success" => true, "number" => $trano, "jurnal_number" => $tranoJurnal));
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($result);
        } else {
            $result = Zend_Json::encode(array("success" => false, "number" => 0));
            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($result);
        }
    }

    public function appChargingAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");

        $this->view->show = $show;
        $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve || $show) {
            $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $docs = $docs->toArray();
                $trano = $docs['item_id'];
                $docsId = $approve;
                $approve = $trano;

                if ($docs['approve'] == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;

                $data = $this->FINANCE->TemporaryCharging->fetchRow("trano='$trano'");
                if ($data) {
                    $data = $data->toArray();
                    $data = Zend_Json::decode($data['data']);
                }

                $this->view->data = $data;

                $this->view->approve = true;

                if ($from == 'edit') {
                    $this->view->approve = false;
                    $this->view->edit = true;
                }

                if ($show) {
                    $this->view->approve = false;
                }

                $userApp = $this->workflow->getAllApproval($approve);
                $allReject = $this->workflow->getAllReject($approve);
                $lastReject = $this->workflow->getLastReject($approve);

                $this->view->lastReject = $lastReject;
                $this->view->user_approval = $userApp;
                $this->view->allReject = $allReject;
                $this->view->trano = $trano;
                $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                $this->view->docsID = $docsId;
            }
        }
    }

    public function cekWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $item_type = $this->_getParam("item_type");
        $finalOnly = ($this->_getParam("final") !== 'true') ? false : true;

        $isFinal = QDC_Workflow_Transaction::factory()->isDocumentFinal($trano);
        $isReject = QDC_Workflow_Transaction::factory()->isDocumentReject($trano);
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
        $result = Zend_Json::encode(array("success" => $valid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

}

?>