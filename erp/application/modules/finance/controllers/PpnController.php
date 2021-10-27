<?php
class Finance_PpnController extends Zend_Controller_Action
{
    private $ADMIN;
    private $DEFAULT;
    private $FINANCE;
    private $PROC;

    private $workflow;
    private $const;
    private $db;

    public function init()
    {
        $this->ADMIN = QDC_Model_Admin::init(array(
            "Workflowtrans"
        ));

        $this->FINANCE = QDC_Model_Finance::init(array(
            "PpnReimbursementH",
            "PpnReimbursementD",
            "PaymentPpnReimbursementD",
            "PaymentPpnReimbursementH",
            "PpnReimbursementSettleH",
            "PpnReimbursementSettleD",
            "AccountingTemporaryJurnalAP",
            "AccountingTemporaryBPV",
        ));

        $this->DEFAULT = QDC_Model_Default::init(array(
            "RequestPaymentInvoiceH",
            "MasterProject",
            "MasterSite",
            "Files"
        ));

        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
        $this->db = Zend_Registry::get("db");
    }

    public function ppnRemAction()
    {

    }

    public function appPpnRemAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");

        $jsonFile = $this->_getParam("file");
        if ($jsonFile)
            $file =Zend_Json::decode($jsonFile);
        else
            $file = array();
        $deletedFile = $this->getRequest()->getParam('deletedfile');

        $this->view->show = $show;
        $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '')
        {
            $data = Zend_Json::decode($this->_getParam("data"));

            $this->view->data = $data;

            $this->view->json = array(
                "data" => $this->_getParam("data"),
            );

            $this->view->jsonFile = $jsonFile;
            $this->view->file = $file;

            if ($from == 'edit')
            {
                $trano = $this->_getParam("trano");
                $this->view->trano = $trano;
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }
        }
        elseif($approve || $show)
        {
            $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs)
            {
                $docs = $docs->toArray();
                $trano = $docs['item_id'];
                $docsId = $approve;
                $approve = $trano;

                if ($docs['approve'] == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;

//                $data = $this->FINANCE->PpnReimbursementD->fetchAll("trano='$trano'");
//                if ($data)
//                {
//                    $data = $data->toArray();
//                    foreach($data as $k => $v)
//                    {
//                        $data[$k]['total_ppn'] = $v['total'];
//                        $data[$k]['total'] = $v['total_rpi'];
//                    }
//                }
//                $data_header = $this->FINANCE->PpnReimbursementH->fetchRow("trano='$trano'");
//                if ($data)
//                {
//                    $data_header = $data_header->toArray();
//                }

                $data = $this->FINANCE->PpnReimbursementH->fetchRow("trano='$trano'");
                if ($data)
                {
                    $data = $data->toArray();
                }


                $this->view->data = $data;
//                $this->view->data_header = $data_header;

                $this->view->json = array(
                    "data" => $this->_getParam("data"),
//                    "data_header" => $this->_getParam("data_header"),
                );

                $this->view->jsonFile = $jsonFile;
                $file = $this->DEFAULT->Files->fetchAll("trano = '$trano'");
                $file = $file->toArray();

                $this->view->file = $file;

                $this->view->approve = true;

                if ($from == 'edit')
                {
                    $this->view->deletedFile = $deletedFile;
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

    public function indexAction()
    {

    }

    public function insertPpnRemAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $data = Zend_Json::decode($this->_getParam("data"));
        $file = $this->_getParam("file");
        if ($file)
            $file =Zend_Json::decode($file);
        $comment = $this->_getParam("comment");

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'],$data['sit_kode']);
        
        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        
        $params = array(
            "workflowType" => "PPNREM",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);
        
        $total = $data['total'];

//        $arrayBPV[] = array(
//            "trano" => '',
//            "tgl" => '',
//            "item_type" => 'PPNREM',
//            "total_value" => $total,
//            "total_bayar" => $total,
//            "statusppn" => 'N',
//            "valueppn" => 0,
//            "coa_ppn" => '',
//            "grossup_status" => 'N',
//            "holding_tax_status" => 'N',
//            "holding_tax" => 0,
//            "holding_tax_val" => 0,
//            "holding_tax_text" => '',
//            "coa_holding_tax" => '',
//            "deduction" => 0,
//            "total" => $total,
//            "valuta" => $data['val_kode'],
//            "prj_kode" => $data['prj_kode'],
//            "sit_kode" => $data['sit_kode'],
//            "ref_number" => $trano,
//            "coa_kode" => '',
//            "ketin" => $data['ket'],
//            "requester" => QDC_User_Ldap::factory(array(
//                "uid" => QDC_User_Session::factory()->getCurrentUID()
//            ))->getName(),
//            "uid" => QDC_User_Session::factory()->getCurrentUID(),
//            "statuspulsa" => '',
//            "trano_ppn" => '',
//            "ppn_ref_number" => '',
//            "status_bpv_ppn" => '',
//            "productid" => '',
//            "type" => 'ppnrem',
//            "pr_no" => '',
//            "rateidr" => $data['rateidr']
//        );
//
//        if ($data['val_kode'] == 'IDR')
//        {
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//            $coa = "2-1110";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//        }
//        else
//        {
//            //USD Valuta
////            $rateidr = ($data['rateidr'] == '' || $data_header['rateidr'] == 0) ? QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate() : $data['rateidr'];
//            $rateidr = ($data['rateidr'] == '' || $data['rateidr'] == 0) ? QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate() : $data['rateidr'];
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => ($total * $rateidr) - $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//            $coa = "2-1121";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//            $coa = "2-1122";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => ($total * $rateidr) - $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano,
//                "memo" => "PPN Reimbursement"
//            );
//        }
//
//        $this->FINANCE->AccountingTemporaryBPV->insert(
//            array(
//                "trano" => $trano,
//                "data" => Zend_Json::encode($arrayBPV)
//            ));
//        $this->FINANCE->AccountingTemporaryJurnalAP->insert(
//            array(
//                "trano" => $trano,
//                "jurnal" => Zend_Json::encode($jurnal)
//            )
//        );
//        if ($val['val_kode'] == 'IDR')
//            $harga = $val['hargaIDR'];
//        else
//            $harga = $val['hargaUSD'];
//
//        $total = $val['qty'] * $val['harga'];
//        $arrayInsert = array(
//            "trano" => $trano,
//            "tgl" => date('Y-m-d'),
//            "urut" => $urut,
//                "cus_kode" => $jsonEtc[0]['cus_kode'],
//                "prj_kode" => $val['prj_kode'],
//                "prj_nama" => $val['prj_nama'],
//                "sit_kode" => $val['sit_kode'],
//                "sit_nama" => $val['sit_nama'],
//                "workid" => $val['workid'],
//                "workname" => $val['workname'],
//                "kode_brg" => $val['kode_brg'],
//                "nama_brg" => $val['nama_brg'],
//                "qty" => $val['qty'],
//                "harga" => $val['harga'],
//                "jumlah" => $total,
//                "ket" => $val['ket'],
//                "petugas" => $this->session->userName,
//                "val_kode" => $val['val_kode'],
//                "type" => 'P'
//            );
//            $urut++;
//            $totals = $totals + $total;
//
////                var_dump($arrayInsert);die;
//            $this->reimbursD->insert($arrayInsert);
//            $activityDetail['procurement_reimbursementd'][$activityCount]=$arrayInsert;
//            $urut++;
//            $activityCount++;

        //Insert Header
        $arrayInsert = array(
            "trano" => $trano,
            "po_no" => $data['po_no'],
//            "rpi_no" => $data['trano'],
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "sit_kode" => $data['sit_kode'],
            "total" => $total,
            "val_kode" => $data['val_kode2'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date("Y-m-d H:i:s"),
            "faktur_pajak" => $data['faktur_pajak'],
            "ket" => $data['ket'],
            "rateidr" => $data['rateidr']
        );

        $this->FINANCE->PpnReimbursementH->insert($arrayInsert);

        if ($file)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => '',
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->DEFAULT->Files->insert($arrayInsert);
            }
        }

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function updatePpnRemAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $data = Zend_Json::decode($this->_getParam("data"));
//        $data_header = Zend_Json::decode($this->_getParam("data_header"));
        $comment = $this->_getParam("comment");

        $trano = $data['trano'];

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'],$data['sit_kode']);

        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PPNREM",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "itemID" => $trano
        );
        $this->workflow->setWorkflowTransNew($params);

        $total = 0;

        $log = $log2 = array();

        $prev = $this->FINANCE->PpnReimbursementH->fetchRow($this->db->quoteInto("trano=?",$trano));
        if($prev)
        {
            $prev = $prev->toArray();
            $log['ppnrem-header-before']=$prev;
        }
        //Insert Header
        $arrayInsert = array(
            "po_no" => $data['po_no'],
//            "rpi_no" => $data['trano'],
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "sit_kode" => $data['sit_kode'],
            "total" => $total,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "faktur_pajak" => $data['faktur_pajak'],
            "ket" => $data['ket'],
            "rateidr" => $data['rateidr']
        );

        $this->FINANCE->PpnReimbursementH->update($arrayInsert,$this->db->quoteInto("trano=?",$trano));

        $curr = $this->FINANCE->PpnReimbursementH->fetchRow($this->db->quoteInto("trano=?",$trano));
        if($curr)
        {
            $curr = $curr->toArray();
            $log2['ppnrem-header-after']=$curr;
        }

        //Update Jurnal & BPV if exist
//        $arrayBPV[] = array(
//            "trano" => '',
//            "tgl" => '',
//            "item_type" => 'PPNREM',
//            "total_value" => $total,
//            "total_bayar" => $total,
//            "statusppn" => 'N',
//            "valueppn" => 0,
//            "coa_ppn" => '',
//            "grossup_status" => 'N',
//            "holding_tax_status" => 'N',
//            "holding_tax" => 0,
//            "holding_tax_val" => 0,
//            "holding_tax_text" => '',
//            "coa_holding_tax" => '',
//            "deduction" => 0,
//            "total" => $total,
//            "valuta" => $data['val_kode'],
//            "prj_kode" => $data['prj_kode'],
//            "sit_kode" => $data['sit_kode'],
//            "ref_number" => $trano,
//            "coa_kode" => '',
//            "ketin" => $data['ket_ppn'],
//            "requester" => QDC_User_Ldap::factory(array(
//                    "uid" => QDC_User_Session::factory()->getCurrentUID()
//                ))->getName(),
//            "uid" => QDC_User_Session::factory()->getCurrentUID(),
//            "statuspulsa" => '',
//            "trano_ppn" => '',
//            "ppn_ref_number" => '',
//            "status_bpv_ppn" => '',
//            "productid" => '',
//            "type" => 'ppnrem',
//            "pr_no" => ''
//        );
//
//        if ($data['val_kode'] == 'IDR')
//        {
//
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//            $coa = "2-1110";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//        }
//        else
//        {
//            //USD Valuta
//            $rateidr = ($data['rateidr'] == '' || $data['rateidr'] == 0) ? QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate() : $data['rateidr'];
//
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//            $coa = "1-4400";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "debit" => ($total * $rateidr) - $total,
//                "credit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//            $coa = "2-1121";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//            $coa = "2-1122";
//            $coas = QDC_Finance_Coa::factory()->getCoa(array("coa_kode" => $coa));
//            $jurnal[] = array(
//                "coa_kode" => $coa,
//                "coa_nama" => $coas['coa_nama'],
//                "credit" => ($total * $rateidr) - $total,
//                "debit" => 0,
//                "prj_kode" => $data['prj_kode'],
//                "sit_kode" => $data['sit_kode'],
//                "ref_number" => $trano
//            );
//        }
//
//        $this->FINANCE->AccountingTemporaryBPV->delete("trano='$trano'");
//        $this->FINANCE->AccountingTemporaryBPV->insert(
//            array(
//                "trano" => $trano,
//                "data" => Zend_Json::encode($arrayBPV)
//            ));
//        $this->FINANCE->AccountingTemporaryJurnalAP->delete("trano='$trano'");
//        $this->FINANCE->AccountingTemporaryJurnalAP->insert(
//            array(
//                "trano" => $trano,
//                "jurnal" => Zend_Json::encode($jurnal)
//            )
//        );

        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

        $file = $this->_getParam("file");
        if ($file)
            $file =Zend_Json::decode($file);

        if ($file)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
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

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function editPpnRemAction()
    {
        $trano = $this->_getParam("trano");
        $this->view->trano = $trano;

//        $data = $this->FINANCE->PpnReimbursementD->fetchAll("trano='$trano'");
//        if ($data)
//        {
//            $data = $data->toArray();
//            foreach($data as $k => $v)
//            {
//                $data[$k]['total_ppn'] = floatval($v['total']);
//                $data[$k]['total'] = floatval($v['total_rpi']);
//                $data[$k]['ppn_persen'] = floatval($v['ppn_persen']);
//
//                $select = $this->db->select()
//                    ->from(array($this->FINANCE->PpnReimbursementSettleD->__name()),array(
//                        "total_settle" => "COALESCE(SUM(qty*harga),0)"
//                    ))
//                    ->where("trano_ppn = ?",$v['trano'])
//                    ->where("rpi_no = ?",$v['rpi_no'])
//                    ->where("po_no = ?",$v['po_no'])
//                    ->where("prj_kode = ?",$v['prj_kode'])
//                    ->where("sit_kode = ?",$v['sit_kode'])
//                    ->where("workid = ?",$v['workid'])
//                    ->where("kode_brg = ?",$v['kode_brg'])
//                    ->group(array("trano_ppn"));
//
//                $tot = $this->db->fetchRow($select);
//                $data[$k]['total_settle'] = ($tot['total_settle'] != '') ? $tot['total_settle'] : 0;
//            }
//        }
//        else
//            $data = array();
//
//        $data = $this->FINANCE->PpnReimbursementH->fetchRow("trano='$trano'");
//        if ($data_header)
//        {
//            $data_header = $data_header->toArray();
//        }
//        else
//            $data_header = array();

        $data = $this->FINANCE->PpnReimbursementH->fetchRow("trano='$trano'");
        if ($data)
        {
            $data = $data->toArray();

            $select = $this->db->select()
                ->from(array($this->FINANCE->PpnReimbursementSettleH->__name()),array(
                    "total_settle" => "COALESCE(SUM(total),0)"
                ))
                ->where("trano_ppn = ?",$data['trano'])
                ->where("po_no = ?",$data['po_no'])
                ->group(array("trano_ppn"));

            $tot = $this->db->fetchRow($select);
            $data['total_settle'] = ($tot['total_settle'] != '') ? $tot['total_settle'] : 0;

        }
        else
            $data = array();

        $this->view->data = $data;
//        $this->view->data_header = $data_header;

        $file = $this->DEFAULT->Files->fetchAll("trano = '$trano'");
        $file = $file->toArray();

        if (!$file)
            $file = array();
        $this->view->file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
    }

    public function getTranoPpnRemAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $offset = ($_POST['start']) ? $_POST['start'] : 0;
        $limit = ($_POST['limit']) ? $_POST['limit'] : 100;

        $select = $this->db->select()
            ->from(array($this->FINANCE->PpnReimbursementH->__name()),array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                "rpi_no",
                "po_no",
                "prj_kode",
                "sit_kode"
            ));

        $trano = $this->_getParam("trano");
        if ($trano)
        {
            $select = $select->where("trano LIKE '%$trano%'");
        }

        $select = $select->order(array("trano DESC"))->limit($limit,$offset);

        $data = $this->db->fetchAll($select);

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $result = Zend_Json::encode(array("data" => $data, "count" => $count));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function getTranoPpnRemSettleAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $offset = ($_POST['start']) ? $_POST['start'] : 0;
        $limit = ($_POST['limit']) ? $_POST['limit'] : 100;

        $select = $this->db->select()
            ->from(array($this->FINANCE->PpnReimbursementSettleH->__name()),array(
                new Zend_Db_Expr("SQL_CALC_FOUND_ROWS trano"),
                "trano_ppn",
                "prj_kode",
                "sit_kode"
            ));

        $trano = $this->_getParam("trano");
        if ($trano)
        {
            $select = $select->where("trano LIKE '%$trano%'");
        }

        $select = $select->order(array("trano DESC"))->limit($limit,$offset);

        $data = $this->db->fetchAll($select);

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $result = Zend_Json::encode(array("data" => $data, "count" => $count));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function ppnRemSettleAction()
    {

    }

    public function getHeaderAction()
    {

        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $return['success'] = false;
        $select = $this->db->select()
            ->from(array($this->FINANCE->PpnReimbursementH->__name()))
            ->where("trano = '$trano'");

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $select = $this->db->select()
                ->from(array($this->FINANCE->PpnReimbursementSettleH->__name()),array(
                    "total_settle" => "COALESCE(SUM(total),0)"
                ))
                ->where("trano_ppn = ?",$trano)
                ->group("trano_ppn");

            $dataSettle = $this->db->fetchRow($select);

            $data['total_settle'] = ($dataSettle['total_settle'] == '') ? 0 : $dataSettle['total_settle'];
            $return['data'] = $data;
            $return['success'] = true;
        }
        else
            $return['msg'] = "No data found";

        $result = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function getDetailAction()
    {

        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $tranoEdit = $this->_getParam("trano_edit");
        $return['success'] = false;
        $select = $this->db->select()
            ->from(array($this->FINANCE->PpnReimbursementD->__name()))
            ->where("trano = '$trano'");

        $data = $this->db->fetchAll($select);
        if ($data)
        {
            foreach($data as $k => $v)
            {
                $select = $this->db->select()
                    ->from(array($this->FINANCE->PpnReimbursementSettleD->__name()),array(
                        "total_settle" => "COALESCE(SUM(qty*harga),0)"
                    ))
                    ->where("trano_ppn = ?",$trano);

                if ($tranoEdit)
                    $select = $select->where("trano != ?",$tranoEdit);

                $select = $select->where("rpi_no = ?",$v['rpi_no'])
                    ->where("po_no = ?",$v['po_no'])
                    ->where("prj_kode = ?",$v['prj_kode'])
                    ->where("sit_kode = ?",$v['sit_kode'])
                    ->where("workid = ?",$v['workid'])
                    ->where("kode_brg = ?",$v['kode_brg'])
                    ->group(array("trano_ppn"));

                $tot = $this->db->fetchRow($select);
                $data[$k]['total_settle'] = ($tot['total_settle'] != '') ? $tot['total_settle'] : 0;
                if ($tot['total_settle'] >= $v['total'])
                    $data[$k]['invalid'] = true;
            }
            $return['data'] = $data;
            $return['success'] = true;
        }
        else
            $return['msg'] = "No data found";

        $result = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function appPpnRemSettleAction()
    {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");

        $jsonFile = $this->_getParam("file");
        if ($jsonFile)
            $file =Zend_Json::decode($jsonFile);
        else
            $file = array();
        $deletedFile = $this->getRequest()->getParam('deletedfile');

        $this->view->show = $show;
        $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '')
        {
            $data = Zend_Json::decode($this->_getParam("data"));
//            $data_header = Zend_Json::decode($this->_getParam("data_header"));

            $this->view->data = $data;
//            $this->view->data_header = $data_header;

            $this->view->json = array(
                "data" => $this->_getParam("data"),
//                "data_header" => $this->_getParam("data_header"),
            );

            $this->view->jsonFile = $jsonFile;
            $this->view->file = $file;

            if ($from == 'edit')
            {
                $trano = $this->_getParam("trano");
                $this->view->trano = $trano;
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }
        }
        elseif($approve || $show)
        {
            $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs)
            {
                $docs = $docs->toArray();
                $trano = $docs['item_id'];
                $docsId = $approve;
                $approve = $trano;

                if ($docs['approve'] == $this->const['DOCUMENT_REJECT'])
                    $this->view->reject = true;

//                $data = $this->FINANCE->PpnReimbursementSettleD->fetchAll("trano='$trano'");
//                if ($data)
//                {
//                    $data = $data->toArray();
////                    foreach($data as $k => $v)
////                    {
////                        $data[$k]['total_ppn'] = $v['total'];
////                        $data[$k]['total'] = $v['total_rpi'];
////                    }
//                }
                $data = $this->FINANCE->PpnReimbursementSettleH->fetchRow("trano='$trano'");
                if ($data)
                {
                    $data = $data->toArray();
                }

                $this->view->data = $data;
//                $this->view->data_header = $data_header;

                $this->view->json = array(
                    "data" => $this->_getParam("data"),
//                    "data_header" => $this->_getParam("data_header"),
                );

                $this->view->jsonFile = $jsonFile;
                $file = $this->DEFAULT->Files->fetchAll("trano = '$trano'");
                $file = $file->toArray();

                $this->view->file = $file;

                $this->view->approve = true;

                if ($from == 'edit')
                {
                    $this->view->deletedFile = $deletedFile;
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

    public function insertPpnRemSettleAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $data = Zend_Json::decode($this->_getParam("data"));
//        $data_header = Zend_Json::decode($this->_getParam("data_header"));
        $file = $this->_getParam("file");
        if ($file)
            $file =Zend_Json::decode($file);
        $comment = $this->_getParam("comment");

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'],$data['sit_kode']);

        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PPNSET",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        //Insert Header
        $arrayInsert = array(
            "trano" => $trano,
            "trano_ppn" => $data['trano_ppn'],
            "po_no" => $data['po_no'],
//            "rpi_no" => $data['rpi_no'],
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "sit_kode" => $data['sit_kode'],
            "total" => $data['total'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date("Y-m-d H:i:s"),
//            "faktur_pajak" => $data['faktur_pajak'],
            "ket" => $data['ket'],
        );

        $this->FINANCE->PpnReimbursementSettleH->insert($arrayInsert);

        if ($file)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => '',
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->DEFAULT->Files->insert($arrayInsert);
            }
        }

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function editPpnRemSettleAction()
    {
        $trano = $this->_getParam("trano");
        $this->view->trano = $trano;

        $data = $this->FINANCE->PpnReimbursementSettleH->fetchRow("trano='$trano'");
        if ($data)
        {
            $data = $data->toArray();

            $select = $this->db->select()
                ->from(array($this->FINANCE->PpnReimbursementH->__name()),array(
                    "total_ppn" => "total"
                ))
                ->where("trano = ?",$data['trano_ppn']);
            $r = $this->db->fetchRow($select);
            $data['total_ppn'] = $r['total_ppn'];

            $select = $this->db->select()
                ->from(array($this->FINANCE->PpnReimbursementSettleH->__name()),array(
                    "total_settle" => "COALESCE(SUM(total),0)"
                ))
                ->where("trano_ppn = ?",$data['trano_ppn'])
                ->where("trano != ?",$trano)
                ->group(array("trano_ppn"));

            $tot = $this->db->fetchRow($select);
                $data['total_settle'] = ($tot['total_settle'] != '') ? $tot['total_settle'] : 0;
        }
        else
            $data = array();

        $this->view->data = $data;
        $this->view->jsonData = Zend_Json::encode($data);

        $file = $this->DEFAULT->Files->fetchAll("trano = '$trano'");
        $file = $file->toArray();

        if (!$file)
            $file = array();
        $this->view->file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
    }

    public function updatePpnRemSettleAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $data = Zend_Json::decode($this->_getParam("data"));
        $comment = $this->_getParam("comment");

        $trano = $data['trano'];

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'],$data['sit_kode']);

        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => "PPNSET",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => false,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "itemID" => $trano
        );
        $this->workflow->setWorkflowTransNew($params);

        $total = 0;

        $log = $log2 = array();
        $prev = $this->FINANCE->PpnReimbursementSettleD->fetchAll("trano='$trano'");
        if($prev)
        {
            $prev = $prev->toArray();
            $log['ppnrem-detail-before'] = $prev;
        }

        $prev = $this->FINANCE->PpnReimbursementSettleH->fetchRow("trano='$trano'");
        if($prev)
        {
            $prev = $prev->toArray();
            $log['ppnrem-header-before'] = $prev;
        }
        //Insert Header
        $arrayInsert = array(
            "prj_kode" => $data['prj_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "sit_kode" => $data['sit_kode'],
            "total" => $data['total'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "faktur_pajak" => $data['faktur_pajak'],
            "ket" => $data['ket_ppn']
        );

        $this->FINANCE->PpnReimbursementSettleH->update($arrayInsert,"trano='$trano'");

        $curr = $this->FINANCE->PpnReimbursementSettleH->fetchRow("trano='$trano'");
        if($curr)
        {
            $curr = $curr->toArray();
            $log2['ppnrem-header-after'] = $curr;
        }

        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array (
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);

        $file = $this->_getParam("file");
        if ($file)
            $file =Zend_Json::decode($file);

        if ($file)
        {
            foreach ($file as $key => $val)
            {
                $arrayInsert = array (
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

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
}
?>