<?php

/**
 * Created by JetBrains PhpStorm.
 * Date: 12/13/11
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_JurnalasfController extends Zend_Controller_Action {

    private $jurnalasf;
    private $counter;
    private $db;
    private $asf;
    private $asfc;
    private $asfH;
    private $periodeOpen;
    private $saldo_coa;
    private $coa;
    private $js;
    private $files;
    private $phpexcel;
    private $upload;
    private $filepath;

    public function init() {
        $this->jurnalasf = new Finance_Models_BankReceiveMoney();
        $this->counter = new Default_Models_MasterCounter();
        $this->files = new Default_Models_Files();
        $this->session = new Zend_Session_Namespace('login');

        $this->asf = new Default_Models_AdvanceSettlementForm();
        $this->asfc = new Default_Models_AdvanceSettlementFormCancel();
        $this->asfH = new Default_Models_AdvanceSettlementFormH();
        $this->periodeOpen = QDC_Finance_Periode::factory(array("notClose" => true))->getCurrentPeriode();
        $this->saldo_coa = new Finance_Models_AccountingSaldoCoa();
        $this->coa = new Finance_Models_MasterCoa();
        $this->js = new Finance_Models_AccountingJurnalSettlement();
        $this->phpexcel = $this->_helper->getHelper('phpexcel');
        $this->upload = $this->_helper->getHelper('uploadfile');

        $this->db = Zend_Registry::get("db");
    }

    public function menuAction() {
        
    }

    public function insertjurnalasfAction() {

        if ($this->periodeOpen) {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
        }
    }

    public function editjurnalasfAction() {

        if ($this->periodeOpen) {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
        }
    }

    public function doinsertjurnalasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $json = Zend_Json::decode($this->getRequest()->getParam('json'));
        $perkode = $this->getRequest()->getParam('perkode');
        $year = $this->getRequest()->getParam('year');
        $val_kode = $this->getRequest()->getParam('val_kode');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $bulan = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
        $month = date("m", strtotime($bulan));
        $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $rateidr = $exhangeRate['rateidr'];

        $uid = $this->session->userName;

        $m = new Default_Models_MasterCounter();
        $trano = $m->setNewTrans("JSR");

        foreach ($json as $key => $val) {

            if ($val['type'] == 'asfdd' || $val['type'] == 'asfddcancel') {

                //debit

                $coa_debit = $val['coa_kode'];
                $coa = $this->coa->fetchRow("coa_kode = '$coa_debit'");

                //Insert jurnal settlement out

                $this->js->insertJurnal(array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "total" => floatval($val['debit']),
                    "val_kode" => $val_kode,
                    "type" => "JS",
                    "coa_kode" => $coa_debit,
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => true,
                    "prj_kode" => $prj_kode,
                    "sit_kode" => $sit_kode,
                    "job_number" => $val['job_number'],
                    "description" => $val['description']
                ));
            } else {
                //untuk credit
                $coa_credit = $val['coa_kode'];
                $coa = $this->coa->fetchRow("coa_kode = '$coa_credit'");

                //Insert jurnal settlement out

                $this->js->insertJurnal(array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "total" => floatval($val['credit']),
                    "val_kode" => $val_kode,
                    "type" => "JS",
                    "coa_kode" => $coa_credit,
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => false,
                    "prj_kode" => $prj_kode,
                    "sit_kode" => $sit_kode,
                    "job_number" => $val['job_number'],
                    "description" => $val['description']
                ));
            }
        }
        
        
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => true,
                    "trano" => $trano
        )));
    }

    public function doupdatejurnalasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $json = Zend_Json::decode($this->getRequest()->getParam('json'));
        $trano = $this->getRequest()->getParam('trano');
        $val_kode = $this->getRequest()->getParam('val_kode');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $sit_kode = $this->getRequest()->getParam('sit_kode');
        $uid = $this->session->userName;

        $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $rateidr = $exhangeRate['rateidr'];

        $before = $this->js->fetchAll("trano = '$trano'");
        if (!$before)
            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
        else {
            $log['jurnalasf-detail-before'] = $before->toArray();
            $this->js->delete("trano = '$trano'");
            foreach ($json as $key => $val) {
                $insertjurnalasf = array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "uid" => $uid,
                    "coa_kode" => $val['coa_kode'],
                    "prj_kode" => $val['prj_kode'],
                    "sit_kode" => $val['sit_kode'],
                    "job_number" => $val['job_number'],
                    "coa_nama" => $val['coa_nama'],
                    "val_kode" => $val_kode,
                    "debit" => floatval($val['debit']),
                    "credit" => floatval($val['credit']),
                    "type" => "JS",
                    "tgl" => date('Y-m-d H:i:s'),
                    "rateidr" => $rateidr,
                    "prj_kode" => $prj_kode,
                    "sit_kode" => $sit_kode,
                    "description" => $val['description']
                    
                );

                $this->js->insert($insertjurnalasf);
            }
            $log2 = $this->js->fetchAll("trano = '$trano'")->toArray();

            $arrayLog = array(
                "trano" => $trano,
                "uid" => $this->session->userName,
                "tgl" => date('Y-m-d H:i:s'),
                "prj_kode" => '',
                "sit_kode" => '',
                "action" => "UPDATE",
                "data_before" => Zend_Json::encode($log),
                "data_after" => Zend_Json::encode($log2),
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $log = new Admin_Models_Logtransaction();
            $log->insert($arrayLog);
            $this->getResponse()->setBody("{success: true}");
        }
    }

    public function getdetailasfAction() {

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');
        $filename = $this->getRequest()->getParam('filename');
        $upload = $this->getRequest()->getParam('upload');
        $type = $this->getRequest()->getParam('type');

        $hasil['asfdd'] = array();
        $hasil['asfheader'] = array();
        $hasil['asfddcancel'] = array();

        //ASF Cancel
        $asfheader = $this->asfH->fetchAll("trano = '$trano'")->toArray();
        $asfcancel = $this->asfc->fetchAll("trano = '$trano'")->toArray();

        //asfheader
        foreach ($asfheader as $key => $key2) {
            foreach ($key2 as $val => $val2) {
                $asfheader[$key][$val] = str_replace('""', '', $val2);
            }
            $hasil['asfheader'][] = $asfheader[$key];
        }

        //asfheader
        foreach ($asfcancel as $key => $key2) {
            foreach ($key2 as $val => $val2) {
                $asfcancel[$key][$val] = str_replace('""', '', $val2);
            }
            $hasil['asfddcancel'][] = $asfcancel[$key];
        }

        //----------get data from files
        
        $dummypath = "";
        if($upload=='true')
            $dummypath = "files/";

        $path = Zend_Registry::get('uploadPath');
        $filepath = $path . "/" .$dummypath. $filename;
        $files = $this->phpexcel->readasffile($filepath, '',$type);
        $this->filepath = $filepath;

        //----------



        Zend_Loader::loadClass('Zend_Json');
        $jsonasfheader = Zend_Json::encode($hasil['asfheader']);
        $jsonasfddcancel = Zend_Json::encode($hasil['asfddcancel']);
        $jsonfiles = Zend_Json::encode($files);
        $jsonasfheader = str_replace("\\", "", $jsonasfheader);
        $jsonasfddcancel = str_replace("\\", "", $jsonasfddcancel);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{asfheader:'$jsonasfheader',asfddcancel:'$jsonasfddcancel',files:'$jsonfiles'}");
    }

    public function getAsfFilesAction() {

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        if ($trano)
            $where[] = "trano = '$trano'";

        $where[] = "(filename like '%.xls' || filename like '%.xlsx')";

        if ($where)
            $where = implode(" AND ", $where);

        $files = $this->files->getFilesWithFilter($where);


        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($files);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');

        $this->getResponse()->setBody("{success: true, data : '$json'}");
    }

    public function getjurnalasfAction() {

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

//        $hasil['asfdd'] = array();
        $hasil['asfheader'] = array();
        $hasil['asfdetail'] = array();

        //ASF Cancel
        $jurnalasf = $this->js->fetchAll("trano = '$trano'")->toArray();

        //asfheader
        foreach ($jurnalasf as $key => $key2) {
            foreach ($key2 as $val => $val2) {
                $jurnalasf[$key][$val] = str_replace('""', '', $val2);
            }
            if ($key2['debit'] == 0)
                $hasil['asfheader'][] = $jurnalasf[$key];
            else
                $hasil['asfdetail'][] = $jurnalasf[$key];
        }


        Zend_Loader::loadClass('Zend_Json');
        $jsonasfheader = Zend_Json::encode($hasil['asfheader']);
        $jsonasfdetail = Zend_Json::encode($hasil['asfdetail']);
        $jsonasfheader = str_replace("\\", "", $jsonasfheader);
        $jsonasfdetail = str_replace("\\", "", $jsonasfdetail);
//        $jsonfiles = str_replace("\\", "", $jsonfiles);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody("{asfheader:'$jsonasfheader',asfdetail:'$jsonasfdetail'}");
    }

//    public function douploadasffilesAction() {
//
//        $this->_helper->viewRenderer->setNoRender();
//        $this->_helper->layout->disableLayout();
//        
//        $result = $this->upload->uploadFile($_FILES, 'filepath');
//        if ($result) {
//            $filename = $result['save_file'];
//            $success = true;
//        } else {
//            $success = false;
//            $filename = '';
//        }
//        Zend_Loader::loadClass('Zend_Json');
//        $json = Zend_Json::encode($filename);
//        $json = str_replace("\\", "", $json);
//        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
//
//        $this->getResponse()->setBody("{success: '$success', data : '$json'}");
//    }

}
