<?php
class Finance_ClosingController extends Zend_Controller_Action
{
    private $FINANCE;
    private $periodeOpen;

    public function init()
    {
        $this->FINANCE = QDC_Model_Finance::init(array(
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingJurnalBank",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "AdjustingJournal"
        ));

        $this->periodeOpen = QDC_Finance_Periode::factory(array("notClose" => true))->getCurrentPeriode();
    }

    public function closingAction()
    {
        if ($this->periodeOpen)
        {
            $this->view->year = $this->periodeOpen['tahun'];
            $this->view->perkode = $this->periodeOpen['perkode'];
            $this->view->month = date("F",strtotime($this->periodeOpen['tahun'] . "-" . $this->periodeOpen['bulan'] . "-01"));
            $this->view->tgl_awal = date("d M Y",strtotime($this->periodeOpen['tgl_awal']));
            $this->view->tgl_akhir = date("d M Y",strtotime($this->periodeOpen['tgl_akhir']));
        }
    }

    public function doclosingAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");

        if (!$perkode)
        {
            echo "{success: false, msg: 'No Periode to Close'";
            return false;
        }

        $result = QDC_Finance_Periode::factory()->closing($perkode);

        echo "{success: true}";
    }

    public function getcoaforclosingAction()
    {
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

        if ($type && $search)
        {
            $where = " AND $type LIKE '%$search%'";
        }

        $coas = array();
        $ap = $this->FINANCE->AccountingCloseAP->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($ap)
        {
            $ap = $ap->toArray();
        }

        $ar = $this->FINANCE->AccountingCloseAR->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($ar)
        {
            $ar = $ar->toArray();
        }

        $bank = $this->FINANCE->AccountingJurnalBank->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0 $where");
        if ($bank)
            $bank = $bank->toArray();

        $general = $this->FINANCE->AdjustingJournal->fetchAll("tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND stsclose = 0 $where");
        if ($general)
            $general = $general->toArray();

        $coas = QDC_Common_Array::factory()->merge(array($ap,$ar,$bank,$general));

        $id = 1;
        $count = count($coas);
        $data = array();

        foreach($coas as $k => $v)
        {
            if ($k >= $offset && $k < ($limit + $offset))
            {
                $coas[$k]['id'] = $id;
                $data[] = $coas[$k];
                $id++;
            }
            else
                unset($coas[$k]);
        }

        $json = Zend_Json::encode(array("data" => $data, "count" => $count));
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>