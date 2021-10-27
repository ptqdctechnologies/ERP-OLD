<?php
class Logistic_ReportController extends Zend_Controller_Action
{
    private $db;
    private $periodeOpen;
    private $LOGISTIC;
    private $FINANCE;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->periodeOpen = QDC_Finance_Periode::factory(array("inventoryNotClose" => true))->getCurrentPeriode();
        $models = array(
            "LogisticInputSupplierH",
            "LogisticInputSupplier",
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);
        $models = array(
            "AccountingSaldoStock",
            "MasterPeriode"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function inventoryAction()
    {

    }

    public function showinventoryAction()
    {
        $perkode = $this->_getParam("perkode");

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);
        if (!$periode)
        {
            echo 'No Data found...';
            return false;
        }

        $bulan = $periode['bulan'];
        $tahun = $periode['tahun'];

        $data = $this->FINANCE->AccountingSaldoStock->fetchAll("periode = '$bulan' AND tahun = '$tahun'",array("kode_brg ASC"));
        if ($data)
        {
            $return['data'] = $data->toArray();
        }
        else
        {
            echo 'No Data found...';
            return false;
        }

        $this->view->data = Zend_Json::encode($return);
    }
}
?>