<?php

class Procurement_ErfController extends Zend_Controller_Action {
    /* Define protected variable     */

    //define erf,asf object for models
    protected $erfd, $erfh, $budget, $quantity, $project;
    protected $asfd, $asfh, $asfc, $asf;
    //define workflow item
    protected $workflow, $workflowClas, $workflowTrans;
    //define tools object            
    protected $util, $json, $token, $files, $upload, $db, $request;
    protected $const, $creTrans, $log_trans, $trans, $log;
    protected $ADMIN;

    public function init() {

        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->const = Zend_Registry::get('constant');
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

        $this->erfd = new Procurement_Models_EntertainmentRequestD();
        $this->erfh = new Procurement_Models_EntertainmentRequestH();

        //ASF Models Section
        $this->asf = new Default_Models_AdvanceSettlementForm();
        $this->asfc = new Default_Models_AdvanceSettlementFormCancel();
        $this->asfd = new Default_Models_AdvanceSettlementFormD();
        $this->asfh = new Default_Models_AdvanceSettlementFormH();
        //---------------------
        //Workflow,Log, and Transcation Models
        $this->trans = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction');
        $this->workflowTrans = new Admin_Models_Workflowtrans();
        $this->workflowClas = new Admin_Models_Workflow();
        $this->log = new Admin_Models_Logtransaction();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->creTrans = new Admin_Model_CredentialTrans();
        //---------------------
        //Tools Models Section
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->token = Zend_Controller_Action_HelperBroker::getStaticHelper('token');
        $this->files = new Default_Models_Files();
        //---------------------
        //Quantity,budget, etc Models Section
        $this->budget = new Default_Models_Budget();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        //---------------------

        $models = array(
            "Logtransaction",
            "Masterrole"
        );
        $this->ADMIN = QDC_Model_Admin::init($models);


        Zend_Loader::loadClass('Zend_Json');
    }

    public function indexAction() {
        
    }

    public function erfAction() {
        $this->view->render('erf/erf.phtml');
    }

    public function commonAddEvent() {

        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ERFtrano = $trano;
        if ($isCancel) {

            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
            $this->view->jsonPerson = $this->getRequest()->getParam("person");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function adderfAction() {
        $this->getUidName();
        $this->commonAddEvent();
    }

    public function adderfsalesAction() {
        $this->getUidName();
        $this->commonAddEvent();
    }

    public function adderfbudgetAction() {
        $this->getUidName();
        $this->commonAddEvent();
    }

    public function editerfAction() {
        $this->getUidName();

        $trano = $this->getRequest()->getParam("trano");
        $erfh = $this->erfh->fetchRow("trano = '$trano'");
        $erfd = $this->erfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($erfh)
            $erfh = $erfh->toArray();
        $tmp = array();

        foreach ($erfd as $key => $val) {
            $erfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $erfd[$key]['priceErf'] = $val['harga'];
            $erfd[$key]['totalERF'] = $val['total'];
            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();

            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $erfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBoq3ByOne($prjKode, $sitKode, $workid, $kodeBrg);
            if ($erfd[$key]['val_kode'] == 'IDR') {
                $erfd[$key]['totalBOQ3'] = $boq3['totalIDR'];
            } else {
                $erfd[$key]['totalBOQ3'] = $boq3['totalUSD'];
            }

            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $erf = $this->quantity->getErfQuantity($prjKode, $sitKode, $workid, $kodeBrg);
            $esfcancel = $this->quantity->getEsfcancelQuantity($prjKode, $sitKode, $workid, $kodeBrg);

            if ($po != '') {
                $erfd[$key]['totalqtyPO'] = $po['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $erfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyPO'] = 0;
                $erfd[$key]['totalPO'] = 0;
            }
            if ($erf != '') {
                $erfd[$key]['totalqtyERF'] = $erf['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR') {
//                    $erfd[$key]['totalInERF'] = $erf['totalIDR'];
                    $erfd[$key]['totalInERF'] = $erf['realtotalIDR'];
                } else {
//                    $erfd[$key]['totalInERF'] = $erf['totalUSD'];
                    $erfd[$key]['totalInERF'] = $erf['realtotalUSD'];
                }
            } else {
                $erfd[$key]['totalqtyERF'] = 0;
                $erfd[$key]['totalERF'] = 0;
            }

            if ($esfcancel != '') {
                $erfd[$key]['totalqtyESFCancel'] = $esfcancel['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR') {
//                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalIDR'];
                    $erfd[$key]['totalESFCancel'] = $esfcancel['realtotalIDR'];
                } else {
//                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalUSD'];
                    $erfd[$key]['totalESFCancel'] = $esfcancel['realtotalUSD'];
                }
            } else {
                $erfd[$key]['totalqtyESFCancel'] = 0;
                $erfd[$key]['totalESFCancel'] = 0;
            }


            $totalpoerfesfc = (($erfd[$key]['totalPO'] + $erfd[$key]['totalInERF']) - $erfd[$key]['totalESFCancel'] );
            $erfd[$key]['totalPoErfEsfc'] = $totalpoerfesfc;
        }

        $tmp2 = $erfh;
        unset($erfh);
        $erfh[0] = $tmp2;


        $erfd[0]['net_act'] = $erfd[0]['cfs_kode'];
        $jsonData = Zend_Json::encode($erfd);
        $erfh[0]['bank'] = $erfh[0]['namabank'];
        $erfh[0]['bankaccountname'] = $erfh[0]['reknamabank'];
        $erfh[0]['bankaccountno'] = $erfh[0]['rekbank'];
        
        $jsonData2 = Zend_Json::encode($erfh);
        

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
            $this->view->jsonPerson = $this->getRequest()->getParam("person");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }


        $this->view->edit = true;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($erfh[0]['tgl']));
        $this->view->val_kode = $erfh[0]['val_kode'];
        $this->view->request = $erfh[0]['request'];
        $this->view->ket = $erfh[0]['ket'];
        $this->view->ketin = $erfh[0]['ketin'];

        $this->view->file = Zend_Json::encode($file);
    }

    public function editerfbudgetAction() {
        $this->getUidName();

        $trano = $this->getRequest()->getParam("trano");
        $erfh = $this->erfh->fetchRow("trano = '$trano'");
        $erfd = $this->erfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($erfh)
            $erfh = $erfh->toArray();
        $tmp = array();

        foreach ($erfd as $key => $val) {

            $erfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];

            $erfd[$key]['priceErf'] = $val['harga'];
            $erfd[$key]['totalERF'] = $val['total'];
            $erfd[$key]['budgetid'] = $val['workid'];
            $erfd[$key]['budgetname'] = $val['workname'];
            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $erfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode, $workid);

            if ($erfd[$key]['val_kode'] == 'IDR') {
                $erfd[$key]['totalBOQ3'] = $boq3[0]['totalIDR'];
            } else {
                $erfd[$key]['totalBOQ3'] = $boq3[0]['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid);
            $erf = $this->quantity->getErfQuantity($prjKode, $sitKode, $workid);
            $esfcancel = $this->quantity->getEsfcancelQuantity($prjKode, $sitKode, $workid);
            $reimburs = $this->quantity->getReimbursementQuantity($prjKode, $sitKode, $workid);

            if ($po != '') {
                $erfd[$key]['totalqtyPO'] = $po['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $erfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyPO'] = 0;
                $erfd[$key]['totalPO'] = 0;
            }
            if ($erf != '') {
                $erfd[$key]['totalqtyERF'] = $erf['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalInERF'] = $erf['totalIDR'];
                else
                    $erfd[$key]['totalInERF'] = $erf['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyERF'] = 0;
                $erfd[$key]['totalERF'] = 0;
            }

            if ($esfcancel != '') {
                $erfd[$key]['totalqtyESFCancel'] = $esfcancel['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalIDR'];
                else
                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyESFCancel'] = 0;
                $erfd[$key]['totalESFCancel'] = 0;
            }

            if ($reimburs != '') {
                $erfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                else
                    $erfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyReimburs'] = 0;
                $erfd[$key]['totalReimburs'] = 0;
            }
            $totalpoerfesfc = (($erfd[$key]['totalPO'] + $erfd[$key]['totalInERF']) - $erfd[$key]['totalESFCancel'] );
            $erfd[$key]['totalPoErfEsfc'] = $totalpoerfesfc;
        }

        $tmp2 = $erfh;
        unset($erfh);
        $erfh[0] = $tmp2;

        $jsonData = Zend_Json::encode($erfd);
        $jsonData2 = Zend_Json::encode($erfh);

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
        $this->view->tgl = date('d-m-Y', strtotime($erfh[0]['tgl']));
        $this->view->pr_no = $erfh[0]['pr_no'];
        $this->view->val_kode = $erfh[0]['val_kode'];
        $this->view->request = $erfh[0]['request'];
        $this->view->ket = $erfh[0]['ket'];
        $this->view->ketin = $erfh[0]['ketin'];

        $this->view->file = Zend_Json::encode($file);
    }

    public function editerfsalesAction() {

        $this->getUidName();

        $trano = $this->getRequest()->getParam("trano");
        $erfh = $this->erfh->fetchRow("trano = '$trano'");
        $erfd = $this->erfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $file = $this->files->fetchAll("trano = '$trano'");

        if ($file)
            $file = $file->toArray();
        else
            $file = array();

        if ($erfh)
            $erfh = $erfh->toArray();
        $tmp = array();

        foreach ($erfd as $key => $val) {

            $erfd[$key]['id'] = $key + 1;
            $kodeBrg = $val['kode_brg'];
            $workid = $val['workid'];
            $sitKode = $val['sit_kode'];
            $prjKode = $val['prj_kode'];


            $erfd[$key]['priceErf'] = $val['harga'];
            $erfd[$key]['totalERF'] = $val['total'];
            $erfd[$key]['budgetid'] = $val['workid'];
            $erfd[$key]['budgetname'] = $val['workname'];
            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            if ($barang) {
                $erfd[$key]['uom'] = $barang['sat_kode'];
            }

            $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode, $workid);


            if ($erfd[$key]['val_kode'] == 'IDR') {
                $erfd[$key]['totalBOQ3'] = $boq3[0]['totalIDR'];
            } else {
                $erfd[$key]['totalBOQ3'] = $boq3[0]['totalUSD'];
            }
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workid);
            $erf = $this->quantity->getErfQuantity($prjKode, $sitKode, $workid);
            $esfcancel = $this->quantity->getEsfcancelQuantity($prjKode, $sitKode, $workid);
            $reimburs = $this->quantity->getReimbursementQuantity($prjKode, $sitKode, $workid);

            if ($po != '') {
                $erfd[$key]['totalqtyPO'] = $po['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalPO'] = $po['totalIDR'];
                else
                    $erfd[$key]['totalPO'] = $po['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyPO'] = 0;
                $erfd[$key]['totalPO'] = 0;
            }
            if ($erf != '') {
                $erfd[$key]['totalqtyERF'] = $erf['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR') {
//                    $erfd[$key]['totalInERF'] = $erf['totalIDR'];
                    $erfd[$key]['totalInERF'] = $erf['realtotalIDR'];
                } else {
//                    $erfd[$key]['totalInERF'] = $erf['totalUSD'];
                    $erfd[$key]['totalInERF'] = $erf['realtotalUSD'];
                }
            } else {
                $erfd[$key]['totalqtyERF'] = 0;
                $erfd[$key]['totalERF'] = 0;
            }

            if ($esfcancel != '') {
                $erfd[$key]['totalqtyESFCancel'] = $esfcancel['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR') {
//                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalIDR'];
                    $erfd[$key]['totalESFCancel'] = $esfcancel['realtotalIDR'];
                } else {
//                    $erfd[$key]['totalESFCancel'] = $esfcancel['totalUSD'];
                    $erfd[$key]['totalESFCancel'] = $esfcancel['realtotalUSD'];
                }
            } else {
                $erfd[$key]['totalqtyESFCancel'] = 0;
                $erfd[$key]['totalESFCancel'] = 0;
            }

            if ($reimburs != '') {
                $erfd[$key]['totalqtyReimburs'] = $reimburs['qty'];
                if ($erfd[$key]['val_kode'] == 'IDR')
                    $erfd[$key]['totalReimburs'] = $reimburs['totalIDR'];
                else
                    $erfd[$key]['totalReimburs'] = $reimburs['totalUSD'];
            }
            else {
                $erfd[$key]['totalqtyReimburs'] = 0;
                $erfd[$key]['totalReimburs'] = 0;
            }
            $totalpoerfesfc = (($erfd[$key]['totalPO'] + $erfd[$key]['totalInERF']) - $erfd[$key]['totalESFCancel'] );
            $erfd[$key]['totalPoErfEsfc'] = $totalpoerfesfc;
        }


        $tmp2 = $erfh;
        unset($erfh);
        $erfh[0] = $tmp2;
        
  
        $jsonData = Zend_Json::encode($erfd);
        
        $erfh[0]['bank'] = $erfh[0]['namabank'];
        $erfh[0]['bankaccountname'] = $erfh[0]['reknamabank'];
        $erfh[0]['bankaccountno'] = $erfh[0]['rekbank'];
        $jsonData2 = Zend_Json::encode($erfh);

        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->jsonEtc = $this->getRequest()->getParam("etc");
            $this->view->jsonPerson = $this->getRequest()->getParam("person");
        } else {
            $this->view->json = $jsonData;
            $this->view->jsonEtc = $jsonData2;
        }

        $this->view->edit = true;
        $this->view->trano = $trano;
        $this->view->tgl = date('d-m-Y', strtotime($erfh[0]['tgl']));
        $this->view->val_kode = $erfh[0]['val_kode'];
        $this->view->request = $erfh[0]['request'];
        $this->view->ket = $erfh[0]['ket'];
        $this->view->ketin = $erfh[0]['ketin'];

        $this->view->file = Zend_Json::encode($file);
    }

    public function getlasterfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $number = $this->util->getLastNumber('ERF');
        $number++;
        echo "{ ERF:'$number' }";
    }

    public function inserterfAction() {
        $this->_helper->viewRenderer->setNoRender();

        $json = $this->getRequest()->getParam('posts');
        $file = $this->getRequest()->getParam('file');
        $person = $this->getRequest()->getParam('person');
        $etc = str_replace("\\", "", $this->getRequest()->getParam('etc'));
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $jsonPerson = Zend_Json::decode($person);
        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('ERF');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ERF01-' . $last;

        $ada = false;
        while (!$ada) {
            $cek = $this->erfd->fetchRow("trano = '$trano'");
            if ($cek) {
                $lastTrans = $counter->getLastTrans('ERF');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ERF01-' . $last;
            } elseif (!$cek)
                $ada = true;
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

        $result = $this->workflow->setWorkflowTrans($trano, 'ERF', '', $this->const['DOCUMENT_SUBMIT'], $items);
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

//        $lastid='';
//        $lastTrans['id'] = $lastid;
        $counter->update(array("urut" => $last), "id=" . $lastTrans['id']);

        foreach ($jsonData as $key => $val) {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

            $total = $val['qty'] * $val['priceErf'];
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
                "harga" => $val['priceErf'],
                "total" => $val['totalERF'],
                "ket" => $val['ket'],
                "cfs_kode" => $val['net_act'],
                "petugas" => $this->session->userName,
                "requester" => $val['requester'],
                "val_kode" => $val['val_kode']
            );
            $urut++;
            $totals = $totals + $val['totalERF'];

            $this->erfd->insert($arrayInsert);
        }

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tgl_hold" => $jsonEtc[0]['tgl_hold'],
            "place_hold" => $jsonEtc[0]['place_hold'],
            "cus_kode" => $jsonEtc[0]['cus_kode'],
            "cus_nama" => $jsonEtc[0]['cus_nama'],
            "penerima" => $jsonEtc[0]['penerima'],
            "namabank" => $jsonEtc[0]['bank'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "person_accom" => $person
                
                  
        );
        $this->erfh->insert($arrayInsert);

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

    public function inserterfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json = $this->getRequest()->getParam('posts');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
        $person = $this->getRequest()->getParam('person');

        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $persons = Zend_Json::decode($person);
        $counter = new Default_Models_MasterCounter();


        if ($sales) {
            $lastTrans = $counter->getLastTrans('ERF');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ERF01-' . $last;
            $tipe = 'S';
            $itemType = 'ERFS';
        } else {
            $lastTrans = $counter->getLastTrans('ERFO');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ERF02-' . $last;
            $tipe = 'O';
            $itemType = 'ERFO';
        }

        $ada = false;
        while (!$ada) {
            $cek = $this->erfd->fetchRow("trano = '$trano'");
            if ($cek) {
                if ($sales) {

                    $lastTrans = $counter->getLastTrans('ERF');
                    $last = abs($lastTrans['urut']);
                    $last = $last + 1;
                    $trano = 'ERF01-' . $last;
                } else {
                    $lastTrans = $counter->getLastTrans('ERFO');
                    $last = abs($lastTrans['urut']);
                    $last = $last + 1;
                    $trano = 'ERF02-' . $last;
                }
            } elseif (!$cek)
                $ada = true;
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


        $result = $this->workflow->setWorkflowTrans($trano, $itemType, '', $this->const['DOCUMENT_SUBMIT'], $items);
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
        $lastid = '';
        $lastid = $lastTrans['id'];

        $counter->update(array("urut" => $last), "id=" . $lastid);
        foreach ($jsonData as $key => $val) {

            $total = $val['qty'] * $val['priceErf'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['priceErf'],
                "total" => $val['totalERF'],
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "requester" => $val['requester'],
                "tipe" => $tipe
            );
            $urut++;
            $totals = $totals + $val['totalERF'];

            $this->erfd->insert($arrayInsert);
        }


        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "val_kode" => $jsonEtc[0]['valuta'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tgl_hold" => $jsonEtc[0]['tgl_hold'],
            "place_hold" => $jsonEtc[0]['place_hold'],
            "cus_kode" => $jsonEtc[0]['cus_kode'],
            "cus_nama" => $jsonEtc[0]['cus_nama'],
           "penerima" => $jsonEtc[0]['penerima'],
            "namabank" => $jsonEtc[0]['bank'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "person_accom" => $person,
            "tipe" => $tipe
        );
        $this->erfh->insert($arrayInsert);

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

    public function updateerfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $person = $this->getRequest()->getParam('person');

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;


        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'ERF', '', $this->const['DOCUMENT_RESUBMIT'], $items);
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
        $log['erf-detail-before'] = $this->erfd->fetchAll("trano = '$trano'")->toArray();
        $this->erfd->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

            $total = $val['qty'] * $val['priceErf'];
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
                "cfs_kode" => $val['net_act'],
                "qty" => $val['qty'],
                "harga" => $val['priceErf'],
                "total" => $val['totalERF'],
                "ket" => $val['ket'],
                "requester" => $val['requester'],
                "petugas" => $this->session->userName,
                "val_kode" => $val['val_kode']
            );
            $urut++;
            $totals = $totals + $val['totalERF'];

            $this->erfd->insert($arrayInsert);
        }
        $log2['erf-detail-after'] = $this->erfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "ketin" => $jsonEtc[0]['ketin'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tgl_hold" => $jsonEtc[0]['tgl_hold'],
            "place_hold" => $jsonEtc[0]['place_hold'],
            "cus_kode" => $jsonEtc[0]['cus_kode'],
            "cus_nama" => $jsonEtc[0]['cus_nama'],
           "penerima" => $jsonEtc[0]['penerima'],
            "namabank" => $jsonEtc[0]['bank'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "person_accom" => $person
        );
        $log['erf-header-before'] = $this->erfh->fetchRow("trano = '$trano'")->toArray();
        $this->erfh->update($arrayInsert, "trano = '$trano'");
        $log2['erf-header-after'] = $this->erfh->fetchRow("trano = '$trano'")->toArray();

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

    public function updateerfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();

        $etc = $this->getRequest()->getParam('etc');
        $sales = $this->getRequest()->getParam('sales');
        $file = $this->getRequest()->getParam('file');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        $person = $this->getRequest()->getParam('person');

        $trano = $jsonEtc[0]['trano'];
        $total = 0;
        $totals = 0;

        if ($sales) {
            $tipe = 'S';
            $itemType = 'ERFS';
        } else {
            $tipe = 'O';
            $itemType = 'ERFO';
        }

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, $itemType, '', $this->const['DOCUMENT_RESUBMIT'], $items);
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
        $log['erf-detail-before'] = $this->erfd->fetchAll("trano = '$trano'")->toArray();
        $this->erfd->delete("trano = '$trano'");
        foreach ($jsonData as $key => $val) {
            if ($val['val_kode'] == 'IDR')
                $harga = $val['hargaIDR'];
            else
                $harga = $val['hargaUSD'];

            $total = $val['qty'] * $val['priceErf'];
            $arrayInsert = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "urut" => $urut,
                "prj_kode" => $val['prj_kode'],
                "prj_nama" => $val['prj_nama'],
                "sit_kode" => $val['sit_kode'],
                "sit_nama" => $val['sit_nama'],
                "workid" => $val['budgetid'],
                "workname" => $val['budgetname'],
                "kode_brg" => $val['kode_brg'],
                "nama_brg" => $val['nama_brg'],
                "qty" => $val['qty'],
                "harga" => $val['priceErf'],
                "total" => $val['totalERF'],
                "ket" => $val['ket'],
                "petugas" => $this->session->userName,
                "requester" => $val['requester'],
                "val_kode" => $val['val_kode'],
                "cfs_kode" => $val['net_act'],
                "tipe" => $tipe
            );
            $urut++;
            $totals = $totals + $val['totalERF'];

            $this->erfd->insert($arrayInsert);
        }
        $log2['erf-detail-after'] = $this->erfd->fetchAll("trano = '$trano'")->toArray();
        $arrayInsert = array(
            "ketin" => $jsonEtc[0]['ketin'],
            "request" => $jsonEtc[0]['mgr_kode'],
            "orangpic" => $jsonEtc[0]['pic_kode'],
            "total" => $totals,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "budgettype" => $jsonEtc[0]['budgettype'],
            "jam" => date('H:i:s'),
            "tgl_hold" => $jsonEtc[0]['tgl_hold'],
            "place_hold" => $jsonEtc[0]['place_hold'],
            "cus_kode" => $jsonEtc[0]['cus_kode'],
            "cus_nama" => $jsonEtc[0]['cus_nama'],
           "penerima" => $jsonEtc[0]['penerima'],
            "namabank" => $jsonEtc[0]['bank'],
            "reknamabank" => $jsonEtc[0]['bankaccountname'],
            "rekbank" => $jsonEtc[0]['bankaccountno'],
            "person_accom" => $person
        );
        $log['erf-header-before'] = $this->erfh->fetchRow("trano = '$trano'")->toArray();
        $this->erfh->update($arrayInsert, "trano = '$trano'");
        $log2['erf-header-after'] = $this->erfh->fetchRow("trano = '$trano'")->toArray();

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

    public function apperfAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;
        $doc_file = $this->getRequest()->getParam("doc_file");
        $this->view->doc_file = $doc_file;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ERF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $persons = $this->getRequest()->getParam("person");
            $etc = str_replace("\\", "", $etc);


            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);
            $person = Zend_Json::decode($persons);

            foreach ($jsonData as $k => $v) {
                $jsonData[$k]['cfs_kode'] = $v['net_act'];
                $jsonData[$k]['cfs_nama'] = $v['net_act'];
            }

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->jsonResult = $json;
            $this->view->jsonFile = $files;
            $this->view->file = $file;
            $this->view->person = $person;
            $this->view->jsonPerson = $persons;

            if ($from == 'edit') {
                $this->view->edit = true;
            }
        } elseif ($approve || $tranoShow != '') {


            if ($tranoShow) {
                $docs = $this->workflowTrans->fetchRow("item_id = '$tranoShow'", array("date DESC"));
            }
            else
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
                    $erfd = $this->erfd->fetchAll("trano = '$approve'")->toArray();
                    $erfh = $this->erfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($erfd) {
                        foreach ($erfd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $erfd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $erfd[$key]['priceErf'] = $val['harga'];
                            $erfd[$key]['totalERF'] = $val['total'];
                            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $erfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $erfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $erfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $erfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $erfh['budgettype'];
                        $jsonData2[0]['valuta'] = $erfh['val_kode'];
                        $jsonData2[0]['mgr_kode'] = $erfh['request'];
                        $jsonData2[0]['pic_kode'] = $erfh['orangpic'];
                        $jsonData2[0]['ketin'] = $erfh['ketin'];
                        $jsonData2[0]['tgl_hold'] = $erfh['tgl_hold'];
                        $jsonData2[0]['penerima'] = $erfh['penerima'];
                        $jsonData2[0]['bank'] = $erfh['namabank'];
                        $jsonData2[0]['bankaccountname'] = $erfh['reknamabank'];
                        $jsonData2[0]['bankaccountno'] = $erfh['rekbank'];
                        $jsonData2[0]['place_hold'] = $erfh['place_hold'];



                        $erfh['person_accom'] = Zend_Json::decode($erfh['person_accom']);

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

                        $jsonData2[0]['cus_nama'] = $erfh['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $erfh['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);

                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $erfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->person = $erfh['person_accom'];
                        $this->view->jsonPerson = $persons;
                    }
                } else {
                    $this->view->approve = false;
                }

//                if ($this->ADMIN->Masterrole->cekUserInRole(array(
//                    "userID" => QDC_User_Session::factory()->getCurrentUID(),
//                    "prjKode" =>  $erfh['prj_kode'],
//                    "roleName" => "Finance and Logistic",
//                    "roleDisplayName" => array(
//                        "Finance and Accounting Officer",
//                        "Finance and Accounting Jr. Officer"
//                    )
//                ))){
//                    $this->view->canEditTrans = true;
//                    $this->view->editTransType = $docs['item_type'];
//                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apperfbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ERFO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            $persons = $this->getRequest()->getParam("person");

            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);
            $person = Zend_Json::decode($persons);

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
            $this->view->person = $person;
            $this->view->jsonPerson = $persons;

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
                    if ($potong == 'ERF01')
                        $this->view->sales = true;

                    $erfd = $this->erfd->fetchAll("trano = '$approve'")->toArray();
                    $erfh = $this->erfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($erfd) {
                        foreach ($erfd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $erfd[$key]['uom'] = $barang['sat_kode'];
                            }
//                                $erfd[$key]['priceErf'] = $val['priceErf'];
                            $erfd[$key]['priceErf'] = $val['harga'];
                            $erfd[$key]['totalERF'] = $val['total'];
                            $erfd[$key]['budgetid'] = $val['workid'];
                            $erfd[$key]['budgetname'] = $val['workname'];
                            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $erfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $erfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $erfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $erfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $erfh['budgettype'];
                        $jsonData2[0]['valuta'] = $erfh['val_kode'];

                          $jsonData2[0]['penerima'] = $erfh['penerima'];
                        $jsonData2[0]['bank'] = $erfh['namabank'];
                        $jsonData2[0]['bankaccountname'] = $erfh['reknamabank'];
                        $jsonData2[0]['bankaccountno'] = $erfh['rekbank'];

                        $jsonData2[0]['mgr_kode'] = $erfh['request'];
                        $jsonData2[0]['pic_kode'] = $erfh['orangpic'];
                        $jsonData2[0]['ketin'] = $erfh['ketin'];

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

                        $jsonData2[0]['cus_nama'] = $erfh['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $erfh['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $erfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->person = $person;
                        $this->view->jsonPerson = $persons;
                    }
                } else {
                    $this->view->approve = false;
                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function apperfsalesAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ERFS';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");


        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $files = $this->getRequest()->getParam("file");
            $etc = str_replace("\\", "", $etc);
            $persons = $this->getRequest()->getParam("person");
            
//            var_dump($json);die;
            
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $file = Zend_Json::decode($files);
            $person = Zend_Json::decode($persons);

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
            $this->view->person = $person;
            $this->view->jsonPerson = $persons;

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
                    if ($potong == 'ERF01')
                        $this->view->sales = true;

                    $erfd = $this->erfd->fetchAll("trano = '$approve'")->toArray();
                    $erfh = $this->erfh->fetchRow("trano = '$approve'");
                    $file = $this->files->fetchAll("trano = '$approve'");

                    if ($erfd) {
                        foreach ($erfd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $erfd[$key]['uom'] = $barang['sat_kode'];
                            }
                            $erfd[$key]['priceErf'] = $val['harga'];
                            $erfd[$key]['totalERF'] = $val['total'];
                            $erfd[$key]['budgetid'] = $val['workid'];
                            $erfd[$key]['budgetname'] = $val['workname'];
                            $erfd[$key]['requesterName'] = QDC_User_Ldap::factory(array("uid" => $val['requester']))->getName();
                        }

                        $userApp = $this->workflow->getAllApproval($approve);
                        $jsonData2[0]['user_approval'] = $userApp;
                        $jsonData2[0]['prj_kode'] = $erfh['prj_kode'];
                        $jsonData2[0]['prj_nama'] = $erfh['prj_nama'];
                        $jsonData2[0]['sit_kode'] = $erfh['sit_kode'];
                        $jsonData2[0]['sit_nama'] = $erfh['sit_nama'];
                        $jsonData2[0]['budgettype'] = $erfh['budgettype'];
                        $jsonData2[0]['valuta'] = $erfh['val_kode'];

                        $jsonData2[0]['mgr_kode'] = $erfh['request'];
                        $jsonData2[0]['pic_kode'] = $erfh['orangpic'];
                        $jsonData2[0]['ketin'] = $erfh['ketin'];
                        $jsonData2[0]['tgl_hold'] = $erfh['tgl_hold'];
                        $jsonData2[0]['place_hold'] = $erfh['place_hold'];
                        $jsonData2[0]['cus_nama'] = $erfh['cus_nama'];
                          $jsonData2[0]['penerima'] = $erfh['penerima'];
                        $jsonData2[0]['bank'] = $erfh['namabank'];
                        $jsonData2[0]['bankaccountname'] = $erfh['reknamabank'];
                        $jsonData2[0]['bankaccountno'] = $erfh['rekbank'];

                        $erfh['person_accom'] = Zend_Json::decode($erfh['person_accom']);

                        $picName = $this->trans->getPICName($jsonData2[0]['pic_kode']);
                        $jsonData2[0]['pic_nama'] = $picName['Name'];
                        $mgrName = $this->trans->getManagerName($approve);
                        $jsonData[0]['mgr_nama'] = $mgrName;

                        $jsonData2[0]['cus_nama'] = $erfh['cus_nama'];
                        $jsonData2[0]['cus_kode'] = $erfh['cus_kode'];
                        $jsonData2[0]['trano'] = $approve;

                        $allReject = $this->workflow->getAllReject($approve);
                        $lastReject = $this->workflow->getLastReject($approve);
                        $this->view->lastReject = $lastReject;
                        $this->view->allReject = $allReject;
                        $this->view->etc = $jsonData2;
                        $this->view->result = $erfd;
                        $this->view->file = $file;
                        $this->view->trano = $approve;
                        $this->view->approve = true;
                        $this->view->uid = $this->session->userName;
                        $this->view->userID = $this->session->idUser;
                        $this->view->docsID = $id;
                        $this->view->person = $erfh['person_accom'];
                        $this->view->jsonPerson = $persons;
                    }
                } else {
                    $this->view->approve = false;
                }
//                if ($this->ADMIN->Masterrole->cekUserInRole(array(
//                    "userID" => QDC_User_Session::factory()->getCurrentUID(),
//                    "prjKode" =>  $erfh['prj_kode'],
//                    "roleName" => "Finance and Logistic",
//                    "roleDisplayName" => array(
//                        "Finance and Accounting Officer",
//                        "Finance and Accounting Jr. Officer"
//                    )
//                ))){
//                    $this->view->canEditTrans = true;
//                    $this->view->editTransType = $docs['item_type'];
//                }
            } else {
                $this->view->approve = false;
            }
        }
    }

    public function cekcostAction() {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->getRequest()->getParam("posts");

        //COST = ERF + PO

        $data = Zend_Json::decode($json);
        $budget = new Default_Models_Budget();

        $error = array();
        $i = 0;
        foreach ($data as $k => $v) {
            $totalRequest = floatval($v['qty']) * floatval($v['priceErf']);
            $cek = $budget->getBoq3ByOne($v['prj_kode'], $v['sit_kode'], $v['workid'], $v['kode_brg']);
            if ($cek) {
                if ($v['val_kode'] == 'IDR') {
                    $boq3Total = floatval($cek['qty']) * floatval($cek['totalHargaIDR']);
                } else {
                    $boq3Total = floatval($cek['qty']) * floatval($cek['totalHargaUSD']);
                }
                //jangan pernah pake float untuk compare value, gunakan bccomp
//                if ($prTotal > $total)
                if (bccomp($totalRequest, $boq3Total) > 0) {
                    $error[$i] = array(
                        "kode_brg" => $cek['kode_brg'],
                        "workid" => $cek['workid'],
                        "workname" => $cek['workname'],
                        "nama_brg" => $cek['nama_brg'],
                        "total" => $totalRequest,
                        "totalBOQ3" => $boq3Total,
                        "val_kode" => $cek['val_kode'],
                        "msg" => "Greater Than Current Budget (BOQ3)"
                    );
                }
                $pod = $this->quantity->getPoQuantity($v['prj_kode'], $v['sit_kode'], $v['workid'], $v['kode_brg']);
                $erf = $this->quantity->getErfQuantity($v['prj_kode'], $v['sit_kode'], $v['workid'], $v['kode_brg']);
                $esfcancel = $this->quantity->getEsfcancelQuantity($v['prj_kode'], $v['sit_kode'], $v['workid'], $v['kode_brg']);
                if ($pod != '') {
                    if ($v['val_kode'] == 'IDR')
                        $totalPOD = $pod['totalIDR'];
                    else
                        $totalPOD = $pod['totalHargaUSD'];
                }
                else {
                    $totalPOD = 0;
                }
                if ($erf != '') {
                    if ($v['val_kode'] == 'IDR')
                        $totalERF = $erf['realtotalIDR'];
                    else
                        $totalERF = $erf['realtotalHargaUSD'];
                }
                else {
                    $totalERF = 0;
                }
                if ($esfcancel != '') {
                    if ($v['val_kode'] == 'IDR')
                        $totalESF = $esfcancel['realtotalIDR'];
                    else
                        $totalESF = $esfcancel['realtotalHargaUSD'];
                }
                else {
                    $totalESF = 0;
                }

                $totalCost = ($totalPOD + $totalERF - $totalESF);
                $balance = $boq3Total - $totalCost;
                if (bccomp($totalRequest, $balance) > 0) {
                    if ($error[$i] != '') {
                        $error[$i]['totalCost'] = $totalCost;
                        $error[$i]['msg'] = "Greater than Allowed Cost";
                    } else {
                        $error[$i] = array(
                            "kode_brg" => $cek['kode_brg'],
                            "workid" => $cek['workid'],
                            "workname" => $cek['workname'],
                            "nama_brg" => $cek['nama_brg'],
                            "total" => $totalRequest,
                            "totalBOQ3" => $boq3Total,
                            "totalCost" => $totalCost,
                            "val_kode" => $cek['val_kode'],
                            "msg" => "Greater than Allowed Cost"
                        );
                    }
                }
                $i++;
            }
        }
        $success = true;
        if (count($error) > 0) {
            $success = false;
        }


        $json = Zend_Json::encode(array(
                    "success" => $success,
                    "error" => $error
        ));
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();


        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $workid = $request->getParam('workid');
        $kode_brg = $request->getParam('kode_brg');


        // call store procedure
        $sp = $this->db->prepare("call sp_boq3erf('$prj_kode','$sit_kode','$workid','$kode_brg')");
        $sp->execute();
        $return['posts'] = $sp->fetchAll();
        $return['count'] = count($return['posts']);

        //the posts

        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getUidName() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

}

