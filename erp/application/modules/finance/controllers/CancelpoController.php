<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/8/11
 * Time: 1:57 PM
 * To change this template use File | Settings | File Templates.
 */
class Finance_CancelpoController extends Zend_Controller_Action {

    private $poheader;
    private $podetail;
    private $workflowtrans;
    private $workflow;
    private $db;
    private $token;
    private $creTrans;
    private $log_trans;
    private $log;
    private $session;
    private $requestcancel;
    private $model;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->poheader = new Finance_Models_POHeader();
        $this->session = new Zend_Session_Namespace('login');
        $this->podetail = new Finance_Models_PODetail();
        $this->workflowtrans = new Admin_Models_Workflowtrans();
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->creTrans = new Admin_Model_CredentialTrans();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->log = new Admin_Models_Logtransaction();
        $this->requestcancel = new Procurement_Model_RequestCancel();
        $this->model = Zend_Controller_Action_HelperBroker::getStaticHelper('model');
    }

    public function cancelpomenuAction() {
        
    }

    public function formcancelpoAction() {
        $potrano = $this->getRequest()->getParam('trano');
        $id_cancel = $this->getRequest()->getParam('id_cancel');

        $podata = $this->poheader->fetchAll(" trano = '$potrano'  ")->toArray();

//        $poworkflow = $this->workflowtrans->fetchRow ("item_id = '$potrano'");
        $userApp = $this->workflow->getAllApproval($potrano);

        $sql = "SELECT * FROM procurement_rpid WHERE po_no = '$potrano' GROUP BY trano;";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();
        $rpidata = null;
        $rpipaydata = null;
        if ($hasil) {
            if (count($hasil) > 0) {
                $rpidata = $hasil;
                foreach ($hasil as $key => $val) {
                    $result['This document has RPI'][] = $val['trano'];
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

        $cancel = $this->requestcancel->fetchRow("id = $id_cancel");
        $cancel = $cancel->toArray();

        $ldap = new Default_Models_Ldap();
//        $acc = $ldap->getAccount($cancel['uid']);
        $this->view->reason = $cancel['reason'];
//        $this->view->requesterName = $acc['displayname'][0];
        $this->view->requesterName = QDC_User_Ldap::factory(array("uid" => $cancel['uid']))->getName();

        $this->view->rpipaydata = $rpipaydata;
        $this->view->rpidata = $rpidata;
        $this->view->potrano = $potrano;
        $this->view->id_cancel = $id_cancel;
        $this->view->podata = $podata;
        $this->view->approval = $userApp;
    }

    public function docancelpoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('potrano');
        $id_cancel = $this->getRequest()->getParam('id_cancel');
        $isRpi = $this->getRequest()->getParam('isrpi');

        if ($isRpi == '')
            $isRpi = false;
        else
            $isRpi = true;
        $request = $this->requestcancel->fetchRow("id = '$id_cancel'");

        $myUid = $this->session->userName;

        $token = $this->token->getDocumentSignatureByUserID($myUid);
        $tgl = $token['date'];
        $sign = $token['signature'];
        $fetch = $this->workflowtrans->fetchAll("item_id = '$trano'");

        if ($fetch) {
            $hasil = $fetch->toArray();
            $prjKode = $hasil[0]['prj_kode'];
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($hasil);

            $myClass = $this->model->getModelClass('PO');

            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => $tgl,
                "prj_kode" => $prjKode,
                "uid" => $myUid,
                "uid_requestor" => $request['uid'],
                "sign" => $sign,
                "reason" => 'PO CANCEL',
                "data" => $json,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->creTrans->insert($arrayInsert);
            $this->workflowtrans->delete("item_id = '$trano'");

            //updating approve status @transaction table
            $this->workflowtrans->updateStatusApprove($myClass, $hasil[0]['item_type'], $trano, 300);
        }

        if (!$isRpi) {
            $log['po-detail-before'] = $this->podetail->fetchAll("trano = '$trano'")->toArray();
            $updatePOdetail = array(
                "qty" => 0,
                "qtyspl" => 0
            );
            $this->podetail->update($updatePOdetail, "trano = '$trano'");
            $log2['po-detail-after'] = $this->podetail->fetchAll("trano = '$trano'")->toArray();

            $log['po-header-before'] = $this->poheader->fetchRow("trano = '$trano'")->toArray();
            $updatePOheader = array(
                "revisi"=> intval($log['po-header-before']['revisi'])+1,
                "total" => 0,
                "totalspl" => 0,
                "jumlahspl" => 0,
                "jumlah" => 0,
                "ppn" => 0,
                "ppnspl" => 0
            );
            $this->poheader->update($updatePOheader, "trano = '$trano'");
            $log2['po-header-after'] = $this->poheader->fetchRow("trano = '$trano'")->toArray();

            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);

            $arrayLog = array(
                "trano" => $trano,
                "uid" => $this->session->userName,
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => $log['po-header-before']['prj_kode'],
                "sit_kode" => $log['po-header-before']['sit_kode'],
                "action" => "CANCEL",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
        }
        $updateRequest = array(
            "stsproses" => 1
        );

        $this->requestcancel->update($updateRequest, "id = '$id_cancel'");

        $myUid = $this->session->userName;
        $ldap = new Default_Models_Ldap();
//        $acc = $ldap->getAccount($myUid);
//        $myName = $acc['displayname'][0];
        $myName = QDC_User_Ldap::factory(array("uid" => $myUid))->getName();

        $conversation = new Default_Models_Conversation();
        $arrayInsert = array(
            "id_reply" => 0,
            "workflow_item_id" => 0,
            "uid_sender" => $myUid,
            "uid_receiver" => $request['uid'],
            "message" => "Your request for cancel Purchase Order with number : $trano has been Approved by $myName. Use Edit PO Transaction for Editing & Resubmit this PO to Workflow.",
            "date" => date('Y-m-d H:i:s'),
            "trano" => $trano,
            "prj_kode" => ''
        );

        $conversation->insert($arrayInsert);

        echo "{success: true}";

        $return = array("success" => true);

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

        $data = $this->poheader->finalpolist($offset, $limit, $dir, $sort, $search);

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
