<?php

class Procurement_BtRequestController extends Zend_Controller_Action {

    private $DEFAULT;
    private $ADMIN;
    private $PROC;
    private $const;
    private $quantity;
    private $workflow;
    private $workflowTrans;
    private $db;
    private $budget;
    private $cost;
    private $files;

    public function init() {
        $this->ADMIN = QDC_Model_Admin::init(array(
                    "KodePulsa",
                    "Workflowtrans",
                    "Masterrole"
        ));
        $this->DEFAULT = QDC_Model_Default::init(array(
                    "MasterSite",
                    "Budget",
                    "MasterProject",
                    "MasterWork",
                    "MasterBarang",
                    "Files",
                    "MasterUser",
                    "AdvanceSettlementFormD", //asfd
                    "AdvanceSettlementFormH", //asfh
                    "AdvanceSettlementForm", //asfdd
                    "AdvanceSettlementFormCancel" //asfddcancel
        ));
        $this->PROC = QDC_Model_Procurement::init(array(
                    "Procurementarfh",
                    "Procurementarfd",
                    "BusinessTripDetail",
                    "BusinessTripHeader",
                    "BusinessTripPayment",
        ));

        $this->db = Zend_Registry::get("db");
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->budget = new Default_Models_Budget();
        $this->const = Zend_Registry::get('constant');
        $this->cost = new Default_Models_Cost();
        $this->files = new Default_Models_Files();
        $this->paymentBrf = new Finance_Models_PaymentARF();
    }

    public function indexAction() {
        
    }

    public function addAction() {
        $this->view->return = $this->_getParam("returnback");
        $this->view->payment = $this->_getParam("payment");
        $this->view->data = $this->_getParam("data");
        $this->view->transport = $this->_getParam("transport");
        $this->view->file = $this->_getParam("file");
        $this->view->request =$this->_getParam("request");
    }

    public function editAction() {
        $trano = $this->getRequest()->getParam('trano');
        $type = $this->getRequest()->getParam('item_type');
        
        $isPayment = false;
        if ($type == 'BRFP') {
            $cek = $this->PROC->BusinessTripPayment->fetchRow("trano = '$trano'");
            if ($cek) {
                $cek = $cek->toArray();
                $tranoEdit = $trano;
                $trano = $cek['trano_ref'];
                $editedPayment = $cek;
                $isPayment = true;
            } else {
                echo 'No Data found...';
                die;
            }
        }

        $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'");
        $data = ($data ? $data->toArray() : array());
        $payment = $this->PROC->BusinessTripDetail->fetchAll("trano = '$trano'");
        $payment = ($payment ? $payment->toArray() : array());
        $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());

        if ($data['transport_type']) {
            $tmp = explode(",", $data['transport_type']);
            foreach ($tmp as $k => $v) {
                $data[$v] = 'on';
            }
        }

        $budget = new Default_Models_Budget();
        foreach ($payment as $k => $v) {
            $invalid = null;
            $invalidDelete = null;
            //Filter payment yg diedit (jika yg akan diedit adalah Pyament Request / BRFP)..
            if ($isPayment) {
                $invalid = true;
                if ($v['prj_kode'] == $editedPayment['prj_kode'] &&
                        $v['sit_kode'] == $editedPayment['sit_kode'] &&
                        $v['workid'] == $editedPayment['workid'] &&
                        $v['kode_brg'] == $editedPayment['kode_brg'] &&
                        $v['sequence'] == $editedPayment['sequence']) {
                    $invalid = false;
                }
            }

            //Filter payment jika sudah di ARF...
            $arf = $this->checkArfOfPayment($trano, $v, true);
            if ($arf) {
                if (($arf['qty'] * $arf['harga']) > 0)
                    $invalidDelete = true;
            }

            $boq3 = $budget->getBudgetWithCost(
                    array(
                "prjKode" => $v['prj_kode'],
                "sitKode" => $v['sit_kode'],
                "workid" => $v['workid'],
                "kodeBrg" => $v['kode_brg'],
                "budgetType" => strtolower($budget->getBudgetType($v['budget_type']))
                    ), array(
                "PO" => 1,
                "ARFASF" => 1
                    )
            );

            

            

            $payment[$k]['allowance'] = floatval($payment[$k]['allowance']);
            $payment[$k]['transport'] = floatval($payment[$k]['transport']);
            $payment[$k]['airport_tax'] = floatval($payment[$k]['airport_tax']);
            $payment[$k]['accomodation'] = floatval($payment[$k]['accomodation']);
            $payment[$k]['others'] = floatval($payment[$k]['others']);
            $payment[$k]['total'] = floatval($payment[$k]['total']);
            if ($invalid != null)
                $payment[$k]['invalid'] = $invalid;
            if ($invalidDelete != null)
                $payment[$k]['invalid_delete'] = $invalidDelete;
            $boq3 = $this->parseBudget($boq3, $trano);
            $payment[$k]['record'] = $boq3[0];
        }

        $data['budget_type'] = strtolower($budget->getBudgetType($data['budget_type']));
        $this->view->trano = $trano;
        $this->view->tranoEdit = $tranoEdit;
        $this->view->data = $data;
        $this->view->payment = $this->sortPayment($payment);
        $this->view->transport = $transport;
        $this->view->type = $type;

        $file = $this->files->fetchAll("trano = '$trano'");
        $this->view->file = $file;
        if ($file == '')
            $file = array();
        else
            $file = $file->toArray();

        $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }

     public function revisionAction() {
        $trano = $this->getRequest()->getParam('trano');
        $type = $this->getRequest()->getParam('item_type');
        $paymentBrf = $this->paymentBrf->getPayment($trano);
        $totalPayment = $paymentBrf == null ? 0 : $paymentBrf;
        $isPayment = false;
        if ($type == 'BRFP') {
            $cek = $this->PROC->BusinessTripPayment->fetchRow("trano = '$trano'");
            if ($cek) {
                $cek = $cek->toArray();
                $tranoEdit = $trano;
                $trano = $cek['trano_ref'];
                $seq = $cek['sequence'];
                $editedPayment = $cek;
                $isPayment = true;
            } else {
                echo 'No Data found...';
                die;
            }
        }

        $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'");
        $data = ($data ? $data->toArray() : array());
        if ($type == 'BRFP') {
            $payment = $this->PROC->BusinessTripDetail->fetchAll(array("trano = '$trano' and sequence = '$seq'"));
        } else {
            $payment = $this->PROC->BusinessTripDetail->fetchAll(array("trano = '$trano'"));
            
        }
        $payment = ($payment ? $payment->toArray() : array());
        $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());

        if ($data['transport_type']) {
            $tmp = explode(",", $data['transport_type']);
            foreach ($tmp as $k => $v) {
                $data[$v] = 'on';
            }
        }

        $budget = new Default_Models_Budget();
        
        foreach ($payment as $k => $v) {
            $invalid = null;
            $invalidDelete = null;
            //Filter payment yg diedit (jika yg akan diedit adalah Pyament Request / BRFP)..
            if ($isPayment) {
                $invalid = true;
                if ($v['prj_kode'] == $editedPayment['prj_kode'] &&
                        $v['sit_kode'] == $editedPayment['sit_kode'] &&
                        $v['workid'] == $editedPayment['workid'] &&
                        $v['kode_brg'] == $editedPayment['kode_brg'] &&
                        $v['sequence'] == $editedPayment['sequence']) {
                    $invalid = false;
                }
            }

            //Filter payment jika sudah di ARF...
            $arf = $this->checkArfOfPayment($trano, $v, true);
            if ($arf) {
                if (($arf['qty'] * $arf['harga']) > 0)
                    $invalidDelete = true;
            }

            $boq3 = $budget->getBudgetWithCost(
                    array(
                "prjKode" => $v['prj_kode'],
                "sitKode" => $v['sit_kode'],
                "workid" => $v['workid'],
                "kodeBrg" => $v['kode_brg'],
                "budgetType" => strtolower($budget->getBudgetType($v['budget_type']))
                    ), array(
                "PO" => 1,
                "ARFASF" => 1
                    )
            );

            $payment[$k]['allowance'] = floatval($payment[$k]['allowance']);
            $payment[$k]['transport'] = floatval($payment[$k]['transport']);
            $payment[$k]['airport_tax'] = floatval($payment[$k]['airport_tax']);
            $payment[$k]['accomodation'] = floatval($payment[$k]['accomodation']);
            $payment[$k]['others'] = floatval($payment[$k]['others']);
            $payment[$k]['total'] = floatval($payment[$k]['total']);
            // $payment[$k]['brfp'] = $payment[$k]['sequence']==$cek['sequence'] ? $tranoEdit : '';
            
            if ($invalid != null)
                $payment[$k]['invalid'] = $invalid;
            
            if ($invalidDelete != null)
                $payment[$k]['invalid_delete'] = $invalidDelete;
            
            $boq3 = $this->parseBudget($boq3, $trano);
            
            $payment[$k]['record'] = $boq3[0];
        }
        $data['budget_type'] = strtolower($budget->getBudgetType($data['budget_type']));
        $this->view->trano = $trano;
        $this->view->tranoEdit = $tranoEdit;
        $this->view->data = $data;
        $this->view->payment = $this->sortPayment($payment);
        // $this->view->paymentbrfp = $totalPayment;
        $this->view->transport = $transport;
        $this->view->type = $type;

        $file = $this->files->fetchAll("trano = '$trano'");
        $this->view->file = $file;
        
        if ($file == '')
            $file = array();
        else
            $file = $file->toArray();

        $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        Zend_Loader::loadClass('Zend_Json');
        $file = Zend_Json::encode($file);
        $this->view->file = $file;
    }
    
    public function getBudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $budgetType = $this->_getParam("budget_type");
        $exTrano = $this->_getParam("exclude_trano");

        $budget = new Default_Models_Budget();

        $boq3 = $budget->getBudgetWithCost(
                array(
            "prjKode" => $prjKode,
            "sitKode" => $sitKode,
            "budgetType" => $budgetType
                ), array(
            "PO" => 1,
//                "ARFASF_NO_BRF" => 1,
//                "ARFASF_BRF" => 1
            "ARFASF" => 1,
                )
        );
        $boq3 = $this->parseBudget($boq3, $budgetType, $exTrano);
        $result = Zend_Json::encode(array("success" => true, "data" => $boq3));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function getboq3forbrfAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $budgetType = $this->_getParam("budget_type");
        
        if($budgetType=='project')
            {$boq3 = $this->budget->getBOQ3CurrentPerItem($prjKode, $sitKode);}
        else
            {$boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode);}
            
        $this->budget->createOnGoingAFESaving($prjKode);
    
        $result = array();
        $i=1;
        
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
            $boq3[$key]['uom'] = $budgetType=='project' ? $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']) : '';
            $boq3[$key]['nama_brg'] = $budgetType=='project' ? str_replace("\"", "'", $boq3[$key]['nama_brg']) : '';
            $boq3[$key]['price'] = $budgetType=='project' ? $val['harga'] : 0;
            $boq3[$key]['qty'] = $budgetType=='project' ? $val['qty'] : 0;
            $boq3[$key]['total'] = $val['val_kode']=='USD' ? $val['totalUSD'] : $val['totalIDR'];
            $boq3[$key]['workid']  = $budgetType=='project' ? $boq3[$key]['workid'] : $boq3[$key]['budgetid'];
            $boq3[$key]['workname']  = $budgetType=='project' ? $boq3[$key]['workname'] : $boq3[$key]['budgetname'];
            $boq3[$key]['kode_brg'] = $budgetType=='project' ? $boq3[$key]['kode_brg'] : '';
                    
            // Get All Related OCA
            $oca = $this->cost->totalOCA($prjKode, $sitKode,$boq3[$key]['workid'], $boq3[$key]['kode_brg']);
            $boq3[$key]['totalOCA']= $val['val_kode']=='USD' ? $oca['totalUSD'] : $oca['totalIDR'];
            
            // Get All Requests of The Item PR + ARF - (Material Return + ASF Cancel)
            $requests=$this->cost->totalRequests($prjKode, $sitKode,$boq3[$key]['workid'], $boq3[$key]['kode_brg']);
            $boq3[$key]['totalCost']= $val['val_kode']=='USD' ? $requests['totalUSD'] : $requests['totalIDR'];
            
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg'],$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            $boq3[$key]['total'] = $boq3[$key]['tranoAFE'] !='' ? $boq3[$key]['totalAFE'] : $boq3[$key]['total'];
            $boq3[$key]['total'] = $boq3[$key]['total'] - $boq3[$key]['totalOCA'];
            
            $prosentase = $boq3[$key]['total']==0 ? 0 : ($boq3[$key]['totalCost']/$boq3[$key]['total'])*100;
            $boq3[$key]['invalid'] =  $prosentase >= 100 ? true :false;
            
            $boq3[$key]['totalBalance'] = ($boq3[$key]['total'] - $boq3[$key]['totalCost'])<=0 ? 0 : ($boq3[$key]['total'] - $boq3[$key]['totalCost']);
                    
            $i++;

        }
        
        $result = Zend_Json::encode(array("success" => true, "data" => $boq3));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    
    }
    
    private function parseBudget($boq3, $budgetType = 'project', $exTrano = '') {
        $brd = new Procurement_Models_BusinessTripDetail();
        foreach ($boq3 as $k => $v) {
            $params = array(
                "prjKode" => $v['prj_kode'],
                "sitKode" => $v['sit_kode'],
                "workid" => $v['workid'],
                "kodeBrg" => $v['kode_brg'],
                "exclude_trano" => $exTrano,
                "returnORM" => true
            );

            if ($budgetType == 'overhead')
                unset($params['kodeBrg']);

            $brdCost = $brd->getCost(
                            $params
                    )->getCostSummary();

            $cost = 0;
            $total = 0;
            $balance = 0;
            if ($v['val_kode'] == 'IDR') {
//                $cost = $v['po_IDR'] + $v['arfasf_no_brf_IDR'] + ($brdCost[0]['totalIDR'] - $v['arfasf_brf_IDR']) + $v['arfasf_brf_IDR'];
                $cost = $v['po_IDR'] + $v['arfasf_IDR'];
                $balance = $v['totalIDR'] - $cost;
                $total = $v['totalIDR'];
            } elseif ($v['val_kode'] == 'USD') {
//                $cost = $v['po_USD'] + $v['arfasf_no_brf_USD'] + ($brdCost[0]['totalHargaUSD'] - $v['arfasf_brf_USD']) + $v['arfasf_brf_USD'];
                $cost = $v['po_USD'] + $v['arfasf_USD'];
                $balance = $v['totalUSD'] - $cost;
                $total = $v['totalHargaUSD'];
            }

            $boq3[$k]['totalCost'] = $cost;
            $boq3[$k]['total'] = $total;
            $boq3[$k]['totalBalance'] = $balance;

            if ($balance <= 0)
                $boq3[$k]['invalid'] = true;
        }

        return $boq3;
    }

    public function isPaid($trano)
    {
            
            $sql = "SELECT COUNT(*) As exist FROM imderpdb.finance_payment_arf WHERE doc_trano='$trano' ";
      
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();    

        return $data['exist'];

    }
    
    public function isPartial($trano){
        $sql = "SELECT travel_arrangement,IF(travel_arrangement IN('long','short','day_trip') && totalsequence='1','0','1') As exist  FROM imderpdb.procurement_brfh WHERE trano='$trano'";
      
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();    

        return $data['exist'];

    }

    public function cekPaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $payment = Zend_Json::decode($this->_getParam("payment"));
        $exTrano = $this->_getParam("exclude_trano");
        $budgetType = $this->_getParam("budget_type");

        $budget = new Default_Models_Budget();

        $foundInvalid = false;
        $invalid = array();
        foreach ($payment as $k => $v) {
            $prjKode = $v['prj_kode'];
            $sitKode = $v['sit_kode'];
            $workid = $v['workid'];
            $kodeBrg = $v['kode_brg'];
            $boq3 = $budget->getBudgetWithCost(
                    array(
                "prjKode" => $prjKode,
                "sitKode" => $sitKode,
                "workid" => $workid,
                "kodeBrg" => $kodeBrg,
                "budgetType" => $budgetType,
                "exclude_trano" => $exTrano
                    ), array(
                "PO" => 1,
                "ARFASF" => 1
                    )
            );
            $boq3 = $this->parseBudget($boq3, $budgetType, $exTrano);
            if ($boq3[0]['invalid'] == true) {
                $foundInvalid = true;
                $invalid[] = $v;
                $msg = "These items cannot be processed because over the budget";
            } else
                unset($payment[$k]);
        }

        $result = Zend_Json::encode(array("success" => !$foundInvalid, "data" => $invalid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function appAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $item_type = $this->getRequest()->getParam("item_type");
        $lastReject = array();

        $this->view->isPayment = false;

        $jsonFile = $this->_getParam("file");
        if ($jsonFile)
            $file = Zend_Json::decode($jsonFile);
        else
            $file = array();
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/BRF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '') {
            $payment = Zend_Json::decode($this->_getParam("payment"));
            $groupPayment = $this->groupPaymentByBudget($payment);
            $data = Zend_Json::decode($this->_getParam("data"));
            $transport = Zend_Json::decode($this->_getParam("transport"));
            $data['transport_type'] = $this->getTransportType($data);
            if ($data['payment_applicable'] == 'lumpsum')
                $data['payment_applicable_text'] = "Lumpsum";
            if ($data['payment_applicable'] == 'non_lumpsum')
                $data['payment_applicable_text'] = "Non Lumpsum";
            if ($data['travel_arrangement'] == 'day_trip')
                $data['travel_arrangement_text'] = "Day Trip";
            if ($data['travel_arrangement'] == 'short')
                $data['travel_arrangement_text'] = "Short Term";
            if ($data['travel_arrangement'] == 'long')
                $data['travel_arrangement_text'] = "Long Term";
            if ($data['accomodation'] == 'company')
                $data['accomodation_text'] = "Arrange By Company";
            if ($data['accomodation'] == 'employee')
                $data['accomodation_text'] = "Arrange By Employee";

            $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
            $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'], $data['sit_kode']);

            $payment = $this->sortPayment($payment);
            $this->view->payment = $payment;
            $this->view->groupPayment = $groupPayment;
            $this->view->data = $data;
            $this->view->transport = $transport;

            $this->view->json = array(
                "payment" => $this->_getParam("payment"),
                "data" => $this->_getParam("data"),
                "transport" => $this->_getParam("transport")
            );

            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');
            $this->view->jsonFile = $this->getRequest()->getParam('filedata');
            $this->view->file = $filedata;
            if ($from == 'edit') {
                $trano = $data['trano'];
                $this->view->trano = $trano;
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }

            if ($trano != '' && $item_type == 'BRF') {
                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("trano_ref = '$trano'")
                        ->order(array("sequence ASC"));

                $subselect = $this->db->select()
                        ->from(array("b" => $this->PROC->Procurementarfd->__name()))
                        ->joinLeft(array("a" => $select), "a.trano_ref = b.trano_ref AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND a.sequence = b.bt_sequence"
                        )
                        ->where("b.bt = 'Y'")
                        ->where("b.trano_ref = '$trano'");

                $paymentFund = $this->db->fetchAll($subselect);
                if ($paymentFund) {
                    $this->view->paymentFundFirst = $paymentFund;
                }
            } elseif ($trano != '' && $item_type == 'BRFP') {
                $tranoEdit = $this->getRequest()->getParam("trano_edit");
                $this->view->tranoEdit = $tranoEdit;
                $this->view->isPayment = true;
                $this->view->paymentFundFirst = $payment;

                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("trano = '$tranoEdit'");

                $theEdit = $this->db->fetchRow($select);
                $sequence = $theEdit['sequence'];
                $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix($sequence);
                $this->view->sequence = $sequence;
            } else {
                $this->view->paymentFundFirst = array($payment[0]);
            }
        } elseif ($approve || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$tranoShow'", array("date DESC"), 1, 0);
                if (!$docs) {
                    $brfp = $this->PROC->BusinessTripPayment->fetchRow("trano = '$tranoShow'");
                    if ($brfp['sequence'] == "1") {
                        echo "This BRF Payment was Automatically Approved via BRF Request";
                    }
                }
            } else
                $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], QDC_User_Session::factory()->getCurrentID());
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $payment = ($this->_getParam("payment") != '') ? (boolean) $this->_getParam("payment") : false;
                    $this->view->isPayment = $payment;
                    if ($payment) {
                        $approve = $docs['item_id'];
                        $brfp = $this->PROC->BusinessTripPayment->fetchRow("trano = '$approve'");
                        if ($brfp) {
                            $brfp = $brfp->toArray();
                            $tranoBRF = $brfp['trano_ref'];
                            $this->view->tranoBRF = $tranoBRF;
                            $sequence = $brfp['sequence'];
                            $prjKode = $docs['prj_kode'];
                            $userApp = $this->workflow->getAllApprovalGeneric($approve);
                            $this->view->user_approval = $userApp;
                            $data['user_approval'] = $userApp;
                            $statApprove = $docs['approve'];

                            if ($statApprove == $this->const['DOCUMENT_REJECT'])
                                $this->view->reject = true;

                            $filedata = $this->files->fetchAll("trano = '$tranoBRF'");

                            //Ambil trans dari database
                            $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$tranoBRF'");
                            $data = ($data ? $data->toArray() : array());
                            $payment = $this->PROC->BusinessTripDetail->fetchAll("trano = '$tranoBRF'");
                            $payment = ($payment ? $payment->toArray() : array());
                            $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());

                            foreach ($payment as $k => $v) {
                                $tranoP = $v['trano'];
                                $seq = $v['sequence'];
                                $payment[$k]['total_payment'] = 0;
                                $cek = $this->PROC->BusinessTripPayment->fetchRow("trano_ref = '$tranoP' AND sequence = $seq");
                                if ($cek) {
                                    $cek = $cek->toArray();
                                    $tranoPay = $cek['trano'];
                                    $arfd = $this->PROC->Procurementarfd->fetchRow("trano = '$tranoPay' AND trano_ref = '$tranoP' AND bt_sequence = $seq AND bt = 'Y'");
                                    if ($arfd) {
                                        $arfh = $this->PROC->Procurementarfh->fetchRow("trano = '$tranoPay' AND trano_ref = '$tranoP' AND bt_sequence = $seq AND bt = 'Y'")->toArray();

                                        $arfd = $arfd->toArray();
                                        $payment[$k]['total_payment'] = $arfd['qty'] * $arfd['harga'];
                                        $payment[$k]['tgl_payment'] = $arfh['tgl'];
                                        $payment[$k]['trano_payment'] = $arfh['trano'];
                                    }
                                }
                            }

                            $data['transport_type'] = $this->getTransportType($data);
                            if ($data['payment_applicable'] == 'lumpsum')
                                $data['payment_applicable_text'] = "Lumpsum";
                            if ($data['payment_applicable'] == 'non_lumpsum')
                                $data['payment_applicable_text'] = "Non Lumpsum";
                            if ($data['travel_arrangement'] == 'day_trip')
                                $data['travel_arrangement_text'] = "Day Trip";
                            if ($data['travel_arrangement'] == 'short')
                                $data['travel_arrangement_text'] = "Short Term";
                            if ($data['travel_arrangement'] == 'long')
                                $data['travel_arrangement_text'] = "Long Term";
                            if ($data['accomodation'] == 'company')
                                $data['accomodation_text'] = "Arrange By Company";
                            if ($data['accomodation'] == 'employee')
                                $data['accomodation_text'] = "Arrange By Employee";

//                            $allReject = $this->workflow->getAllReject($approve);
//                            $lastReject = $this->workflow->getAllRejectNew($approve);
                          
                            $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                            $lastReject[0]['date'] = $docs['date'];
                            $lastReject[0]['comment']= $docs['comment'];
                            
                            $this->view->lastReject = $lastReject;
//                            $this->view->allReject = $allReject;
                            
                            $this->view->data = $data;
                            $this->view->payment = $this->sortPayment($payment);
                            $this->view->transport = $transport;

                            $this->view->json = array(
                                "payment" => $payment,
                                "data" => $data,
                                "transport" => $transport
                            );
                            //$filedata = $this->files->fetchAll("trano = '$approve'");
                            $this->view->file = $filedata;
                            $this->view->file = $filedata;
                            if (count($file)<=0)
                                $file = array();
                            else
                                $file = $file->toArray();

                            $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
                            $this->view->trano = $approve;
                            $this->view->tranoBRF = $tranoBRF;
                            $this->view->approve = true;
                            $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                            $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                            $this->view->docsID = $id;

                            $brfd = $this->PROC->BusinessTripDetail->fetchRow("trano = '$tranoBRF' AND sequence = $sequence")->toArray();
                            $this->view->paymentFundFirst = array($brfd);
                            $this->view->paymentFundID = array($brfd['id']);
                            $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix($sequence);
                            $this->view->sequence = $sequence;
                        }
                    }
                    else {
                        $approve = $docs['item_id'];
                        $prjKode = $docs['prj_kode'];
                        $userApp = $this->workflow->getAllApprovalGeneric($approve);
                        $this->view->user_approval = $userApp;
                        $data['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                        //                    $this->ADMIN->Workflowtrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;

                        $filedata = $this->files->fetchAll("trano = '$approve'");

                        //Ambil trans dari database
                        $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$approve'");
                        $data = ($data ? $data->toArray() : array());
                        $payment = $this->PROC->BusinessTripDetail->fetchAll("trano = '$approve'");
                        $payment = ($payment ? $payment->toArray() : array());
                        $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());


                        $data['transport_type'] = $this->getTransportType($data);
                        if ($data['payment_applicable'] == 'lumpsum')
                            $data['payment_applicable_text'] = "Lumpsum";
                        if ($data['payment_applicable'] == 'non_lumpsum')
                            $data['payment_applicable_text'] = "Non Lumpsum";
                        if ($data['travel_arrangement'] == 'day_trip')
                            $data['travel_arrangement_text'] = "Day Trip";
                        if ($data['travel_arrangement'] == 'short')
                            $data['travel_arrangement_text'] = "Short Term";
                        if ($data['travel_arrangement'] == 'long')
                            $data['travel_arrangement_text'] = "Long Term";
                        if ($data['accomodation'] == 'company')
                            $data['accomodation_text'] = "Arrange By Company";
                        if ($data['accomodation'] == 'employee')
                            $data['accomodation_text'] = "Arrange By Employee";

//                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];
//                        $lastReject = $this->workflow->getAllRejectNew($approve);

                        //$filedata = $this->files->fetchAll("trano = '$approve'");
                        $this->view->lastReject = $lastReject;
                        
//                        $this->view->allReject = $allReject;

                        $this->view->data = $data;
                        $this->view->payment = $this->sortPayment($payment);
                        $this->view->transport = $transport;

                        $this->view->json = array(
                            "payment" => $payment,
                            "data" => $data,
                            "transport" => $transport
                        );
                        $this->view->file = $filedata;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                        $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                        $this->view->docsID = $id;

                        //cari Payment sequence ke 1 untuk di fund jika belum ada payment sama sekali
                        $select = $this->db->select()
                                ->from(array($this->PROC->BusinessTripPayment->__name()))
                                ->where("trano_ref = '$approve'")
                                ->order(array("sequence ASC"));

                        $cekPayment = $this->db->fetchAll($select);
                        if (!$cekPayment) {
                            $this->view->paymentFundFirst = array($this->view->payment[0]);
                            $this->view->paymentFundID = array($this->view->payment[0]['id']);
                            $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix(1);
                        }
                    }
                }

                if ($this->ADMIN->Masterrole->cekUserInRole(array(
                            "userID" => QDC_User_Session::factory()->getCurrentUID(),
                            "prjKode" => $prjKode,
                            "roleName" => "Finance and Logistic",
                            "roleDisplayName" => array(
                                "Finance and Accounting Officer",
                                "Finance and Accounting Jr. Officer"
                            )
                        ))) {
                    $this->view->canEditTrans = true;
                    $this->view->editTransType = $docs['item_type'];
                }
            }
        }
                        }

    public function apprevisionAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $item_type = $this->getRequest()->getParam("item_type");
        $lastReject = array();

        $this->view->isPayment = false;

        $jsonFile = $this->_getParam("file");
        if ($jsonFile)
            $file = Zend_Json::decode($jsonFile);
        else
            $file = array();
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/BRF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve == '') {
            $payment = Zend_Json::decode($this->_getParam("payment"));
            $groupPayment = $this->groupPaymentByBudget($payment);
            $data = Zend_Json::decode($this->_getParam("data"));
            $transport = Zend_Json::decode($this->_getParam("transport"));
            $data['transport_type'] = $this->getTransportType($data);
            if ($data['payment_applicable'] == 'lumpsum')
                $data['payment_applicable_text'] = "Lumpsum";
            if ($data['payment_applicable'] == 'non_lumpsum')
                $data['payment_applicable_text'] = "Non Lumpsum";
            if ($data['travel_arrangement'] == 'day_trip')
                $data['travel_arrangement_text'] = "Day Trip";
            if ($data['travel_arrangement'] == 'short')
                $data['travel_arrangement_text'] = "Short Term";
            if ($data['travel_arrangement'] == 'long')
                $data['travel_arrangement_text'] = "Long Term";
            if ($data['accomodation'] == 'company')
                $data['accomodation_text'] = "Arrange By Company";
            if ($data['accomodation'] == 'employee')
                $data['accomodation_text'] = "Arrange By Employee";

            $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
            $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'], $data['sit_kode']);

            $payment = $this->sortPayment($payment);
            $this->view->payment = $payment;
            $this->view->groupPayment = $groupPayment;
            $this->view->data = $data;
            $this->view->transport = $transport;

            $this->view->json = array(
                "payment" => $this->_getParam("payment"),
                "data" => $this->_getParam("data"),
                "transport" => $this->_getParam("transport")
            );

            $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));
            $this->view->DeletedFile = $this->getRequest()->getParam('deletedfile');
            $this->view->jsonFile = $this->getRequest()->getParam('filedata');
            $this->view->file = $filedata;
            if ($from == 'edit') {
                $trano = $data['trano'];
                $this->view->trano = $trano;
                $this->view->deletedFile = $deleted;
                unset($data['deletedfile']);
                $this->view->edit = true;
            }

            if ($trano != '' && $item_type == 'BRF') {
                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("trano_ref = '$trano'")
                        ->order(array("sequence ASC"));

                $subselect = $this->db->select()
                        ->from(array("b" => $this->PROC->Procurementarfd->__name()))
                        ->joinLeft(array("a" => $select), "a.trano_ref = b.trano_ref AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND a.sequence = b.bt_sequence"
                        )
                        ->where("b.bt = 'Y'")
                        ->where("b.trano_ref = '$trano'");

                $paymentFund = $this->db->fetchAll($subselect);
                if ($paymentFund) {
                    $this->view->paymentFundFirst = $paymentFund;
                }
            } elseif ($trano != '' && $item_type == 'BRFP') {
                $tranoEdit = $this->getRequest()->getParam("trano_edit");
                $this->view->tranoEdit = $tranoEdit;
                $this->view->isPayment = true;
                $this->view->paymentFundFirst = $payment;

                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("trano = '$tranoEdit'");

                $theEdit = $this->db->fetchRow($select);
                $sequence = $theEdit['sequence'];
                $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix($sequence);
                $this->view->sequence = $sequence;
            } else {
                $this->view->paymentFundFirst = array($payment[0]);
            }
        } elseif ($approve || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$tranoShow'", array("date DESC"), 1, 0);
                if (!$docs) {
                    $brfp = $this->PROC->BusinessTripPayment->fetchRow("trano = '$tranoShow'");
                    if ($brfp['sequence'] == "1") {
                        echo "This BRF Payment was Automatically Approved via BRF Request";
                    }
                }
            } else
                $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], QDC_User_Session::factory()->getCurrentID());
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];
                    $payment = ($this->_getParam("payment") != '') ? (boolean) $this->_getParam("payment") : false;
                    $this->view->isPayment = $payment;
                    if ($payment) {
                        $approve = $docs['item_id'];
                        $brfp = $this->PROC->BusinessTripPayment->fetchRow("trano = '$approve'");
                        if ($brfp) {
                            $brfp = $brfp->toArray();
                            $tranoBRF = $brfp['trano_ref'];
                            $this->view->tranoBRF = $tranoBRF;
                            $sequence = $brfp['sequence'];
                            $prjKode = $docs['prj_kode'];
                            $userApp = $this->workflow->getAllApprovalGeneric($approve);
                            $this->view->user_approval = $userApp;
                            $data['user_approval'] = $userApp;
                            $statApprove = $docs['approve'];

                            if ($statApprove == $this->const['DOCUMENT_REJECT'])
                                $this->view->reject = true;

                            $filedata = $this->files->fetchAll("trano = '$approve'");

                            //Ambil trans dari database
                            $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$tranoBRF'");
                            $data = ($data ? $data->toArray() : array());
                            $payment = $this->PROC->BusinessTripDetail->fetchAll("trano = '$tranoBRF'");
                            $payment = ($payment ? $payment->toArray() : array());
                            $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());

                            foreach ($payment as $k => $v) {
                                $tranoP = $v['trano'];
                                $seq = $v['sequence'];
                                $payment[$k]['total_payment'] = 0;
                                $cek = $this->PROC->BusinessTripPayment->fetchRow("trano_ref = '$tranoP' AND sequence = $seq");
                                if ($cek) {
                                    $cek = $cek->toArray();
                                    $tranoPay = $cek['trano'];
                                    $arfd = $this->PROC->Procurementarfd->fetchRow("trano = '$tranoPay' AND trano_ref = '$tranoP' AND bt_sequence = $seq AND bt = 'Y'");
                                    if ($arfd) {
                                        $arfh = $this->PROC->Procurementarfh->fetchRow("trano = '$tranoPay' AND trano_ref = '$tranoP' AND bt_sequence = $seq AND bt = 'Y'")->toArray();

                                        $arfd = $arfd->toArray();
                                        $payment[$k]['total_payment'] = $arfd['qty'] * $arfd['harga'];
                                        $payment[$k]['tgl_payment'] = $arfh['tgl'];
                                        $payment[$k]['trano_payment'] = $arfh['trano'];
                                    }
                                }
                            }

                            $data['transport_type'] = $this->getTransportType($data);
                            if ($data['payment_applicable'] == 'lumpsum')
                                $data['payment_applicable_text'] = "Lumpsum";
                            if ($data['payment_applicable'] == 'non_lumpsum')
                                $data['payment_applicable_text'] = "Non Lumpsum";
                            if ($data['travel_arrangement'] == 'day_trip')
                                $data['travel_arrangement_text'] = "Day Trip";
                            if ($data['travel_arrangement'] == 'short')
                                $data['travel_arrangement_text'] = "Short Term";
                            if ($data['travel_arrangement'] == 'long')
                                $data['travel_arrangement_text'] = "Long Term";
                            if ($data['accomodation'] == 'company')
                                $data['accomodation_text'] = "Arrange By Company";
                            if ($data['accomodation'] == 'employee')
                                $data['accomodation_text'] = "Arrange By Employee";

//                            $allReject = $this->workflow->getAllReject($approve);
//                            $lastReject = $this->workflow->getAllRejectNew($approve);
                            $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                            $lastReject[0]['date'] = $docs['date'];
                            $lastReject[0]['comment']= $docs['comment'];

                            $this->view->lastReject = $lastReject;
//                            $this->view->allReject = $allReject;

                            $this->view->data = $data;
                            $this->view->payment = $this->sortPayment($payment);
                            $this->view->transport = $transport;

                            $this->view->json = array(
                                "payment" => $payment,
                                "data" => $data,
                                "transport" => $transport
                            );
                            $filedata = $this->files->fetchAll("trano = '$approve'");
                            $this->view->file = $filedata;
                            if ($file == '')
                                $file = array();
                            else
                                $file = $file->toArray();

                            $this->view->jsonFile = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
                            $this->view->trano = $approve;
                            $this->view->tranoBRF = $tranoBRF;
                            $this->view->approve = true;
                            $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                            $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                            $this->view->docsID = $id;

                            $brfd = $this->PROC->BusinessTripDetail->fetchRow("trano = '$tranoBRF' AND sequence = $sequence")->toArray();
                            $this->view->paymentFundFirst = array($brfd);
                            $this->view->paymentFundID = array($brfd['id']);
                            $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix($sequence);
                            $this->view->sequence = $sequence;
                        }
                    }
                    else {
                        $approve = $docs['item_id'];
                        $prjKode = $docs['prj_kode'];
                        $userApp = $this->workflow->getAllApprovalGeneric($approve);
                        $this->view->user_approval = $userApp;
                        $data['user_approval'] = $userApp;
                        $statApprove = $docs['approve'];

                        //                    $this->ADMIN->Workflowtrans->fetchAll("workflow_trans_id=$id AND item_id='$id' AND workflow_id='$workflowId'",array(''));

                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                            $this->view->reject = true;

                        $filedata = $this->files->fetchAll("trano = '$approve'");
                        

                        //Ambil trans dari database
                        $data = $this->PROC->BusinessTripHeader->fetchRow("trano = '$approve'");
                        $data = ($data ? $data->toArray() : array());
                        $payment = $this->PROC->BusinessTripDetail->fetchAll("trano = '$approve'");
                        $payment = ($payment ? $payment->toArray() : array());
                        $transport = ($data['transport'] != '' ? Zend_Json::decode($data['transport']) : array());


                        $data['transport_type'] = $this->getTransportType($data);
                        if ($data['payment_applicable'] == 'lumpsum')
                            $data['payment_applicable_text'] = "Lumpsum";
                        if ($data['payment_applicable'] == 'non_lumpsum')
                            $data['payment_applicable_text'] = "Non Lumpsum";
                        if ($data['travel_arrangement'] == 'day_trip')
                            $data['travel_arrangement_text'] = "Day Trip";
                        if ($data['travel_arrangement'] == 'short')
                            $data['travel_arrangement_text'] = "Short Term";
                        if ($data['travel_arrangement'] == 'long')
                            $data['travel_arrangement_text'] = "Long Term";
                        if ($data['accomodation'] == 'company')
                            $data['accomodation_text'] = "Arrange By Company";
                        if ($data['accomodation'] == 'employee')
                            $data['accomodation_text'] = "Arrange By Employee";

//                        $allReject = $this->workflow->getAllReject($approve);
//                        $lastReject = $this->workflow->getAllRejectNew($approve);
                        $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                        $lastReject[0]['date'] = $docs['date'];
                        $lastReject[0]['comment']= $docs['comment'];

                        $filedata = $this->files->fetchAll("trano = '$approve'");
                        $this->view->lastReject = $lastReject;
//                        $this->view->allReject = $allReject;
                        
                        $this->view->data = $data;
                        $this->view->payment = $this->sortPayment($payment);
                        $this->view->transport = $transport;

                        $this->view->json = array(
                            "payment" => $payment,
                            "data" => $data,
                            "transport" => $transport
                        );
                        $this->view->file = $filedata;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                        $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                        $this->view->docsID = $id;

                        //cari Payment sequence ke 1 untuk di fund jika belum ada payment sama sekali
                        $select = $this->db->select()
                                ->from(array($this->PROC->BusinessTripPayment->__name()))
                                ->where("trano_ref = '$approve'")
                                ->order(array("sequence ASC"));

                        $cekPayment = $this->db->fetchAll($select);
                        if (!$cekPayment) {
                            $this->view->paymentFundFirst = array($this->view->payment[0]);
                            $this->view->paymentFundID = array($this->view->payment[0]['id']);
                            $this->view->sequenceNotation = QDC_Common_Number::factory()->addOrdinalNumberSuffix(1);
                        }
                    }
                }

                if ($this->ADMIN->Masterrole->cekUserInRole(array(
                            "userID" => QDC_User_Session::factory()->getCurrentUID(),
                            "prjKode" => $prjKode,
                            "roleName" => "Finance and Logistic",
                            "roleDisplayName" => array(
                                "Finance and Accounting Officer",
                                "Finance and Accounting Jr. Officer"
                            )
                        ))) {
                    $this->view->canEditTrans = true;
                    $this->view->editTransType = $docs['item_type'];
                }
            }
        }
    }

    private function groupPaymentByBudget($payment) {
        $group = array();
        foreach ($payment as $k => $v) {
            $str = $v['prj_kode'] . $v['sit_kode'] . $v['workid'] . $v['kode_brg'];
            $group[$str] = $v['record'];
        }

        return $group;
    }

    private function getTransportType($data, $returnValue = false) {
        $trans = array();
        $transValue = array();
        if ($data['bus'] == 'on') {
            $trans[] = "Bus";
            $transValue[] = "bus";
        }
        if ($data['rail'] == 'on') {
            $trans[] = "Rail";
            $transValue[] = "rail";
        }
        if ($data['air'] == 'on') {
            $trans[] = "Air";
            $transValue[] = "air";
        }
        if ($data['sea'] == 'on') {
            $trans[] = "Sea";
            $transValue[] = "sea";
        }
        if ($data['qdc'] == 'on') {
            $trans[] = "QDC Vehicle";
            $transValue[] = "qdc";
        }
        if ($data['train'] == 'on') {
            $trans[] = "Train";
            $transValue[] = "train";
        }

        if (!$returnValue)
            return implode(", ", $trans);
        else
            return implode(",", $transValue);
    }

    private function sortPayment($payment) {
        $tmp = array();
        foreach ($payment as $k => $v) {
            $seq = $v['sequence'];
            $tmp[$seq] = $v;
        }
        ksort($tmp);

        $payment = array();
        foreach ($tmp as $k => $v) {
            $payment[] = $v;
        }

        return $payment;
    }

    public function insertBrfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $payment = Zend_Json::decode($this->_getParam("payment"));
        $groupPayment = $this->groupPaymentByBudget($payment);
        $data = Zend_Json::decode($this->_getParam("data"));
        $jsonfile = Zend_Json::decode($this->_getParam("file"));
        $activitylog2 = new Admin_Models_Activitylog();
        $urut = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();
        
        $comment = $this->_getParam("comment");
//        $transport = Zend_Json::decode($this->_getParam("transport"));
        $transport = $this->_getParam("transport");
        $data['transport_type'] = $this->getTransportType($data, true);

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'], $data['sit_kode']);
        $data['workname'] = $this->DEFAULT->MasterWork->getWorkname($data['workid']);

        $items = $data;
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;

        $params = array(
            "workflowType" => "BRF",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "useOverride" => $useOverride
        );
        $trano = $this->workflow->setWorkflowTransNew($params);

        if ($data['budget_type'] == 'Overhead')
            $data['budget_type'] = 'O';
        elseif ($data['budget_type'] == 'Project')
            $data['budget_type'] = 'P';

        $total = 0;
        $kode_brg = '';
        foreach ($payment as $k => $v) {
            $record = $v['record'];

            if ($data['budget_type'] == 'O')
                $kode_brg = $v['kode_brg'];
            else
                $kode_brg = $record['kode_brg'];

            $arrayInsert = array(
                "trano" => $trano,
                "prj_kode" => $data['prj_kode'],
                "sit_kode" => $data['sit_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_nama" => $data['sit_nama'],
                "requester" => $data['requester'],
                "budget_type" => $data['budget_type'],
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date("Y-m-d H:i:s"),
                "workid" => $v['workid'],
                "workname" => $this->DEFAULT->MasterWork->getWorkname($v['workid']),
                "kode_brg" => $kode_brg,
                "nama_brg" => $this->DEFAULT->MasterBarang->getName($record['kode_brg']),
                "qty" => 1,
                "harga" => $v['total'],
                "total" => $v['total'],
                "val_kode" => $record['val_kode'],
                "allowance" => $v['allowance'],
                "airport_tax" => $v['airport_tax'],
                "transport" => $v['transport'],
                "accomodation" => $v['accomodation'],
                "others" => $v['others'],
                "sequence" => $v['sequence'],
                "total" => $v['total'],
            );

            $total += $v['total'];
            $this->PROC->BusinessTripDetail->insert($arrayInsert);
            //detail
            $activityDetail['procurement_brfd'][$activityCount]=$arrayInsert;
            $urut++;
            $activityCount++;
        }


        $arrayInsert = array(
            "trano" => $trano,
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "budget_type" => $data['budget_type'],
            "requester" => $data['requester'],
            "job_title" => $data['job_title'],
            "department" => $data['department'],
            "reason" => $data['reason'],
            "tgl_awal" => date("Y-m-d", strtotime($data['tgl_awal'])),
            "tgl_akhir" => date("Y-m-d", strtotime($data['tgl_akhir'])),
            "head_station" => $data['head_station'],
            "bt_location" => $data['bt_location'],
            "contact_phone" => $data['contact_phone'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date("Y-m-d H:i:s"),
            "transport" => $transport,
            "transport_type" => $data['transport_type'],
            "travel_arrangement" => $data['travel_arrangement'],
            "payment_applicable" => $data['payment_applicable'],
            "accomodation" => $data['accomodation'],
            "totalsequence" => $data['totalsequence'],
            "total" => $v['total'],
            "val_kode" => $record['val_kode'],
        );
        $this->PROC->BusinessTripHeader->insert($arrayInsert);
         $activityHead['procurement_brfh'][0]=$arrayInsert;
        
        $activityCount=0;
        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $data['prj_kode'],
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->DEFAULT->Files->insert($arrayInsert);
                $activityFile['files'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
            }
        }
        
          $activityLog = array(
            "menu_name" => "Create BRF",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" =>  $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
        $activitylog2->insert($activityLog);

        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function updateAction() {
        $this->_helper->viewRenderer->setNoRender();
        $payment = Zend_Json::decode($this->_getParam("payment"));
        $comment = $this->_getParam("comment");
        $groupPayment = $this->groupPaymentByBudget($payment);
        $data = Zend_Json::decode($this->_getParam("data"));
        $transport = $this->_getParam("transport");
        $data['transport_type'] = $this->getTransportType($data, true);


        $file = $this->getRequest()->getParam('file');
        $deletedFile = $this->getRequest()->getParam('deletedfile');
        $jsonFile = Zend_Json::decode($file);
        $jsonDeletedFile = Zend_Json::decode($deletedFile);

        $tranoEdit = $this->getRequest()->getParam('trano_edit');
        $item_type = $this->getRequest()->getParam('item_type');


        $trano = $data['trano'];
        $tranoWorkflow = $trano;
        if ($item_type == 'BRFP')
            $tranoWorkflow = $tranoEdit;

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'], $data['sit_kode']);
        $data['workname'] = $this->DEFAULT->MasterWork->getWorkname($data['workid']);

        $items = $jsonEtc[0];
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $params = array(
            "workflowType" => $item_type,
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "itemID" => $tranoWorkflow,
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

//        //Ubah trano untuk item_type == BRFP
//        if ($item_type == 'BRFP')
//            $trano = $tranoEdit;
        //Insert ke log transaction
        $old = $this->PROC->BusinessTripDetail->fetchAll("trano = '$trano'")->toArray();
        $log['brf-detail-before'] = $old;
        $this->PROC->BusinessTripDetail->delete("trano = '$trano'");

        $total = 0;
        $kode_brg = '';
        foreach ($payment as $k => $v) {
            $record = $v['record'];

            if ($data['budget_type'] == 'O')
                $kode_brg = $v['kode_brg'];
            else
                $kode_brg = $record['kode_brg'];

            $arrayInsert = array(
                "trano" => $trano,
                "prj_kode" => $data['prj_kode'],
                "sit_kode" => $data['sit_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_nama" => $data['sit_nama'],
                "requester" => $data['requester'],
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date("Y-m-d H:i:s"),
                "workid" => $v['workid'],
                "workname" => $this->DEFAULT->MasterWork->getWorkname($v['workid']),
                "kode_brg" => $kode_brg,
                "nama_brg" => $this->DEFAULT->MasterBarang->getName($record['kode_brg']),
                "qty" => 1,
                "harga" => $v['total'],
                "total" => $v['total'],
                "val_kode" => $record['val_kode'],
                "allowance" => $v['allowance'],
                "airport_tax" => $v['airport_tax'],
                "transport" => $v['transport'],
                "accomodation" => $v['accomodation'],
                "others" => $v['others'],
                "sequence" => $v['sequence']
            );

            if ($item_type == 'BRFP') {
                $paymentReq = $this->PROC->BusinessTripPayment->fetchRow("trano='$tranoEdit' AND sequence = {$v['sequence']}");
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "requester" => $data['requester'],
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "tgl" => date("Y-m-d H:i:s"),
                        "workid" => $v['workid'],
                        "workname" => $this->DEFAULT->MasterWork->getWorkname($v['workid']),
                        "kode_brg" => $record['kode_brg'],
                        "nama_brg" => $this->DEFAULT->MasterBarang->getName($record['kode_brg']),
                        "qty" => 1,
                        "harga" => $v['total'],
                        "total" => $v['total'],
                        "val_kode" => $record['val_kode']
                    );
                    $this->PROC->BusinessTripPayment->update($arrayUpdate, "trano='$tranoEdit' AND sequence = {$v['sequence']}");
                }

                $paymentReq = $this->PROC->Procurementarfh->fetchRow("trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "total" => $v['total'],
                        "request" => $data['requester'],
                        "user" => QDC_User_Session::factory()->getCurrentUID(),
                        "tglinput" => date("Y-m-d"),
                        "jam" => date("H:i:s"),
                        "val_kode" => $record['val_kode'],
                    );
                    $this->PROC->Procurementarfh->update($arrayUpdate, "trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                }

                $paymentReq = $this->PROC->Procurementarfd->fetchRow("trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "tgl" => date("Y-m-d"),
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => 1,
                        "harga" => $v['total'],
                        "total" => $v['total'],
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $data['val_kode'],
                        "requester" => $v['requester'],
                    );
                    $this->PROC->Procurementarfd->update($arrayUpdate, "trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                }
            }

            $total += $v['total'];
            $this->PROC->BusinessTripDetail->insert($arrayInsert);
        }

        $new = $this->PROC->BusinessTripDetail->fetchAll("trano = '$trano'")->toArray();
        $log2['brf-detail-after'] = $new;

        //Insert ke log transaction
        $old = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'")->toArray();
        $log['brf-header-before'] = $old;
        
        foreach ($payment as $k => $v) {
        $arrayInsert = array(
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "requester" => $data['requester'],
            "job_title" => $data['job_title'],
            "department" => $data['department'],
            "reason" => $data['reason'],
            "tgl_awal" => date("Y-m-d", strtotime($data['tgl_awal'])),
            "tgl_akhir" => date("Y-m-d", strtotime($data['tgl_akhir'])),
            "head_station" => $data['head_station'],
            "bt_location" => $data['bt_location'],
            "contact_phone" => $data['contact_phone'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "transport" => $transport,
            "transport_type" => $data['transport_type'],
            "travel_arrangement" => $data['travel_arrangement'],
            "payment_applicable" => $data['payment_applicable'],
            "accomodation" => $data['accomodation'],
            "total" => $v['total'],
        );
        }

        $this->PROC->BusinessTripHeader->update($arrayInsert, "trano='$trano'");

        $new = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'")->toArray();
        $log2['brf-header-after'] = $new;

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
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

        $this->files->delete("trano = '$trano'");
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
        
        $result = Zend_Json::encode(array("success" => true, "number" => $trano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function reviseAction() {
        $this->_helper->viewRenderer->setNoRender();
        $payment = Zend_Json::decode($this->_getParam("payment"));
        $comment = $this->_getParam("comment");
        $groupPayment = $this->groupPaymentByBudget($payment);
        $data = Zend_Json::decode($this->_getParam("data"));
        $transport = $this->_getParam("transport");
        $data['transport_type'] = $this->getTransportType($data, true);
        
        $file = $this->getRequest()->getParam('file');
        $deletedFile = $this->getRequest()->getParam('deletedfile');
        $jsonFile = Zend_Json::decode($file);
        $jsonDeletedFile = Zend_Json::decode($deletedFile);

        $item_type = $this->getRequest()->getParam('item_type');
          if ($item_type == 'BRFP') {
              $tranoEdit = $this->getRequest()->getParam('trano_edit');
          }else{
               $tranoEdit = $data['trano'];
          }

        $trano = $data['trano'];
        /*$tranoWorkflow = $trano;
        if ($item_type == 'BRFP')
            $tranoWorkflow = $tranoEdit;*/

        $data['prj_nama'] = $this->DEFAULT->MasterProject->getProjectName($data['prj_kode']);
        $data['sit_nama'] = $this->DEFAULT->MasterSite->getSiteName($data['prj_kode'], $data['sit_kode']);
        $data['workname'] = $this->DEFAULT->MasterWork->getWorkname($data['workid']);

        $items = $jsonEtc[0];
        $items["prj_kode"] = $data['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $this->workflowTrans->delete("item_id = '$tranoEdit'");
        
        $params = array(
            "workflowType" => $item_type,
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "itemID" => $tranoEdit,
            "items" => $items,
            "prjKode" => $data['prj_kode'],
            "generic" => true,
            "revisi" => true,
            "returnException" => false,
            "comment" => $comment
        );
        $this->workflow->setWorkflowTransNew($params);

        //Insert ke log transaction
        $old = $this->PROC->BusinessTripDetail->fetchAll("trano = '$trano'")->toArray();
        $log['brf-detail-before'] = $old;
        //$this->PROC->BusinessTripDetail->delete("trano = '$trano'");

        $total = 0;
        $kode_brg = '';
        foreach ($payment as $k => $v) {
            $record = $v['record'];

            if ($data['budget_type'] == 'O')
                $kode_brg = $v['kode_brg'];
            else
                $kode_brg = $record['kode_brg'];

            $arrayUpdateBTD = array(
                "trano" => $trano,
                "prj_kode" => $data['prj_kode'],
                "sit_kode" => $data['sit_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_nama" => $data['sit_nama'],
                "requester" => $data['requester'],
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date("Y-m-d H:i:s"),
                "workid" => $v['workid'],
                "workname" => $this->DEFAULT->MasterWork->getWorkname($v['workid']),
                "kode_brg" => $kode_brg,
                "nama_brg" => $this->DEFAULT->MasterBarang->getName($record['kode_brg']),
                "qty" => 1,
                "harga" => $v['total'],
                "total" => $v['total'],
                "val_kode" => $record['val_kode'],
                "allowance" => $v['allowance'],
                "airport_tax" => $v['airport_tax'],
                "transport" => $v['transport'],
                "accomodation" => $v['accomodation'],
                "others" => $v['others'],
                "sequence" => $v['sequence']
            );

            if ($item_type == 'BRFP') {
                $paymentReq = $this->PROC->BusinessTripPayment->fetchRow("trano='$tranoEdit' AND sequence = {$v['sequence']}")->toArray();
                $log3['brfp-before'] = $paymentReq ;
                
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "requester" => $data['requester'],
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "tgl" => date("Y-m-d H:i:s"),
                        "workid" => $v['workid'],
                        "workname" => $this->DEFAULT->MasterWork->getWorkname($v['workid']),
                        "kode_brg" => $record['kode_brg'],
                        "nama_brg" => $this->DEFAULT->MasterBarang->getName($record['kode_brg']),
                        "qty" => 1,
                        "harga" => $v['total'],
                        "total" => $v['total'],
                        "val_kode" => $record['val_kode']
                    );
                    $this->PROC->BusinessTripPayment->update($arrayUpdate, "trano='$tranoEdit' AND sequence = {$v['sequence']}");
                }

                $paymentReq = $this->PROC->Procurementarfh->fetchRow("trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "total" => $v['total'],
                        "request" => $data['requester'],
                        "user" => QDC_User_Session::factory()->getCurrentUID(),
                        "tglinput" => date("Y-m-d"),
                        "jam" => date("H:i:s"),
                        "val_kode" => $record['val_kode'],
                    );
                    $this->PROC->Procurementarfh->update($arrayUpdate, "trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                }

                $paymentReq = $this->PROC->Procurementarfd->fetchRow("trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                if ($paymentReq) {
                    $arrayUpdate = array(
                        "tgl" => date("Y-m-d"),
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => 1,
                        "harga" => $v['total'],
                        "total" => $v['total'],
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $data['val_kode'],
                        "requester" => $v['requester'],
                    );
                    $this->PROC->Procurementarfd->update($arrayUpdate, "trano='$tranoEdit' AND bt_sequence = {$v['sequence']}");
                }
            }

            $total += $v['total'];
            //$this->PROC->BusinessTripDetail->insert($arrayInsert);
            $this->PROC->BusinessTripDetail->update($arrayUpdateBTD,"trano='$trano' AND sequence = {$v['sequence']}");
        }
       
        $new = $this->PROC->BusinessTripDetail->fetchAll("trano = '$trano'")->toArray();
        $log2['brf-detail-after'] = $new;
        
        if ($item_type == 'BRFP') {
        $new2 = $this->PROC->BusinessTripPayment->fetchRow("trano='$tranoEdit' AND sequence = {$v['sequence']}")->toArray();
        $log4['brfp-after'] = $new2;
        }
        //Insert ke log transaction
        $old = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'")->toArray();
        $log['brf-header-before'] = $old;
        
        foreach ($payment as $k => $v) {
        $arrayUpdateBTH = array(
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "prj_nama" => $data['prj_nama'],
            "sit_nama" => $data['sit_nama'],
            "requester" => $data['requester'],
            "job_title" => $data['job_title'],
            "department" => $data['department'],
            "reason" => $data['reason'],
            "tgl_awal" => date("Y-m-d", strtotime($data['tgl_awal'])),
            "tgl_akhir" => date("Y-m-d", strtotime($data['tgl_akhir'])),
            "head_station" => $data['head_station'],
            "bt_location" => $data['bt_location'],
            "contact_phone" => $data['contact_phone'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "transport" => $transport,
            "transport_type" => $data['transport_type'],
            "travel_arrangement" => $data['travel_arrangement'],
            "payment_applicable" => $data['payment_applicable'],
            "accomodation" => $data['accomodation'],
            "total" => $v['total'],
        );

        $this->PROC->BusinessTripHeader->update($arrayUpdateBTH, "trano='$trano'");
        }
        
        $new = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'")->toArray();
        $log2['brf-header-after'] = $new;

        //Log Transaction
        $logs = new Admin_Models_Logtransaction();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $jsonLog3 = Zend_Json::encode($log3);
        $jsonLog4 = Zend_Json::encode($log4);
        
        $arrayLog = array(
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "REVISI",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog);
        
        $arrayLog2 = array(
            "trano" => $tranoEdit,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $data['prj_kode'],
            "sit_kode" => $data['sit_kode'],
            "action" => "REVISI",
            "data_before" => $jsonLog3,
            "data_after" => $jsonLog4,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs->insert($arrayLog2);

        $this->files->delete("trano = '$trano'");
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

        $result = Zend_Json::encode(array("success" => true, "number" => $tranoEdit));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function fundPaymentAction() {
        $this->view->trano = $this->_getParam("trano");
    }

    public function cekWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $item_type = $this->_getParam("item_type");
        $finalOnly = ($this->_getParam("final") !== 'true') ? false : true;
        $valid = true;
        $msg = null;
        if($item_type == 'BSF'){
            $result = Zend_Json::encode(array("success" => $valid, "msg" => $msg));
        } else {
            $isFinal = QDC_Workflow_Transaction::factory()->isDocumentFinal($trano);
            $isReject = QDC_Workflow_Transaction::factory()->isDocumentReject($trano);
            $isExpired = QDC_Workflow_Transaction::factory()->isDocumentExpired($trano);

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
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
    
    public function cekFundWorkflowAction() {
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
        
        if($this->isPartial($trano)==0) {
                $msg = "This Document is not Partial/Sequences.";
                $valid = false;
        }
        
        $result = Zend_Json::encode(array("success" => $valid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function cekRevisionWorkflowAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $item_type = $this->_getParam("item_type");
        $finalOnly = ($this->_getParam("final") !== 'true') ? false : true;

        $isFinal = QDC_Workflow_Transaction::factory()->isDocumentFinal($trano);
        //$isReject = QDC_Workflow_Transaction::factory()->isDocumentReject($trano);
        $valid = true;
        if ($finalOnly) {
            if (!$isFinal) {
                $msg = "This Document is not Final Approval yet.";
                $valid = false;
            }
        }
        /*else {
            if (!$isReject || $isFinal) {
                $msg = "This Document is already Final Approval or not Rejected yet.";
                $valid = false;
            }
        }*/

        if($this->isPaid($trano)==0) {
                $msg = "Please Call Finance to Submit Its Payment Document in ERP.";
                $valid = false;
        }
  
        $result = Zend_Json::encode(array("success" => $valid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function getPaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->_getParam("trano");
        $allowedID = ($this->_getParam("allowedID") != '') ? Zend_Json::decode($this->_getParam("allowedID")) : '';

        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripDetail->__name()))
                ->where("trano = '$trano'")
                ->order(array("sequence ASC"));

        if ($allowedID) {
            $tmp = implode(",", $allowedID);
            $select = $select->where("id IN ($tmp)");
        }

        $data = $this->db->fetchAll($select);
        $payment = array();
        $success = false;
        if ($data) {
            $header = $this->PROC->BusinessTripHeader->fetchRow("trano = '$trano'")->toArray();
            $payment = $data;
            foreach ($payment as $k => $v) {
                $payment[$k]['allowance'] = floatval($payment[$k]['allowance']);
                $payment[$k]['transport'] = floatval($payment[$k]['transport']);
                $payment[$k]['airport_tax'] = floatval($payment[$k]['airport_tax']);
                $payment[$k]['accomodation'] = floatval($payment[$k]['accomodation']);
                $payment[$k]['others'] = floatval($payment[$k]['others']);
                $payment[$k]['total'] = floatval($payment[$k]['total']);

                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("trano_ref = '{$v['trano']}'")
                        ->where("sequence = {$v['sequence']}")
                        ->where("prj_kode = '{$v['prj_kode']}'")
                        ->where("sit_kode = '{$v['sit_kode']}'")
                        ->where("workid = '{$v['workid']}'")
                        ->where("kode_brg = '{$v['kode_brg']}'");

                $cek = $this->db->fetchRow($select);
                $payment[$k]['total_fund'] = 0;
                if ($cek) {
                    $payment[$k]['total_fund'] = $cek['total'];
                    $arr = array(
                        "tgl_fund" => date("d M Y", strtotime($cek['tgl'])),
                        "uid" => QDC_User_Ldap::factory(array("uid" => $cek['uid']))->getName(),
                        "arf_no" => $cek['arf_no'],
                        "approval" => QDC_Workflow_Transaction::factory()->getDocumentLastStatusByApproval($cek['arf_no'], true)->getApprovalStatus(true)
                    );
                    $payment[$k]['fund'] = $arr;
                }
                $bal = $v['total'] - $payment[$k]['total_fund'];
                if ($bal <= 0)
                    $payment[$k]['invalid'] = true;
                $payment[$k]['balance'] = $bal;
            }
            $success = true;
            $payment = $this->sortPayment($payment);
            $data = array(
                "requester" => QDC_User_Ldap::factory(array("uid" => $header['requester']))->getName(),
                "bt_location" => $header['bt_location'],
                "date" => date("d M Y", strtotime($header['tgl_awal'])) . " - " . date("d M Y", strtotime($header['tgl_akhir'])),
                "prj_kode" => $header['prj_kode'],
                "prj_nama" => $header['prj_nama'],
                "sit_kode" => $header['sit_kode'],
                "sit_nama" => $header['sit_nama']
            );
        } else
            $msg = "BRF is not found!";
        $result = Zend_Json::encode(array("success" => $success, "payment" => $payment, "data" => $data, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function doFundPaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $sequence = $this->_getParam("sequence");
        $trano = $this->_getParam("brf_trano");
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $workid = $this->_getParam("workid");
        $kodeBrg = $this->_getParam("kode_brg");

        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripDetail->__name()))
                ->where("trano = '$trano'")
                ->where("sequence = $sequence")
                ->where("prj_kode = '$prjKode'")
                ->where("sit_kode = '$sitKode'")
                ->where("workid = '$workid'")
                ->where("kode_brg = '$kodeBrg'");

        $data = $this->db->fetchRow($select);
        $success = false;
        if ($data) {
            $success = true;

            $dataHeader = $this->PROC->BusinessTripDetail->fetchRow("trano = '$trano'")->toArray();
            $items = $dataHeader;
            $items["prj_kode"] = $dataHeader['prj_kode'];
            $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $params = array(
                "workflowType" => "BRFP",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $dataHeader['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => false,
//                "useOverride" => true,
                "uidApproval" => QDC_User_Session::factory()->getCurrentUID()
            );

            $paymentTrano = $this->workflow->setWorkflowTransNew($params);

            $arrayInsert = array(
                "trano" => $paymentTrano,
                "trano_ref" => $trano,
                "arf_no" => $paymentTrano,
                "tgl" => date("Y-m-d"),
                "sequence" => $sequence,
                "prj_kode" => $data['prj_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_kode" => $data['sit_kode'],
                "sit_nama" => $data['sit_nama'],
                "workid" => $data['workid'],
                "workname" => $data['workname'],
                "kode_brg" => $data['kode_brg'],
                "nama_brg" => $data['nama_brg'],
                "qty" => $data['qty'],
                "harga" => $data['harga'],
                "total" => $data['total'],
                "requester" => $data['requester'],
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "val_kode" => $data['val_kode'],
            );
            $this->PROC->BusinessTripPayment->insert($arrayInsert);

            $arrayInsert = array(
                "trano" => $paymentTrano,
                "trano_ref" => $trano,
                "tgl" => date("Y-m-d"),
                "prj_kode" => $data['prj_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_kode" => $data['sit_kode'],
                "sit_nama" => $data['sit_nama'],
                "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                "total" => $data['total'],
                "request" => $data['requester'],
                "user" => QDC_User_Session::factory()->getCurrentUID(),
                "tglinput" => date("Y-m-d"),
                "jam" => date("H:i:s"),
                "val_kode" => $data['val_kode'],
                "bt_sequence" => $sequence,
                "bt" => 'Y',
                "ketin" => "BRF Payment Sequence No. " . $sequence . ". This ARF is auto generated from Business Trip Request (BRF) transaction, please refer to Trano : " . $trano . " for further detail."
            );
            $this->PROC->Procurementarfh->insert($arrayInsert);

            $arrayInsert = array(
                "trano" => $paymentTrano,
                "trano_ref" => $trano,
                "tgl" => date("Y-m-d"),
                "prj_kode" => $data['prj_kode'],
                "prj_nama" => $data['prj_nama'],
                "sit_kode" => $data['sit_kode'],
                "sit_nama" => $data['sit_nama'],
                "workid" => $data['workid'],
                "workname" => $data['workname'],
                "kode_brg" => $data['kode_brg'],
                "nama_brg" => $data['nama_brg'],
                "qty" => $data['qty'],
                "harga" => $data['harga'],
                "total" => $data['total'],
                "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                "val_kode" => $data['val_kode'],
                "requester" => $data['requester'],
                "urut" => 1,
                "bt_sequence" => $sequence,
                "bt" => 'Y',
                "ket" => "BRF Payment Sequence No. " . $sequence
            );
            $this->PROC->Procurementarfd->insert($arrayInsert);
        } else {
//            $msg = (string)$select;
            $msg = "Payment Sequence not found.";
        }


        $result = Zend_Json::encode(array("success" => $success, "msg" => $msg, "number" => $paymentTrano));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function cekEditedPaymentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $payment = Zend_Json::decode($this->_getParam("data"));
        $bulk = ($this->_getParam("bulk") != '') ? ($this->_getParam("bulk") === 'true') : false;
        $trano = $this->_getParam("trano");

        $foundInvalid = false;
        $invalid = array();
        if (!$bulk) {
            $arf = $this->checkArfOfPayment($trano, $payment, true);
            if ($arf) {
                $total = $arf['total_arf'];
                if ($total > $payment['total']) {
                    $foundInvalid = true;
                    $msg = 'Payment Request has been funded : ' . number_format($total, 2) . ', therefore Your request must be greater than ' . number_format($total, 2);
                }
            }
        }

        $result = Zend_Json::encode(array("success" => !$foundInvalid, "data" => $invalid, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    private function checkArfOfPayment($trano, $payment = array(), $returnArray = false) {
        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripPayment->__name()))
                ->where("trano_ref = ?", $trano)
                ->where("prj_kode = ?", $payment['prj_kode'])
                ->where("sit_kode = ?", $payment['sit_kode'])
                ->where("kode_brg = ?", $payment['kode_brg'])
                ->where("workid = ?", $payment['workid'])
                ->where("sequence = ?", $payment['sequence']);

        $subselect = $this->db->select()
                ->from(array("a" => $select))
                ->joinLeft(array("b" => $this->PROC->Procurementarfd->__name()), "a.trano_ref = b.trano_ref AND a.arf_no = b.trano AND a.prj_kode = b.prj_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND a.kode_brg = b.kode_brg AND a.sequence = b.bt_sequence", array(
            "total_arf" => "SUM(b.qty*b.harga)"
                )
        );

        $arf = $this->db->fetchRow($subselect);

        if ($returnArray)
            return $arf;
        else {
            if ($arf)
                return true;
            else
                return false;
        }
    }

    public function cekBsfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $sequence = $this->_getParam("sequence");
        $trano = $this->_getParam("trano");
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $workid = $this->_getParam("workid");
        $kodeBrg = $this->_getParam("kode_brg");

        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripDetail->__name()))
                ->where("trano = '$trano'")
                ->where("sequence = $sequence")
                ->where("prj_kode = '$prjKode'")
                ->where("sit_kode = '$sitKode'")
                ->where("workid = '$workid'")
                ->where("kode_brg = '$kodeBrg'");

        $data = $this->db->fetchRow($select);
        $success = false;
        if ($data) {
            if ($sequence > 1) {
                $lastSequence = $sequence - 1;
                $trano_ref = $data['trano'];
                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("sequence = $lastSequence")
                        ->where("trano_ref = '$trano_ref'");

                $lastData = $this->db->fetchRow($select);
                if ($lastData) {
                    $lastTrano = $lastData['trano'];
                    $select = $this->db->select()
                            ->from(array($this->DEFAULT->AdvanceSettlementForm->__name()))
                            ->where("arf_no = '$lastTrano'")
                            ->where("prj_kode = ?", $lastData['prj_kode'])
                            ->where("sit_kode = ?", $lastData['sit_kode'])
                            ->where("workid = ?", $lastData['workid'])
                            ->where("kode_brg = ?", $lastData['kode_brg']);

                    $BSFdata = $this->db->fetchRow($select);
                    if ($BSFdata) {
                        $success = true;
                    } else
                        $msg = "Last fund payment of this Business Trip was not settled yet, please settle : <b>" . $lastTrano . "</b>, sequence : <b>" . $lastSequence . "</b>";
                } else
                    $msg = "BRFP not found.";
            } else
                $success = true;
        }

        $result = Zend_Json::encode(array("success" => $success, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
    
    public function cekBrfpAction() {
        
        $this->_helper->viewRenderer->setNoRender();
        $sequence = $this->_getParam("sequence");
        $trano = $this->_getParam("trano");
        $prjKode = $this->_getParam("prj_kode");
        $sitKode = $this->_getParam("sit_kode");
        $workid = $this->_getParam("workid");
        $kodeBrg = $this->_getParam("kode_brg");

        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripDetail->__name()))
                ->where("trano = '$trano'")
                ->where("sequence = $sequence")
                ->where("prj_kode = '$prjKode'")
                ->where("sit_kode = '$sitKode'")
                ->where("workid = '$workid'")
                ->where("kode_brg = '$kodeBrg'");

        $data = $this->db->fetchRow($select);
        $success = false;
        if ($data) {
            if ($sequence > 1) {
                $lastSequence = $sequence - 1;
                $trano_ref = $data['trano'];
                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("sequence = $lastSequence")
                        ->where("trano_ref = '$trano_ref'");

                $lastData = $this->db->fetchRow($select);
                if ($lastData) {
                     $success = true;
                } else
                    $msg = "BRFP for sequence ".$lastSequence." is not submitted yet.";
            } else
                $success = true;
        }

        $result = Zend_Json::encode(array("success" => $success, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
    
    public function cekPreviousBsfAction() {
        $this->_helper->viewRenderer->setNoRender();
        
        $trano = $this->_getParam("trano");
        
        //Fetch the BRFP details.
        $select = $this->db->select()
                ->from(array($this->PROC->BusinessTripPayment->__name()))
                ->where("trano = '$trano'");              
        $data = $this->db->fetchRow($select);
        $success = false;
        
        if ($data) {
            
            //Only BRFP sequence 2 and so on which has previous BRFP
            if ($data['sequence'] > 1) {
                
                //Fetch previous BRFP
                $lastSequence = $data['sequence']-1;
                $trano_ref = $data['trano_ref'];
                
                $select = $this->db->select()
                        ->from(array($this->PROC->BusinessTripPayment->__name()))
                        ->where("sequence = $lastSequence")
                        ->where("trano_ref = '$trano_ref'");
                $brfpData = $this->db->fetchRow($select);
                
                 if ($brfpData){
                    
                     // Fetch its BSF details
                    $lastTrano = $brfpData['trano'];
                    $select = $this->db->select()
                            ->from(array($this->DEFAULT->AdvanceSettlementForm->__name()))
                            ->where("arf_no = '$lastTrano'")
                            ->where("prj_kode = ?", $brfpData['prj_kode'])
                            ->where("sit_kode = ?", $brfpData['sit_kode'])
                            ->where("workid = ?", $brfpData['workid'])
                            ->where("kode_brg = ?", $brfpData['kode_brg']);
                    $BSFdata = $this->db->fetchRow($select);
                    
                    if ($BSFdata) {
                        
                        $bsfTrano= $BSFdata['trano'];
                        
                        // Check whether the BSF final or not
                        $select = $this->db->select()
                            ->from('imderpdb.workflow_trans')
                            ->where("item_id = '$bsfTrano'")
                            ->where("final = 1");
                        $isFinal = $this->db->fetchRow($select);
                        
                        if($isFinal)
                            $success = true;
                        else
                          $msg = "BSF number : <b>" . $bsfTrano . "</b> is not final approved yet.";                        
                        
                    }else
                        $msg = "Last fund payment of this Business Trip was not settled yet, please settle : <b>" .$brfpData['trano']. "</b>, sequence : <b>" . $lastSequence . "</b>";
                    
                 } else
                        $msg = "Last fund payment of this Business Trip is not submitted yet.";
            
                
            } else
                $success = true;  
                      
        } else
            $msg = "BRFP not found.";

        $result = Zend_Json::encode(array("success" => $success, "msg" => $msg));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }
                                            
    public function brfToBrfpAction()
    {

    }
    
                }
                
?>


