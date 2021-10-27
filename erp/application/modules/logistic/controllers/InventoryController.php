<?php

class Logistic_InventoryController extends Zend_Controller_Action {

    private $db;
    private $periodeOpen;
    private $LOGISTIC, $ADMIN, $DEFAULT, $FINANCE;
    private $const, $workflow;
    private $saldo_coa;
    private $coa;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->periodeOpen = QDC_Finance_Periode::factory(array("inventoryNotClose" => true))->getCurrentPeriode();
        $models = array(
            "LogisticInputSupplierH",
            "LogisticInputSupplier",
            "InventoryAdjustment",
            "MasterGudang"
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);

        $this->ADMIN = QDC_Model_Admin::init(array(
                    "Workflowtrans"
        ));

        $this->FINANCE = QDC_Model_Finance::init(array(
                    "AccountingSaldoStock",
                    "AdjustingJournal"
        ));
        $this->DEFAULT = QDC_Model_Default::init(array(
                    "Files",
                    "MasterBarang"
        ));

        $this->workflow = $this->_helper->getHelper('workflow');
        $this->const = Zend_Registry::get('constant');

        $this->saldo_coa = new Finance_Models_AccountingSaldoCoa();
        $this->coa = new Finance_Models_MasterCoa();
    }

    public function closingAction() {
        if ($this->periodeOpen) {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
            $this->view->tgl_awal = date("d M Y", strtotime($this->periodeOpen['tgl_awal']));
            $this->view->tgl_akhir = date("d M Y", strtotime($this->periodeOpen['tgl_akhir']));
        }
    }

    public function closingmenuAction() {
        
    }

    public function getitemsforclosingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");
        $tahun = $this->getRequest()->getParam("tahun");
        $bulan = $this->getRequest()->getParam("bulan");
        $search = $this->getRequest()->getParam("search");
        $type = $this->getRequest()->getParam("type");

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);

        $bulan = $periode['bulan'];
        $tahun = $periode['tahun'];

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $a = new Finance_Models_AccountingSaldoStock();


        $data = QDC_Inventory_Periode::factory()->getCurrentItems(array(
            "perKode" => $this->periodeOpen['perkode'],
            "offset" => $offset,
            "limit" => $limit
        ));

        $prevData = QDC_Inventory_Periode::factory()->getPrevItems();
        if (!$prevData) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['newItem'] = true;
                $data['data'][$k]['saldoQty'] = 0;
            }
        } else {
            foreach ($data['data'] as $k => $v) {
                $kodeBrg = $v['kode_brg'];
                if ($prevData[$kodeBrg]) {
                    $data['data'][$k]['newItem'] = false;
                    $data['data'][$k]['saldoQty'] = $prevData[$kodeBrg]['qty'];
                } else {
                    $data['data'][$k]['newItem'] = true;
                    $data['data'][$k]['saldoQty'] = 0;
                }
            }
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doClosingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $gdg_kode = $this->getRequest()->getParam("gdg_kode");
        $perkode = $this->getRequest()->getParam("perkode");
//        $coa_debit = $this->getRequest()->getParam("coa_debit");
//        $coa_credit = $this->getRequest()->getParam("coa_credit");

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);

        $bulan = $periode['bulan'];
        $tahun = $periode['tahun'];

        $a = new Finance_Models_AccountingSaldoStock();
        $b = new Default_Models_MasterBarang();

        //cek adjustment

        $data_saldo = $a->fetchAll("periode = '$bulan' "
                        . "and tahun ='$tahun'"
                        . "and stsadjustment = 1 ")->toArray();

        if ($data_saldo) {
            echo "{success: false}";
            return;
        }

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];


        //delete saldo stock if already exists
        $data_saldo = $a->fetchAll("periode = '$bulan' "
                        . "and tahun ='$tahun'"
                        . "and stsadjustment = 0 ")->toArray();

        if ($data_saldo) {
            foreach ($data_saldo as $key => $val) {
                $a->delete("id = '{$val['id']}'");
            }
        }

        $tgl = date("Y-m-d H:i:s");
        $token = QDC_User_Token::factory()->getRandomToken();
        $uid = QDC_User_Session::factory()->getCurrentUID();

        $data = QDC_Inventory_Periode::factory()->getCurrentItems(array(
            "perKode" => $perkode
        ));


        foreach ($data['data'] as $k => $v) {
            if ($v['qty'] < 0)
                $is_inv_stock = 'Y';
            else
                $is_inv_stock = 'N';

            $arrayInsert = array(
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "qty" => $v['qty'],
                "hargaavg" => $v['hargaavg'],
                "total_harga" => floatval($v['hargaavg'] * $v['qty']),
                "periode" => $bulan,
                "tahun" => $tahun,
                "stsslowmoving" => $v['stsslowmoving'],
                "val_kode" => $v['val_kode'],
                "gdg_kode" => $v['gdg_kode'],
                "rateidr" => $currentRateidr,
                "tgl" => $tgl,
                "uid" => $uid,
                "signature" => $token
            );
            //Insert ke saldo stock..
            $a->insert($arrayInsert);

            $b->update(array("is_invalid_stock" => $is_inv_stock), "kode_brg = '{$v['kode_brg']}'");
        }


        //update hargaavg
        $data_saldo = $a->fetchAll("periode = '$bulan' and tahun ='$tahun' and (hargaavg is not null || hargaavg != 0)")->toArray();
        foreach ($data_saldo as $k => $v) {
            $a->update(array(
                "hargaavg" => $v['hargaavg'],
                "val_kode" => $v['val_kode']
                    ), "kode_brg = '{$v['kode_brg']}' and periode = '$bulan' and tahun ='$tahun'");
        }
        //update total harga
        $data_saldo = $a->fetchAll("periode = '$bulan' and tahun ='$tahun'")->toArray();
        foreach ($data_saldo as $k => $v) {
            $totalharga = floatval($v['hargaavg'] * $v['qty']);
            $a->update(array("total_harga" => $totalharga), "id = '{$v['id']}' and periode = '$bulan' and tahun ='$tahun'");
        }


        /* ---------------- insert to saldo coa ------------------------------- */
        //get last data from saldo stock
        $data = $a->fetchAll("periode = '$bulan' and tahun ='$tahun'")->toArray();

//        foreach ($data as $key => $val) {
//            $debit = $coa_debit;
//            $credit = $coa_credit;
//
//            if ($val['val_kode'] != 'IDR')
//                $totalharga = floatval($val['rateidr'] * $val['total_harga']);
//            else
//                $totalharga = $val['total_harga'];
//
//            //untuk debit
//            $totalSebelumnya = 0;
//            $saldo_coa_debit = $this->saldo_coa->fetchRow("coa_kode = '$debit' AND periode = '$bulan' AND tahun = '$tahun' ");
//            if ($saldo_coa_debit) {
//                $totalSebelumnya = $saldo_coa_debit['totaldebit'];
//                $totalBaru = $totalharga + $totalSebelumnya;
//                $arrayUpdate = array(
//                    "totaldebit" => $totalBaru,
//                    "total" => $totalBaru
//                );
//
//                $this->saldo_coa->update($arrayUpdate, "coa_kode = '$debit' AND periode = '$bulan' AND tahun = '$tahun' ");
//            } else {
//                $coa = $this->coa->fetchRow("coa_kode = '$debit'");
//
//                $arrayinsertdebit = array(
//                    "coa_kode" => $debit,
//                    "coa_nama" => $coa['coa_nama'],
//                    "totaldebit" => $totalharga,
//                    "totalkredit" => 0,
//                    "val_kode" => $val['val_kode'],
//                    "periode" => $bulan,
//                    "tahun" => $tahun,
//                    "total" => $totalharga
//                );
//
//                $this->saldo_coa->insert($arrayinsertdebit);
//            }
//
//            //untuk credit
//            $saldo_coa_credit = $this->saldo_coa->fetchRow("coa_kode = '$credit' AND periode = '$bulan' AND tahun = '$tahun' ");
//            if ($saldo_coa_credit) {
//                $totalSebelumnya = $saldo_coa_credit['totalkredit'];
//
//                $totalBaru = $totalharga + $totalSebelumnya;
//                $arrayUpdate = array(
//                    "totalkredit" => $totalBaru,
//                    "total" => $totalBaru
//                );
//
//                $this->saldo_coa->update($arrayUpdate, "coa_kode = '$credit' AND periode = '$bulan' AND tahun = '$tahun' ");
//            } else {
//                $coa = $this->coa->fetchRow("coa_kode = '$credit'");
//
//                $arrayinsertcredit = array(
//                    "coa_kode" => $credit,
//                    "coa_nama" => $coa['coa_nama'],
//                    "totaldebit" => 0,
//                    "totalkredit" => $totalharga,
//                    "val_kode" => $val['val_kode'],
//                    "periode" => $bulan,
//                    "tahun" => $tahun,
//                    "total" => $totalharga
//                );
//
//                $this->saldo_coa->insert($arrayinsertcredit);
//            }
//        }

        echo "{success: true}";
    }

    public function getCurrentInventoryAction() {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");
        $start_date = $this->getRequest()->getParam("start_date");
        $end_date = $this->getRequest()->getParam("end_date");
        $gdg_kode = $this->getRequest()->getParam("gdg_kode");
        $option = $this->getRequest()->getParam("option");
        $search = $this->getRequest()->getParam("search");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        if ($option && $search) {
            $where = "$option LIKE '%$search%'";
        }

        $data = QDC_Inventory_Periode::factory()->getCurrentItems(array(
            "perKode" => $perkode,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "gdg_kode" => $gdg_kode,
            "offset" => $offset,
            "limit" => $limit,
            "query" => $where
        ));

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getTransactionDetailAction() {
        $this->_helper->viewRenderer->setNoRender();
        $kode_brg = $this->getRequest()->getParam("kode_brg");
        $perkode = $this->getRequest()->getParam("perkode");
        $start_date = $this->getRequest()->getParam("start_date");
        $end_date = $this->getRequest()->getParam("end_date");
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        if (!$kode_brg)
            return false;

        $data['data'] = QDC_Inventory_Periode::factory()->getItemsDetail(array(
            "perKode" => $perkode,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "offset" => $offset,
            "limit" => $limit,
            "kode_brg" => $kode_brg
        ));

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getClosingPeriodeAction() {
        $this->_helper->viewRenderer->setNoRender();
        $gdg_kode = $this->_getParam("gdg_kode");
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;

        $a = new Finance_Models_AccountingSaldoStock();
        $select = $this->db->select()
                ->from(array($a->__name()), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS id"),
//                "tgl" => "COALESCE(tgl,NOW())",
                    "tgl" => "DATE_FORMAT(tgl,'%d %b %Y %H:%i:%s')",
                    "signature",
                    "uid"
                ))
                ->where("signature is not NULL")
                ->where("tgl is not NULL");

        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        $select = $select->group(array("signature"))
                ->order(array("tgl desc"))
                ->limit($limit, $offset);

        $data = $this->db->fetchAll($select);
        foreach ($data as $k => $v) {
            $data[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
        }

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $json = Zend_Json::encode(array(
                    "posts" => $data,
                    "total" => $count
        ));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function adjustmentMenuAction() {
        
    }

    public function adjustmentAction() {
        
    }

    public function uploadAdjustmentAction() {
        $this->_helper->viewRenderer->setNoRender();

        $file = QDC_Adapter_File::factory()->upload($_FILES, 'file-path');

        $data = array();
        $return['success'] = false;
        if ($file) {
            $column = array(
                2 => "kode_brg",
                4 => "nama_brg",
                7 => "begin_balance",
                8 => "in",
                9 => "out",
                10 => "balance",
                12 => "actual"
            );
            $result = QDC_Adapter_Excel::factory(array("fileName" => $file['save_file']))->read(10, $column);
            foreach ($result as $k => $v) {
                $value = 0;

                $actualEmpty = strpos($v['actual'], '..');

                $invalid = false;
                if ($v['actual'] == '') {
                    $invalid = true;
                }

                if ($v['kode_brg'] == '')
                    continue;

                $value = $v['actual'];

                if ($actualEmpty !== false)
                    $value = 0;

                $data[] = array(
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $v['nama_brg'],
                    "actual" => $value,
                    "invalid" => $invalid
                );
            }
            if (file_exists($file['save_file'])) {
                unlink($file['save_file']);
            }

            $return['success'] = true;
            $return['data'] = $data;
        }
        $json = Zend_Json::encode($return);
        echo $json;
    }

    public function insertAdjustmentAction() {
        $this->_helper->viewRenderer->setNoRender();
        $data = ($this->_getParam("data")) ? Zend_Json::decode($this->_getParam("data")) : array();
        $periode = $this->_getParam("periode");
        $warehouse = $this->_getParam("gdg_kode");

        $return['success'] = false;
        $valid = true;
        foreach ($data as $k => $v) {
            if ($v['actual'] == '') {

                if ($v['actual'] == 0)
                    continue;

                if ($v['kode_brg'] == '') {
                    unset($data[$k]);
                    continue;
                }

                $valid = false;
                $return['msg'] = $v['kode_brg'] . " " . $v['nama_brg'] . " Still Empty.";
                break;
            }
        }

        if ($valid) {
            $compare = QDC_Inventory_Stock::factory()->compareActualStock(array(
                "actual_stock" => $data,
                "gdg_kode" => $warehouse,
                "periode" => $periode
            ));

            if ($compare) {
                foreach ($compare as $k => $v) {
                    $compare[$k]['diff'] = false;
                    if ($v['balance'] != $v['actual']) {
                        $compare[$k]['diff'] = true;
                    }
                }

                $comment = $this->getRequest()->getParam('comment');
                $data['prj_kode'] = 'ACF';

                $items = $data;
                $items["prj_kode"] = $data['prj_kode'];
                $items['next'] = $this->getRequest()->getParam('next');
                $items['uid_next'] = $this->getRequest()->getParam('uid_next');
                $items['workflow_item_id'] = $this->getRequest()->getParam('workflow_item_id');
                $items['workflow_item_type_id'] = $this->getRequest()->getParam('workflow_item_type_id');
                $items['workflow_structure_id'] = $this->getRequest()->getParam('workflow_structure_id');
                $items['workflow_id'] = $this->getRequest()->getParam('workflow_id');

                $params = array(
                    "workflowType" => "IA",
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

                $arrayInsert = array(
                    "trano" => $trano,
                    "tgl" => date("Y-m-d H:i:s"),
                    "uid" => QDC_User_Session::factory()->getCurrentUID(),
                    "upload" => Zend_Json::encode($data),
                    "comparison" => Zend_Json::encode($compare),
                    "periode" => date("m", strtotime($periode)),
                    "tahun" => date("Y", strtotime($periode)),
                    "gdg_kode" => $warehouse,
                    "gdg_nama" => $this->LOGISTIC->MasterGudang->getName($warehouse),
                    "prj_kode" => "ACF"
                );

                $this->LOGISTIC->InventoryAdjustment->insert($arrayInsert);

                $return['success'] = true;
                $return['trano'] = $trano;
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function appInventoryAdjustmentAction() {
        $type = $this->getRequest()->getParam("type");
        $from = $this->getRequest()->getParam("from");
        $show = $this->getRequest()->getParam("show");
        $item_type = $this->getRequest()->getParam("item_type");

        $jsonFile = $this->_getParam("file");
        if ($jsonFile)
            $file = Zend_Json::decode($jsonFile);
        else
            $file = array();
        $tranoShow = $this->getRequest()->getParam("trano_show");
        $this->view->show = $show;

        if ($type != '')
            $this->view->urlBack = '/default/home/showprocessdocument/type/IA';
        else
            $this->view->urlBack = '/default/home/showprocessdocument';

        $approve = $this->getRequest()->getParam("approve");

        if ($approve || $tranoShow != '') {
            if ($tranoShow) {
                $docs = $this->ADMIN->Workflowtrans->fetchRow("item_id = '$tranoShow'", array("date DESC"), 1, 0);
            } else
                $docs = $this->ADMIN->Workflowtrans->fetchRow("workflow_trans_id=$approve");

            if ($docs) {
                $user = $this->workflow->checkWorkflowInDocs($docs['workflow_trans_id'], QDC_User_Session::factory()->getCurrentID());
                if ($user || $show) {
                    $id = $docs['workflow_trans_id'];
                    $workflowId = $docs['workflow_id'];

                    $approve = $docs['item_id'];
                    $prjKode = $docs['prj_kode'];
                    $userApp = $this->workflow->getAllApproval($approve);
                    $this->view->user_approval = $userApp;
                    $data['user_approval'] = $userApp;
                    $statApprove = $docs['approve'];

                    if ($statApprove == $this->const['DOCUMENT_REJECT'])
                        $this->view->reject = true;

                    $file = $this->DEFAULT->Files->fetchAll("trano = '$approve'");

                    //Ambil trans dari database
                    $data = $this->LOGISTIC->InventoryAdjustment->fetchRow("trano = '$approve'");
                    $data = ($data ? $data->toArray() : array());

                    $allReject = $this->workflow->getAllReject($approve);
                    $lastReject = $this->workflow->getAllRejectNew($approve);

                    $this->view->lastReject = $lastReject;
                    $this->view->allReject = $allReject;

                    $this->view->data = $data;

                    $this->view->json = array(
                        "data" => $data
                    );
                    $this->view->file = $file;
                    $this->view->trano = $approve;
                    $this->view->approve = true;
                    $this->view->uid = QDC_User_Session::factory()->getCurrentUID();
                    $this->view->userID = QDC_User_Session::factory()->getCurrentID();
                    $this->view->docsID = $id;
                }

//                if ($this->ADMIN->Masterrole->cekUserInRole(array(
//                    "userID" => QDC_User_Session::factory()->getCurrentUID(),
//                    "prjKode" => $prjKode,
//                    "roleName" => "Finance and Logistic",
//                    "roleDisplayName" => array(
//                        "Finance and Accounting Officer",
//                        "Finance and Accounting Jr. Officer"
//                    )
//                ))){
//                    $this->view->canEditTrans = true;
//                    $this->view->editTransType = $docs['item_type'];
//                }
            }
        }
    }

    public function postingAdjustmentAction() {
        
    }

    public function getInventoryAdjustmentAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $return['success'] = false;
        $return['data'] = array();
        $data = $this->LOGISTIC->InventoryAdjustment->fetchRow("trano = '$trano'");
        if ($data) {
            $data = $data->toArray();

            if ($data['stsadjustment'] == 0) {
                $return['periode'] = $data['periode'] . "-" . $data['tahun'];
                $return['warehouse'] = $data['gdg_nama'];
                $return['pic'] = QDC_User_Ldap::factory(array("uid" => $data['uid']))->getName();
                $return['upload'] = date("d M Y H:i", strtotime($data['tgl']));
                $data = Zend_Json::decode($data['comparison']);
                $r = array();
                foreach ($data as $k => $v) {
                    if ($v['diff'] != true) {
                        continue;
                    }
                    $select = $this->db->select()
                            ->from(array($this->DEFAULT->MasterBarang->__name()))
                            ->where("kode_brg=?", $v['kode_brg']);

                    $d = $this->db->fetchRow($select);
                    $data[$k]['hargaavg'] = $d['hargaavg'];

                    $r[] = $data[$k];
                }

                $return['data'] = $r;
                $return['success'] = true;
            } else {
                $return['msg'] = "This trano has been posted";
            }
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doPostingAdjustmentAction() {
        $this->_helper->viewRenderer->setNoRender();

        $trano = $this->_getParam("trano");
        $jurnal = ($this->_getParam("jurnal")) ? Zend_Json::decode($this->_getParam("jurnal")) : array();
        $return['success'] = false;

        $select = $this->db->select()
                ->from(array($this->LOGISTIC->InventoryAdjustment->__name()))
                ->where("trano=?", $trano);

        $data = $this->db->fetchRow($select);
        $newData = array();
        if ($data) {
            $b = Zend_Json::decode($data['comparison']);
            $p = $data['periode'];
            $t = $data['tahun'];
            $g = $data['gdg_kode'];

            //cek temporary table
            $gudang_model = new Logistic_Models_MasterGudang();
            $isTemporary = $gudang_model->isTemporary($g);

            $tgl = date("Y-m-d H:i:s");
            $uid = QDC_User_Session::factory()->getCurrentUID();

            foreach ($b as $k => $v) {
                $select = $this->db->select()
                        ->from(array($this->DEFAULT->MasterBarang->__name()))
                        ->where("kode_brg=?", $v['kode_brg']);

                $d = $this->db->fetchRow($select);
                $harga = $d['hargaavg'];

                $arrayInsert = array(
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $v['nama_brg'],
                    "qty" => ($v['actual']-$v['balance']),
                    "hargaavg" => $harga,
                    "periode" => $p,
                    "tahun" => $t,
                    "gdg_kode" => $g,
                    "uid" => $uid,
                    "tgl" => $tgl,
                    "stsadjustment" => 1,
                    "val_kode" => ($d['val_kode'] != '' ? $d['val_kode'] : 'IDR')
                );

                $this->FINANCE->AccountingSaldoStock->insert($arrayInsert);

                $b[$k]['adjustment'] = $v['actual'];
                $newData[] = $b[$k];
            }

            $this->LOGISTIC->InventoryAdjustment->update(array(
                "adjustment" => Zend_Json::encode($newData),
                "stsadjustment" => 1
                    ), "trano = '$trano'");

            if ($isTemporary == 0) {
                $m = new Default_Models_MasterCounter();
                $newtrano = $m->setNewTrans("ADJ");
                foreach ($jurnal as $k => $val) {
                    $arrayInsert = array(
                        "trano" => $newtrano,
                        "type" => "INVENTORY-ADJ",
                        "prj_kode" => "ACF",
                        "ref_number" => $trano,
                        "ref_number2" => $val['ref_number'],
                        "tgl" => $tgl,
                        "tgl_input" => date("Y-m-d H:i:s"),
                        "uid" => $uid,
                        "ket" => $val['ket'],
                        "coa_kode" => $val['coa_kode'],
                        "coa_nama" => $val['coa_nama'],
                        "val_kode" => $val['val_kode'],
                        "rateidr" => $val['rateidr'],
                        "job_number" => $val['job_number'],
                        "debit" => floatval($val['debit']),
                        "credit" => floatval($val['credit'])
                    );
                    $this->FINANCE->AdjustingJournal->insert($arrayInsert);
                }
            }
            $return['success'] = true;
            $return['msg'] = "Adjustment Journal Trano : <b>" . $newtrano . "</b>";
        } else {
            $return['msg'] = "Inventory Adjustment data not found";
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>