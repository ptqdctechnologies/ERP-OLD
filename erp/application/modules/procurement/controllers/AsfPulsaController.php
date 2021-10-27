<?php

class Procurement_AsfPulsaController extends Zend_Controller_Action
{
    private $DEFAULT;
    private $ADMIN;
    private $PROC;
    private $const;
    private $quantity;
    private $workflow;
    private $db;

    public function init()
    {
        $this->ADMIN = QDC_Model_Admin::init(array(
            "KodePulsa",
            "Workflowtrans"
        ));
        $this->DEFAULT = QDC_Model_Default::init(array(
            "MasterSite",
            "Budget",
            "MasterProject",
            "MasterWork",
            "MasterBarang",
            "Files",
            "MasterUser",
            "AdvanceSettlementForm",
            "AdvanceSettlementFormD",
            "AdvanceSettlementFormCancel",
            "AdvanceSettlementFormH",
        ));
        $this->PROC = QDC_Model_Procurement::init(array(
            "Procurementarfh",
            "Procurementarfd"
        ));

        $this->db = Zend_Registry::get("db");
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');
    }

    public function addAction()
    {

    }

    public function cekArfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $kode_brg = $this->_getParam("kode_brg");
        $workid = $this->_getParam("workid");
        $prj_kode = $this->_getParam("prj_kode");
        $sit_kode = $this->_getParam("sit_kode");
        $qtyAsf = $this->_getParam("qty_asf");
        $hargaAsf = $this->_getParam("harga_asf");
        $totalAsf = $this->_getParam("total_asf");
        $qtyAsfcancel = $this->_getParam("qty_asfcancel");
        $hargaAsfcancel = $this->_getParam("harga_asfcancel");
        $totalAsfcancel = $this->_getParam("total_asfcancel");
        $isEdit = ($this->_getParam("isEdit") == '' || $this->_getParam("isEdit") == false) ? false : true;
        if ($isEdit)
        {
            $asfNo = $this->_getParam("asf_no");
            $exclude = "'" . $asfNo . "'";
        }

        $totalASF = floatval($totalAsf) + floatval($totalAsfcancel);

        $arfd = $this->db->fetchRow(
            $this->db->select()
                ->from(array($this->PROC->Procurementarfd->__name()),array(
                "id",
                "trano",
                "trano_ref",
                "prj_kode",
                "prj_nama",
                "sit_kode",
                "sit_nama",
                "workid",
                "workname",
                "kode_brg",
                "nama_brg",
                "qty",
                "harga",
                "total" => "(qty*harga)",
                "val_kode",
                "ket"
            ))
                ->where("trano = '$trano'")
                ->where("prj_kode = '$prj_kode'")
                ->where("sit_kode = '$sit_kode'")
                ->where("workid = '$workid'")
                ->where("kode_brg = '$kode_brg'")
        );
        if ($arfd)
        {
            $qty = 0;$qtyc = 0;$balance = 0;$totalc = 0;$total = 0;
            $asf = $this->quantity->getArfAsfQuantity($trano,$prj_kode,$sit_kode,$workid,$kode_brg,$exclude);
            if ($asf)
            {
                $qty = $asf['qty'];
                $total = $asf['totalHargaIDR'];
            }
            $asfc = $this->quantity->getArfAsfcancelQuantity($trano,$prj_kode,$sit_kode,$workid,$kode_brg,$exclude);
            if ($asfc)
            {
                $qtyc = $asfc['qty'];
                $totalc = $asfc['totalHargaIDR'];
            }

            $totalARF = ($arfd['qty'] * $arfd['harga']);
            $balance = $totalARF - ($total + $totalc);

            if(bccomp($totalASF,$totalARF,2) == 1)
            {
                $array['success'] = false;
                $array['msg'] = "Total ASF (Expense Claim + Due to Company) is greater than ARF itself, Total ASF : " . number_format($totalASF,2) . ", Total ARF : " . number_format($totalARF,2);
            }
            elseif(bccomp($totalASF,$balance,2) == 1)
            {
                $array['success'] = false;
                $array['msg'] = "Total ASF (Expense Claim + Due to Company) is greater than Balance, Total ASF : " . number_format($totalASF,2) . ", Balance : " . number_format($balance,2);
            }
            else
            {
                $array['success'] = true;
            }
        }

        $json = Zend_Json::encode($array);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function submitAsfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $activitylog2 = new Admin_Models_Activitylog();
        $jsonData = Zend_Json::decode($this->json);
        $jsonData2 = Zend_Json::decode($json2);
        $jsonEtc = Zend_Json::decode($etc);
        $jsonFile = Zend_Json::decode($file);
        
        $json = $this->_getParam("json");
        $file = $this->_getParam("file");
        if (!$json)
            return false;

        $useOverride = ($this->_getParam("useOverride") != '') ? true : false;

        $data =Zend_Json::decode($json);
        if ($file)
            $file =Zend_Json::decode($file);

        $arrayError = array(); $arrayPrj = array();
        foreach($data as $k => $v)
        {
            $items = $v;
            $items["prj_kode"] = $v['prj_kode'];
            $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $prj = $v['prj_kode'];
            $site = $v['sit_kode'];
            $arrayPrj[$prj][$site] = '';
            $arrayTrano[$prj] = '';
            $params = array(
                "workflowType" => "ASFP",
                "paramArray" => '',
                "approve" => $this->const['DOCUMENT_SUBMIT'],
                "items" => $items,
                "prjKode" => $v['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => true
            );
            $cek = $this->workflow->checkWorkflowTrans($params);
            if ($cek !== true)
            {
                $arrayError[] = "Workflow Error for Project <b>" . $v['prj_kode'] . "</b> :<br>" . $cek;
            }
        }

        if (count($arrayError) > 0)
        {
            $result = array(
                "success" => false,
                "msgArray" => $arrayError
            );
        }
        else
        {
            $lastTrano = '';
            $urut = 1;
            //activity log
            $activityCount=0;
            $activityHead=array();
            $activityDetail=array();
            $activityFile=array();
        
            $captionID = $this->ADMIN->Workflowtrans->getCaptionID("ASF Pulsa " . date ("d M Y"));
            foreach($data as $k => $v)
            {
                $items = $v;
                $items["prj_kode"] = $v['prj_kode'];
                $items['next'] = $this->getRequest()->getParam('next');
                $items['uid_next'] = $this->getRequest()->getParam('uid_next');
                $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
                $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
                $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
                $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

                $prj = $v['prj_kode'];
                $site = $v['sit_kode'];
                $params = array(
                    "workflowType" => "ASFP",
                    "paramArray" => '',
                    "approve" => $this->const['DOCUMENT_SUBMIT'],
                    "items" => $items,
                    "prjKode" => $v['prj_kode'],
                    "generic" => true,
                    "revisi" => false,
                    "returnException" => true,
                    "captionId" => $captionID,
                    "useOverride" => $useOverride,
                    "uidApproval" => QDC_User_Session::factory()->getCurrentUID()
                );

                if ($arrayTrano[$prj] != '')
                {
                    $params['lastTrano'] = $arrayTrano[$prj];
                }

                $trano = $this->workflow->setWorkflowTransNew($params);
                if ($arrayTrano[$prj] == '')
                {
                    $arrayTrano[$prj] = $trano;
                }

                //Insert ARF Pulsa to database

                $cekArf = $this->PROC->Procurementarfd->fetchRow("trano = '{$v['arf_no']}'");
                $tglArf = '';
                if ($cekArf)
                    $tglArf = $cekArf['tgl'];

                if ($arrayPrj[$prj]['site'][$site] == '')
                {
                    $counter = new Default_Models_MasterCounter();
                    $asfTrano = $counter->setNewTrans('ASF'); //Trano baru untuk ASF

                    $arrayPrj[$prj]['site'][$site] = array(
                        "trano" => $asfTrano,
                        "trano_ref" => $arrayTrano[$prj],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "petugas" => $v['manager'],
                        "total" => ($v['qty'] * $v['harga']),
                        "total_cancel" => ($v['qtycancel'] * $v['hargacancel']),
                        "total_arf" => $v['total_arf'],
                        "orangpic" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    if ($arrayPrj[$prj]['header'] == '')
                    {
                        $arrayPrj[$prj]['header'] = array(
                            "trano" => $asfTrano,
                            "trano_ref" => $arrayTrano[$prj],
                            "arf_no" => $v['arf_no'],
                            "tgl" => date("Y-m-d"),
                            "tglarf" => $tglArf,
                            "ket" => $this->_getParam('ket'),
                            "prj_kode" => $v['prj_kode'],
                            "prj_nama" => $v['prj_nama'],
                            "sit_kode" => $v['sit_kode'],
                            "sit_nama" => $v['sit_nama'],
                            "petugas" => $this->_getParam('manager'),
                            "finance" => $this->_getParam('finance'),
                            "requester" => $this->_getParam('requester'),
                            "orangpic" => QDC_User_Session::factory()->getCurrentUID(),
                            "val_kode" => $v['val_kode'],
                            "arf_trano_ref" => $v['arf_trano_ref'],
                            "arf_caption_id" => $v['arf_caption_id'],
                            "caption_id" => $captionID,
                        );
                    }
                }
                else
                {
                    $arrayPrj[$prj]['site'][$site]['total'] += ($v['qty'] * $v['harga']);
                    $arrayPrj[$prj]['site'][$site]['totalcancel'] += ($v['qtycancel'] * $v['hargacancel']);
                }

                if ($v['qty'] > 0 || $v['harga'] > 0 )
                {
                    $arrayInsert = array(
                        "trano" => $arrayPrj[$prj]['site'][$site]['trano'],
                        "trano_ref" => $arrayTrano[$prj],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => $v['qty'],
                        "harga" => $v['harga'],
                        "total" => ($v['qty'] * $v['harga']),
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "urut" => $urut,
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    $this->DEFAULT->AdvanceSettlementForm->insert($arrayInsert);
                }

                if ($v['qtycancel'] > 0 || $v['hargacancel'] > 0 )
                {
                    $arrayInsert = array(
                        "trano" => $arrayPrj[$prj]['site'][$site]['trano'],
                        "trano_ref" => $arrayTrano[$prj],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => $v['qtycancel'],
                        "harga" => $v['hargacancel'],
                        "total" => ($v['qtycancel'] * $v['hargacancel']),
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "urut" => $urut,
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    $this->DEFAULT->AdvanceSettlementFormCancel->insert($arrayInsert);
                }

            }

            foreach($arrayPrj as $k => $v)
            {
                $grandTotalASF = 0;$arfNo = '';
                if (is_array($v['site']))
                {
                    foreach($v['site'] as $k2 => $v2)
                    {
                        $arfNo = $v2['arf_no'];
                        $totalARF = 0;
                        $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '{$v2['arf_no']}'");
                        if ($cekArf)
                            $totalARF = $cekArf['total'];
                        $totalASF = $v2['total'] + $v2['total_cancel'];
                        $balance = $totalARF - $totalASF;

                        $arrayInsert = array(
                            "trano" => $v2['trano'],
                            "trano_ref" => $v2['trano_ref'],
                            "tgl" => $v2['tgl'],
                            "arf_no" => $v2['arf_no'],
                            "tglarf" => $v2['tglarf'],
                            "prj_kode" => $v2['prj_kode'],
                            "prj_nama" => $v2['prj_nama'],
                            "sit_kode" => $v2['sit_kode'],
                            "sit_nama" => $v2['sit_nama'],
                            "total" => $balance,
                            "petugas" => $v2['petugas'],
                            "requestv" => $totalARF,
                            "totalasf" => $totalASF,
                            "val_kode" => $v2['val_kode'],
                            "rateidr" => $v2['rateidr'],
                            "arf_trano_ref" => $v2['arf_trano_ref'],
                            "arf_caption_id" => $v2['arf_caption_id'],
                            "caption_id" => $captionID,
                        );

                        $this->DEFAULT->AdvanceSettlementFormD->insert($arrayInsert);
                          // detail
                        $activityDetail['procurement_asfd'][$activityCount]=$arrayInsert;
                        $urut++;
                        $activityCount++;
                    }
                }
                
                if ($v['header'])
                {
                    $header = $v['header'];
                    $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '{$header['arf_no']}'");
                    if ($cekArf)
                        $totalARF = $cekArf['total'];
                    $arrayInsert = array (
                        "trano" => $header['trano'],
                        "trano_ref" => $header['trano_ref'],
                        "tgl" => $header['tgl'],
                        "arf_no" =>  $header['arf_no'],
                        "tglarf" => $header['tglarf'],
                        "prj_kode" => $header['prj_kode'],
                        "prj_nama" => $header['prj_nama'],
                        "sit_kode" => $header['sit_kode'],
                        "sit_nama" => $header['sit_nama'],
                        "ket" => $header['ket'],
                        "petugas" => $header['petugas'],
                        "total" => $totalARF,
                        "orangpic" => $header['petugas'],
                        "orangfinance" => $header['finance'],
                        "requestv" => $totalARF,
                        "user" => QDC_User_Session::factory()->getCurrentUID(),
                        "tglinput" => date('Y-m-d'),
                        "jam" => date('H:i:s'),
                        "val_kode" => $header['val_kode'],
                        "rateidr" => $header['rateidr'],
                        "request2" => $header['requester'],
                        "arf_trano_ref" => $header['arf_trano_ref'],
                        "arf_caption_id" => $header['arf_caption_id'],
                        "caption_id" => $captionID,
                    );
                    $this->DEFAULT->AdvanceSettlementFormH->insert($arrayInsert);
                    //header
                    $activityHead['procurement_asfh'][0]=$arrayInsert;
                }

            }
            
            $activityCount=0;
            if ($file)
            {
                foreach ($file as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $captionID,
                        "prj_kode" => '',
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->DEFAULT->Files->insert($arrayInsert);
                    $activityFile['file'][$activityCount]=$arrayInsert;
                $urut++;
                $activityCount++;
                }
            }
        
        $activityLog = array(
            "menu_name" => "Create ASF Pulsa",
            "trano" => $trano,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => $header['prj_kode'],
            "sit_kode" => $header['sit_kode'],
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "header" => Zend_Json::encode($activityHead),
            "detail" => Zend_Json::encode($activityDetail),
            "file" => Zend_Json::encode($activityFile),
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        
            $activitylog2->insert($activityLog);
        
            $result = array(
                "success" => true,
                "number" => $captionID
            );
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function appAsfPulsaAction()
    {
        $workflowID = $this->_getParam("approve");
        $approve = $this->_getParam("approve");
        $show = $this->_getParam("show");
        $params = $this->_getParam("params");
        $params2 = strval($this->_getParam("params"));
        if ($params)
        {
            $params = Zend_Json::decode($params);
        }

        if ($show)
        {
            $cek = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id = $workflowID");
            if ($cek)
            {
                $captionID = $cek['caption_id'];
                $this->view->caption_id = $captionID;
                $select = $this->db->select()
                    ->from(array($this->ADMIN->Workflowtrans->__name()))
                    ->where("caption_id = '$captionID'")
                    ->group(array("item_id"));

                $data = $this->db->fetchAll($select);
                if ($data)
                {
                    foreach($data as $k => $v)
                    {
                        $trano = $v['item_id'];
                        $pulsa = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));

                        if($pulsa)
                        {
                            $pulsa = $pulsa->toArray();
                            $prj_kode = $pulsa['prj_kode'];
                            $sit_kode = $pulsa['sit_kode'];
                            $dataPulsaDetail[$prj_kode]['header'] = $pulsa;
                            $dataPulsaDetail[$prj_kode]['item_id'] = $trano;
                        }

                        $pulsaDetail = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                        if($pulsaDetail)
                            $pulsaDetail = $pulsaDetail->toArray();
                        foreach($pulsaDetail as $k2 => $v2)
                        {
                            $dataPulsaDetail[$prj_kode]['detail'][] = $v2;
                        }

                        $pulsaDetailCancel = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                        if($pulsaDetailCancel)
                            $pulsaDetailCancel = $pulsaDetailCancel->toArray();
                        foreach($pulsaDetailCancel as $k2 => $v2)
                        {
                            $dataPulsaDetail[$prj_kode]['detailcancel'][] = $v2;
                        }
                    }
                }
                $cekFile = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                if ($cekFile)
                    $dataFile = $cekFile->toArray();

                $this->view->show = $show;
            }
        }
        else
        {
            if ($workflowID != '')
            {
                if (!$params)
                    $params[] = $workflowID;

                $allTrans = array();

                foreach($params as $k => $v)
                {
                    $cek = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id = $v");
                    if ($cek)
                    {
                        $docs = $cek->toArray();
                        $captionID = $docs['caption_id'];
                        $this->view->caption_id = $captionID;
                        $trano= $docs['item_id'];
                        $allTrans[] = array(
                            "trano" => $trano,
                            "trans_id" => $v
                        );
                        $statApprove = $docs['approve'];
                        $prjKode = $docs['prj_kode'];
                        if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        {
                            $this->view->reject = true;
                            $lastReject = $this->workflow->getLastRejectGeneric($trano);
                            $this->view->lastReject = $lastReject;
                        }

                        //Gather all ARF Pulsa
                        $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'],QDC_User_Session::factory()->getCurrentID());
                        if ($user)
                        {
                            $pulsa = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));

                            if($pulsa)
                            {
                                $pulsa = $pulsa->toArray();
                                $prj_kode = $pulsa['prj_kode'];
                                $sit_kode = $pulsa['sit_kode'];
                                $dataPulsaDetail[$prj_kode]['header'] = $pulsa;
                                $dataPulsaDetail[$prj_kode]['item_id'] = $trano;
                                $dataPulsaDetail[$prj_kode]['workflow_trans_id'] = $v;
                                $dataPulsaDetail[$prj_kode]['uid_next'] = $docs['uid_next'];
                            }

                            $pulsaDetail = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                            if($pulsaDetail)
                                $pulsaDetail = $pulsaDetail->toArray();
                            foreach($pulsaDetail as $k2 => $v2)
                            {
                                $dataPulsaDetail[$prj_kode]['detail'][] = $v2;
                            }

                            $pulsaDetailCancel = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano_ref = '$trano'",array("prj_kode ASC", "sit_kode ASC"));
                            if($pulsaDetailCancel)
                                $pulsaDetailCancel = $pulsaDetailCancel->toArray();
                            foreach($pulsaDetailCancel as $k2 => $v2)
                            {
                                $dataPulsaDetail[$prj_kode]['detailcancel'][] = $v2;
                            }

                        }
                    }
                }

                $cekFile = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                if ($cekFile)
                    $dataFile = $cekFile->toArray();
            }
        }


        if ($allTrans)
            $this->view->allTrans = Zend_Json::encode($allTrans);
        $this->view->dataPulsa = $dataPulsa;
        $this->view->dataPulsaDetail = $dataPulsaDetail;
        $this->view->jsonFile = Zend_Json::encode($dataFile);
        $this->view->dataFile = $dataFile;
        $this->view->approve = $approve;
        $this->view->params = Zend_Json::encode($params2);
        $this->view->userID = QDC_User_Session::factory()->getCurrentID();
        $this->view->uidNext = QDC_User_Session::factory()->getCurrentUID();
    }

    public function editAction()
    {
        $this->view->json = $this->_getParam("json");
        if ($this->_getParam("json"))
        {
            $file = array();
            $json = Zend_Json::decode($this->_getParam("json"));
            foreach($json as $key => $val)
            {
                $trano = $val['trano'];
                $wt = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$trano'")->toArray();
                $captionID = $wt['caption_id'];
                $this->view->captionID = $captionID;
                $file = $this->DEFAULT->Files->fetchAll("trano = '$captionID'");
                $file = $file->toArray();
                break;
            }
            $asf = $this->DEFAULT->AdvanceSettlementFormH->fetchRow("caption_id = '$captionID'");
            if ($asf)
            {
                $asf = $asf->toArray();
                $this->view->arfCaptionID = $asf['arf_caption_id'];
                $this->view->captionID = $captionID;
                $this->view->manager = $asf['petugas'];
                $this->view->managerName = QDC_User_Ldap::factory(array("uid" => $asf['petugas']))->getName();
                $this->view->requester = $asf['request2'];
                $this->view->requesterName = QDC_User_Ldap::factory(array("uid" => $asf['request2']))->getName();
                $this->view->finance = $asf['orangfinance'];
                $this->view->financeName = QDC_User_Ldap::factory(array("uid" => $asf['finance']))->getName();
                $this->view->ket = $asf['ket'];
            }
            $this->view->file = Zend_Json::encode(array('data' => $file, 'count' => count($file)));
        }
    }

    public function editGetDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        $captionID = $this->_getParam("caption_id");
        if (!$json || !$captionID)
            return false;

        $json = Zend_Json::decode($json);
        $data = array();$i = 0;

        foreach($json as $k => $v)
        {
            $asf = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano_ref = '{$v['trano']}' AND caption_id = '$captionID'");
            if ($asf)
            {
                $asf = $asf->toArray();
                foreach($asf as $k2 => $v2)
                {
                    $arfTranoRef = $v2['arf_trano_ref'];
                    $prjKode = $v2['prj_kode'];
                    $sitKode = $v2['sit_kode'];
                    $workid = $v2['workid'];
                    $kodeBrg = $v2['kode_brg'];
                    $arfNo = $v2['arf_no'];
                    $trano = $v2['trano'];
                    $tranoRef = $v2['trano_ref'];
                    $asfc = $this->DEFAULT->AdvanceSettlementFormCancel->fetchRow("trano_ref = '{$v['trano']}' AND caption_id = '$captionID' AND trano = '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND workid = '$workid' AND kode_brg = '$kodeBrg' AND arf_no = '$arfNo'");
                    if ($asfc)
                        $asfc = $asfc->toArray();
                    else
                    {
                        $asfc['qty'] = 0;
                        $asfc['harga'] = 0;
                        $asfc['total'] = 0;
                    }

                    $arf = $this->PROC->Procurementarfd->fetchRow("trano_ref = '$arfTranoRef' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND workid = '$workid' AND kode_brg = '$kodeBrg' AND trano = '$arfNo'");
                    if ($arf)
                    {
                        $arf = $arf->toArray();
                        $totalARF = ($arf['qty'] * $arf['harga']);
                    }
                    else
                    {
                        $arfd = $this->DEFAULT->AdvanceSettlementForm->fetchRow("trano = '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano_ref = '$tranoRef' AND arf_no = '$arfNo'");
                        $arfd = $arfd->toArray();

                        $totalARF = $arfd['requestv'];
                    }

                    $totalASF = ($v2['qty'] * $v2['harga']) + ($asfc['qty'] * $asfc['harga']);

                    $select = $this->db->select()
                        ->from(array($this->DEFAULT->AdvanceSettlementForm->__name()),array(
                            "total_asf" => ("SUM(qty*harga)")
                        ))
                        ->where("trano_ref != '{$v['trano']}' AND caption_id != '$captionID' AND trano != '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND workid = '$workid' AND kode_brg = '$kodeBrg' AND arf_no = '$arfNo'");
                    $otherAsf = $this->db->fetchRow($select);

                    //Cari total nilai ASF lain selain yang diedit...
                    $totalASFOther = 0;
                    if ($otherAsf)
                    {
                        $totalASFOther = $otherAsf['total_asf'];
                    }
                    $select = $this->db->select()
                        ->from(array($this->DEFAULT->AdvanceSettlementFormCancel->__name()),array(
                        "total_asf" => ("SUM(qty*harga)")
                    ))
                        ->where("trano_ref != '{$v['trano']}' AND caption_id != '$captionID' AND trano != '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND workid = '$workid' AND kode_brg = '$kodeBrg' AND arf_no = '$arfNo'");
                    $otherAsfc = $this->db->fetchRow($select);
                    if ($otherAsfc)
                    {
                        $totalASFOther += $otherAsfc['total_asf'];
                    }

                    $data[] = array(
                        "trano" => $v2['trano'],
                        "trano_ref" => $v['trano'],
                        "arf_trano_ref" => $v2['arf_trano_ref'],
                        "arf_caption_id" => $v2['arf_caption_id'],
                        "caption_id" => $captionID,
                        "arf_no" => $v2['arf_no'],
                        "prj_kode" => $v2['prj_kode'],
                        "prj_nama" => $v2['prj_nama'],
                        "sit_kode" => $v2['sit_kode'],
                        "sit_nama" => $v2['sit_nama'],
                        "workid" => $v2['workid'],
                        "workname" => $v2['workname'],
                        "kode_brg" => $v2['kode_brg'],
                        "nama_brg" => $v2['nama_brg'],
                        "qty" => $v2['qty'],
                        "harga" => $v2['harga'],
                        "total" => ($v2['qty'] * $v2['harga']),
                        "qtycancel" => $asfc['qty'],
                        "hargacancel" => $asfc['harga'],
                        "totalcancel" => ($asfc['qty'] * $asfc['harga']),
                        "val_kode" => $v2['val_kode'],
                        "grand_total" => $totalASF,
                        "total_arf" => $totalARF,
                        "total_in_asf" => $totalASFOther,
                        "qty_arf" => $arf['qty'],
                        "harga_arf" => $arf['harga'],
                        "ket" => $v2['ket'],
                    );
                }
            }
            else
            {
                $asfc = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano_ref = '{$v['trano']}' AND caption_id = '$captionID'");
                foreach($asf as $k2 => $v2)
                {
                    $arfTranoRef = $v2['arf_trano_ref'];
                    $prjKode = $v2['prj_kode'];
                    $sitKode = $v2['sit_kode'];
                    $workid = $v2['workid'];
                    $kodeBrg = $v2['kode_brg'];
                    $arfNo = $v2['arf_no'];
                    $trano = $v2['trano'];
                    $tranoRef = $v2['trano_ref'];

                    $totalASF = ($v2['qty'] * $v2['harga']);

                    $arf = $this->PROC->ProcurementArfd->fetchRow("trano_ref = '$arfTranoRef' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND workid = '$workid' AND kode_brg = '$kodeBrg' AND trano = '$arfNo'");
                    if ($arf)
                    {
                        $arf = $arf->toArray();
                        $totalARF = ($arf['qty'] * $arf['harga']);
                    }
                    else
                    {
                        $arfd = $this->DEFAULT->AdvanceSettlementForm->fetchRow("trano = '$trano' AND prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND trano_ref = '$tranoRef' AND arf_no = '$arfNo'");
                        $arfd = $arfd->toArray();

                        $totalARF = $arfd['requestv'];
                    }

                    $data[] = array(
                        "trano" => $v2['trano'],
                        "arf_no" => $v2['arf_no'],
                        "prj_kode" => $v2['prj_kode'],
                        "prj_nama" => $v2['prj_nama'],
                        "sit_kode" => $v2['sit_kode'],
                        "sit_nama" => $v2['sit_nama'],
                        "workid" => $v2['workid'],
                        "workname" => $v2['workname'],
                        "kode_brg" => $v2['kode_brg'],
                        "nama_brg" => $v2['nama_brg'],
                        "qtycancel" => $v2['qty'],
                        "hargacancel" => $v2['harga'],
                        "totalcancel" => ($v2['qty'] * $v2['harga']),
                        "qty" => 0,
                        "harga" => 0,
                        "total" => 0,
                        "val_kode" => $v2['val_kode'],
                        "grand_total" => $totalASF,
                        "total_arf" => $totalARF,
                        "ket" => $v2['ket'],
                    );
                }
            }
        }

        $result = array(
            "success" => true,
            "posts" => $data
        );
        $result = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($result);
    }

    public function updateAsfPulsaAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $json = $this->_getParam("json");
        $file = $this->_getParam("file");
        if (!$json)
            return false;

        $useOverride = ($this->_getParam("useOverride") != '') ? true : false;

        $data =Zend_Json::decode($json);
        if ($file)
            $file =Zend_Json::decode($file);

        $arrayError = array(); $arrayPrj = array();
        foreach($data as $k => $v)
        {
            $items = $v;
            $itemID = $v['trano_ref'];
            $tranoAsli = $v['trano'];
            $items["prj_kode"] = $v['prj_kode'];
            $items['next'] = $this->getRequest()->getParam('next');
            $items['uid_next'] = $this->getRequest()->getParam('uid_next');
            $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
            $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
            $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
            $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

            $prj = $v['prj_kode'];
            $site = $v['sit_kode'];
            $arrayPrj[$prj][$site] = '';
            $arrayTrano[$prj] = '';
            $params = array(
                "paramArray" => '',
                "useOverride" => $useOverride,
                "uidApproval" => QDC_User_Session::factory()->getCurrentUID(),
                "skipClassCheck" => true,
                "workflowType" => "ASFP",
                "approve" => $this->const['DOCUMENT_RESUBMIT'],
                "items" => $items,
                "prjKode" => $v['prj_kode'],
                "generic" => true,
                "revisi" => false,
                "returnException" => true,
                "itemID" => $itemID
            );
            $cek = $this->workflow->checkWorkflowTrans($params);
            if ($cek !== true)
            {
                $arrayError[] = "Workflow Error for Project <b>" . $v['prj_kode'] . "</b> :<br>" . $cek;
            }
        }

        if (count($arrayError) > 0)
        {
            $result = array(
                "success" => false,
                "msgArray" => $arrayError
            );
        }
        else
        {
            $lastTrano = '';
            $urut = 1;
            $log['asfpulsa-header-before'] = array();
            $log['asfpulsa-detail-before'] = array();
            $log2['asfpulsa-header-after'] = array();
            $log2['asfpulsa-detail-after'] = array();
            $captionID = $this->_getParam("captionID");
            foreach($data as $k => $v)
            {
                $items = $v;
                $itemID = $v['trano_ref'];
                $tranoAsli = $v['trano'];
                $items["prj_kode"] = $v['prj_kode'];
                $items['next'] = $this->getRequest()->getParam('next');
                $items['uid_next'] = $this->getRequest()->getParam('uid_next');
                $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
                $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
                $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
                $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

                $prj = $v['prj_kode'];
                $site = $v['sit_kode'];
                $params = array(
                    "paramArray" => '',
                    "useOverride" => $useOverride,
                    "uidApproval" => QDC_User_Session::factory()->getCurrentUID(),
                    "skipClassCheck" => true,
                    "workflowType" => "ASFP",
                    "approve" => $this->const['DOCUMENT_RESUBMIT'],
                    "items" => $items,
                    "prjKode" => $v['prj_kode'],
                    "generic" => true,
                    "revisi" => false,
                    "returnException" => true,
                    "itemID" => $itemID,
                    "captionId" => $v['caption_id'],
                );

                if ($arrayTrano[$prj] == '')
                {
                    $this->workflow->setWorkflowTransNew($params);
                }
                $arrayTrano[$prj] = $itemID;

                //Insert ASF Pulsa to database

                $cekArf = $this->PROC->Procurementarfd->fetchRow("trano = '{$v['arf_no']}'");
                $tglArf = '';
                if ($cekArf)
                    $tglArf = $cekArf['tgl'];

                if ($arrayPrj[$prj]['site'][$site] == '')
                {
                    $asfTrano = $v['trano'];

                    $arrayPrj[$prj]['site'][$site] = array(
                        "trano" => $asfTrano,
                        "trano_ref" => $arrayTrano[$prj],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "petugas" => $v['manager'],
                        "total" => ($v['qty'] * $v['harga']),
                        "total_cancel" => ($v['qtycancel'] * $v['hargacancel']),
                        "total_arf" => $v['total_arf'],
                        "orangpic" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    if ($arrayPrj[$prj]['header'] == '')
                    {
                        $arrayPrj[$prj]['header'] = array(
                            "trano" => $asfTrano,
                            "trano_ref" => $v['trano_ref'],
                            "arf_no" => $v['arf_no'],
                            "tgl" => date("Y-m-d"),
                            "tglarf" => $tglArf,
                            "ket" => $this->_getParam('ket'),
                            "prj_kode" => $v['prj_kode'],
                            "prj_nama" => $v['prj_nama'],
                            "sit_kode" => $v['sit_kode'],
                            "sit_nama" => $v['sit_nama'],
                            "petugas" => $this->_getParam('manager'),
                            "finance" => $this->_getParam('finance'),
                            "requester" => $this->_getParam('requester'),
                            "orangpic" => QDC_User_Session::factory()->getCurrentUID(),
                            "val_kode" => $v['val_kode'],
                            "arf_trano_ref" => $v['arf_trano_ref'],
                            "arf_caption_id" => $v['arf_caption_id'],
                            "caption_id" => $captionID,
                        );
                    }
                }
                else
                {
                    $arrayPrj[$prj]['site'][$site]['total'] += ($v['qty'] * $v['harga']);
                    $arrayPrj[$prj]['site'][$site]['totalcancel'] += ($v['qtycancel'] * $v['hargacancel']);
                }

                if ($v['qty'] > 0 || $v['harga'] > 0 )
                {
                    $arrayInsert = array(
                        "trano" => $arrayPrj[$prj]['site'][$site]['trano'],
                        "trano_ref" => $v['trano_ref'],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => $v['qty'],
                        "harga" => $v['harga'],
                        "total" => ($v['qty'] * $v['harga']),
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "urut" => $urut,
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    $cekAsfdd = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                    if ($cekAsfdd)
                        $cekAsfdd = $cekAsfdd->toArray();
                    array_push($log['asfpulsa-detail-before'],$cekAsfdd);

                    $this->DEFAULT->AdvanceSettlementForm->delete("trano = '$tranoAsli' AND trano_ref = '$itemID'");

                    $this->DEFAULT->AdvanceSettlementForm->insert($arrayInsert);

                    $cekAsfdd = $this->DEFAULT->AdvanceSettlementForm->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                    if ($cekAsfdd)
                        $cekAsfdd = $cekAsfdd->toArray();
                    array_push($log2['asfpulsa-detail-after'],$cekAsfdd);
                }

                if ($v['qtycancel'] > 0 || $v['hargacancel'] > 0 )
                {
                    $arrayInsert = array(
                        "trano" => $arrayPrj[$prj]['site'][$site]['trano'],
                        "trano_ref" => $v['trano_ref'],
                        "arf_no" => $v['arf_no'],
                        "tgl" => date("Y-m-d"),
                        "tglarf" => $tglArf,
                        "prj_kode" => $v['prj_kode'],
                        "prj_nama" => $v['prj_nama'],
                        "sit_kode" => $v['sit_kode'],
                        "sit_nama" => $v['sit_nama'],
                        "workid" => $v['workid'],
                        "workname" => $v['workname'],
                        "kode_brg" => $v['kode_brg'],
                        "nama_brg" => $v['nama_brg'],
                        "qty" => $v['qtycancel'],
                        "harga" => $v['hargacancel'],
                        "total" => ($v['qtycancel'] * $v['hargacancel']),
                        "petugas" => QDC_User_Session::factory()->getCurrentUID(),
                        "val_kode" => $v['val_kode'],
                        "urut" => $urut,
                        "arf_trano_ref" => $v['arf_trano_ref'],
                        "arf_caption_id" => $v['arf_caption_id'],
                        "caption_id" => $captionID,
                    );

                    $cekAsfddc = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                    if ($cekAsfddc)
                        $cekAsfddc = $cekAsfddc->toArray();
                    array_push($log['asfpulsa-detail-before'],$cekAsfddc);

                    $this->DEFAULT->AdvanceSettlementFormCancel->delete("trano = '$tranoAsli' AND trano_ref = '$itemID'");

                    $this->DEFAULT->AdvanceSettlementFormCancel->insert($arrayInsert);

                    $cekAsfddc = $this->DEFAULT->AdvanceSettlementFormCancel->fetchAll("trano = '$tranoAsli' AND trano_ref = '$itemID'");
                    if ($cekAsfddc)
                        $cekAsfddc = $cekAsfddc->toArray();
                    array_push($log2['asfpulsa-detail-after'],$cekAsfddc);
                }

            }

            foreach($arrayPrj as $k => $v)
            {
                $grandTotalASF = 0;$arfNo = '';
                if (is_array($v['site']))
                {
                    foreach($v['site'] as $k2 => $v2)
                    {
                        $arfNo = $v2['arf_no'];
                        $totalARF = 0;
                        $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '{$v2['arf_no']}'");
                        if ($cekArf)
                            $totalARF = $cekArf['total'];
                        $totalASF = $v2['total'] + $v2['total_cancel'];
                        $balance = $totalARF - $totalASF;

                        $arrayInsert = array(
                            "trano" => $v2['trano'],
                            "trano_ref" => $v2['trano_ref'],
                            "tgl" => $v2['tgl'],
                            "arf_no" => $v2['arf_no'],
                            "tglarf" => $v2['tglarf'],
                            "prj_kode" => $v2['prj_kode'],
                            "prj_nama" => $v2['prj_nama'],
                            "sit_kode" => $v2['sit_kode'],
                            "sit_nama" => $v2['sit_nama'],
                            "total" => $balance,
                            "petugas" => $v2['petugas'],
                            "requestv" => $totalARF,
                            "totalasf" => $totalASF,
                            "val_kode" => $v2['val_kode'],
                            "rateidr" => $v2['rateidr'],
                            "arf_trano_ref" => $v2['arf_trano_ref'],
                            "arf_caption_id" => $v2['arf_caption_id'],
                            "caption_id" => $captionID,
                        );

                        $this->DEFAULT->AdvanceSettlementFormD->delete("trano = '{$v2['trano']}' AND trano_ref = '{$v2['trano_ref']}'");

                        $this->DEFAULT->AdvanceSettlementFormD->insert($arrayInsert);
                    }
                }
                if ($v['header'])
                {
                    $header = $v['header'];
                    $cekArf = $this->PROC->Procurementarfh->fetchRow("trano = '{$header['arf_no']}'");
                    if ($cekArf)
                        $totalARF = $cekArf['total'];
                    $arrayInsert = array (
                        "ket" => $header['ket'],
                        "petugas" => $header['petugas'],
                        "total" => $totalARF,
                        "orangpic" => $header['petugas'],
                        "orangfinance" => $header['finance'],
                        "requestv" => $totalARF,
                        "user" => QDC_User_Session::factory()->getCurrentUID(),
                        "tglinput" => date('Y-m-d'),
                        "jam" => date('H:i:s'),
                        "val_kode" => $header['val_kode'],
                        "rateidr" => $header['rateidr'],
                        "request2" => $header['requester']
                    );

                    $cekAsfh = $this->DEFAULT->AdvanceSettlementFormH->fetchAll("trano = '{$header['trano']}' AND trano_ref = '{$header['trano_ref']}'");
                    if ($cekAsfh)
                        $cekAsfh = $cekAsfh->toArray();
                    array_push($log2['asfpulsa-header-after'],$cekAsfh);
                    $this->DEFAULT->AdvanceSettlementFormH->update($arrayInsert,"trano = '{$header['trano']}' AND trano_ref = '{$header['trano_ref']}' AND prj_kode = '{$header['prj_kode']}' AND sit_kode = '{$header['sit_kode']}'");
                }

            }

            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                "trano" => $captionID,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => '',
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);

            if ($file)
            {
                foreach ($file as $key => $val)
                {
                    $arrayInsert = array (
                        "trano" => $captionID,
                        "prj_kode" => '',
                        "date" => date("Y-m-d H:i:s"),
                        "uid" => QDC_User_Session::factory()->getCurrentUID(),
                        "filename" => $val['filename'],
                        "savename" => $val['savename']
                    );
                    $this->DEFAULT->Files->insert($arrayInsert);
                }
            }

            $result = array(
                "success" => true,
                "number" => $captionID
            );
        }

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
}
?>