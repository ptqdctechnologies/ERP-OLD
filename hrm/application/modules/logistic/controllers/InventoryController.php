<?php

class Logistic_InventoryController extends Zend_Controller_Action
{
    private $db;
    private $periodeOpen;
    private $LOGISTIC;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->periodeOpen = QDC_Finance_Periode::factory(array("inventoryNotClose" => true))->getCurrentPeriode();
        $models = array(
            "LogisticInputSupplierH",
            "LogisticInputSupplier",
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);
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

    public function closingmenuAction()
    {

    }

    public function getitemsforclosingAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");
        $tahun = $this->getRequest()->getParam("tahun");
        $bulan = $this->getRequest()->getParam("bulan");
        $search = $this->getRequest()->getParam("search");
        $type = $this->getRequest()->getParam("type");

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $data = QDC_Inventory_Periode::factory()->getCurrentItems(array(
            "perKode" => $this->periodeOpen['perkode'],
            "offset" => $offset,
            "limit" => $limit
        ));

        $prevData = QDC_Inventory_Periode::factory()->getPrevItems();
        if (!$prevData)
        {
            foreach($data['data'] as $k => $v)
            {
                $data['data'][$k]['newItem'] = true;
                $data['data'][$k]['saldoQty'] = 0;
            }
        }
        else
        {
            foreach($data['data'] as $k => $v)
            {
                $kodeBrg = $v['kode_brg'];
                if ($prevData[$kodeBrg])
                {
                    $data['data'][$k]['newItem'] = false;
                    $data['data'][$k]['saldoQty'] = $prevData[$kodeBrg]['qty'];
                }
                else
                {
                    $data['data'][$k]['newItem'] = true;
                    $data['data'][$k]['saldoQty'] = 0;
                }
            }
        }

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doclosingAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $perkode = $this->getRequest()->getParam("perkode");

        if (!$perkode)
        {
            echo "{success: false, msg: 'No Periode to Close'}";
            return false;
        }

        $result = QDC_Inventory_Periode::factory()->closing($perkode);

        echo "{success: true}";
    }


}
?>