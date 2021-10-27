<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/8/11
 * Time: 1:57 PM
 * To change this template use File | Settings | File Templates.
 */
class Finance_CancelrpiController extends Zend_Controller_Action {

    private $workflowtrans;
    private $workflow;
    private $db;
    private $token;
    private $json;
    private $creTrans;
    private $log_trans;
    private $log;
    private $const;
    private $session;
    private $requestcancel, $closeAP, $bpvD, $bpvH;
    private $DEFAULT;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->const = Zend_Registry::get('constant');
        $this->workflowtrans = new Finance_Models_WorkflowTrans();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->log = new Admin_Models_Logtransaction();
        $this->requestcancel = new Finance_Models_AccountingCancelRPI();
        $this->rpi = new Default_Models_RequestPaymentInvoice();
        $this->rpiH = new Default_Models_RequestPaymentInvoiceH();
        $this->closeAP = new Finance_Models_AccountingCloseAP();
        $this->bpvD = new Finance_Models_BankPaymentVoucherD();
        $this->bpvH = new Finance_Models_BankPaymentVoucher();
        $this->json = $this->getRequest()->getParam('posts');
        if (isset($this->json)) {
            //Fix unknown JSON format (Bugs on Firefox 3.6)
            $this->json = str_replace("\\", "", $this->json);
            if (substr($this->json, 0, 1) != '[') {
                $this->json = "[" . $this->json . "]";
            }
        }
        $this->jsondocs = $this->getRequest()->getParam('document_valid');
        if (isset($this->jsondocs)) {
            //Fix unknown JSON format (Bugs on Firefox 3.6)
            $this->jsondocs = str_replace("\\", "", $this->jsondocs);
            if (substr($this->jsondocs, 0, 1) != '[') {
                $this->jsondocs = "[" . $this->jsondocs . "]";
            }
        }

        $this->DEFAULT = QDC_Model_Default::init(array(
                    "Files"
        ));
    }

    public function cancelrpimenuAction() {
        
    }

    public function cancelrpiAction() {
        $this->view->user = $this->session->userName;
    }

    public function editcancelrpiAction() {
        $this->view->user = $this->session->userName;
    }

    public function dorequestcancelrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        Zend_Loader::loadClass('Zend_Json');
        $user = $this->getRequest()->getParam('user');


        $jsonfile = Zend_Json::decode($this->_getParam("file"));

        $reason = $this->getRequest()->getParam('reason');
        $jsonData = str_replace('""', '', $this->json);
        $jsonData = Zend_Json::decode($this->json);
        $urut = 1;


        $items = $jsonData[0];
        $items['prj_kode'] = $jsonData[0]['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "CRPI",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false
        );

        $trano = $this->workflow->setWorkflowTransNew($params);


//        die;
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER["REMOTE_ADDR"];
        $hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $insertcancel = array(
            "trano" => $trano,
            "uid" => $this->session->userName,
            "date" => $date,
            "ref_number" => $jsonData[0]['trano'],
            "reason" => $reason,
            "ip" => $ip,
            "hostname" => $hostname
        );
        //new request rpi cancel  model
        $this->requestcancel->insert($insertcancel);

        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->DEFAULT->Files->insert($arrayInsert);
            }
        }

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function doupdatecancelrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        Zend_Loader::loadClass('Zend_Json');

        $user = $this->getRequest()->getParam('user');
        $tranoEdit = $this->getRequest()->getParam('trano_edit');

        $jsonfile = Zend_Json::decode($this->_getParam("file"));

        $reason = $this->getRequest()->getParam('reason');
        $jsonData = str_replace('""', '', $this->json);
        $jsonData = Zend_Json::decode($this->json);


        $items = $jsonData[0];
        $items['prj_kode'] = $jsonData[0]['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => CRPI,
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "itemID" => $tranoEdit,
            "items" => $items,
            "prjKode" => $jsonData[0]['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false
        );

        $this->workflow->setWorkflowTransNew($params);

         //log before   
        $old = $this->requestcancel->fetchAll("trano = '$tranoEdit'")->toArray();
        $log['crpi-detail-before'] = $old;
        $this->requestcancel->delete("trano = '$tranoEdit'");
 
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER["REMOTE_ADDR"];
        $hostname = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        $insertcancel = array(
            "trano" => $tranoEdit,
            "uid" => $this->session->userName,
            "date" => $date,
            "ref_number" => $jsonData[0]['trano'],
            "reason" => $reason,
            "ip" => $ip,
            "hostname" => $hostname
        );
        //new request rpi cancel  model
        $this->requestcancel->insert($insertcancel);
        
        $new = $this->requestcancel->fetchAll("trano = '$tranoEdit'")->toArray();
        $log2['crpi-detail-after'] = $new;
        
        $this->DEFAULT->Files->delete("trano = '$tranoEdit'");
        
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $tranoEdit,
                    "prj_kode" => $jsonData[0]['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->DEFAULT->Files->insert($arrayInsert);
            }
        }
        
         $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $tranoEdit,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonData[0]['prj_kode'],
            "sit_kode" => $jsonData[0]['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function formcancelrpiAction() {
        $rpitrano = $this->getRequest()->getParam('trano');
//        $id_cancel = $this->getRequest()->getParam('id_cancel');

        $rpidata = $this->rpiH->fetchAll("trano = '$rpitrano'")->toArray();

        $userApp = $this->workflow->getAllApproval($rpitrano);

        $sql = "SELECT * FROM procurement_rpid WHERE trano = '$rpitrano' GROUP BY trano;";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        $rpipaydata = null;
        if ($hasil) {
            if (count($hasil) > 0) {
//                $rpidata = $hasil;
                foreach ($hasil as $key => $val) {
//                    $result['This document has PO'][] = $val['po_no'];
                    $rpi = $val['trano'];
                    $sql = "SELECT * FROM finance_payment_rpi WHERE doc_trano = '$rpi'";
                    $fetch = $this->db->query($sql);
                    $hasil2 = $fetch->fetchAll();

                    if ($hasil2) {
                        foreach ($hasil2 as $key2 => $val2) {
                            $rpipaydata[] = $val2;
                        }
                    }
                }
            }
        }
//        if ($id_cancel) {
//            $cancel = $this->requestcancel->fetchRow("id = $id_cancel");
//
//            $cancel = $cancel->toArray();
//        }
        $ldap = new Default_Models_Ldap();
//        $this->view->reason = $cancel['reason'];
//        $this->view->requesterName = QDC_User_Ldap::factory(array("uid" => $cancel['uid']))->getName();
//        $this->view->rpipaydata = $rpipaydata;
        $this->view->rpidata = $rpidata;
//        $this->view->id_cancel = $id_cancel;
        $this->view->approval = $userApp;
    }

    public function appcancelrpiAction() {
        $type = $this->getRequest()->getParam("type");
        
        $from = ($this->_getParam("edit") != '') ? true : false;
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;
        $jsonFile = $this->getRequest()->getParam("file");
        if ($jsonFile)
            $file = Zend_Json::decode($jsonFile);
        else
            $file = array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/CRPI';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        $preview = $this->getRequest()->getParam("preview");
        if ($approve == '') {
            $rpitrano = $this->getRequest()->getParam("trano");
            $reason = $this->getRequest()->getParam("reason");
            $this->view->trano_preview = $rpitrano;
            $rpidata = $this->rpiH->fetchAll("trano = '$rpitrano'")->toArray();
            $rpidata = str_replace('""', '', $rpidata);
            foreach ($rpidata as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    if ($v2 == "\"\"")
                        $rpidata[$k][$k2] = '';
                    if ($v2 == '""')
                        $rpidata[$k][$k2] = '';
                }
                if ($v['document_valid']) {
                    Zend_Loader::loadClass('Zend_Json');
                    $document_valid[$k]['document_valid'] = Zend_Json::encode($v['document_valid']);
                }

                unset($rpidata[$k]['document_valid']);
            }
            $userApp = $this->workflow->getAllApproval($rpitrano);

            $sql = "SELECT * FROM procurement_rpid WHERE trano = '$rpitrano' GROUP BY trano;";
            $fetch = $this->db->query($sql);
            $hasil = $fetch->fetchAll();

            $rpipaydata = null;
            if ($hasil) {
                if (count($hasil) > 0) {
                    foreach ($hasil as $key => $val) {
                        $rpi = $val['trano'];
                        $sql = "SELECT * FROM finance_payment_rpi WHERE doc_trano = '$rpi'";
                        $fetch = $this->db->query($sql);
                        $hasil2 = $fetch->fetchAll();

                        if ($hasil2) {
                            foreach ($hasil2 as $key2 => $val2) {
                                $rpipaydata[] = $val2;
                            }
                        }
                    }
                }
            }

            $this->view->reason = $reason;
            $myUid = $this->session->userName;
            $ldap = new Default_Models_Ldap();
            $myName = QDC_User_Ldap::factory(array("uid" => $myUid))->getName();
            $this->view->requesterName = $myName;
            $this->view->rpipaydata = $rpipaydata;
            $this->view->rpidata = $rpidata;
            Zend_Loader::loadClass('Zend_Json');
            $jsonRpiData = Zend_Json::encode($rpidata);

            $this->view->document_valid = Zend_Json::encode($document_valid);
            $this->view->jsonrpidata = $jsonRpiData;
            $this->view->approval = $userApp;

            $this->view->jsonFile = $jsonFile;
            $this->view->file = $file;

            if ($from) {
                $trano = $data['trano'];
                $this->view->trano = $rpitrano;
                $this->view->edit = true;
                $tranoEdit = $this->getRequest()->getParam("trano_edit");
                $this->view->tranoEdit = $tranoEdit;
            }
            
           
        } else {
            $docs = $this->workflowtrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->session->idUser);
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $approve = $docs['item_id'];
                    $statApprove = $docs['approve'];

                    $this->workflowtrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'", array(''));

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $userApp = $this->workflow->getAllApprovalGeneric($approve);
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;

                    $rpidatacancel = $this->requestcancel->fetchRow("trano= '$approve'");
                    if ($rpidatacancel->toArray()) {
                        $rpidatacancel = $rpidatacancel->toArray();
                        $rpi = $rpidatacancel['ref_number'];
                        $reason = $rpidatacancel['reason'];
                        $myUid = $rpidatacancel['uid'];
                    }

                    $rpidata = $this->rpiH->fetchAll("trano = '$rpi'")->toArray();
                    $this->view->reason = $reason;
                    $ldap = new Default_Models_Ldap();
                    $myName = QDC_User_Ldap::factory(array("uid" => $myUid))->getName();
                    $this->view->requesterName = $myName;
                    $this->view->rpidata = $rpidata;

                    $sql = "SELECT * FROM procurement_rpid WHERE trano = '$rpi' GROUP BY trano;";
                    $fetch = $this->db->query($sql);
                    $hasil = $fetch->fetchAll();

                    $rpipaydata = null;
                    if ($hasil) {
                        if (count($hasil) > 0) {
                            foreach ($hasil as $key => $val) {
                                $rpi = $val['trano'];
                                $sql = "SELECT * FROM finance_payment_rpi WHERE doc_trano = '$rpi'";
                                $fetch = $this->db->query($sql);
                                $hasil2 = $fetch->fetchAll();

                                if ($hasil2) {
                                    foreach ($hasil2 as $key2 => $val2) {
                                        $rpipaydata[] = $val2;
                                    }
                                }
                            }
                        }
                    }

                    $this->view->rpipaydata = $rpipaydata;
                    $this->view->approval = $userApp;

                    $this->view->trano = $approve;
                    $this->view->trano_preview = $rpidata[0]['trano'];
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->preview = $preview;

                    $filemodel = new Default_Models_Files();
                    $file = $filemodel->fetchAll("trano = '$approve'");
                    $this->view->file = $file;
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function getdataCrpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->_getParam("trano");

        $data = $this->requestcancel->fetchAll("trano = '$trano'")->toArray();

        $filemodel = new Default_Models_Files();
        $file = $filemodel->fetchAll("trano = '$trano'")->toArray();
        $data_file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));

        $result = array(
            "success" => true,
            "data" => $data,
            "file" => $data_file
        );

        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
