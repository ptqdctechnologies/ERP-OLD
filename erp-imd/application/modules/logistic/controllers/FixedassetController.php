<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 5/16/12
 * Time: 10:49 AM
 * To change this template use File | Settings | File Templates.
 */
class Logistic_FixedassetController extends Zend_Controller_Action {

    private $pod;
    private $gudang;
    private $upload;
    private $phpexcel;
    private $fixedasset;
    private $fixedassetstatus;
    private $session;
    private $brand;
    private $kondisi;
    private $log_trans;
    private $trans_asset;
    private $files;
    private $db;
    private $counter;
    private $coa;
    private $kategori;
    private $jurnal_bank;

    public function init() {
        $this->pod = new Default_Models_ProcurementPod();
        $this->gudang = new Logistic_Models_MasterGudang();
        $this->upload = $this->_helper->getHelper('uploadfile');
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->fixedasset = new Logistic_Models_MasterFixedAsset();
        $this->fixedassetstatus = new Logistic_Models_FixedAssetStatus();
        $this->session = new Zend_Session_Namespace('login');
        $this->brand = new Logistic_Model_MasterBrand();
        $this->kondisi = new Logistic_Models_MasterKondisi();
        $this->log_trans = new Procurement_Model_Logtransaction();
        $this->trans_asset = new Logistic_Models_FixedAssetStatus();
        $this->files = new Default_Models_Files();
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->counter = new Default_Models_MasterCounter();
        $this->coa = new Finance_Models_MasterCoa();
        $this->kategori = new Finance_Models_MasterKategoriAsset();
        $this->jurnal_bank = new Finance_Models_AccountingJurnalBank();
    }

    public function menuAction() {
        
    }

    public function insertfixedassetAction() {
        
    }

    public function getpoditemAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data['data'] = $this->pod->fetchAll("trano = '$trano'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getstorageAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $type = $this->getRequest()->getParam('type');

        if ($type == 'Central Storage') {
            $data['data'] = $this->gudang->fetchAll("gdg_kode = '000'")->toArray();
        } else {
            $data['data'] = $this->gudang->fetchAll("gdg_kode != '000'")->toArray();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function uploadfixedassetAction() {
        
    }

    public function douploadfixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();

        $location = $this->getRequest()->getParam('location');
        $storage = $this->getRequest()->getParam('storage');

        $result = $this->upload->uploadFile($_FILES, 'file-path');

        if ($result) {
            Zend_Loader::loadClass('Zend_Json');
            $asset = $this->phpexcel->readfixedasset($result['save_file'], $result['id_file']);

            foreach ($asset as $k => $v) {

                $asset[$k]['location'] = $location;
                $asset[$k]['storage'] = $storage;
                $asset[$k]['valuta'] = strtoupper($v['valuta']);
                $brand = $v['brand'];
                $kondisi = $v['condition'];
                $coa_kode = $v['coa_kode'];
                $asset[$k]['numValid'] = 0;

                $asset[$k]['depr_exp'] = $v['purchase_price'] / ($v['depr_rate'] * 12);

                $cekbrand = $this->brand->fetchRow(" mrk_nama = '$brand' ");
                if ($cekbrand == '') {
//                    $asset[$k]['valid'] = false;
                    $asset[$k]['numValid'] ++;
                }
//
//                $condition = $v['condition'];
                $cekcondition = $this->kondisi->fetchRow(" kds_nama = '$kondisi' ");
                if ($cekcondition == '') {
//                    $asset[$k]['valid'] = false;
                    $asset[$k]['numValid'] ++;
                }

                $cekcoa_kode = $this->coa->fetchRow(" coa_kode = '$coa_kode' ");
                if ($cekcoa_kode == '') {
//                    $asset[$k]['valid'] = false;
                    $asset[$k]['numValid'] ++;
                }
            }

            $json = Zend_Json::encode($asset);
            $fields = array(
                array("name" => "id", "type" => "string"),
                array("name" => "new_code", "type" => "string"),
                array("name" => "clasification", "type" => "string"),
                array("name" => "code_part_old", "type" => "string"),
                array("name" => "marking_date", "type" => "date"),
                array("name" => "accessories", "type" => "string"),
                array("name" => "brand", "type" => "string"),
                array("name" => "type", "type" => "string"),
                array("name" => "serial_number", "type" => "string"),
                array("name" => "description", "type" => "string"),
                array("name" => "purchase_status", "type" => "string"),
                array("name" => "purchase_date", "type" => "date"),
                array("name" => "condition", "type" => "string"),
                array("name" => "valuta", "type" => "string"),
                array("name" => "purchase_price"),
                array("name" => "depr_rate"),
                array("name" => "depr_exp"),
                array("name" => "location", "type" => "string"),
                array("name" => "storage", "type" => "string"),
                array("name" => "valid"),
                array("name" => "numValid"),
//                array("name" => "total_depr"),
                array("name" => "status_aktif"),
                array("name" => "kode_kategori"),
                array("name" => "coa_debit"),
                array("name" => "coa_credit")
            );

            $fields = Zend_Json::encode($fields);
            if (file_exists($result['save_file'])) {
                unlink($result['save_file']);
            }

            echo "{success:true, file:\"" . $result['origin_name'] . "\", newfile:\"" . $result['origin_name'] . "\",RESULT:{\"posts\" : $json,fields:$fields }}";
        } else
            echo "{success:false}";
    }

    private function checkfixedasset($asset = '') {
        if ($asset == '') {
            return false;
        }

        $msg = array();
        foreach ($asset as $k => $v) {
            $baris = $k + 1;

            $code = $v['new_code'];
            $cekcode = $this->fixedasset->fetchRow(" code = '$code' ");
            if ($cekcode != '') {
                $msg[] = "Error on " . $v['new_code'] . " (Row $baris) : Code Already Exists. Please Use Edit";
            }

            $brand = $v['brand'];
            $cekbrand = $this->brand->fetchRow(" mrk_nama = '$brand' ");
            if ($cekbrand == '') {
                $msg[] = "Error on " . $v['new_code'] . " (Row $baris) : Brand not found";
            }

            $kondisi = $v['condition'];
            $cekcondition = $this->kondisi->fetchRow(" kds_nama = '$kondisi' ");
            if ($cekcondition == '') {
                $msg[] = "Error on " . $v['new_code'] . " (Row $baris) : Condition not found";
            }

            $kategoriasset = $v['kode_kategori'];
            $cekkategori = $this->kategori->fetchRow(" kode_ktfa = '$kategoriasset' ");
            if ($cekkategori == '') {
                $msg[] = "Error on " . $v['new_code'] . " (Row $baris) : Kode Kategori kode not found";
            }
        }

        if (count($msg) > 0) {
            $msg = implode("<br>", $msg);
            return $msg;
        } else
            return true;
    }

    public function doinsertuploadfixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $fixedassetdata = Zend_Json::decode($this->getRequest()->getParam('posts'));
        $gdg_kode = $this->getRequest()->getParam('gdg_kode');

        $cek = $this->checkfixedasset($fixedassetdata);
        if ($cek !== true) {
            $json = "{success: false, msg: '$cek'}";

            $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            $this->getResponse()->setBody($json);

            return false;
        }

        $type = 'FIXA';
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');


        $counter = new Default_Models_MasterCounter();

        foreach ($fixedassetdata as $key => $val) {
            $trano = $counter->setNewTrans($type);
            $insert = array(
                'trano' => $trano,
                'code' => $val['new_code'],
                'location' => $val['location'],
                'storage' => $val['storage'],
                'gdg_kode' => $gdg_kode,
                'clasification' => $val['clasification'],
                'old_code' => $val['code_part_old'],
                'marking_date' => date('Y-m-d', strtotime($val['marking_date'])),
                'accessories' => $val['accessories'],
                'brand' => $val['brand'],
                'type' => $val['type'],
                'serial_number' => $val['serial_number'],
                'description' => $val['description'],
                'purchase_status' => $val['purchase_status'],
                'purchase_date' => date('Y-m-d', strtotime($val['purchase_date'])),
                'condition' => $val['condition'],
                'val_kode' => $val['valuta'],
                'purchase_price' => $val['purchase_price'],
                'depr_rate' => $val['depr_rate'],
                'depr_exp' => $val['depr_exp'],
                'input_date' => date('Y-m-d H:i:s'),
                'uid' => $this->session->userName,
                'token' => QDC_User_Token::factory()->getRandomToken(),
//                'total_depr' => $val['total_depr'],
                'status_aktif' => $val['status_aktif'],
                'kode_kategori' => $val['kode_kategori']
            );

            $this->fixedasset->insert($insert);

            //jurnal action
            $debit = $val['coa_debit'];
            $coa_debit = $this->coa->fetchRow("coa_kode = '$debit'");
            $credit = $val['coa_credit'];
            $coa_credit = $this->coa->fetchRow("coa_kode = '$credit'");

            if ($val['valuta'] != 'IDR') {
                $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                            "valuta" => "USD"))->getExchangeRate();
                $currentRateidr = $currentRateidr['rateidr'];
                $fixedAssetPrice = floatval($val['purchase_price'] * $currentRateidr);
            } else
                $fixedAssetPrice = floatval($val['purchase_price']);

            //debit
            $insertbank = array(
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa_debit['coa_kode'],
                "coa_nama" => $coa_debit['coa_nama'],
                "credit" => 0,
                "debit" => $fixedAssetPrice,
                "val_kode" => 'IDR',
                "type" => 'FIXA'
            );
            $this->jurnal_bank->insert($insertbank);

            //credit
            $insertbank = array(
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa_credit['coa_kode'],
                "coa_nama" => $coa_credit['coa_nama'],
                "credit" => $fixedAssetPrice,
                "debit" => 0,
                "val_kode" => 'IDR',
                "type" => 'FIXA'
            );
            $this->jurnal_bank->insert($insertbank);
        }

        $json = "{success: true}";

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewfixedassetAction() {
        
    }

    public function getfixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');

        $search = "";

        if ($textsearch == "" || $textsearch == null) {
            $search = null;
        } else if ($textsearch != null && $option != null) {
            $search = " $option like '%$textsearch%' ";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $data['data'] = array();
        $data['success'] = false;
        $data['total'] = 0;
        $fixedasset = $this->fixedasset->fetchAll($search, array($sort . " " . $dir), $limit, $offset);
        if ($fixedasset) {
            $data['data'] = $fixedasset->toArray();
            foreach ($data['data'] as $k => $v) {
                if ($v['marking_date'] == '1970-01-01 00:00:00' || $v['marking_date'] == '0000-00-00 00:00:00')
                    $v['marking_date'] = '';
                if ($v['purchase_date'] == '1970-01-01 00:00:00' || $v['purchase_date'] == '0000-00-00 00:00:00')
                    $v['purchase_date'] = '';
                if ($v['status_aktif'] == 0)
                    $data['data'][$k]['status_aktif'] = 'Not Active';
                else
                    $data['data'][$k]['status_aktif'] = 'Active';
                $data['data'][$k]['username'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();

                $token = $v['token'];
                $cekStatus = $this->fixedassetstatus->fetchRow("token = '$token'", array("tgl DESC"));

                $data['data'][$k]['book_value'] = $v['purchase_price'] - $v['total_depr'];

                $data['data'][$k]['last_condition'] = '';
                if ($cekStatus) {
                    $data['data'][$k]['last_condition'] = $cekStatus['condition'];
                }
            }
            $data['total'] = $this->fixedasset->fetchAll()->count();
            $data['success'] = true;
        } else {
            $data['msg'] = 'Asset not found..';
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewdetailfixedassetAction() {
        $code = $this->getRequest()->getParam('code');

        $fixedassetdata = $this->fixedasset->fetchRow("code = '$code'");

        if (!$fixedassetdata)
            return false;
        $fixedassetdata = $fixedassetdata->toArray();
        $status = $fixedassetdata['status_aktif'];

        if ($fixedassetdata['marking_date'] == '1970-01-01 00:00:00' || $fixedassetdata['marking_date'] == '0000-00-00 00:00:00')
            $fixedassetdata['marking_date'] = '';
        if ($fixedassetdata['purchase_date'] == '1970-01-01 00:00:00' || $fixedassetdata['purchase_date'] == '0000-00-00 00:00:00')
            $fixedassetdata['purchase_date'] = '';

        $fixedassetdata['username'] = QDC_User_Ldap::factory(array("uid" => $fixedassetdata['uid']))->getName();
        if ($status == 0) {
            $fixedassetdata['status_aktif'] = 'Not Active';
        } else {
            $fixedassetdata['status_aktif'] = 'Active';
        }

        $sql = "SELECT LA.trano,LA.code,LA.tgl,LA.uid_pic,LA.status,LA.condition,LA.ket,MS.sup_nama,FI.filename,FI.savename
                FROM (logistic_fixed_asset_status LA LEFT JOIN master_suplier MS ON MS.sup_kode = LA.sup_kode)
                LEFT JOIN files FI ON FI.trano = LA.token
                WHERE code='$code' ORDER BY tgl desc";
        $fetch = $this->db->query($sql);
        $trans_asset = $fetch->fetchAll();


        $this->view->trans = $trans_asset;
        $this->view->tampil = $fixedassetdata;
    }

    public function doinsertfixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $jsonassetdata = $this->getRequest()->getParam('fixedassetdata');
        $assetdata = Zend_Json::decode($jsonassetdata);

        $type = 'FIXA';

        $counter = new Default_Models_MasterCounter();
        $trano = $counter->setNewTrans($type);

        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        foreach ($assetdata as $key => $val) {
            $insertdata = array(
                'trano' => $trano,
                'code' => $val['code'],
                'location' => $val['location'],
                'storage' => $val['storage'],
                'gdg_kode' => $val['gdg_kode'],
                'clasification' => $val['clasification'],
                'marking_date' => date('Y-m-d', strtotime($val['marking_date'])),
                'accessories' => $val['accessories'],
                'brand' => $val['brand'],
                'type' => $val['type'],
                'serial_number' => $val['serial_number'],
                'description' => $val['description'],
                'purchase_status' => $val['purchase_status'],
                'purchase_date' => date('Y-m-d', strtotime($val['purchase_date'])),
                'condition' => 'BARU',
                'val_kode' => $val['val_kode'],
                'purchase_price' => $val['purchase_price'],
                'depr_rate' => $val['dep_rate'],
                'depr_exp' => $val['dep_exp'],
                'input_date' => date('Y-m-d H:i:s'),
                'uid' => $this->session->userName,
                'token' => QDC_User_Token::factory()->getRandomToken(),
                'status_aktif' => 1,
                'kode_kategori' => $val['kode_kategori']
            );

            $this->fixedasset->insert($insertdata);


            //jurnal action
            $debit = $val['coa_debit'];
            $coa_debit = $this->coa->fetchRow("coa_kode = '$debit'");
            $credit = $val['coa_credit'];
            $coa_credit = $this->coa->fetchRow("coa_kode = '$credit'");

            if ($val['val_kode'] != 'IDR')
                $fixedAssetPrice = floatval($val['purchase_price'] * $val['rateidr']);
            else
                $fixedAssetPrice = floatval($val['purchase_price']);

            //debit
            $insertbank = array(
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa_debit['coa_kode'],
                "coa_nama" => $coa_debit['coa_nama'],
                "credit" => 0,
                "debit" => $fixedAssetPrice,
                "val_kode" => 'IDR',
                "type" => 'FIXA'
            );
            $this->jurnal_bank->insert($insertbank);

            //credit
            $insertbank = array(
                "trano" => $trano,
                "prj_kode" => $val['prj_kode'],
                "sit_kode" => $val['sit_kode'],
                "tgl" => $tgl,
                "uid" => $uid,
                "coa_kode" => $coa_credit['coa_kode'],
                "coa_nama" => $coa_credit['coa_nama'],
                "credit" => $fixedAssetPrice,
                "debit" => 0,
                "val_kode" => 'IDR',
                "type" => 'FIXA'
            );
            $this->jurnal_bank->insert($insertbank);
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function cekcodefixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');

        $cekcode = $this->fixedasset->fetchRow("code = '$code'");

        if ($cekcode) {
            echo "{success: false,message : ' code <b><font color=red>$code</font></b> Has been created '}";
            die;
        } else {
            echo "{success: true}";
            die;
        }

        $json = "{success: true}";
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editfixedassetAction() {
        
    }

    public function getkondisiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $data['data'] = $this->kondisi->fetchAll()->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatefixedassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');
        $marking_date = $this->getRequest()->getParam('marking_date');
        $storage = $this->getRequest()->getParam('storage');
        $location = $this->getRequest()->getParam('location');
        $description = $this->getRequest()->getParam('description');
        $clasification = $this->getRequest()->getParam('clasification');
        $brand = $this->getRequest()->getParam('brand');
        $type = $this->getRequest()->getParam('type');
        $serial_number = $this->getRequest()->getParam('serial_number');
        $accessories = $this->getRequest()->getParam('accessories');
        $condition = $this->getRequest()->getParam('condition');
        $status = $this->getRequest()->getParam('status_asset');
        $price = $this->getRequest()->getParam('price');
        $depr_rate = $this->getRequest()->getParam('dep_rate');
        $depr_exp = $this->getRequest()->getParam('dep_exp');
        $kode_kategori = $this->getRequest()->getParam('kode_kategori');

        $log['asset-before'] = $this->fixedasset->fetchRow("code = '$code'")->toArray();

        $update = array(
            "location" => $location,
            "storage" => $storage,
            "marking_date" => date('Y-m-d', strtotime($marking_date)),
            "description" => $description,
            "clasification" => $clasification,
            "brand" => $brand,
            "type" => $type,
            "serial_number" => $serial_number,
            "accessories" => $accessories,
            "condition" => $condition,
            "status_aktif" => $status,
            "purchase_price" => str_replace(",", "", $price),
            "depr_rate" => str_replace(",", "", $depr_rate),
            "depr_exp" => str_replace(",", "", $depr_exp),
            "kode_kategori" => $kode_kategori
        );

        $this->fixedasset->update($update, "code = '$code'");

        $log2['asset-after'] = $this->fixedasset->fetchRow("code = '$code'")->toArray();

        $jsonLog = Zend_Json::encode($log);
        $jsonLog2 = Zend_Json::encode($log2);

//        var_dump($jsonLog);die;

        $arrayLog = array(
            "trano" => $code,
            "uid" => $this->session->userName,
            "tgl" => date('Y-m-d H:i:s'),
            "prj_kode" => '',
            "sit_kode" => '',
            "action" => "UPDATE",
            "data_before" => $jsonLog,
            "data_after" => $jsonLog2,
            "ip" => $_SERVER["REMOTE_ADDR"],
            "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
        );
        $this->log_trans->insert($arrayLog);

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function transactionAction() {
        
    }

    public function doinserttransassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');
        $token = $this->getRequest()->getParam('token');
        $uid_pic = $this->getRequest()->getParam('pic');
        $status = $this->getRequest()->getParam('trans-status');
        $condition = $this->getRequest()->getParam('condition');
        $ket = $this->getRequest()->getParam('comment');
        $sup_kode = $this->getRequest()->getParam('sup_kode');
        $prj_kode = $this->getRequest()->getParam('prj_kode');

        $filedata = Zend_Json::decode($this->getRequest()->getParam('filedata'));

        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $type = 'FA';
        $trano = $this->counter->setNewTrans($type);

        $insert = array(
            "trano" => $trano,
            "token" => $token,
            "code" => $code,
            "tgl" => $tgl,
            "uid" => $uid,
            "uid_pic" => $uid_pic,
            "status" => $status,
            "condition" => $condition,
            "ket" => $ket,
            "sup_kode" => $sup_kode,
            "prj_kode" => $prj_kode
        );

        $this->trans_asset->insert($insert);

        if (count($filedata) > 0) {
            foreach ($filedata as $key => $val) {
                $arrayInsert = array(
                    "trano" => $trano,
                    "prj_kode" => '',
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $return = array("success" => true, "trano" => $trano);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getstatusassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');

        $trans = $this->trans_asset->fetchAll("code = '$code'", array("tgl desc"));

        if ($trans) {
            $data['data'] = $trans->toArray();
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getfileAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $data['data'] = $this->files->fetchAll("trano = '$trano'")->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
