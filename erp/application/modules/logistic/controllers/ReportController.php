<?php

class Logistic_ReportController extends Zend_Controller_Action {

    private $db;
    private $periodeOpen;
    private $LOGISTIC;
    private $FINANCE;
    private $saldoin;
    private $saldoout;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->periodeOpen = QDC_Finance_Periode::factory(array("inventoryNotClose" => true))->getCurrentPeriode();
        $models = array(
            "LogisticInputSupplierH",
            "LogisticInputSupplier",
            "LogisticMaterialReturn",
            "LogisticMaterialReturnH",
            "LogisticMaterialCancel",
            "LogisticMaterialCancelH"
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);
        $models = array(
            "AccountingSaldoStock",
            "MasterPeriode"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function inventoryAction() {
        
    }

    public function showinventoryAction() {
        $periode = $this->_getParam("perkode");
        $gdg_kode = $this->_getParam("gdg_kode");
        $option = $this->_getParam("option");
        $search = $this->_getParam("search");
        if ($option && $search) {
            $where = " AND $option LIKE '%$search%'";
        }

        $data = $this->FINANCE->AccountingSaldoStock->fetchAll("signature = '$periode' AND gdg_kode = '$gdg_kode' $where", array("kode_brg ASC"));
        if ($data) {
            $return['data'] = $data->toArray();
            $name = QDC_User_Ldap::factory(array("uid" => $return['data'][0]['uid']))->getName();
            foreach ($return['data'] as $k => $v) {
                $return['data'][$k]['name'] = $name;
            }
        } else {
            echo 'No Data found...';
            return false;
        }

        $this->view->data = Zend_Json::encode($return);
    }

    public function currentInventoryAction() {
        
    }

    public function showCurrentInventoryAction() {
        $this->view->perkode = $this->_getParam("perkode");
        $this->view->start_date = $this->_getParam("start_date");
        $this->view->end_date = $this->_getParam("end_date");
        $this->view->gdg_kode = $this->_getParam("gdg_kode");
    }

    public function isuppDetailAction() {
        $trano = $this->_getParam("trano");
        $this->view->popup = $this->_getParam("popup");
        $this->view->isprinted = $this->_getParam("isprinted");

        $cek = $this->LOGISTIC->LogisticInputSupplierH->fetchRow("trano = '$trano'");
        if ($cek) {
            $data = $this->LOGISTIC->LogisticInputSupplier->fetchAll("trano = '$trano'");
            $data = $data->toArray();
        }

        $this->view->result = $data;
    }

    public function icanDetailAction() {
        $trano = $this->_getParam("trano");
        $this->view->popup = $this->_getParam("popup");
        $this->view->isprinted = $this->_getParam("isprinted");

        $cek = $this->LOGISTIC->LogisticMaterialCancelH->fetchRow("trano = '$trano'");
        if ($cek) {
            $data = $this->LOGISTIC->LogisticMaterialCancel->fetchAll("trano = '$trano'");
            $data = $data->toArray();
        }

        $this->view->result = $data;
    }

    public function ilovDetailAction() {
        $trano = $this->_getParam("trano");
        $this->view->popup = $this->_getParam("popup");
        $this->view->isprinted = $this->_getParam("isprinted");

        $cek = $this->LOGISTIC->LogisticMaterialReturnH->fetchRow("trano = '$trano'");
        if ($cek) {
            $data = $this->LOGISTIC->LogisticMaterialReturn->fetchAll("trano = '$trano'");
            $data = $data->toArray();
        }

        $this->view->result = $data;
    }

    public function assetreportmenuAction() {
        
    }

    public function viewassetreportAction() {
        $code = $this->getRequest()->getParam('code');
        $tgl = $this->getRequest()->getParam('date');
        $pic = $this->getRequest()->getParam('pic');
        $status = $this->getRequest()->getParam('status');
        $sup_kode = $this->getRequest()->getParam('sup_kode');

        $this->view->code = $code;
        $this->view->tgl = $tgl;
        $this->view->pic = $pic;
        $this->view->status = $status;
        $this->view->sup_kode = $sup_kode;
    }

    public function getviewreportassetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $code = $this->getRequest()->getParam('code');
        $tgl = $this->getRequest()->getParam('tgl');
        $pic = $this->getRequest()->getParam('pic');
        $status = $this->getRequest()->getParam('status');
        $sup_kode = $this->getRequest()->getParam('sup_kode');

        if ($tgl != '' || $tgl != null) {
            $date = date('Y-m-d', strtotime($tgl));
        }

        $search = '';

        if ($code == '') {
            $search = null;
        } else {
            $search = "WHERE FA.code = '$code'";
        }

        if ($date != '' || $date != null) {
            if ($search == null) {
                $search = " WHERE DATE(tgl) = '$date'";
            } else {
                $search .= "AND DATE(tgl) = '$date'";
            }
        }

        if ($pic != '') {
            if ($search == null) {
                $search = "WHERE uid_pic = '$pic' ";
            } else {
                $search .= "AND uid_pic = '$pic'";
            }
        }

        if ($status != '') {
            if ($search == null) {
                $search = "WHERE status = '$status' ";
            } else {
                $search .= "AND status = '$status'";
            }
        }

        if ($sup_kode != '') {
            if ($search == null) {
                $search = "WHERE FA.sup_kode = '$sup_kode' ";
            } else {
                $search .= "AND FA.sup_kode = '$sup_kode'";
            }
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 20;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

//        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM accounting_journal $search ORDER BY $sort $dir LIMIT $offset,$limit";
        $sql = "SELECT SQL_CALC_FOUND_ROWS FA.*,MS.sup_nama,MF.description FROM (logistic_fixed_asset_status FA
                LEFT JOIN master_suplier MS ON FA.sup_kode = MS.sup_kode)
                LEFT JOIN master_fixed_asset MF ON MF.code = FA.code $search
                ORDER BY tgl desc";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

//        $return['data'] = $arfs;

        $data['data'] = $fetch;
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function printCurrentInventoryAction() {
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
            "query" => $where
        ));

        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['qty'] = floatval($v['qty']);
            $data['data'][$k]['qtyIn'] = floatval($v['qtyIn']);
            $data['data'][$k]['qtyOut'] = floatval($v['qtyOut']);
        }

        $signature = $this->_helper->getHelper('token')->generateDocumentSignature();
        $params = array(
            "signature" => $signature
        );

        QDC_Jasper_Report::factory(
                array(
                    "reportType" => 'pdf',
                    "arrayData" => $data['data'],
                    "arrayParams" => $params,
                    "fileName" => "stokcard.jrxml",
                    "outputName" => 'stokcard',
                    "dataSource" => 'NoDataSource'
                )
        )->generate();
    }

    public function gencardstockAction() {
        
    }
    
    public function genopnamestockAction() {
        
    }

    public function detcardstockAction() {
        
    }

    public function pricehistoryAction() {
        
    }

    public function viewpricehistoryAction() {
        $request = $this->getRequest();

        $code = $request->getParam('kode_brg');
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 50;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'DESC';

        $export = ($this->_getParam("export") == 'true') ? true : false;

        $limit = 50;

        //Paging Stuff
        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * $limit;

        $customer = new Default_Models_BarangHistories();

        if ($code != '')
            $return = $customer->getAllDistinct($code, '', $offset, $limit);

        if (!$export) {

            $this->view->result = $return['posts'];

            //Paging stuff.
            $this->view->limitPerPage = $limit;
            $this->view->totalResult = $return['count'];
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
            $this->view->pagingParam = array("kode_brg" => $code);
        } else {

            $arrayData = array();
            foreach ($return['posts'] as $k => $v) {
                $arrayData[] = array(
                    "trano" => $v['tra_no'],
                    "tgl" => date("d M Y", strtotime($v['tgl'])),
                    "Product ID" => $v['brg_kode'],
                    "Description" => $v['brg_nama'],
                    "Valuta" => $v['val_kode'],
                    "Uom" => $v['sat_kode'],
                    "Price" => floatval($v['harga']),
                    "Vendor" => $v['sup_kode'],
                    "Vendor Name" => $v['sup_nama'],
                    "Vendor City" => $v['master_kota']
                );
            }
            
          
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Material List Price History"
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($arrayData)->toExcel5Stream();
        }
    }

    public function cardstockAction() {
        
    }

    public function genStockCardAction() {
        $gdg_kode = $this->_getParam("gdg_kode");
        $kode_brg = $this->_getParam("kode_brg");
        $perkode = $this->_getParam("periode");
        $viewtype = $this->_getParam("company");
        $title = ($this->_getParam("company") == 'true') ? "Stock" : "Opname";
        
        $this->view->gdg_kode = $gdg_kode;
        $this->view->kode_brg = $kode_brg;
        $this->view->perkode = $perkode;
        $this->view->viewtype = $viewtype;
        $this->view->title = $title;
    }
    
    public function getdatastockcardAction() {
        $this->_helper->viewRenderer->setNoRender();
        $gdg_kode = $this->_getParam("gdg_kode");
        $kode_brg = $this->_getParam("kode_brg");
        $perkode = $this->_getParam("periode");
        $viewtype = ($this->_getParam("type") == 'true') ? true : false;
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'kode_brg';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        
        $data = QDC_Inventory_Stock::factory()->getStockAll(array(
            "kode_brg" => $kode_brg,
            "gdg_kode" => $gdg_kode,
            "periode" => $perkode,
            "viewtype" => $viewtype,
            "offset" => $offset,
            "limit" => $limit
        ));
        
        $result['posts'] = $data['data'];
        $result['count'] = $data['count'];
        
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }

    public function detStockCardAction() {

        $gdg_kode = $this->_getParam("gdg_kode");
        $kode_brg = $this->_getParam("kode_brg");
        $perkode = $this->_getParam("periode");

        $current = $this->_getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->_getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $this->_getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 100;

        $data = QDC_Inventory_Stock::factory()->getStock(array(
            "kode_brg" => $kode_brg,
            "gdg_kode" => $gdg_kode,
            "periode" => $perkode,
            "offset" => $offset,
            "limit" => 100
        ));


        $saldo = $data['data']['0']['saldo'];
        $tmp = array();

        $flagSaldo;
        if ($saldo != '')
            $flagSaldo = true;


        foreach ($data['data']as $k => $v) {

            if ($v['saldo'] == '0') {

                if ($v['masuk'] !== '0') {
                    $data['data'][$k]['balance'] = $saldo + $v['masuk'];
                    $saldo = $data['data'][$k]['balance'];
                }

                if ($v['keluar'] !== '0') {
                    $data['data'][$k]['balance'] = $saldo - $v['keluar'];
                    $saldo = $data['data'][$k]['balance'];
                }
            }
        }

        $this->SaldoAwal = $data['data']['0']['saldo'];



        $this->view->flagSaldo = $flagSaldo;
        $this->view->result = $data['data'];
        $this->view->productid = $data['data']['1']['kode_brg'];
        $this->view->productname = $data['data']['1']['nama_brg'];
        $this->view->limitPerPage = 100;
        $this->view->totalResult = $data['count'];
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
        $this->view->pagingParam = array(
            "gdg_kode" => $gdg_kode,
            "kode_brg" => $kode_brg,
            "periode" => $perkode,
        );
    }

    public function masterListNewProjectAction() {
        
    }

}

?>