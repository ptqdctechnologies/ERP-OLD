<?php

class Procurement_EsfController extends Zend_Controller_Action {
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

        // ERF Models Section
        $this->erfd = new Procurement_Models_EntertainmentRequestD();
        $this->erfh = new Procurement_Models_EntertainmentRequestH();

        //---------------------
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
    }

    public function indexAction() {
        
    }

    public function esfAction() {
        $this->view->render('esf/esf.phtml');
    }

    public function addesfAction() {
        $this->getUidName();

        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ESFtrano = $trano;
        $this->view->mode = $this->getRequest()->getParam("mode");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function addesfbudgetAction() {
        $this->getUidName();

        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ESFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function addesfsalesAction() {
        $this->getUidName();

        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ESFtrano = $trano;
        $this->view->mode = $this->getRequest()->getParam("mode");
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->file = $this->getRequest()->getParam("file");
        }
    }

    public function appesfAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ESF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            $this->view->mode = $this->getRequest()->getParam("mode");

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
                    $asfh = $this->asfh->fetchRow("trano = '$approve'");
                    if ($asfdd) {
                        foreach ($asfdd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfdd[$key]['uom'] = $barang['sat_kode'];
                            }
                            $asfdd[$key]['price'] = $val['harga'];
                            $asfdd[$key]['erf_no'] = $val['arf_no'];
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
                            $asfddcancel[$key]['erf_no'] = $val['arf_no'];
                        }
                    }

                    $jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
                    $jsonData2[0]['ket'] = $asfh['ket'];
                    $jsonData2[0]['petugas'] = $asfh['petugas'];
                    $jsonData2[0]['requester'] = $asfh['request2'];
                    $jsonData2[0]['erf_no'] = $asfh['arf_no'];
                    $jsonData2[0]['tgl'] = $asfh['tgl'];
                    $jsonData2[0]['tgl_erf'] = $asfh['tglarf'];
                    $jsonData2[0]['pic'] = $asfh['orangpic'];
//                    $jsonData2[0]['finance'] = $asfh['orangfinance'];
                    $jsonData2[0]['trano'] = $approve;
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $asfdd;
                    $this->view->trano = $approve;
                    $this->view->result2 = $asfddcancel;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->mode = $this->getRequest()->getParam("mode");
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

    public function appesfbudgetAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $sales = $this->getRequest()->getParam("sales");
        $show = $this->getRequest()->getParam("show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/ESFO';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
            $etc = str_replace("\\", "", $etc);
            Zend_Loader::loadClass('Zend_Json');
            $jsonData = Zend_Json::decode($json);
            $jsonData2 = Zend_Json::decode($etc);
            $jsonData3 = Zend_Json::decode($json2);

            $this->view->result = $jsonData;
            $this->view->etc = $jsonData2;
            $this->view->result2 = $jsonData3;
            $this->view->jsonResult = $json;
            $this->view->jsonResult2 = $json2;
            $this->view->mode = $this->getRequest()->getParam("mode");
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
                    if ($potong == 'ESF01')
                        $this->view->sales = true;

                    $asfdd = $this->asf->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->asfc->fetchAll("trano = '$approve'")->toArray();
                    $asfh = $this->asfh->fetchRow("trano = '$approve'");
                    if ($asfdd) {
                        foreach ($asfdd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfdd[$key]['uom'] = $barang['sat_kode'];
                            }
                            $asfdd[$key]['price'] = $val['harga'];
                            $asfdd[$key]['erf_no'] = $val['arf_no'];
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
                            $asfddcancel[$key]['erf_no'] = $val['arf_no'];
                            
                        }
                    }
                    $jsonData2[0]['prj_kode'] = $asfh['prj_kode'];
                    $jsonData2[0]['prj_nama'] = $asfh['prj_nama'];
                    $jsonData2[0]['sit_kode'] = $asfh['sit_kode'];
                    $jsonData2[0]['sit_nama'] = $asfh['sit_nama'];
                    $jsonData2[0]['ket'] = $asfh['ket'];
                    $jsonData2[0]['petugas'] = $asfh['petugas'];
                    $jsonData2[0]['requester'] = $asfh['request2'];
                    $jsonData2[0]['erf_no'] = $asfh['arf_no'];
                    $jsonData2[0]['tgl'] = $asfh['tgl'];
                    $jsonData2[0]['tgl_erf'] = $asfh['tglarf'];
                    $jsonData2[0]['pic'] = $asfh['orangpic'];
                    $jsonData2[0]['trano'] = $approve;
                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getLastReject($approve);
                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    $this->view->result = $asfdd;
                    $this->view->trano = $approve;
                    $this->view->result2 = $asfddcancel;
                    $this->view->approve = true;
                    $this->view->uid = $this->session->userName;
                    $this->view->userID = $this->session->idUser;
                    $this->view->docsID = $id;
                    $this->view->mode = $this->getRequest()->getParam("mode");
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

    public function editesfAction() {
        $this->getUidName();

        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfh->fetchRow("trano = '$trano'");

        $sql = "SELECT a.* FROM procurement_erfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
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

                $erfh = $this->quantity->getErfhTotal($return[$key]['trano']);

                if ($erfh != '')
                    $inerfhtotal = $erfh['total'];
                else
                    $inerfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalESF'] = $asfqty;
                $return[$key]['totalPriceESF'] = $asftotal;
                $return[$key]['totalESFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceESFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInErfh'] = $inerfhtotal;
            }
        }
        else
            $asfddcancel = Array();

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

                $asfdd[$i]['tgl_erf'] = $asfdd[$i]['tglarf'];
                $asfdd[$i]['erf_no'] = $asfdd[$i]['arf_no'];
            }
        }
        else
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

                $asfddcancel[$i]['tgl_erf'] = $asfddcancel[$i]['tglarf'];
                $asfddcancel[$i]['erf_no'] = $asfddcancel[$i]['arf_no'];
            }
        }
        else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $erf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        }
        else
            $this->view->json = $jsonData;

        $this->view->json2 = $jsonData2;
        $this->view->erf = $erf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
//        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];
        $this->view->mode = $this->getRequest()->getParam("mode");
    }

    public function editesfbudgetAction() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfh->fetchRow("trano = '$trano'");

        $sql = "SELECT a.* FROM procurement_erfd a LEFT JOIN procurement_asfd b ON a.trano = b.erf_no WHERE b.trano = '$trano'";
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

                $erfh = $this->quantity->getErfhTotal($return[$key]['trano']);

                if ($erfh != '')
                    $inerfhtotal = $erfh['total'];
                else
                    $inerfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalESF'] = $asfqty;
                $return[$key]['totalPriceESF'] = $asftotal;
                $return[$key]['totalESFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceESFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInErfh'] = $inerfhtotal;
            }
        }
        else
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
        }
        else
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
        }
        else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $erf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        }
        else
            $this->view->json = $jsonData;
        $this->view->json2 = $jsonData2;
        $this->view->erf = $erf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
//        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];
    }

    public function editesfsalesAction() {

        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->asf->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->asfc->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->asfd->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->asfh->fetchRow("trano = '$trano'");

        $sql = "SELECT a.* FROM procurement_erfd a LEFT JOIN procurement_asfd b ON a.trano = b.arf_no WHERE b.trano = '$trano'";
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

                $erfh = $this->quantity->getErfhTotal($return[$key]['trano']);

                if ($erfh != '')
                    $inerfhtotal = $erfh['total'];
                else
                    $inerfhtotal = 0;


                $return[$key]['id'] = $key + 1;
                foreach ($return[$key] as $key2 => $val2) {
                    if ($val2 == "\"\"")
                        $return[$key][$key2] = '';
                }
                $return[$key]['price'] = $return[$key]['harga'];
                $return[$key]['totalPrice'] = $return[$key]['total'];
                unset($return[$key]['harga']);
                unset($return[$key]['total']);
                $return[$key]['totalESF'] = $asfqty;
                $return[$key]['totalPriceESF'] = $asftotal;
                $return[$key]['totalESFCancel'] = $asfcancelqty;
                $return[$key]['totalPriceESFCancel'] = $asfcanceltotal;
                $return[$key]['totalPriceInErfh'] = $inerfhtotal;
            }
        }
        else
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
                $asfdd[$i]['tgl_erf'] = $asfdd[$i]['tglarf'];
                $asfdd[$i]['erf_no'] = $asfdd[$i]['arf_no'];
            }
        }
        else
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
                $asfddcancel[$i]['tgl_erf'] = $asfddcancel[$i]['tglarf'];
                $asfddcancel[$i]['erf_no'] = $asfddcancel[$i]['arf_no'];
            }
        }
        else
            $asfddcancel = Array();

        Zend_Loader::loadClass('Zend_Json');
        $jsonData = Zend_Json::encode($asfdd);
        $jsonData2 = Zend_Json::encode($asfddcancel);
        $erf = Zend_Json::encode($return);
        $isCancel = $this->getRequest()->getParam("returnback");
        if ($isCancel) {
            $this->view->cancel = true;
            $this->view->json = $this->getRequest()->getParam("posts");
            $this->view->json2 = $this->getRequest()->getParam("posts2");
        }
        else
            $this->view->json = $jsonData;
        $this->view->json2 = $jsonData2;
        $this->view->erf = $erf;

        $this->view->trano = $trano;
        $this->view->tgl = $asfh['tgl'];

        $this->view->ket = trim($asfh['ket']);
        $this->view->requester = trim($asfh['petugas']);
        $this->view->requester2 = trim($asfh['request2']);
        $this->view->pic = trim($asfh['orangpic']);
//        $this->view->finance = trim($asfh['orangfinance']);
        $this->view->val_kode = $asfh['val_kode'];
        $this->view->rateidr = $asfh['rateidr'];
        $this->view->mode = $this->getRequest()->getParam("mode");
    }

    public function insertesfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $counter = new Default_Models_MasterCounter();

        $lastTrans = $counter->getLastTrans('ESF');
        $last = abs($lastTrans['urut']);
        $last = $last + 1;
        $trano = 'ESF01-' . $last;

        $items = $jsonEtc[0];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $result = $this->workflow->setWorkflowTrans($trano, 'ESF', '', $this->const['DOCUMENT_SUBMIT'], $items);
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

        $ada = false;
        while (!$ada) {
            $cek = $this->asf->fetchRow("trano = '$trano'");
            if ($cek) {
                $lastTrans = $counter->getLastTrans('ESF');
                $last = abs($lastTrans['urut']);
                $last = $last + 1;
                $trano = 'ESF01-' . $last;
            } elseif (!$cek)
                $ada = true;
        }

        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceErf = 0;

        $temp = array();
        if ($jsonData) {
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
            $balance = $val['totalPriceInErfh'] - $val['total'];
            $totalPriceErf = $totalPriceErf + $val['totalPriceErf'];

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
                "requestv" => $val['totalPriceInErfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
            );
            $this->asfd->insert($arrayD);
        }

        if ($jsonData)
            $erfno = $jsonData[0]['erf_no'];
        else
            $erfno = $jsonData2[0]['erf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $erfno,
            "tglarf" => $jsonEtc[0]['tgl_erf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalerfh'],
            "orangpic" => $jsonEtc[0]['pic'],
//            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceErf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
                //"cus_kode" => $cusKode,
        );
        $this->asfh->insert($arrayInsert);
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function insertesfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
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
            $lastTrans = $counter->getLastTrans('ESF');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ESF01-' . $last;
            $tipe = 'S';
            $itemType = 'ESFS';
        } else {
            $lastTrans = $counter->getLastTrans('ESFO');
            $last = abs($lastTrans['urut']);
            $last = $last + 1;
            $trano = 'ESF02-' . $last;
            $tipe = 'O';
            $itemType = 'ESFO';
        }

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
        $where = "id=" . $lastTrans['id'];
        $counter->update(array("urut" => $last), $where);
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));

        $totalPriceErf = 0;

        $temp = array();
        if ($jsonData) {
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
            $balance = $val['totalPriceInErfh'] - $val['total'];
            $totalPriceErf = $totalPriceErf + $val['totalPriceErf'];

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
                "requestv" => $val['totalPriceInErfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfd->insert($arrayD);
        }
//         $this->asfd->insert($arrayD);

        if ($jsonData)
            $erfno = $jsonData[0]['erf_no'];
        else
            $erfno = $jsonData2[0]['erf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $erfno,
            "tglarf" => $jsonEtc[0]['tgl_erf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalerfh'],
            "orangpic" => $jsonEtc[0]['pic'],
//            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceErf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe
                //"cus_kode" => $cusKode,
        );
        $this->asfh->insert($arrayInsert);
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    
    
    
    public function updateesfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $totalPriceErf = 0;
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


        $result = $this->workflow->setWorkflowTrans($trano, 'ESF', '', $this->const['DOCUMENT_RESUBMIT'], $items);
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

                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
        $log['asfd-detail-before'] = $this->asfd->fetchAll("trano = '$trano'")->toArray();
        $this->asfd->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInErfh'] - $val['total'];
            $totalPriceErf = $totalPriceErf + $val['totalPriceErf'];

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
                "requestv" => $val['totalPriceInErfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
            );
            $this->asfd->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->asfd->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $erfno = $jsonData[0]['erf_no'];
        else
            $erfno = $jsonData2[0]['erf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $erfno,
            "tglarf" => $jsonEtc[0]['tgl_erf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalerfh'],
            "orangpic" => $jsonEtc[0]['pic'],
//            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceErf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
                //"cus_kode" => $cusKode,
        );
        $log['asf-header-before'] = $this->asfh->fetchRow("trano = '$trano'");
        $this->asfh->delete("trano = '$trano'");
        $this->asfh->insert($arrayInsert);
        $log2['asf-header-after'] = $this->asfh->fetchRow("trano = '$trano'");
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
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateesfbudgetAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $totalPriceErf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc[0]['tgl']));
        if ($sales) {
            $tipe = 'S';
            $itemType = 'ESFS';
        } else {
            $tipe = 'O';
            $itemType = 'ESFO';
        }

        $trano = $jsonEtc[0]['trano'];

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
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
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
        $log['asfd-detail-before'] = $this->asfd->fetchAll("trano = '$trano'")->toArray();
        $this->asfd->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInErfh'] - $val['total'];
            $totalPriceErf = $totalPriceErf + $val['totalPriceErf'];

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
                "requestv" => $val['totalPriceInErfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfd->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->asfd->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['erf_no'];
        else
            $arfno = $jsonData2[0]['erf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $erfno,
            "tglarf" => $jsonEtc[0]['tgl_erf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalerfh'],
            "orangpic" => $jsonEtc[0]['pic'],
//            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceErf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe

                //"cus_kode" => $cusKode,
        );
        $log['asf-header-before'] = $this->asfh->fetchRow("trano = '$trano'")->toArray();
        $this->asfh->delete("trano = '$trano'");
        $this->asfh->insert($arrayInsert);
        $log2['asf-header-after'] = $this->asfh->fetchRow("trano = '$trano'")->toArray();
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
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }

    public function updateesfsalesAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $sales = $this->getRequest()->getParam('sales');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);

        $totalPriceErf = 0;
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


        $result = $this->workflow->setWorkflowTrans($trano, 'ESFS', '', $this->const['DOCUMENT_RESUBMIT'], $items);
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
            $this->asf->delete("trano = '$trano'");
            foreach ($jsonData as $key => $val) {

                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
        }

        if ($jsonData2) {
            $this->asfc->delete("trano = '$trano'");
            foreach ($jsonData2 as $key => $val) {
                $tranotemp = $val['erf_no'];

                if ($temp[$tranotemp] == '') {
                    $temp[$tranotemp]['total'] = $val['totalPrice'];
                    $temp[$tranotemp]['trano'] = $tranotemp;
                    $temp[$tranotemp]['tgl'] = $val['tgl_erf'];
                    $temp[$tranotemp]['totalPriceInErfh'] = $val['totalPriceInErfh'];
                    $temp[$tranotemp]['totalPriceErf'] = $val['totalPriceErf'];
                }
                else
                    $temp[$tranotemp]['total'] += $val['totalPrice'];

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date('Y-m-d'),
                    "arf_no" => $val['erf_no'],
                    "tglarf" => $val['tgl_erf'],
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
        }

        $this->asfd->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInErfh'] - $val['total'];
            $totalPriceErf = $totalPriceErf + $val['totalPriceErf'];

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
                "requestv" => $val['totalPriceInErfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc[0]['val_kode'],
                "rateidr" => $jsonEtc[0]['rateidr'],
                "tipe" => $tipe
            );
            $this->asfd->insert($arrayD);
        }
//         $this->asfd->insert($arrayD);

        if ($jsonData)
            $arfno = $jsonData[0]['erf_no'];
        else
            $arfno = $jsonData2[0]['erf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $erfno,
            "tglarf" => $jsonEtc[0]['tgl_erf'],
            "prj_kode" => $jsonEtc[0]['prj_kode'],
            "prj_nama" => $jsonEtc[0]['prj_nama'],
            "sit_kode" => $jsonEtc[0]['sit_kode'],
            "sit_nama" => $jsonEtc[0]['sit_nama'],
            "ket" => $jsonEtc[0]['ket'],
            "petugas" => $jsonEtc[0]['petugas'],
            "total" => $jsonEtc[0]['totalerfh'],
            "orangpic" => $jsonEtc[0]['pic'],
            "orangfinance" => $jsonEtc[0]['finance'],
            "requestv" => $totalPriceErf,
            "user" => $this->session->userName,
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc[0]['val_kode'],
            "rateidr" => $jsonEtc[0]['rateidr'],
            "request2" => $jsonEtc[0]['requester'],
            "tipe" => $tipe

                //"cus_kode" => $cusKode,
        );
        $this->asfh->delete("trano = '$trano'");
        $this->asfh->insert($arrayInsert);
        $this->getResponse()->setBody("{success: true, number : '$trano'}");
    }
    
    public function updatepersonAction(){
         $this->_helper->viewRenderer->setNoRender();
         Zend_Loader::loadClass('Zend_Json');
         
         $trano = $this->getRequest()->getParam('trano');
         $person = $this->getRequest()->getParam('person');
         
        $arrayInsert = array(
            "person_accom" => $person
        );
        $this->erfh->update($arrayInsert, "trano = '$trano'");
        
        $this->getResponse()->setBody("{success: true}");
    }

    public function getUidName() {
        $this->view->uid = $this->session->userName;
        $this->view->nama = $this->session->name;
    }

}

