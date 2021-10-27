<?php

/**
 * Created by JetBrains PhpStorm.
 * Date: 12/13/11
 * Time: 9:28 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_JurnalppnremsettlementController extends Zend_Controller_Action {

    private $jurnalppn;
    private $counter;
    private $db;
    private $asf;
    private $asfc;
    private $asfH;
    private $periodeOpen;
    private $saldo_coa;
    private $coa;
    private $js;

    public function init() {
        $this->counter = new Default_Models_MasterCounter();
        $this->session = new Zend_Session_Namespace('login');
        
        $this->ppnremsetH = new Finance_Models_PpnReimbursementSettleH();
        $this->periodeOpen = QDC_Finance_Periode::factory(array("notClose" => true))->getCurrentPeriode();
        $this->saldo_coa = new Finance_Models_AccountingSaldoCoa();
        $this->coa = new Finance_Models_MasterCoa();
        $this->js = new Finance_Models_AccountingJurnalSettlement();


        $this->db = Zend_Registry::get("db");
    }

    public function menuAction() {
        
    }

    public function insertjurnalppnremsettleAction() {

        if ($this->periodeOpen) {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
        }
    }

    public function doinsertjurnalppnremsettleAction() {
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

            if ($val['type'] == 'debit') {
                
                //debit
                
                $coa_debit = $val['coa_kode'];
                $coa = $this->coa->fetchRow("coa_kode = '$coa_debit'");
                
                //Insert jurnal settlement out
                
                $this->js->insertJurnal(array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "total" => $val['debit'],
                    "val_kode" => $val_kode,
                    "type" => "JPPN",
                    "coa_kode" => $coa_debit,
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => true,
                    "prj_kode" => $prj_kode,
                    "sit_kode" => $sit_kode
                ));
                
            } else {
                //untuk credit
                $coa_credit = $val['coa_kode'];
                $coa = $this->coa->fetchRow("coa_kode = '$coa_credit'");

                //Insert jurnal settlement out

                $this->js->insertJurnal(array(
                    "trano" => $trano,
                    "ref_number" => $val['ref_number'],
                    "total" => $val['credit'],
                    "val_kode" => $val_kode,
                    "type" => "JPPN",
                    "coa_kode" => $coa_credit,
                    "coa_nama" => $coa['coa_nama'],
                    "debit" => false,
                    "prj_kode" => $prj_kode,
                    "sit_kode" => $sit_kode
                ));
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode(array(
                    "success" => true,
                    "trano" => $trano
        )));
    }

    public function editJurnalasfAction() {
        
    }

//    public function doUpdateJurnalasfAction() {
//        $this->_helper->viewRenderer->setNoRender();
//        $this->_helper->layout->disableLayout();
//
//        $jurnalasfdata = Zend_Json::decode($this->getRequest()->getParam('jurnalasfdata'));
//        $trano = $this->_getParam("trano");
//        $tgl = ($this->_getParam("tgl") != '') ? date("Y-m-d", strtotime($this->_getParam("tgl"))) : date('Y-m-d H:i:s');
//
//        $uid = $this->session->userName;
//
//        $before = $this->jurnalasf->fetchAll("trano = '$trano'");
//        if (!$before)
//            $this->getResponse()->setBody("{success: false, msg: 'Journal not found'}");
//        else {
//            $log['jurnalasf-detail-before'] = $before->toArray();
//            $this->jurnalasf->delete("trano = '$trano'");
//            foreach ($jurnalasfdata as $key => $val) {
//                $insertjurnalasf = array(
//                    "trano" => $trano,
//                    "ref_number" => $val['ref_number'],
//                    "tgl" => $tgl,
//                    "uid" => $uid,
//                    "coa_kode" => $val['coa_kode'],
//                    "prj_kode" => $val['prj_kode'],
//                    "sit_kode" => $val['sit_kode'],
//                    "job_number" => $val['job_number'],
//                    "coa_nama" => $val['coa_nama'],
//                    "val_kode" => $val['val_kode'],
//                    "debit" => floatval($val['debit']),
//                    "credit" => floatval($val['credit'])
//                );
//
//                $this->jurnalasf->insert($insertjurnalasf);
//            }
//            $log2 = $this->jurnalasf->fetchAll("trano = '$trano'")->toArray();
//
//            $arrayLog = array(
//                "trano" => $trano,
//                "uid" => $this->session->userName,
//                "tgl" => date('Y-m-d H:i:s'),
//                "prj_kode" => '',
//                "sit_kode" => '',
//                "action" => "UPDATE",
//                "data_before" => Zend_Json::encode($log),
//                "data_after" => Zend_Json::encode($log2),
//                "ip" => $_SERVER["REMOTE_ADDR"],
//                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
//            );
//            $log = new Admin_Models_Logtransaction();
//            $log->insert($arrayLog);
//            $this->getResponse()->setBody("{success: true}");
//        }
//    }

    public function getdetailppnremAction() {

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $trano = $this->getRequest()->getParam('trano');

        $hasil['ppnrem'] = array();

        //PPNREM Cancel
        $ppnrem = $this->ppnremsetH->fetchAll("trano = '$trano'")->toArray();



        //asfdd
        foreach ($ppnrem as $key => $key2) {
            foreach ($key2 as $val => $val2) {
                $ppnrem[$key][$val] = str_replace('""', '', $val2);
            }
            $hasil['ppnrem'][] = $ppnrem[$key];
        }

        

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($hasil);
        $json = str_replace("\\", "", $json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}
