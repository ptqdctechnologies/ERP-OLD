<?php

class Procurement_BtSettlementController extends Zend_Controller_Action {

    private $DEFAULT;
    private $ADMIN;
    private $PROC;
    private $const;
    private $quantity;
    private $workflow;
    private $db;
    private $uid_session;
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
        $this->const = Zend_Registry::get('constant');
        $this->uid_session = QDC_User_Session::factory()->getCurrentUID();
        $this->id_user_session = QDC_User_Session::factory()->getCurrentID();
        $this->files = new Default_Models_Files();
    }

    public function indexAction() {
        
    }

    public function addAction() {
        $this->view->uid = $this->uid_session;
        $this->view->nama = $this->session->name;
        $isCancel = $this->getRequest()->getParam("returnback");
        $trano = $this->getRequest()->getParam("trano");
        $this->view->ARFtrano = $trano;
        if ($isCancel) {
            $this->view->json = $this->getRequest()->getParam("posts");
        }
    }

    public function appAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;
        $lastReject=array();

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/BSF';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");
        if ($approve == '') {
            $json = $this->getRequest()->getParam("posts");
            $etc = $this->getRequest()->getParam("etc");
            $json2 = $this->getRequest()->getParam("posts2");
//            $files = $this->getRequest()->getParam("file");

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
        } elseif ($approve != '' || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$tranoShow'", array("date DESC"), 1, 0);
            } else
                $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");
            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], $this->id_user_session);

                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $approve = $docs['item_id'];
                    $userApp = $this->workflow->getAllApprovalGeneric($approve);
                    $jsonData2[0]['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];
                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;
                    $asfdd = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$approve'")->toArray();
                    $asfddcancel = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$approve'")->toArray();
                    $asfh = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano = '$approve'");

                    if ($asfdd) {
                        foreach ($asfdd as $key => $val) {
                            $kodeBrg = $val['kode_brg'];
                            $barang = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfdd[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfdd[$key]['price'] = $val['harga'];
                            $asfdd[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];

                            $asfD = $this->DEFAULT->AdvanceSettlementFormD->fetchAll("arf_no = '$arf'")->toArray();

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
                            $barang = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
                            if ($barang) {
                                $asfddcancel[$key]['uom'] = $barang['sat_kode'];
                            }

                            $asfddcancel[$key]['price'] = $val['harga'];
                            $asfddcancel[$key]['totalPrice'] = $val['total'];

                            $arf = $val['arf_no'];

                            $asfD = $this->DEFAULT->AdvanceSettlementFormD->fetchAll("arf_no = '$arf'")->toArray();
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


                    $jsonData2[0]['trano'] = $approve;
//                    $allReject = $this->workflow->getAllReject($approve);
//                    $lastReject = $this->workflow->getLastReject($approve);
                    $lastReject[0]['name'] = QDC_User_Ldap::factory(array("uid" => $docs['uid']))->getName();
                    $lastReject[0]['date'] = $docs['date'];
                    $lastReject[0]['comment']= $docs['comment'];

                    Zend_Loader::loadClass('Zend_Json');
                    $jsonRev = Zend_Json::encode($asfdd);
                    $jsonRev2 = Zend_Json::encode($asfddcancel);


                    $filedata = $this->files->fetchAll("trano = '$approve'");
                    $this->view->lastReject = $lastReject;
//                    $this->view->allReject = $allReject;
                    $this->view->etc = $jsonData2;
                    unset($jsonData2[0]['user_approval']);
                    $this->view->revetc = $jsonData2;

                    $this->view->result = $asfdd;
                    $this->view->trano = $approve;
                    $this->view->result2 = $asfddcancel;
                    $this->view->approve = true;
                    $this->view->uid = $this->uid_session;
                    $this->view->userID = $this->id_user_session;
                    $this->view->docsID = $id;
                    $this->view->file = $filedata;
                    $this->view->jsonRev = $jsonRev;
                    $this->view->jsonRev2 = $jsonRev2;
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

    public function insertBsfAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $json = $this->getRequest()->getParam('posts');
        $jsonfile = Zend_Json::decode($this->getRequest()->getParam("file"));
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $comment = $this->_getParam("comment");

      $activitylog2 = new Admin_Models_Activitylog();
        
        $items = $jsonEtc;
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

        $items["prj_kode"] = $jsonEtc['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;
        
        $params = array(
            "workflowType" => "BSF",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_SUBMIT'],
            "items" => $items,
            "prjKode" => $items["prj_kode"],
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "useOverride" => $useOverride
        );
        $trano = $this->workflow->setWorkflowTransNew($params);


        $urut = 1;
        $urut2 = 1;
        $activityCount=0;
        $activityHead=array();
        $activityDetail=array();
        $activityFile=array();

        $tgl = date('Y-m-d', strtotime($jsonEtc['tgl']));

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
//				"petugas" => $this->uid_session,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut++;
                $this->DEFAULT->AdvanceSettlementForm->insert($arrayInsert);
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
//				"petugas" => $this->uid_session,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut2++;
                $this->DEFAULT->AdvanceSettlementFormCancel->insert($arrayInsert);
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
                "prj_kode" => $jsonEtc['prj_kode'],
                "prj_nama" => $jsonEtc['prj_nama'],
                "sit_kode" => $jsonEtc['sit_kode'],
                "sit_nama" => $jsonEtc['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc['val_kode'],
                "rateidr" => $jsonEtc['rateidr'],
            );
            $this->DEFAULT->AdvanceSettlementFormD->insert($arrayD);
            //detail
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
            "tglarf" => $jsonEtc['tgl_arf'],
            "prj_kode" => $jsonEtc['prj_kode'],
            "prj_nama" => $jsonEtc['prj_nama'],
            "sit_kode" => $jsonEtc['sit_kode'],
            "sit_nama" => $jsonEtc['sit_nama'],
            "ket" => $jsonEtc['ket'],
            "petugas" => $jsonEtc['petugas'],
            "total" => $jsonEtc['totalarfh'],
            "orangpic" => $jsonEtc['pic'],
            "orangfinance" => $jsonEtc['finance'],
            "requestv" => $totalPriceArf,
            "user" => QDC_User_Session::factory()->getCurrentUID(),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc['val_kode'],
            "rateidr" => $jsonEtc['rateidr'],
            "request2" => $jsonEtc['requester'],
                //"cus_kode" => $cusKode,
        );
        $this->DEFAULT->AdvanceSettlementFormH->insert($arrayInsert);
         $activityHead['procurement_asfh'][0]=$arrayInsert;
        
        $activityCount=0;

        if (count($jsonfile) > 0) {
            foreach ($jsonfile as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => $jsonData2[0]['prj_kode'],
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
            "menu_name" => "Create BSF",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc['prj_kode'],
            "sit_kode" => $jsonEtc['sit_kode'],
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

    public function editAction() {
        $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
        $this->view->nama = QDC_User_Ldap::factory()->getName();
        $trano = $this->getRequest()->getParam("trano");
        $asfdd = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfddcancel = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfd = $this->DEFAULT->AdvanceSettlementFormD->fetchAll("trano = '$trano'", array("urut ASC"))->toArray();
        $asfh = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano = '$trano'")->toArray();

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
                $barang = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
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
                $barang = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
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
                $barang = $this->DEFAULT->MasterBarang->fetchRow("kode_brg = '$kodeBrg'");
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

    public function updateAction() {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Zend_Json');
        $etc = $this->getRequest()->getParam('etc');
        $json2 = $this->getRequest()->getParam('posts2');
        $json = $this->getRequest()->getParam('posts');
        $comment = $this->_getParam("comment");
        $etc = str_replace("\\", "", $etc);
        $jsonData = Zend_Json::decode($json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $file = $this->getRequest()->getParam('file');
        $jsonFile = Zend_Json::decode($file);

        $totalPriceArf = 0;
        $urut = 1;
        $urut2 = 1;

        $tgl = date('Y-m-d', strtotime($jsonEtc['tgl']));

        $trano = $jsonEtc['trano'];

        $items = $jsonEtc;

        $items["prj_kode"] = $jsonEtc['prj_kode'];
        $items['next'] = $this->getRequest()->getParam('next');
        $items['uid_next'] = $this->getRequest()->getParam('uid_next');
        $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
        $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
        $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');
        $useOverride = ($this->_getParam("useOverride") == 'true') ? true : false;
        
        $params = array(
            "workflowType" => "BSF",
            "paramArray" => '',
            "approve" => $this->const['DOCUMENT_RESUBMIT'],
            "itemID" => $trano,
            "items" => $items,
            "prjKode" => $items['prj_kode'],
            "generic" => true,
            "revisi" => false,
            "returnException" => false,
            "comment" => $comment,
            "useOverride" => $useOverride
        );
        $this->workflow->setWorkflowTransNew($params);

        $temp = array();
        if ($jsonData) {
            $log['asfdd-detail-before'] = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$trano'")->toArray();
            $this->DEFAULT->AdvanceSettlementForm->delete("trano = '$trano'");
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
//				"petugas" => $this->uid_session,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut++;

                $this->DEFAULT->AdvanceSettlementForm->insert($arrayInsert);
            }
            $log2['asfdd-detail-after'] = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$trano'")->toArray();
        }

        if ($jsonData2) {
            $log['asfddcancel-detail-before'] = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$trano'")->toArray();
            $this->DEFAULT->AdvanceSettlementFormCancel->delete("trano = '$trano'");
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
//				"petugas" => $this->uid_session,
                    "petugas" => $val['petugas'],
                    "val_kode" => $val['val_kode'],
                    "rateidr" => $val['rateidr'],
                    "cfs_kode" => $val['cfs_kode']
                );
                $urut2++;

                $this->DEFAULT->AdvanceSettlementFormCancel->insert($arrayInsert);
            }
            $log2['asfddcancel-detail-after'] = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$trano'")->toArray();
        }
        $log['asfd-detail-before'] = $this->DEFAULT->AdvanceSettlementFormD->fetchAll("trano = '$trano'")->toArray();
        $this->DEFAULT->AdvanceSettlementFormD->delete("trano = '$trano'");
        foreach ($temp as $key => $val) {
            $balance = $val['totalPriceInArfh'] - $val['total'];
            $totalPriceArf = $totalPriceArf + $val['totalPriceArf'];

            $arrayD = array(
                "trano" => $trano,
                "tgl" => date('Y-m-d'),
                "arf_no" => $key,
                "tglarf" => $val['tgl'],
                "prj_kode" => $jsonEtc['prj_kode'],
                "prj_nama" => $jsonEtc['prj_nama'],
                "sit_kode" => $jsonEtc['sit_kode'],
                "sit_nama" => $jsonEtc['sit_nama'],
                "ket" => $jsonData[0]['ket'],
                "total" => $balance,
                "petugas" => $jsonEtc['petugas'],
                "requestv" => $val['totalPriceInArfh'],
                "totalasf" => $val['total'],
                "val_kode" => $jsonEtc['val_kode'],
                "rateidr" => $jsonEtc['rateidr'],
            );
            $this->DEFAULT->AdvanceSettlementFormD->insert($arrayD);
        }
        $log2['asfd-detail-after'] = $this->DEFAULT->AdvanceSettlementFormD->fetchAll("trano = '$trano'")->toArray();

        if ($jsonData)
            $arfno = $jsonData[0]['arf_no'];
        else
            $arfno = $jsonData2[0]['arf_no'];

        $arrayInsert = array(
            "trano" => $trano,
            "tgl" => date('Y-m-d'),
            "arf_no" => $arfno,
            "tglarf" => $jsonEtc['tgl_arf'],
            "prj_kode" => $jsonEtc['prj_kode'],
            "prj_nama" => $jsonEtc['prj_nama'],
            "sit_kode" => $jsonEtc['sit_kode'],
            "sit_nama" => $jsonEtc['sit_nama'],
            "ket" => $jsonEtc['ket'],
            "petugas" => $jsonEtc['petugas'],
            "total" => $jsonEtc['totalarfh'],
            "orangpic" => $jsonEtc['pic'],
            "orangfinance" => $jsonEtc['finance'],
            "requestv" => $totalPriceArf,
            "user" => QDC_User_Session::factory()->getCurrentUID(),
            "tglinput" => date('Y-m-d'),
            "jam" => date('H:i:s'),
            "val_kode" => $jsonEtc['val_kode'],
            "rateidr" => $jsonEtc['rateidr'],
            "request2" => $jsonEtc['requester'],
                //"cus_kode" => $cusKode,
        );
        $log['asf-header-before'] = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano = '$trano'")->toArray();
        $this->DEFAULT->AdvanceSettlementFormH->delete("trano = '$trano'");
        $this->DEFAULT->AdvanceSettlementFormH->insert($arrayInsert);
        $log2['asf-header-after'] = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano = '$trano'")->toArray();
        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);
        $arrayLog = array(
            "trano" => $trano,
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $jsonEtc['prj_kode'],
            "sit_kode" => $jsonEtc['sit_kode'],
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $logs = new Admin_Models_Logtransaction();
        $logs->insert($arrayLog);
        
        
        $this->files->delete("trano = '$trano'");
        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => $data['prj_kode'],
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

    public function getDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $type = $this->_getParam("item_type");

        switch($type)
        {
             case 'BRF':
                $select = $this->db->select()
                    ->from(array($this->PROC->BusinessTripHeader->__name()),array(
                        "brf_trano" => "trano",
                        "brf_tgl"=>"tgl"
                    ))
                    ->where("trano= ?",$trano);

                $select = $this->db->select()
                    ->from(array("a" => $select))
                    ->joinLeft(array("b" => $this->PROC->BusinessTripPayment->__name()),"a.brf_trano = b.trano_ref",array(
//                        "brfp_trano" => new Zend_Db_Expr("COALESCE(b.trano,'')"),
                        "brfp_trano" => "trano",
                        "brfp_tgl" => "tgl"
                    ));
                $fetch = $this->db->fetchAll($select);
                foreach($fetch as $k => $v)
                {
                }

               
                if ($fetch[$k]['brfp_trano'] !=null)
                {
                     $select = $this->db->select()
                    ->from(array("c" => $select))
                    ->joinLeft(array("d" => $this->DEFAULT->AdvanceSettlementFormH->__name()),"c.brfp_trano = d.arf_no",array(
//                        "bsf_trano" => new Zend_Db_Expr("COALESCE(d.trano,'')"),
                        "bsf_trano" => "trano",
                        "bsf_tgl" => "tgl"
                    ));
                } else {
                     $select = $this->db->select()
                    ->from(array("c" => $select))
                    ->joinLeft(array("d" => $this->DEFAULT->AdvanceSettlementFormH->__name()),"c.brf_trano = d.arf_no",array(
//                        "bsf_trano" => new Zend_Db_Expr("COALESCE(d.trano,'')"),
                        "bsf_trano" => "trano",
                        "bsf_tgl" => "tgl"
                    ));
                }
               
                break;

            case 'BSF':
                $select = $this->db->select()
                    ->from(array($this->DEFAULT->AdvanceSettlementFormH->__name()),array(
                        "bsf_trano" => "trano",
                        "brfp_trano" => "arf_no",
                        "bsf_tgl"=>"tgl"
                    ))
                    ->where("trano= ?",$trano);

                $select = $this->db->select()
                    ->from(array("a" => $select))
                    ->joinLeft(array("b" => $this->PROC->BusinessTripPayment->__name()),"a.brfp_trano = b.trano",array(
                        "brf_trano" => "trano_ref"
                    ));

                break;

            case 'BRFP':


                break;
        }
        $data = $this->db->fetchAll($select);

        foreach($data as $k => $v)
        {
            $data[$k]['brfp_trano'] = ($data[$k]['brfp_trano']) ? $data[$k]['brfp_trano'] : '';
            $data[$k]['bsf_trano'] = ($data[$k]['bsf_trano']) ? $data[$k]['bsf_trano'] : '';
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(
            Zend_Json::encode(array(
                "success" => true,
                "data" => $data
            ))
        );
    }

    public function bsfToBrfAction()
    {

    }

}

?>
