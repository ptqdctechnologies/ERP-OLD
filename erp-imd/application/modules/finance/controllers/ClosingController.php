<?php

class Finance_ClosingController extends Zend_Controller_Action {

    private $FINANCE;
    private $periodeOpen;
    private $session;

    public function init() {
        $this->FINANCE = QDC_Model_Finance::init(array(
                    "AccountingCloseAP",
                    "AccountingCloseAR",
                    "AccountingJurnalBank",
                    "AccountingSaldoCoa",
                    "AccountingSaldoRL",
                    "AdjustingJournal",
                    "AccountingCloseExchangeRate",
        ));

        $this->periodeOpen = QDC_Finance_Periode::factory(array("notClose" => true))->getCurrentPeriode();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function closingAction() {
        if ($this->periodeOpen) {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F", strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
            $this->view->tgl_awal = date("d M Y", strtotime($this->periodeOpen['tgl_awal']));
            $this->view->tgl_akhir = date("d M Y", strtotime($this->periodeOpen['tgl_akhir']));
            $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $this->view->rateidr = $exhangeRate['rateidr'];
        }
    }

    public function doclosingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");
        $tahun = $this->getRequest()->getParam("year");
        $rateidr = $this->getRequest()->getParam("rateidr");
        $startdate = $this->getRequest()->getParam("startdate");
        $enddate = $this->getRequest()->getParam("enddate");
        $uid = $this->session->userName;

        if (!$perkode) {
            echo "{success: false, msg: 'No Periode to Close'";
            return false;
        }

        $insert = array(
            "periode" => $perkode,         
            "uid" => $uid,         
            "tahun" => $tahun,         
            "tgl" => date('Y-m-d H:i:s'),
            "rateidr" => $rateidr,
            "tgl_awal" => date("Y-m-d", strtotime($startdate)),
            "tgl_akhir" => date("Y-m-d", strtotime($enddate)),
        );

        $this->FINANCE->AccountingCloseExchangeRate->insert($insert);

        $result = QDC_Finance_Periode::factory(array(
            "rateidr" => $rateidr
        ))->closing($perkode);

        echo "{success: true}";
    }

    public function getcoaforclosingAction() {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");
        $tahun = $this->getRequest()->getParam("tahun");
        $bulan = $this->getRequest()->getParam("bulan");
        $search = $this->getRequest()->getParam("search");
        $type = $this->getRequest()->getParam("type");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);

        $tglAwal = $periode['tgl_awal'];
        $tglAkhir = $periode['tgl_akhir'];

        if ($type && $search) {
            $where = " AND $type LIKE '%$search%'";
        }

        $coas = array();
        $ap = $this->FINANCE->AccountingCloseAP->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($ap) {
            $ap = $ap->toArray();
        }

        $ar = $this->FINANCE->AccountingCloseAR->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($ar) {
            $ar = $ar->toArray();
        }

        $bank = $this->FINANCE->AccountingJurnalBank->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($bank)
            $bank = $bank->toArray();

        $general = $this->FINANCE->AdjustingJournal->fetchAll("tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND stsclose = 0 $where");
        if ($general)
            $general = $general->toArray();

        $coas = QDC_Common_Array::factory()->merge(array($ap, $ar, $bank, $general));

        $id = 1;
        $count = count($coas);
        $data = array();

        foreach ($coas as $k => $v) {
            if ($k >= $offset && $k < ($limit + $offset)) {
                $coas[$k]['id'] = $id;
                $data[] = $coas[$k];
                $id++;
            } else
                unset($coas[$k]);
        }

        $json = Zend_Json::encode(array("data" => $data, "count" => $count));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function constructionAction() {
        
}

    public function addConstructionAction() {
        $ac = new Finance_Models_AccountingSaldoConstruction();
        $data = $ac->getProjectProgress();

        $this->view->json = Zend_Json::encode($data);
    }

    public function doSubmitConstructionAction() {
        $this->_helper->viewRenderer->setNoRender();

        $data = Zend_Json::decode($this->_getParam("data"));

        $ac = new Finance_Models_AccountingSaldoConstruction();

        $per = new Finance_Models_MasterPeriode();
        $periode = $per->getLastPeriode(date("Y-m-d"));

        //Delete existing data on same periode..
        $ac->truncateExist($periode['bulan'], $periode['tahun']);
        $rateidr = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();

        $date = date("Y-m-d H:i:s");
        foreach ($data as $k => $v) {
            $arrayInsert = array(
                "prj_kode" => $v['prj_kode'],
                "prj_nama" => $v['prj_nama'],
                "total" => $v['total'],
                "coa_kode" => $v['coa_kode'],
                "coa_nama" => $v['coa_nama'],
                "val_kode" => "IDR",
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl_close" => $date,
                "periode" => $periode['bulan'],
                "tahun" => $periode['tahun'],
                "rateidr" => $rateidr['rateidr']
            );
            $ac->insert($arrayInsert);
        }

        $this->FINANCE->AccountingSaldoRL->insertSaldoFromOtherClosing(array(
            "perBulan" => $periode['bulan'],
            "perTahun" => $periode['tahun'],
            "model" => $ac
        ));

        $json = Zend_Json::encode(array("success" => true));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

}

?>