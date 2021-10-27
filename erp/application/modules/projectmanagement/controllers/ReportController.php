<?php

class ProjectManagement_ReportController extends Zend_Controller_Action {

    private $db;
    private $session;
    private $projectHelper;
    private $project;
    private $trans;
    private $util;
    private $barang;
    private $budget;
    private $quantity;
    private $memcache;
    private $memcacheHelper;
    private $regisco;
    private $cost;
    private $rpi;
    private $DEFAULT;
    private $budgt;
    private $boq;
    
    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->budget = new Default_Models_Budget();
        $this->budgetCFS = new Default_Models_BudgetCFS();
        $this->session = new Zend_Session_Namespace('login');
        $this->projectHelper = $this->_helper->getHelper('project');
        $this->memcacheHelper = $this->_helper->getHelper('memcache');
        $this->util = Zend_Controller_Action_HelperBroker::getStaticHelper('transaction_util');
        $this->barang = new Default_Models_MasterBarang();
        $this->project = new Default_Models_MasterProject();
        $this->trans = $this->_helper->getHelper('transaction');
        $this->afe = new ProjectManagement_Models_AFE();
        $this->afes = new ProjectManagement_Models_AFESaving();
        $this->afeh = new ProjectManagement_Models_AFEh();
        $this->invoiced = new Finance_Models_InvoiceDetail();
        $this->rpi = new Finance_Models_PaymentRPI();
        $this->paymentInvoice = new Finance_Models_PaymentInvoice();
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->regisco = new Sales_Models_Regisco();
        $this->cost = new Default_Models_Cost();
        $this->budgt = $this->_helper->getHelper('Budget');
        $this->boq = new Default_Models_BOQ3();

        $model = array(
            "MasterRoleSite"
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function generalAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        $summary = ($this->getRequest()->getParam("summary") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));
        $fromCache = false;
        $cacheID = "REPORT_GENERAL_" . md5($prjKode . $sitKode);
        $cacheTimeID = $cacheID . "_TIME";

        if (!$this->memcache->test($cacheID)) {
            $boq3 = $this->budget->getBoq3('all-current', $prjKode, $sitKode);

            $this->memcache->save($boq3, $cacheID, array('REPORT'));
            //cache time generated...
            $time = array(
                "generate" => $today->format("d M Y H:i:s"),
                "expire" => $expire->format("d M Y H:i:s")
            );
            $this->memcache->save($time, $cacheTimeID, array('REPORT'));
        } else {
            $boq3 = $this->memcache->load($cacheID);
            $time = $this->memcache->load($cacheTimeID);
            $fromCache = true;
        }

        $total = array();

        foreach ($boq3 as $key => $val) {
            $kodeBrg = $val['kode_brg'];
            $workId = $val['workid'];
            
            $total['boq3']['IDR'] += $boq3[$key]['totalHargaIDR'];
            $total['boq3']['USD'] += $boq3[$key]['totalHargaUSD'];

            //PR
            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($pr) {
                $boq3[$key]['pr']['qty'] = +$pr['qty'];
                $boq3[$key]['pr']['IDR'] = +$pr['totalIDR'];
                $boq3[$key]['pr']['USD'] = +$pr['totalHargaUSD'];
            } else {
                $boq3[$key]['pr']['qty'] = 0;
                $boq3[$key]['pr']['IDR'] = 0;
                $boq3[$key]['pr']['USD'] = 0;
            }
            $total['pr']['IDR'] += $boq3[$key]['pr']['IDR'];
            $total['pr']['USD'] += $boq3[$key]['pr']['USD'];
            //PO
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($po) {
                $boq3[$key]['po']['qty'] = $po['qty'];
                $boq3[$key]['po']['IDR'] = $po['totalIDR'];
                $boq3[$key]['po']['USD'] = $po['totalHargaUSD'];
            } else {
                $boq3[$key]['po']['qty'] = 0;
                $boq3[$key]['po']['IDR'] = 0;
                $boq3[$key]['po']['USD'] = 0;
            }
            $total['po']['IDR'] += $boq3[$key]['po']['IDR'];
            $total['po']['USD'] += $boq3[$key]['po']['USD'];
            //RPI
            $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($rpi) {
                $boq3[$key]['rpi']['qty'] = $rpi['qty'];
                $boq3[$key]['rpi']['IDR'] = $rpi['totalIDR'];
                $boq3[$key]['rpi']['USD'] = $rpi['totalHargaUSD'];
            } else {
                $boq3[$key]['rpi']['qty'] = 0;
                $boq3[$key]['rpi']['IDR'] = 0;
                $boq3[$key]['rpi']['USD'] = 0;
            }
            $total['rpi']['IDR'] += $boq3[$key]['rpi']['IDR'];
            $total['rpi']['USD'] += $boq3[$key]['rpi']['USD'];
            //Payment RPI
//            $rpid = $this->budget->getPaymentRPIdNonCFS($prjKode,$sitKode,$workId,$kodeBrg);
//            if ($rpid)
//            {
//                $boq3[$key]['prpi']['qty'] = $rpid['qty'];
//                $boq3[$key]['prpi']['IDR'] = $rpid['totalIDR'];
//                $boq3[$key]['prpi']['USD'] = $rpid['totalHargaUSD'];
//            }
//            else
//            {
//                $boq3[$key]['prpi']['qty'] = 0;
//                $boq3[$key]['prpi']['IDR'] = 0;
//                $boq3[$key]['prpi']['USD'] = 0;
//            }
            //ARF
            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($arf) {
                $boq3[$key]['arf']['qty'] = $arf['qty'];
                $boq3[$key]['arf']['IDR'] = $arf['totalIDR'];
                $boq3[$key]['arf']['USD'] = $arf['totalHargaUSD'];
            } else {
                $boq3[$key]['arf']['qty'] = 0;
                $boq3[$key]['arf']['IDR'] = 0;
                $boq3[$key]['arf']['USD'] = 0;
            }
            $total['arf']['IDR'] += $boq3[$key]['arf']['IDR'];
            $total['arf']['USD'] += $boq3[$key]['arf']['USD'];
            //ASF
            $asf = $this->quantity->getAsfddQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($asf) {
                $boq3[$key]['asf']['qty'] = $asf['qty'];
                $boq3[$key]['asf']['IDR'] = $asf['totalIDR'];
                $boq3[$key]['asf']['USD'] = $asf['totalHargaUSD'];
            } else {
                $boq3[$key]['asf']['qty'] = 0;
                $boq3[$key]['asf']['IDR'] = 0;
                $boq3[$key]['asf']['USD'] = 0;
            }
            $total['asf']['IDR'] += $boq3[$key]['asf']['IDR'];
            $total['asf']['USD'] += $boq3[$key]['asf']['USD'];
            //ASF Cancel
            $asfc = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($asfc) {
                $boq3[$key]['asfc']['qty'] = $asfc['qty'];
                $boq3[$key]['asfc']['IDR'] = $asfc['totalIDR'];
                $boq3[$key]['asfc']['USD'] = $asfc['totalHargaUSD'];
            } else {
                $boq3[$key]['asfc']['qty'] = 0;
                $boq3[$key]['asfc']['IDR'] = 0;
                $boq3[$key]['asfc']['USD'] = 0;
            }
            $total['asfc']['IDR'] += $boq3[$key]['asfc']['IDR'];
            $total['asfc']['USD'] += $boq3[$key]['asfc']['USD'];

            //DOR
            $dor = $this->quantity->getDorQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($dor) {
                $boq3[$key]['dor']['qty'] = $dor['qty'];
                $boq3[$key]['dor']['IDR'] = $dor['totalIDR'];
                $boq3[$key]['dor']['USD'] = $dor['totalHargaUSD'];
            } else {
                $boq3[$key]['dor']['qty'] = 0;
                $boq3[$key]['dor']['IDR'] = 0;
                $boq3[$key]['dor']['USD'] = 0;
            }

            //DO
            $do = $this->quantity->getDoQuantity($prjKode, $sitKode, $workId, $kodeBrg);
            if ($do) {
                $boq3[$key]['do']['qty'] = $do['qty'];
                $boq3[$key]['do']['IDR'] = $do['totalIDR'];
                $boq3[$key]['do']['USD'] = $do['totalHargaUSD'];
            } else {
                $boq3[$key]['do']['qty'] = 0;
                $boq3[$key]['do']['IDR'] = 0;
                $boq3[$key]['do']['USD'] = 0;
            }
        }

        //Salary
        $sal = $this->quantity->getSalQuantity($prjKode);
        if ($sal) {
            $this->view->salary = $sal['totalIDR'];
        } else {
            $this->view->salary = 0;
        }

        if ($fromCache)
            $this->view->time = $time;
        $this->view->result = $boq3;
        $this->view->total = $total;

        if ($print) {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $totalbudget = 0;
            $totalbudgetUSD = 0;
            $totalPRIDR = 0;
            $totalPRUSD = 0;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCIDR = 0;
            $totalASFCUSD = 0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Product Name" => $v['nama_brg'],
                    "Product ID" => $v['kode_brg'],
                    "Work Name" => $v['workname'],
                    "Work ID" => $v['workid'],
                    "Uom" => $v['sat_kode'],
                    "Budget IDR" => floatval($v['totalHargaIDR']),
                    "Budget USD" => floatval($v['totalHargaUSD']),
                    "PR IDR" => floatval($v['pr']['IDR']),
                    "PR USD" => floatval($v['pr']['USD']),
                    "PO IDR" => floatval($v['po']['IDR']),
                    "PO USD" => floatval($v['po']['USD']),
                    "RPI IDR" => floatval($v['rpi']['IDR']),
                    "RPI USD" => floatval($v['rpi']['USD']),
                    "ARF IDR" => floatval($v['arf']['IDR']),
                    "ARF USD" => floatval($v['arf']['USD']),
                    "ASF IDR" => floatval($v['asf']['IDR']),
                    "ASF USD" => floatval($v['asf']['USD']),
                    "ASF Cancel IDR" => floatval($v['asfc']['IDR']),
                    "ASF Cancel USD" => floatval($v['asfc']['USD'])
                );
                $no++;

                $totalbudget += floatval($v['totalHargaIDR']);
                $totalbudgetUSD += floatval($v['totalHargaUSD']);
                $totalPRIDR += floatval($v['pr']['IDR']);
                $totalPRUSD += floatval($v['pr']['USD']);
                $totalPOIDR += floatval($v['po']['IDR']);
                $totalPOUSD += floatval($v['po']['USD']);
                $totalRPIIDR += floatval($v['rpi']['IDR']);
                $totalRPIUSD += floatval($v['rpi']['USD']);
                $totalARFIDR += floatval($v['arf']['IDR']);
                $totalARFUSD += floatval($v['arf']['USD']);
                $totalASFIDR += floatval($v['asf']['IDR']);
                $totalASFUSD += floatval($v['asf']['USD']);
                $totalASFCIDR += floatval($v['asfc']['IDR']);
                $totalASFCUSD += floatval($v['asfc']['USD']);
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Product Name" => "Total",
                "Product ID" => "",
                "Work Name" => "",
                "Work ID" => "",
                "Uom" => "",
                "Budget IDR" => $totalbudget,
                "Budget USD" => $totalbudgetUSD,
                "PR IDR" => $totalPRIDR,
                "PR USD" => $totalPRUSD,
                "PO IDR" => $totalPOIDR,
                "PO USD" => $totalPOUSD,
                "RPI IDR" => $totalRPIIDR,
                "RPI USD" => $totalRPIUSD,
                "ARF IDR" => $totalARFIDR,
                "ARF USD" => $totalARFUSD,
                "ASF IDR" => $totalASFIDR,
                "ASF USD" => $totalASFUSD,
                "ASF Cancel IDR" => $totalASFCIDR,
                "ASF Cancel USD" => $totalASFCUSD
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Report " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        13 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        14 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        15 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        16 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        17 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        18 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        19 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function boq3Action() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        $type = $this->getRequest()->getParam("type");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
       
       
//        $boq3 = $this->budget->getBOQ3CurrentPerItemNonPeacemeal($prjKode, $sitKode,$valKode,$workId,$kodeBrg);
        $boq3 = $this->boq->getAfed($prjKode, $sitKode);

        $total = array();

         foreach ($boq3 as $key => $val) {
            $workId = $val['workid'];
            $kodeBrg = $val['kode_brg'];
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            $boq3[$key]['sat_kode'] = $barang['sat_kode'];
            $boq3[$key]['qty'] = $val['qty'];
            $boq3[$key]['kode_brg'] = $val['kode_brg'];
            $boq3[$key]['harga'] = $val['harga'];
            $boq3[$key]['total'] = $val['total'];
            $boq3[$key]['val_kode'] = $val['val_kode'];
            $total['total'] += $boq3[$key]['total'];
            
            // Get All Requests of The Item
            $requests=$this->boq->totalRequestsV2($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $boq3[$key]['totalRequests']= $requests['amount']==null ? 0 : $requests['amount'];
            
        
        }

         if (!$print) {
            $this->view->result = $boq3;
            $this->view->total = $total;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            
            $total = 0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Work ID" => $v['workid'],
                    "Work Name" => $v['workname'],
                    "Product ID" => $v['kode_brg'],
                    "Description" => $v['nama_brg'],
                    "Qty" => floatval($v['qty']),
                    "Uom" => $v['sat_kode'],
                    "Price" => floatval($v['harga']),
                    "Total" => floatval($v['total']),
                    "Currency" => $v['val_kode'],
                    
                );
                $no++;
            }
            
            
            $newData[] = array(
                "No" => '',
                "Work ID" => "",
                "Work Name" => "",
                "Product ID" => "",
                "Description" => "",
                "Qty" => "",
                "Uom" => "",
                "Price" => "",
                "Total" => "",
                "Currency" => "",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "BOQ3 Report(Detail) " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
    }
    
    public function boq3originalAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        $type = $this->getRequest()->getParam("type");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
       
      
        $boq3 = $this->boq->getBoq3ori($prjKode, $sitKode);
     

        $total = array();

        foreach ($boq3 as $key => $val) {
            $workId = $val['workid'];
            $kodeBrg = $val['kode_brg'];
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            $boq3[$key]['sat_kode'] = $barang['sat_kode'];
            $boq3[$key]['qty'] = $val['qty'];
            $boq3[$key]['harga'] = $val['harga'];
            $boq3[$key]['total'] = $val['total'];
            $boq3[$key]['val_kode'] = $val['val_kode'];
            $boq3[$key]['workname'] = $val['workname'];
            $boq3[$key]['cfs_kode'] = $val['cfs_kode'];
            $boq3[$key]['cfs_nama'] = $val['cfs_nama'];
            $total['total'] += $boq3[$key]['total'];
        }

       //sini blm
        if (!$print) {
            $this->view->result = $boq3;
            $this->view->total = $total;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            
            $total = 0;
            $default=0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Work ID" => $v['workid'],
                    "Work Name" => $v['workname'],
                    "Product ID" => $v['kode_brg'],
                    "Material Name" => $v['nama_brg'],
                    "Qty" => floatval($v['qty']),
                    "Uom" => $v['sat_kode'],
                    "Spec" => $v[''],
                    "Unit Price" => floatval($v['harga']),
                    "Total Price" => floatval($v['total']),
                    "Currency" => $v['val_kode'],
                    "CFS Kode" => $v['cfs_kode'],
                    "CFS Nama" => $v['cfs_nama'],
                    "Days" => $default,
                    
                );
                $no++;

                $total += floatval($v['total']);
            }
            
            
            $newData[] = array(
                "No" => '',
                "Work ID" => "",
                "Work Name" => "",
                "Product ID" => "",
                "Mateial Name" => "",
                "UOM" => "",
                "Spec" => "",
                "QTY" => "",
                "Unit Price" => "",
                "Total Price" => "",
                "Currency" => "",
                "CFS Kode" => "",
                "CFS Nama" => "",
                "Days" => "",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "BOQ3 Report(Original) " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }

        
    }
      public function boq3actualAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        $type = $this->getRequest()->getParam("type");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
       
      
        $boq3 = $this->boq->getAfe($prjKode, $sitKode, $workId, $kodeBrg);
     

        $total = array();

        foreach ($boq3 as $key => $val) {
            $workId = $val['workid'];
            $kodeBrg = $val['kode_brg'];
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            $boq3[$key]['sat_kode'] = $barang['sat_kode'];
            $boq3[$key]['qty'] = $val['qty'];
            $boq3[$key]['harga'] = $val['harga'];
            $boq3[$key]['total'] = $val['total'];
            $boq3[$key]['val_kode'] = $val['val_kode'];
            $boq3[$key]['workname'] = $val['workname'];
            $boq3[$key]['cfs_kode'] = $val['cfs_kode'];
            $boq3[$key]['cfs_nama'] = $val['cfs_nama'];
            $total['total'] += $boq3[$key]['total'];
        }

        if (!$print) {
            $this->view->result = $boq3;
            $this->view->total = $total;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            
            $total = 0;
            $default=0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Work ID" => $v['workid'],
                    "Work Name" => $v['workname'],
                    "Product ID" => $v['kode_brg'],
                    "Material Name" => $v['nama_brg'],
                    "Qty" => floatval($v['qty']),
                    "Spec" => $v[''],
                    "Uom" => $v['sat_kode'],
                    "Unit Price" => floatval($v['harga']),
                    "Total Price" => floatval($v['total']),
                    "Currency" => $v['val_kode'],
                    "CFS Kode" => $v['cfs_kode'],
                    "CFS Nama" => $v['cfs_nama'],
                    "Days" => $default,
                );
                $no++;

                $total += floatval($v['total']);
            }
            
            
            $newData[] = array(
                "No" => '',
                "Work ID" => "",
                "Work Name" => "",
                "Product ID" => "",
                "Mateial Name" => "",
                "UOM" => "",
                "Spec" => "",
                "QTY" => "",
                "Unit Price" => "",
                "Total Price" => "",
                "Currency" => "",
                "CFS Kode" => "",
                "CFS Nama" => "",
                "Days" => "",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "BOQ3 Report(Actual) " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
    }
    
    private function getBoq3Summary($prjKode = '', $sitKode = '', $overhead = false, $isReport = false) {
        $boq3 = array();
        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));
        $fromCache = false;
        $cacheID = "REPORT_GENERAL_SUMMARY_" . (($overhead) ? "_OVERsHEAD_" : "") . md5($prjKode . $sitKode);
        $cacheTimeID = $cacheID . "_TIME";

        if (!$this->memcache->test($cacheID)) {
            if (!$overhead)
                $boq3 = $this->budget->getBoq3('summary-current', $prjKode, $sitKode);
            else {
                if ($isReport)
                    $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode, '', 'summary-current');
                else {
                $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode);
                $boq3 = $boq3[0];
            }
//                
            }

            $this->memcache->save($boq3, $cacheID, array('REPORT'));
            //cache time generated...
            $time = array(
                "generate" => $today->format("d M Y H:i:s"),
                "expire" => $expire->format("d M Y H:i:s")
            );
            $this->memcache->save($time, $cacheTimeID, array('REPORT'));
        } else {
            $boq3 = $this->memcache->load($cacheID);
            $time = $this->memcache->load($cacheTimeID);
            $fromCache = true;
        }

        $boq3 = array(
            "boq3" => array(
                "IDR" => $boq3['totalIDR'],
                "USD" => $boq3['totalHargaUSD'],
            )
        );

        //PR
        $pr = $this->quantity->getPrQuantity($prjKode, $sitKode);
        if ($pr) {
            $boq3['pr']['IDR'] = $pr['totalIDR'];
            $boq3['pr']['USD'] = $pr['totalHargaUSD'];
        } else {
            $boq3['pr']['IDR'] = 0;
            $boq3['pr']['USD'] = 0;
        }

        //PO
        $po = $this->quantity->getPoQuantity($prjKode, $sitKode);
        if ($po) {
            $boq3['po']['IDR'] = $po['totalIDR'];
            $boq3['po']['USD'] = $po['totalHargaUSD'];
        } else {
            $boq3['po']['IDR'] = 0;
            $boq3['po']['USD'] = 0;
        }
        //RPI
        $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode);
        if ($rpi) {
            $boq3['rpi']['IDR'] = $rpi['totalIDR'];
            $boq3['rpi']['USD'] = $rpi['totalHargaUSD'];
        } else {
            $boq3['rpi']['IDR'] = 0;
            $boq3['rpi']['USD'] = 0;
        }

        //ARF
        $arf = $this->quantity->getArfQuantity($prjKode, $sitKode);
        if ($arf) {
            $boq3['arf']['IDR'] = $arf['totalIDR'];
            $boq3['arf']['USD'] = $arf['totalHargaUSD'];
        } else {
            $boq3['arf']['IDR'] = 0;
            $boq3['arf']['USD'] = 0;
        }

        //ASF
        $asf = $this->quantity->getAsfddQuantity($prjKode, $sitKode);
        if ($asf) {
            $boq3['asf']['IDR'] = $asf['totalIDR'];
            $boq3['asf']['USD'] = $asf['totalHargaUSD'];
        } else {
            $boq3['asf']['IDR'] = 0;
            $boq3['asf']['USD'] = 0;
        }

        //ASF Cancel
        $asfc = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode);
        if ($asfc) {
            $boq3['asfc']['IDR'] = $asfc['totalIDR'];
            $boq3['asfc']['USD'] = $asfc['totalHargaUSD'];
        } else {
            $boq3['asfc']['IDR'] = 0;
            $boq3['asfc']['USD'] = 0;
        }

        //DOR
        $dor = $this->quantity->getDorQuantity($prjKode, $sitKode);
        if ($dor) {
            $boq3['dor']['IDR'] = $dor['totalIDR'];
            $boq3['dor']['USD'] = $dor['totalHargaUSD'];
        } else {
            $boq3['dor']['IDR'] = 0;
            $boq3['dor']['USD'] = 0;
        }

        //DO
        $do = $this->quantity->getDoQuantity($prjKode, $sitKode);
        if ($do) {
            $boq3['do']['IDR'] = $do['totalIDR'];
            $boq3['do']['USD'] = $do['totalHargaUSD'];
        } else {
            $boq3['do']['IDR'] = 0;
            $boq3['do']['USD'] = 0;
        }

        return array(
            "boq3" => $boq3,
            "fromCache" => $fromCache,
            "time" => $time
        );
    }

    public function generalSummaryAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        $summary = ($this->getRequest()->getParam("summary") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;

        $theData = array();
        $total = array();
        if ($sitKode == '') {
            $sites = new Default_Models_MasterSite();
            $s = $sites->getList($prjKode);

            foreach ($s as $k => $v) {
                $sitKode = $v['sit_kode'];
                $result = $this->getBoq3Summary($prjKode, $sitKode);

                $theData[$sitKode] = $result['boq3'];

                $res = $result['boq3'];
                //Total
                $total['boq3']['IDR'] += $res['boq3']['IDR'];
                $total['boq3']['USD'] += $res['boq3']['USD'];
                $total['pr']['IDR'] += $res['pr']['IDR'];
                $total['pr']['USD'] += $res['pr']['USD'];
                $total['po']['IDR'] += $res['po']['IDR'];
                $total['po']['USD'] += $res['po']['USD'];
                $total['rpi']['IDR'] += $res['rpi']['IDR'];
                $total['rpi']['USD'] += $res['rpi']['USD'];
                $total['arf']['IDR'] += $res['arf']['IDR'];
                $total['arf']['USD'] += $res['arf']['USD'];
                $total['asf']['IDR'] += $res['asf']['IDR'];
                $total['asf']['USD'] += $res['asf']['USD'];
                $total['asfc']['IDR'] += $res['asfc']['IDR'];
                $total['asfc']['USD'] += $res['asfc']['USD'];


                $theData[$sitKode]['sit_nama'] = $sites->getSiteName($prjKode, $sitKode);
                $fromCache = $result['fromCache'];
                $time = $result['time'];
            }
        }
//        QDC_Kint_Dump::dump($theData);die;
        //Salary
        $sal = $this->quantity->getSalQuantity($prjKode);
        if ($sal) {
            $this->view->salary = $sal['totalIDR'];
        } else {
            $this->view->salary = 0;
        }

        if ($fromCache)
            $this->view->time = $time;
        $this->view->result = $theData;
        $this->view->total = $total;

        if ($print) {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $totalbudget = 0;
            $totalbudgetUSD = 0;
            $totalPRIDR = 0;
            $totalPRUSD = 0;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCIDR = 0;
            $totalASFCUSD = 0;

            foreach ($theData as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Site" => $k . " - " . $v['sit_nama'],
                    "Budget IDR" => floatval($v['boq3']['IDR']),
                    "Budget USD" => floatval($v['boq3']['USD']),
                    "PR IDR" => floatval($v['pr']['IDR']),
                    "PR USD" => floatval($v['pr']['USD']),
                    "PO IDR" => floatval($v['po']['IDR']),
                    "PO USD" => floatval($v['po']['USD']),
                    "RPI IDR" => floatval($v['rpi']['IDR']),
                    "RPI USD" => floatval($v['rpi']['USD']),
                    "ARF IDR" => floatval($v['arf']['IDR']),
                    "ARF USD" => floatval($v['arf']['USD']),
                    "ASF IDR" => floatval($v['asf']['IDR']),
                    "ASF USD" => floatval($v['asf']['USD']),
                    "ASF Cancel IDR" => floatval($v['asfc']['IDR']),
                    "ASF Cancel USD" => floatval($v['asfc']['USD'])
                );
                $no++;

                $totalbudget += floatval($v['boq3']['IDR']);
                $totalbudgetUSD += floatval($v['boq3']['USD']);
                $totalPRIDR += floatval($v['pr']['IDR']);
                $totalPRUSD += floatval($v['pr']['USD']);
                $totalPOIDR += floatval($v['po']['IDR']);
                $totalPOUSD += floatval($v['po']['USD']);
                $totalRPIIDR += floatval($v['rpi']['IDR']);
                $totalRPIUSD += floatval($v['rpi']['USD']);
                $totalARFIDR += floatval($v['arf']['IDR']);
                $totalARFUSD += floatval($v['arf']['USD']);
                $totalASFIDR += floatval($v['asf']['IDR']);
                $totalASFUSD += floatval($v['asf']['USD']);
                $totalASFCIDR += floatval($v['asfc']['IDR']);
                $totalASFCUSD += floatval($v['asfc']['USD']);
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Site" => "Total",
                "Budget IDR" => $totalbudget,
                "Budget USD" => $totalbudgetUSD,
                "PR IDR" => $totalPRIDR,
                "PR USD" => $totalPRUSD,
                "PO IDR" => $totalPOIDR,
                "PO USD" => $totalPOUSD,
                "RPI IDR" => $totalRPIIDR,
                "RPI USD" => $totalRPIUSD,
                "ARF IDR" => $totalARFIDR,
                "ARF USD" => $totalARFUSD,
                "ASF IDR" => $totalASFIDR,
                "ASF USD" => $totalASFUSD,
                "ASF Cancel IDR" => $totalASFCIDR,
                "ASF Cancel USD" => $totalASFCUSD
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Report Summary " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        13 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        14 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        15 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function showgeneralAction() {
        
    }

    public function showgeneraloverheadAction() {
        
    }
    
    public function showboq3Action() {
        
    }

    public function generalOverheadAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));
        $fromCache = false;
        $cacheID = "REPORT_GENERAL_OVERHEAD_" . md5($prjKode . $sitKode);
        $cacheTimeID = $cacheID . "_TIME";

//        if (!$this->memcache->test($cacheID))
//        {

        $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode);

        $this->memcache->save($boq3, $cacheID, array('REPORT'));
        //cache time generated...
        $time = array(
            "generate" => $today->format("d M Y H:i:s"),
            "expire" => $expire->format("d M Y H:i:s")
        );
        $this->memcache->save($time, $cacheTimeID, array('REPORT'));
//        }
//        else
//        {
//            $boq3 = $this->memcache->load($cacheID);
//            $time = $this->memcache->load($cacheTimeID);
//            $fromCache = true;
//        }

        $total = array();

        foreach ($boq3 as $key => $val) {
            $workId = $val['budgetid'];
            $total['boq3']['IDR'] += $boq3[$key]['totalHargaIDR'];
            $total['boq3']['USD'] += $boq3[$key]['totalHargaUSD'];

            //PR
            $pr = $this->quantity->getPrQuantity($prjKode, $sitKode, $workId);
            if ($pr) {
                $boq3[$key]['pr']['qty'] = $pr['qty'];
                $boq3[$key]['pr']['IDR'] = $pr['totalIDR'];
                $boq3[$key]['pr']['USD'] = $pr['totalHargaUSD'];
            } else {
                $boq3[$key]['pr']['qty'] = 0;
                $boq3[$key]['pr']['IDR'] = 0;
                $boq3[$key]['pr']['USD'] = 0;
            }
            $total['pr']['IDR'] += $boq3[$key]['pr']['IDR'];
            $total['pr']['USD'] += $boq3[$key]['pr']['USD'];
            //PO
            $po = $this->quantity->getPoQuantity($prjKode, $sitKode, $workId);
            if ($po) {
                $boq3[$key]['po']['qty'] = $po['qty'];
                $boq3[$key]['po']['IDR'] = $po['totalIDR'];
                $boq3[$key]['po']['USD'] = $po['totalHargaUSD'];
            } else {
                $boq3[$key]['po']['qty'] = 0;
                $boq3[$key]['po']['IDR'] = 0;
                $boq3[$key]['po']['USD'] = 0;
            }
            $total['po']['IDR'] += $boq3[$key]['po']['IDR'];
            $total['po']['USD'] += $boq3[$key]['po']['USD'];
            //RPI
            $rpi = $this->quantity->getRpiQuantity($prjKode, $sitKode, $workId);
            if ($rpi) {
                $boq3[$key]['rpi']['qty'] = $rpi['qty'];
                $boq3[$key]['rpi']['IDR'] = $rpi['totalIDR'];
                $boq3[$key]['rpi']['USD'] = $rpi['totalHargaUSD'];
            } else {
                $boq3[$key]['rpi']['qty'] = 0;
                $boq3[$key]['rpi']['IDR'] = 0;
                $boq3[$key]['rpi']['USD'] = 0;
            }
            $total['rpi']['IDR'] += $boq3[$key]['rpi']['IDR'];
            $total['rpi']['USD'] += $boq3[$key]['rpi']['USD'];
            //Payment RPI
//            $rpid = $this->budget->getPaymentRPIdNonCFS($prjKode,$sitKode,$workId);
//            if ($rpid)
//            {
//                $boq3[$key]['prpi']['qty'] = $rpid['qty'];
//                $boq3[$key]['prpi']['IDR'] = $rpid['totalIDR'];
//                $boq3[$key]['prpi']['USD'] = $rpid['totalHargaUSD'];
//            }
//            else
//            {
//                $boq3[$key]['prpi']['qty'] = 0;
//                $boq3[$key]['prpi']['IDR'] = 0;
//                $boq3[$key]['prpi']['USD'] = 0;
//            }
            //ARF
            $arf = $this->quantity->getArfQuantity($prjKode, $sitKode, $workId);
            if ($arf) {
                $boq3[$key]['arf']['qty'] = $arf['qty'];
                $boq3[$key]['arf']['IDR'] = $arf['totalIDR'];
                $boq3[$key]['arf']['USD'] = $arf['totalHargaUSD'];
            } else {
                $boq3[$key]['arf']['qty'] = 0;
                $boq3[$key]['arf']['IDR'] = 0;
                $boq3[$key]['arf']['USD'] = 0;
            }
            $total['arf']['IDR'] += $boq3[$key]['arf']['IDR'];
            $total['arf']['USD'] += $boq3[$key]['arf']['USD'];
            //ASF
            $asf = $this->quantity->getAsfddQuantity($prjKode, $sitKode, $workId);
            if ($asf) {
                $boq3[$key]['asf']['qty'] = $asf['qty'];
                $boq3[$key]['asf']['IDR'] = $asf['totalIDR'];
                $boq3[$key]['asf']['USD'] = $asf['totalHargaUSD'];
            } else {
                $boq3[$key]['asf']['qty'] = 0;
                $boq3[$key]['asf']['IDR'] = 0;
                $boq3[$key]['asf']['USD'] = 0;
            }
            $total['asf']['IDR'] += $boq3[$key]['asf']['IDR'];
            $total['asf']['USD'] += $boq3[$key]['asf']['USD'];
            //ASF Cancel
            $asfc = $this->quantity->getAsfcancelQuantity($prjKode, $sitKode, $workId);
            if ($asfc) {
                $boq3[$key]['asfc']['qty'] = $asfc['qty'];
                $boq3[$key]['asfc']['IDR'] = $asfc['totalIDR'];
                $boq3[$key]['asfc']['USD'] = $asfc['totalHargaUSD'];
            } else {
                $boq3[$key]['asfc']['qty'] = 0;
                $boq3[$key]['asfc']['IDR'] = 0;
                $boq3[$key]['asfc']['USD'] = 0;
            }
            $total['asfc']['IDR'] += $boq3[$key]['asfc']['IDR'];
            $total['asfc']['USD'] += $boq3[$key]['asfc']['USD'];

            //DOR
//            $dor = $this->quantity->getDorQuantity($prjKode,$sitKode,$workId);
//            if ($dor)
//            {
//                $boq3[$key]['dor']['qty'] = $dor['qty'];
//                $boq3[$key]['dor']['IDR'] = $dor['totalIDR'];
//                $boq3[$key]['dor']['USD'] = $dor['totalHargaUSD'];
//            }
//            else
//            {
//                $boq3[$key]['dor']['qty'] = 0;
//                $boq3[$key]['dor']['IDR'] = 0;
//                $boq3[$key]['dor']['USD'] = 0;
//            }
            //DO
//            $do = $this->quantity->getDoQuantity($prjKode,$sitKode,$workId);
//            if ($do)
//            {
//                $boq3[$key]['do']['qty'] = $do['qty'];
//                $boq3[$key]['do']['IDR'] = $do['totalIDR'];
//                $boq3[$key]['do']['USD'] = $do['totalHargaUSD'];
//            }
//            else
//            {
//                $boq3[$key]['do']['qty'] = 0;
//                $boq3[$key]['do']['IDR'] = 0;
//                $boq3[$key]['do']['USD'] = 0;
//            }
        }

//        //Salary
//        $sal = $this->quantity->getSalQuantity($prjKode);
//        if ($sal)
//        {
//            $this->view->salary = $sal['totalIDR'];
//        }
//        else
//        {
//            $this->view->salary = 0;
//        }


        if ($fromCache)
            $this->view->time = $time;

        if (!$print) {
            $this->view->result = $boq3;
            $this->view->total = $total;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $totalbudget = 0;
            $totalbudgetUSD = 0;
            $totalPRIDR = 0;
            $totalPRUSD = 0;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCIDR = 0;
            $totalASFCUSD = 0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Budget Name" => $v['budgetname'],
                    "Budget ID" => $v['budgetid'],
                    "Budget IDR" => floatval($v['totalHargaIDR']),
                    "Budget USD" => floatval($v['totalHargaUSD']),
                    "PR IDR" => floatval($v['pr']['IDR']),
                    "PR USD" => floatval($v['pr']['USD']),
                    "PO IDR" => floatval($v['po']['IDR']),
                    "PO USD" => floatval($v['po']['USD']),
                    "RPI IDR" => floatval($v['rpi']['IDR']),
                    "RPI USD" => floatval($v['rpi']['USD']),
                    "ARF IDR" => floatval($v['arf']['IDR']),
                    "ARF USD" => floatval($v['arf']['USD']),
                    "ASF IDR" => floatval($v['asf']['IDR']),
                    "ASF USD" => floatval($v['asf']['USD']),
                    "ASF Cancel IDR" => floatval($v['asfc']['IDR']),
                    "ASF Cancel USD" => floatval($v['asfc']['USD'])
                );
                $no++;

                $totalbudget += floatval($v['totalHargaIDR']);
                $totalbudgetUSD += floatval($v['totalHargaUSD']);
                $totalPRIDR += floatval($v['pr']['IDR']);
                $totalPRUSD += floatval($v['pr']['USD']);
                $totalPOIDR += floatval($v['po']['IDR']);
                $totalPOUSD += floatval($v['po']['USD']);
                $totalRPIIDR += floatval($v['rpi']['IDR']);
                $totalRPIUSD += floatval($v['rpi']['USD']);
                $totalARFIDR += floatval($v['arf']['IDR']);
                $totalARFUSD += floatval($v['arf']['USD']);
                $totalASFIDR += floatval($v['asf']['IDR']);
                $totalASFUSD += floatval($v['asf']['USD']);
                $totalASFCIDR += floatval($v['asfc']['IDR']);
                $totalASFCUSD += floatval($v['asfc']['USD']);
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Budget Name" => 'Total',
                "Budget ID" => '',
                "Budget IDR" => $totalbudget,
                "Budget USD" => $totalbudgetUSD,
                "PR IDR" => $totalPRIDR,
                "PR USD" => $totalPRUSD,
                "PO IDR" => $totalPOIDR,
                "PO USD" => $totalPOUSD,
                "RPI IDR" => $totalRPIIDR,
                "RPI USD" => $totalRPIUSD,
                "ARF IDR" => $totalARFIDR,
                "ARF USD" => $totalARFUSD,
                "ASF IDR" => $totalASFIDR,
                "ASF USD" => $totalASFUSD,
                "ASF Cancel IDR" => $totalASFCIDR,
                "ASF Cancel USD" => $totalASFCUSD
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Report(Overhead) "
                    ))
                    ->setCellFormat(array(
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        13 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        14 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        15 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        16 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function generalOverheadSummaryAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;

        $theData = array();
        $total = array();
        if ($sitKode == '') {
            $sites = new Default_Models_MasterSite();
            $s = $sites->getList($prjKode);

            foreach ($s as $k => $v) {
                $sitKode = $v['sit_kode'];
                $isReport = true;
                $result = $this->getBoq3Summary($prjKode, $sitKode, true, $isReport);

                $theData[$sitKode] = $result['boq3'];

                $res = $result['boq3'];
                //Total
                $total['boq3']['IDR'] += $res['boq3']['IDR'];
                $total['boq3']['USD'] += $res['boq3']['USD'];
                $total['pr']['IDR'] += $res['pr']['IDR'];
                $total['pr']['USD'] += $res['pr']['USD'];
                $total['po']['IDR'] += $res['po']['IDR'];
                $total['po']['USD'] += $res['po']['USD'];
                $total['rpi']['IDR'] += $res['rpi']['IDR'];
                $total['rpi']['USD'] += $res['rpi']['USD'];
                $total['arf']['IDR'] += $res['arf']['IDR'];
                $total['arf']['USD'] += $res['arf']['USD'];
                $total['asf']['IDR'] += $res['asf']['IDR'];
                $total['asf']['USD'] += $res['asf']['USD'];
                $total['asfc']['IDR'] += $res['asfc']['IDR'];
                $total['asfc']['USD'] += $res['asfc']['USD'];


                $theData[$sitKode]['sit_nama'] = $sites->getSiteName($prjKode, $sitKode);
                $fromCache = $result['fromCache'];
                $time = $result['time'];
            }
        }
//        QDC_Kint_Dump::dump($theData);die;
        //Salary
        $sal = $this->quantity->getSalQuantity($prjKode);
        if ($sal) {
            $this->view->salary = $sal['totalIDR'];
        } else {
            $this->view->salary = 0;
        }

        if ($fromCache)
            $this->view->time = $time;
        $this->view->result = $theData;
        $this->view->total = $total;

        $this->render('general-summary');

        if ($print) {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $totalbudget = 0;
            $totalbudgetUSD = 0;
            $totalPRIDR = 0;
            $totalPRUSD = 0;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCIDR = 0;
            $totalASFCUSD = 0;

            foreach ($theData as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Site" => $k . " - " . $v['sit_nama'],
                    "Budget IDR" => floatval($v['boq3']['IDR']),
                    "Budget USD" => floatval($v['boq3']['USD']),
                    "PR IDR" => floatval($v['pr']['IDR']),
                    "PR USD" => floatval($v['pr']['USD']),
                    "PO IDR" => floatval($v['po']['IDR']),
                    "PO USD" => floatval($v['po']['USD']),
                    "RPI IDR" => floatval($v['rpi']['IDR']),
                    "RPI USD" => floatval($v['rpi']['USD']),
                    "ARF IDR" => floatval($v['arf']['IDR']),
                    "ARF USD" => floatval($v['arf']['USD']),
                    "ASF IDR" => floatval($v['asf']['IDR']),
                    "ASF USD" => floatval($v['asf']['USD']),
                    "ASF Cancel IDR" => floatval($v['asfc']['IDR']),
                    "ASF Cancel USD" => floatval($v['asfc']['USD'])
                );
                $no++;

                $totalbudget += floatval($v['boq3']['IDR']);
                $totalbudgetUSD += floatval($v['boq3']['USD']);
                $totalPRIDR += floatval($v['pr']['IDR']);
                $totalPRUSD += floatval($v['pr']['USD']);
                $totalPOIDR += floatval($v['po']['IDR']);
                $totalPOUSD += floatval($v['po']['USD']);
                $totalRPIIDR += floatval($v['rpi']['IDR']);
                $totalRPIUSD += floatval($v['rpi']['USD']);
                $totalARFIDR += floatval($v['arf']['IDR']);
                $totalARFUSD += floatval($v['arf']['USD']);
                $totalASFIDR += floatval($v['asf']['IDR']);
                $totalASFUSD += floatval($v['asf']['USD']);
                $totalASFCIDR += floatval($v['asfc']['IDR']);
                $totalASFCUSD += floatval($v['asfc']['USD']);
            }

            //Total...
            $newData[] = array(
                "No" => '',
                "Site" => "Total",
                "Budget IDR" => $totalbudget,
                "Budget USD" => $totalbudgetUSD,
                "PR IDR" => $totalPRIDR,
                "PR USD" => $totalPRUSD,
                "PO IDR" => $totalPOIDR,
                "PO USD" => $totalPOUSD,
                "RPI IDR" => $totalRPIIDR,
                "RPI USD" => $totalRPIUSD,
                "ARF IDR" => $totalARFIDR,
                "ARF USD" => $totalARFUSD,
                "ASF IDR" => $totalASFIDR,
                "ASF USD" => $totalASFUSD,
                "ASF Cancel IDR" => $totalASFCIDR,
                "ASF Cancel USD" => $totalASFCUSD
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "General Overhead Report Summary " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        3 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        13 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        14 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        15 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function showcfsAction() {
        
    }

    public function cfsReportAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");


        $startDate = $this->getRequest()->getParam("start");
        $endDate = $this->getRequest()->getParam("end") == '' ? date("Y-m-d") : $this->getRequest()->getParam("end");
        $rateIDR = $this->budgetCFS->getLatestRate($endDate, 'USD');
        $cip = 0;

        //Get Start & End Date of Project
        //Loop Project & Site Code



        $this->view->prjKode = $prjKode;
        $this->view->result = $sitGroup;
        $this->view->grandTotal = $grandTotal;
        $this->view->rateidr = number_format(floatval($rateIDR), 2);
        $this->view->tgl = date("d M Y", strtotime($endDate));
        $this->view->CIP = $cip;
    }

    public function cfsAction() {

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $this->view->prjKode = $prjKode;

        $gantt = new Extjs4_Models_Gantt();
        $projectShedule = $gantt->getPojectDate($prjKode);

        $startDate = ($this->getRequest()->getParam("start") == '' || $this->getRequest()->getParam("start") < $projectShedule['start_date']) ? $projectShedule['start_date'] : $this->getRequest()->getParam("start");
        $endDate = ($this->getRequest()->getParam("end") == '' || $this->getRequest()->getParam("end") < $projectShedule['start_date']) ? date("Y-m-d") : $this->getRequest()->getParam("end");
        $useCache = $this->getRequest()->getParam("cache") == '' ? true : false;
        $rateIDR = $this->budgetCFS->getLatestRate($endDate, 'USD');
        $grandTotal = array();
        $cip = 0;
        //$this->view->useCache = false;
        // $cacheID = "REPORT_CFS_" . $prjKode . str_replace('-','_',$startDate).str_replace('-', '_', $endDate);
        //  if ($useCache) {
        // if (!$this->memcache->test($cacheID)) {
        //$CIP = $this->processCIPV2($prjKode, $startDate, $endDate,$rateIDR);
        $sitGroup = $this->processBudgetV2($prjKode, $sitKode, $startDate, $endDate, $rateIDR, $cip);
        $CIP = $cip;
        //$budget = array(
        //    "sitGroup" => $sitGroup,
        //    "CIP" => $CIP
        //);
        //$res = $this->memcacheHelper->save($budget, $cacheID, "REPORT");
        //    } else {
        //        $cache = $this->memcache->load($cacheID);
        //        $sitGroup = $cache['sitGroup'];
        //        $CIP = $cache['CIP'];
        //        $res = $this->memcache->load($cacheID . "_TIME");
        //        $this->view->time = $res;
        //    }
        //$this->view->useCache = true;
        //}

        $this->view->CIP = $CIP;

        foreach ($sitGroup as $k => $v) {
            //Grand Total...
            $grandTotal['boq2_variations'] += $sitGroup[$k]['total']['boq2_variations'];
            $grandTotal['boq2_ori'] += $sitGroup[$k]['total']['boq2_ori'];
            $grandTotal['boq2_current'] += $sitGroup[$k]['total']['boq2_current'];
            $grandTotal['progress_amount'] += $sitGroup[$k]['total']['progress_amount'];
            $grandTotal['wip'] += $sitGroup[$k]['total']['wip'];
            $grandTotal['outstanding_payment'] += $sitGroup[$k]['total']['outstanding_payment'];
            $grandTotal['cash_expenses'] += $sitGroup[$k]['total']['cash_expenses'];
            $grandTotal['cash_expenses_previous'] += $sitGroup[$k]['total']['cash_expenses_previous'];
            $grandTotal['movement_this_month'] = $grandTotal['cash_expenses'] - $grandTotal['cash_expenses_previous'];
            $grandTotal['commitment'] += $sitGroup[$k]['total']['commitment'];
            $grandTotal['forecast_final_cost'] += $sitGroup[$k]['total']['forecast_final_cost'];
            $grandTotal['estimated_complete'] += $sitGroup[$k]['total']['estimated_complete'];
            $grandTotal['accrual'] += $sitGroup[$k]['total']['accrual'];
            $grandTotal['total_cost_to_date'] += $sitGroup[$k]['total']['total_cost_to_date'];
            $grandTotal['invoiced'] += $sitGroup[$k]['total']['invoiced'];
            $grandTotal['paidcost'] += $sitGroup[$k]['total']['paidcost'];
            $grandTotal['received'] += $sitGroup[$k]['total']['received'];
            $grandTotal['current_gross_margin'] += $sitGroup[$k]['total']['current_gross_margin'];
            $grandTotal['final_margin'] += $sitGroup[$k]['total']['final_margin'];
            $grandTotal['budget'] += $sitGroup[$k]['total']['budget'];
        }
        if ($grandTotal['boq2_current'] <= 0)
            $grandTotal['progress'] = 0;
        else
            $grandTotal['progress'] = ($grandTotal['progress_amount'] / $grandTotal['boq2_current']) * 100;

        if ($grandTotal['boq2_current'] <= 0)
            $grandTotal['final_persen'] = 0;
        else
            $grandTotal['final_persen'] = ($grandTotal['final_margin'] / $grandTotal['boq2_current']) * 100;

        foreach ($grandTotal as $k => $v) {
            if ($v == 'N/A')
                continue;
            if ($v < 0)
                $grandTotal[$k] = number_format($v * -1, 2);
            else
                $grandTotal[$k] = number_format($v, 2);

            if (strpos($k, 'persen') >= 0) {
                if ($v < 0) {
                    $grandTotal[$k] = "(" . $grandTotal[$k] . ")";
                    $grandTotal[$k] = "<font color=\"ff0000\">" . $grandTotal[$k] . "</font>";
                }
            }
        }


        foreach ($sitGroup as $k => $v) {
            foreach ($v['data'] as $k2 => $v2) {
                foreach ($v2 as $k3 => $v3) {
                    if ($k3 == 'tglcfs' || $k3 == 'prj_kode' || $k3 == 'sit_kode' || $k3 == 'prj_nama' || $k3 == 'sit_nama' || $k3 == 'stsoverhead' || $k3 == 'tglaw' || $k3 == 'progress_ket')
                        continue;
                    if ($v3 == 'N/A')
                        continue;
                    if ($v3 < 0)
                        $sitGroup[$k]['data'][$k2][$k3] = number_format(-1 * $v3, 2);
                    else
                        $sitGroup[$k]['data'][$k2][$k3] = number_format($v3, 2);
                    if (strpos($k3, 'persen') >= 0) {
                        if ($v3 < 0) {
                            $sitGroup[$k]['data'][$k2][$k3] = "(" . $sitGroup[$k]['data'][$k2][$k3] . ")";
                            $sitGroup[$k]['data'][$k2][$k3] = "<font color=\"ff0000\">" . $sitGroup[$k]['data'][$k2][$k3] . "</font>";
                        }
                    }
                }
            }
            foreach ($v['total'] as $k2 => $v2) {
                if ($v2 == 'N/A')
                    continue;
                if ($v2 < 0)
                    $sitGroup[$k]['total'][$k2] = number_format(-1 * $v2, 2);
                else
                    $sitGroup[$k]['total'][$k2] = number_format($v2, 2);
                if (strpos($k2, 'persen') >= 0) {
                    if ($v2 < 0) {
                        $sitGroup[$k]['total'][$k2] = "(" . $sitGroup[$k]['total'][$k2] . ")";
                        $sitGroup[$k]['total'][$k2] = "<font color=\"#ff0000\">" . $sitGroup[$k]['total'][$k2] . "</font>";
                    }
                }
            }
        }

        $this->view->result = $sitGroup;
        $this->view->grandTotal = $grandTotal;

        //if ($startDate != '' && $endDate != '') {
        // $this->view->periode = "Periode : " . date("d M Y", strtotime($startDate)) . " - " . date("d M Y", strtotime($endDate));
        //}
        //if ($startDate)
        //    $this->view->periode = "Start Date : ". date("d M Y", strtotime($startDate));
        //if ($endDate)
        $start = ($startDate == '' || $startDate == null) ? 'Null' : date("d M Y", strtotime($startDate));
        $end = ($endDate == '' || $endDate == null) ? 'Null' : date("d M Y", strtotime($endDate));
        $this->view->periode = $start . '-' . $end;
        $this->view->start_date = date("d M Y", strtotime($startDate));
        $this->view->end_date = date("d M Y", strtotime($endDate));


        $this->view->rateidr = number_format(floatval($rateIDR), 2);
        $this->view->tgl = date("d M Y", strtotime($endDate));
    }

    private function processCIP($prjKode, $startDate = '', $endDate = '', $rateIDR = 0) {
        $arfcip = $this->budgetCFS->getArfd('summary', $prjKode, '', '', $startDate, $endDate);
        $asfddcip = $this->budgetCFS->getAsfdd('summary', $prjKode, '', '', $startDate, $endDate);
        $asfddcancelcip = $this->budgetCFS->getAsfddcancel('summary', $prjKode, '', '', $startDate, $endDate);

        $arftotCIP = (floatval($arfcip['totalIDR']) + (floatval($arfcip['totalHargaUSD']) * floatval($rateIDR)));
        $asftotCIP = (floatval($asfddcip['totalIDR']) + (floatval($asfddcip['totalHargaUSD']) * floatval($rateIDR)));
        $asfctotCIP = (floatval($asfddcancelcip['totalIDR']) + (floatval($asfddcancelcip['totalHargaUSD']) * floatval($rateIDR)));

        return ($arftotCIP - $asftotCIP) + $asfctotCIP;
    }

    private function processCIPV2($prjKode, $startDate = '', $endDate = '', $rateIDR = 'rateidr') {
        //ASF
        $this->budgetCFS->getAsfdWork($startDate, $endDate);
        $asf = $this->budgetCFS->getAsfdV2('summary', $prjKode, $sitKode, '', $startDate, $endDate, $rateIDR);
        $asf_approved = floatval($asf['total']);

        $asfc = $this->budgetCFS->getAsfdcancelV2('summary', $prjKode, $sitKode, '', $startDate, $endDate, $rateIDR);
        $asfcancel_approved = floatval($asfc['total']);

        $asf = floatval($asf_approved - $asfcancel_approved);

        //ARF
        $arf = $this->budgetCFS->getArfdV2('summary', $prjKode, $sitKode, '', $startDate, $endDate, $rateIDR);
        $arf_approved = floatval($arf['total']);

        return ($arf_approved - $asf);

        /* $arfcip = $this->budgetCFS->getArfdV2('summary', $prjKode, '', '', $startDate, $endDate,$rateIDR);
          $asfddcip = $this->budgetCFS->getAsfddV2('summary', $prjKode, '', '', $startDate, $endDate,$rateIDR);
          $asfddcancelcip = $this->budgetCFS->getAsfddcancelV2('summary', $prjKode, '', '', $startDate, $endDate,$rateIDR);

          return floatval(($arfcip['total'] - $asfddcip['total']) + $asfddcancelcip['total']); */
    }

    private function processBudget($prjKode, $startDate = '', $endDate = '', $rateIDR = 0) {
        $fetch = $this->budgetCFS->getBudgetProject(false, $prjKode, '', false, 0, true, $startDate, $endDate);

        $sal = new HumanResource_Models_SalaryD();
        $salary = $sal->getSalarySummaryForCFS($prjKode);

        $progress = new ProjectManagement_Models_ProjectProgress();
        $sitGroup = array();

        foreach ($fetch as $k => $v) {
            $sitKode = $v['sit_kode'];
            $variations = (floatval($v['boq2_currentIDR']) + (floatval($v['boq2_currentHargaUSD']) * floatval($rateIDR))) - (floatval($v['boq2_oriIDR']) + (floatval($v['boq2_oriHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['boq2_variations'] = $variations;
            $fetch[$k]['boq2_ori'] = (floatval($v['boq2_oriIDR']) + (floatval($v['boq2_oriHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['boq2_current'] = (floatval($v['boq2_currentIDR']) + (floatval($v['boq2_currentHargaUSD']) * floatval($rateIDR)));

            //Progress
            $pro = $progress->getSumSiteProgress($prjKode, $sitKode, $startDate, $endDate);
            $pro = $pro['last_progress'];
            $remarkProgress = $progress->getSumSiteProgressRemark($prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['progress'] = $pro;
            $fetch[$k]['progress_ket'] = $remarkProgress;

            $fetch[$k]['progress_amount'] = ($pro / 100) * $fetch[$k]['boq2_current'];
            $fetch[$k]['retention'] = 0;
            $fetch[$k]['wip'] = $fetch[$k]['progress_amount'] - $fetch[$k]['invoiced'];
            //Invoiced
            $inv = $this->budgetCFS->getInvoice('summary', $prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['invoicedUSD'] = 0;
            if (floatval($inv['totalHargaUSD']) > 0) {
                $fetch[$k]['invoicedUSD'] = floatval($inv['totalHargaUSD']);
            }
            $fetch[$k]['invoicedIDR'] = floatval($inv['totalHargaIDR']);
            $fetch[$k]['invoiced'] = (floatval($inv['totalHargaIDR']) + (floatval($inv['totalHargaUSD']) * floatval($rateIDR)));
            //Received
            $pinv = $this->budgetCFS->getPaymentInvoice('summary', $prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['received'] = (floatval($pinv['totalHargaIDR']) + (floatval($pinv['totalHargaUSD']) * floatval($rateIDR)));
            //outstanding payment
            $fetch[$k]['outstanding_payment'] = $fetch[$k]['invoiced'] - $fetch[$k]['received'];

            //Salaries
            $gaji = 0;
            if ($salary[$sitKode] != '') {
                $gaji = $salary[$sitKode];
                $fetch[$k]['salary_exist'] = $gaji;
                unset($salary[$sitKode]);
            }


            $outs = $fetch[$k]['invoiced'] - $fetch[$k]['received'];

            //Cash Expenses
            //RPI
            $this->budgetCFS->getRpidWork($startDate, $endDate);
            $rpi = $this->budgetCFS->getRpid('summary', $prjKode, $sitKode, '', $startDate, $endDate);
            $fetch[$k]['rpi_approved'] = (floatval($rpi['totalIDR']) + (floatval($rpi['totalHargaUSD']) * floatval($rateIDR)));
            //ASF
            $this->budgetCFS->getAsfdWork($startDate, $endDate);
            $asf = $this->budgetCFS->getAsfd('summary', $prjKode, $sitKode, '', $startDate, $endDate);
            $asfc = $this->budgetCFS->getAsfdcancel('summary', $prjKode, $sitKode, '', $startDate, $endDate);
            $fetch[$k]['asf_approved'] = (floatval($asf['totalIDR']) + (floatval($asf['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['asfcancel_approved'] = (floatval($asfc['totalIDR']) + (floatval($asfc['totalHargaUSD']) * floatval($rateIDR)));
            //Piecemeal / PBOQ
            $piecemeal = $this->budgetCFS->getPiecemeal('summary', $prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['piecemeal'] = (floatval($piecemeal['totalPieceMeal']));
            //Material return, cancel
            $LeftOver = $this->budgetCFS->getLeftOver('summary', $prjKode, $sitKode, $startDate, $endDate);
            $totalLeftOver = (floatval($LeftOver['totalIDR']) + (floatval($LeftOver['totalHargaUSD']) * floatval($rateIDR)));
            $Cancel = $this->budgetCFS->getCancel('summary', $prjKode, $sitKode, $startDate, $endDate);
            $totalCancel = (floatval($Cancel['totalIDR']) + (floatval($Cancel['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['material_return'] = $totalLeftOver + $totalCancel;


            $fetch[$k]['rpi_asf_approved'] = floatval((($fetch[$k]['rpi_approved'] + $fetch[$k]['asf_approved']) - $fetch[$k]['asfcancel_approved']));
            $fetch[$k]['cash_expenses'] = $fetch[$k]['rpi_asf_approved'] + $fetch[$k]['piecemeal'] + $gaji - $fetch[$k]['material_return'];
            ;

            //Commitment
            $po = $this->budgetCFS->getPod('summary', $prjKode, $sitKode, '', '', $startDate, $endDate);
            $fetch[$k]['po'] = (floatval($po['totalIDR']) + (floatval($po['totalHargaUSD']) * floatval($rateIDR)));
            $arf = $this->budgetCFS->getArfd('summary', $prjKode, $sitKode, '', $startDate, $endDate);
            $fetch[$k]['arf'] = (floatval($arf['totalIDR']) + (floatval($arf['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['commitment'] = $fetch[$k]['po'] + $fetch[$k]['arf'];

            //Cost Forecast
            $boq3 = $this->budgetCFS->getBoq3('summary-current', $prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['forecast_final_cost'] = (floatval($boq3['totalIDR']) + (floatval($boq3['totalHargaUSD']) * floatval($rateIDR)));

            //Estimated cost to complete
            $fetch[$k]['estimated_complete'] = $fetch[$k]['forecast_final_cost'] - $fetch[$k]['cash_expenses'];

            //Accrual
            $fetch[$k]['accrual'] = ($fetch[$k]['forecast_final_cost'] * ($fetch[$k]['progress'] / 100)) - $fetch[$k]['cash_expenses'];
            if ($fetch[$k]['accrual'] < 0)
                $fetch[$k]['accrual'] = 0;

            //Current Gross Margin
            $fetch[$k]['current_gross_margin'] = $fetch[$k]['progress_amount'] - $fetch[$k]['cash_expenses'];

            //Final Margin
            $fetch[$k]['final_margin'] = $fetch[$k]['boq2_current'] - $fetch[$k]['forecast_final_cost'];
            if ($fetch[$k]['boq2_current'] <= 0)
                $fetch[$k]['final_persen'] = 0;
            else
                $fetch[$k]['final_persen'] = ($fetch[$k]['final_margin'] / $fetch[$k]['boq2_current']) * 100;


            $prefix = substr($sitKode, 0, 1);
            $prefix = $prefix . "00";
            $sitGroup[$prefix]['data'][] = $fetch[$k];

            //Sub Total per Site Group...
            $sitGroup[$prefix]['total']['boq2_variations'] += $fetch[$k]['boq2_variations'];
            $sitGroup[$prefix]['total']['boq2_ori'] += $fetch[$k]['boq2_ori'];
            $sitGroup[$prefix]['total']['boq2_current'] += $fetch[$k]['boq2_current'];
            $sitGroup[$prefix]['total']['progress_amount'] += $fetch[$k]['progress_amount'];
            if ($sitGroup[$prefix]['total']['boq2_current'] <= 0)
                $sitGroup[$prefix]['total']['progress'] = 0;
            else
                $sitGroup[$prefix]['total']['progress'] = ($sitGroup[$prefix]['total']['progress_amount'] / $sitGroup[$prefix]['total']['boq2_current']) * 100;
            $sitGroup[$prefix]['total']['wip'] += $fetch[$k]['wip'];
            $sitGroup[$prefix]['total']['invoiced'] += $fetch[$k]['invoiced'];
            $sitGroup[$prefix]['total']['received'] += $fetch[$k]['received'];
            $sitGroup[$prefix]['total']['outstanding_payment'] += $fetch[$k]['outstanding_payment'];
            $sitGroup[$prefix]['total']['cash_expenses'] += $fetch[$k]['cash_expenses'];
            $sitGroup[$prefix]['total']['commitment'] += $fetch[$k]['commitment'];
            $sitGroup[$prefix]['total']['forecast_final_cost'] += $fetch[$k]['forecast_final_cost'];
            $sitGroup[$prefix]['total']['estimated_complete'] += $fetch[$k]['estimated_complete'];
            $sitGroup[$prefix]['total']['accrual'] += $fetch[$k]['accrual'];
            $sitGroup[$prefix]['total']['current_gross_margin'] += $fetch[$k]['current_gross_margin'];
            $sitGroup[$prefix]['total']['final_margin'] += $fetch[$k]['final_margin'];
            if ($sitGroup[$prefix]['total']['boq2_current'] <= 0)
                $sitGroup[$prefix]['total']['final_persen'] = 0;
            else
                $sitGroup[$prefix]['total']['final_persen'] = ($sitGroup[$prefix]['total']['final_margin'] / $sitGroup[$prefix]['total']['boq2_current']) * 100;
        }

        if (count($salary) > 0) {
            foreach ($salary as $k => $v) {
                $data = array();
                $prefix = substr($k, 0, 1);
                $prefix = $prefix . "00";
                $data['sit_kode'] = $k;
                $data['sit_nama'] = 'Overhead (Salaries)';
                $data['boq2_variations'] = 'N/A';
                $data['boq2_ori'] = 'N/A';
                $data['boq2_current'] = 'N/A';
                $data['progress_amount'] = 'N/A';
                $data['progress'] = 'N/A';
                $data['wip'] = 'N/A';
                $data['outstanding_payment'] = 'N/A';
                $data['cash_expenses'] = $v;
                $data['commitment'] = 'N/A';
                $data['forecast_final_cost'] = 'N/A';
                $data['estimated_complete'] = 'N/A';
                $data['accrual'] = 'N/A';
                $data['current_gross_margin'] = 'N/A';
                $data['final_margin'] = 'N/A';
                $data['final_persen'] = 'N/A';
                $sitGroup[$prefix]['data'][] = $data;

                $sitGroup[$prefix]['total']['boq2_variations'] = 'N/A';
                $sitGroup[$prefix]['total']['boq2_ori'] = 'N/A';
                $sitGroup[$prefix]['total']['boq2_current'] = 'N/A';
                $sitGroup[$prefix]['total']['progress_amount'] = 'N/A';
                $sitGroup[$prefix]['total']['progress'] = 'N/A';
                $sitGroup[$prefix]['total']['wip'] = 'N/A';
                $sitGroup[$prefix]['total']['outstanding_payment'] = 'N/A';
                $sitGroup[$prefix]['total']['cash_expenses'] += floatval($v);
                $sitGroup[$prefix]['total']['commitment'] = 'N/A';
                $sitGroup[$prefix]['total']['forecast_final_cost'] = 'N/A';
                $sitGroup[$prefix]['total']['estimated_complete'] = 'N/A';
                $sitGroup[$prefix]['total']['accrual'] = 'N/A';
                $sitGroup[$prefix]['total']['current_gross_margin'] = 'N/A';
                $sitGroup[$prefix]['total']['final_margin'] = 'N/A';
                $sitGroup[$prefix]['total']['final_persen'] = 'N/A';

//                $grandTotal['cash_expenses'] += $v;
            }
        }
//echo "<pre>";var_dump($sitGroup);echo "</pre>";die;
        return $sitGroup;
    }

    private function processBudgetV2($prjKode, $sitKode = '', $startDate = '', $endDate = '', $rateIDR = 1, &$cip) {
        $fetch = $this->budgetCFS->getBudgetProjectV2(false, $prjKode, $sitKode, false, 0, true, $startDate, $endDate);

        $progress = new ProjectManagement_Models_ProjectProgress();
        $sitGroup = array();
        $cip = 0;

        foreach ($fetch as $k => $v) {

            $sitKode = $v['sit_kode'];

            $fetch[$k]['boq2_oriIDR'] = floatval($v['boq2_oriIDR']);
            $fetch[$k]['boq2_oriUSD'] = floatval($v['boq2_oriHargaUSD']);
            $fetch[$k]['boq2_ori'] = (floatval($v['boq2_oriIDR']) + (floatval($v['boq2_oriHargaUSD'] * $rateIDR)));

            $fetch[$k]['boq2_currentIDR'] = floatval($v['boq2_currentIDR']);
            $fetch[$k]['boq2_currentUSD'] = floatval($v['boq2_currentHargaUSD']);
            $fetch[$k]['boq2_current'] = (floatval($v['boq2_currentIDR']) + (floatval($v['boq2_currentHargaUSD'] * $rateIDR)));

            $fetch[$k]['boq2_variationsIDR'] = $fetch[$k]['boq2_currentIDR'] - $fetch[$k]['boq2_oriIDR'];
            $fetch[$k]['boq2_variationsUSD'] = $fetch[$k]['boq2_currentUSD'] - $fetch[$k]['boq2_oriUSD'];
            $fetch[$k]['boq2_variations'] = $fetch[$k]['boq2_current'] - $fetch[$k]['boq2_ori'];

            //Invoiced
            $invoice = $this->invoiced->getTotalInvoice($prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['invoiced'] = floatval($invoice['total']);
            $fetch[$k]['invoicedIDR'] = floatval($invoice['totalIDR']);
            $fetch[$k]['invoicedUSD'] = floatval($invoice['totalUSD']);

            //Received
            $pinv = $this->paymentInvoice->getTotalPaymentInvoiceNoPPN($prjKode, $sitKode, $startDate, $endDate);
            $fetch[$k]['received'] = floatval($pinv['total']);
            $fetch[$k]['receivedIDR'] = floatval($pinv['totalIDR']);
            $fetch[$k]['receivedUSD'] = floatval($pinv['totalUSD']);

            //outstanding payment
            $fetch[$k]['outstanding_payment'] = $fetch[$k]['invoiced'] - $fetch[$k]['received'];

            //Recorded Cost
            $recorded_cost = $this->cost->recordedCostPerDate($prjKode, $sitKode, $startDate, $endDate);
            $totalRecorded = 0;
            $totalPreviousRecorded = 0;
            $tgl = explode('-', $endDate);
            $totalAsfBsf = 0;

            foreach ($recorded_cost AS $index => $data) {
                $totalRecorded += $data['total'];
                $totalPreviousRecorded += $data['tgl'] < $tgl[0] . '-' . $tgl[1] . '-' . '01' ? $data['total'] : 0;
                $totalRecordedUSD += $data['val_kode'] == 'USD' ? $data['amount'] : 0;
                $totalRecordedUSDConv += $data['val_kode'] == 'USD' ? $data['total'] : 0;
                $totalAsfBsf += $data['kategori'] == 'BSF' || $data['kategori'] == 'ASF' ? $data['total'] : 0;            
            }

            $fetch[$k]['cash_expenses'] = $totalRecorded;
            $fetch[$k]['cash_expenses_previous'] = $totalPreviousRecorded;
            $fetch[$k]['movement_this_month'] = $totalRecorded - $totalPreviousRecorded;

            //Committed Cost
            $committed_cost = $this->cost->committedCostPerDate($prjKode, $sitKode, $startDate, $endDate);
            $totalCommitted = 0;
            $totalArfBrf = 0;

            foreach ($committed_cost AS $index2 => $data2) {
                $totalCommitted += $data2['total'];
                $totalArfBrf += $data['kategori'] == 'ARF' || $data['kategori'] == 'BRF'? $data['total'] : 0;
            }

            $fetch[$k]['commitment'] = $totalCommitted;
            $cip += $totalArfBrf - $totalAsfBsf;

            //Paid Cost
            $totalPaidCost = 0;
            $paidCost = $this->cost->paidCostPerDate($prjKode, $sitKode, $startDate, $endDate);
            foreach ($paidCost AS $index3 => $data3) {
                $totalPaidCost += $data3['total'];
            }
            $fetch[$k]['paidcost'] = $totalPaidCost;
            
            //Cost Budget
            if(substr($prjKode,0,1)=='Q')
            {
                $current_boq3 = $this->budget->getBOQ3Current($prjKode, $sitKode, $endDate);
            }
            else
            {
                $current_boq3 = $this->budget->getBudgetOverheadV2($prjKode, $sitKode,'','summary-current');
            }

            $totalBoq3_currentIDR = floatval($current_boq3['totalIDR']);

            $totalBoq3_currentUSD = floatval($current_boq3['totalUSD']);

            //Get Current Budget
            $currentBuget = $this->budgt->getCurrentBudget($current_boq3, $recorded_cost, $rateIDR);

            $fetch[$k]['budget'] = $currentBuget;

            $fetch[$k]['budgetIDR'] = $totalBoq3_currentIDR;
            $fetch[$k]['budgetUSD'] = $totalBoq3_currentUSD;
            $fetch[$k]['recordedCostUSD'] = $totalRecordedUSD;
            $fetch[$k]['recordedCostUSDConv'] = $totalRecordedUSDConv;

            //Progress
            $site = $progress->getSiteProgressV2($prjKode, $sitKode, $startDate, $endDate);
            $percentProgress = $fetch[$k]['budget']==0 ? 0 : floatval(($fetch[$k]['cash_expenses'] / $fetch[$k]['budget']) * 100); 
            $fetch[$k]['progress'] = $sitKode == '100' ? $percentProgress : floatval($site['progress']);
            $fetch[$k]['progress_ket'] = $site['ket'];

            $fetch[$k]['progress_amount'] = ($fetch[$k]['progress'] / 100) * $fetch[$k]['boq2_current'];
            $fetch[$k]['retention'] = 0;

            //wip
            $fetch[$k]['wip'] = $fetch[$k]['progress_amount'] - $fetch[$k]['invoiced'];

            //Cost Forecast
            $fetch[$k]['forecast_final_cost'] = floatval($fetch[$k]['budget']);

            //Accrual
            $fetch[$k]['accrual'] = ($fetch[$k]['forecast_final_cost'] * (number_format($fetch[$k]['progress'], 2) / 100)) - $fetch[$k]['cash_expenses'];
            if ($fetch[$k]['accrual'] < 0)
                $fetch[$k]['accrual'] = 0;

            //Total Cost to Date
            $fetch[$k]['total_cost_to_date'] = $fetch[$k]['cash_expenses'] + $fetch[$k]['accrual'];

            //Estimated cost to complete
            $fetch[$k]['estimated_complete'] = $fetch[$k]['forecast_final_cost'] - $fetch[$k]['total_cost_to_date'];

            //Current Gross Margin
            $fetch[$k]['current_gross_margin'] = $fetch[$k]['progress_amount'] - $fetch[$k]['total_cost_to_date'];

            //Final Margin
            $fetch[$k]['final_margin'] = $fetch[$k]['boq2_current'] - $fetch[$k]['forecast_final_cost'];
            if ($fetch[$k]['boq2_current'] <= 0)
                $fetch[$k]['final_persen'] = 0;
            else
                $fetch[$k]['final_persen'] = ($fetch[$k]['final_margin'] / $fetch[$k]['boq2_current']) * 100;

            $prefix = substr($sitKode, 0, 1);
            $prefix = $prefix . "00";
            $sitGroup[$prefix]['data'][] = $fetch[$k];

            //Sub Total per Site Group...
            $sitGroup[$prefix]['total']['boq2_variations'] += $fetch[$k]['boq2_variations'];
            $sitGroup[$prefix]['total']['boq2_ori'] += $fetch[$k]['boq2_ori'];
            $sitGroup[$prefix]['total']['boq2_current'] += $fetch[$k]['boq2_current'];
            $sitGroup[$prefix]['total']['progress_amount'] += $fetch[$k]['progress_amount'];

            if ($sitGroup[$prefix]['total']['boq2_current'] <= 0)
                $sitGroup[$prefix]['total']['progress'] = 0;
            else
                $sitGroup[$prefix]['total']['progress'] = ($sitGroup[$prefix]['total']['progress_amount'] / $sitGroup[$prefix]['total']['boq2_current']) * 100;

            $sitGroup[$prefix]['total']['wip'] += $fetch[$k]['wip'];
            $sitGroup[$prefix]['total']['invoiced'] += $fetch[$k]['invoiced'];
            $sitGroup[$prefix]['total']['paidcost'] += $fetch[$k]['paidcost'];
            $sitGroup[$prefix]['total']['received'] += $fetch[$k]['received'];
            $sitGroup[$prefix]['total']['outstanding_payment'] += $fetch[$k]['outstanding_payment'];
            $sitGroup[$prefix]['total']['cash_expenses'] += $fetch[$k]['cash_expenses'];
            $sitGroup[$prefix]['total']['cash_expenses_previous'] += $fetch[$k]['cash_expenses_previous'];
            $sitGroup[$prefix]['total']['movement_this_month']+= $fetch[$k]['movement_this_month'];
            $sitGroup[$prefix]['total']['commitment'] += $fetch[$k]['commitment'];
            $sitGroup[$prefix]['total']['forecast_final_cost'] += $fetch[$k]['forecast_final_cost'];
            $sitGroup[$prefix]['total']['budget'] +=$fetch[$k]['budget'];
            $sitGroup[$prefix]['total']['estimated_complete'] += $fetch[$k]['estimated_complete'];
            $sitGroup[$prefix]['total']['accrual'] += $fetch[$k]['accrual'];
            $sitGroup[$prefix]['total']['total_cost_to_date'] += $fetch[$k]['total_cost_to_date'];
            $sitGroup[$prefix]['total']['current_gross_margin'] += $fetch[$k]['current_gross_margin'];
            $sitGroup[$prefix]['total']['final_margin'] += $fetch[$k]['final_margin'];

            if ($sitGroup[$prefix]['total']['boq2_current'] <= 0)
                $sitGroup[$prefix]['total']['final_persen'] = 0;
            else
                $sitGroup[$prefix]['total']['final_persen'] = ($sitGroup[$prefix]['total']['final_margin'] / $sitGroup[$prefix]['total']['boq2_current']) * 100;
        }

        return $sitGroup;
    }

    public function regiscoAction() {
        
    }

    public function getregiscoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 0;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $data['data'] = $this->regisco->fetchAll(null, array($sort . " " . $dir))->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showscurveAction() {
        
    }

    public function scurvecostAction() {

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $notitle = $this->getRequest()->getParam("notitle");
        $height = $this->getRequest()->getParam("height");
        $width = $this->getRequest()->getParam("width");
        $notitle = (isset($notitle)) ? 'true' : '';
        $height = (isset($height)) ? intval($height) : 600;
        $width = (isset($width)) ? intval($width) : 900;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

        $this->view->notitle = $notitle;
        $this->view->height = $height;
        $this->view->width = $width;
        $cronBudget = new Default_Models_CronBudget();

        $cek = $cronBudget->test($prjKode, $sitKode);
        if ($cek) {
            $select = $this->db->select()
                    ->from(array($cronBudget->__name()));
            if ($prjKode)
                $select = $select->where("prj_kode=?", $prjKode);
            if ($sitKode) {
                $select = $select->where("sit_kode=?", $sitKode);
            } else {
                $select = $select->where("sit_kode = '' OR sit_kode IS NULL");
            }
            $data = $this->db->fetchRow($select);
            $this->view->time = date("d M Y H:i:s", strtotime($data['tgl']));
        }

        $spv = $this->DEFAULT->MasterRoleSite->fetchAll("prj_kode = '$prjKode' AND sit_kode = '$sitKode' AND active = 1");
        if ($spv) {
            $spv = $spv->toArray();
            foreach ($spv as $k => $v) {
                $spv[$k]['name'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            }

            $return['posts'] = $spv;
        } else
            $return['posts'] = array();

        $this->view->spvData = Zend_Json::encode($return);
    }
    
    public function showspendbudgetAction() {
        
    }
    
    public function spendbudgetAction() {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $valKode = $this->getRequest()->getParam("val_kode");
        $type = $this->getRequest()->getParam("type");
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
      
      
        $boq3 = $this->budget->getBOQ3CurrentPerItem($prjKode, $sitKode,$valKode);
        $this->budget->createOnGoingAFE($prjKode);
        

        //$i = 1;
        $current = 0;
        $limit = count($boq3);
        
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
                
        $result = array();

        foreach ($boq3 as $key => $val) {
            
            foreach ($val as $key2 => $val2) {
                if ($val2 == "\"\"")
                    $boq3[$key][$key2] = '';
                if (strpos($val2, "\"") !== false)
                    $boq3[$key][$key2] = str_replace("\"", " inch", $boq3[$key][$key2]);
                if (strpos($val2, "'") !== false)
                    $boq3[$key][$key2] = str_replace("'", " inch", $boq3[$key][$key2]);
            }

            $boq3[$key]['id'] = $val['id'];
            $boq3[$key]['uom'] = $this->quantity->getUOMByProductID($boq3[$key]['kode_brg']);
            $boq3[$key]['nama_brg'] = str_replace("\"", "'", $boq3[$key]['nama_brg']);
            $boq3[$key]['price'] = $val['harga'];
            $total['total'] += $boq3[$key]['total'];
            
            $boq3[$key]['totalPrice'] = $val['val_kode']=='USD' ? $val['totalUSD'] : $val['totalIDR'];
            
            // Get All Related OCA
            $oca = $this->cost->totalOCA($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $totalOCA= $val['val_kode']=='USD' ? $oca['totalUSD'] : $oca['totalIDR'];
            $boq3[$key]['totalOCA'] = $totalOCA;
            
            // Get All Requests of The Item PR + ARF - (Material Return + ASF Cancel)
            $requests=$this->cost->totalRequestsDetail($prjKode, $sitKode,$val['workid'], $val['kode_brg']);
            $totalRequest= $requests['val_kode']=='USD' ? $requests['totalUSD'] : $requests['totalIDR'];
            $qty_spend=$requests['qty'];
            $price = $requests['harga'];
            
            
            $boq3[$key]['totalRequests']=$totalRequest;
            $boq3[$key]['qty_spend']=$qty_spend;
            if ($requests['val_kode_spend']==''){
                $boq3[$key]['val_kode_spend']='-';
            }else{
                $boq3[$key]['val_kode_spend']=$requests['val_kode_spend'];
            }
            if($totalRequest=='0'){
                $boq3[$key]['price_spend']=0;
                }else{
                $boq3[$key]['price_spend']=$totalRequest/$qty_spend;
                
            }
            $total['totalRequests'] += $boq3[$key]['totalRequests'];
            
            // Get On Going AFE
            $afe= $this->budget->getOnGoingAFE($prjKode,$sitKode,$boq3[$key]['workid'],$boq3[$key]['kode_brg'],$boq3[$key]['val_kode']);
            $boq3[$key]['tranoAFE']=$afe['trano']==null ? '' : $afe['trano'];
            $boq3[$key]['totalAFE']=$afe['total']==null ? 0 : $afe['total'];
            
            // Get PO
            $cusorder = $this->trans->getPOCustomer($prjKode, $sitKode);
            $boq3[$key]['pocustomer'] = $cusorder['pocustomer'];
            if ($boq3[$key]['val_kode'] == 'IDR')
                $boq3[$key]['totalpocustomer'] = intval($cusorder['total']);
            else
                $boq3[$key]['totalpocustomer'] = intval($cusorder['totalusd']);
            
            if ($current < ($limit + $offset) && $current >= $offset) {
              $result[] = $boq3[$key];
            }
            
            $current++;
            //$i++;
        }

         if (!$print) {
            $this->view->result = $boq3;
            $this->view->total = $total;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            
            $total = 0;

            foreach ($boq3 as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Product ID" => $v['kode_brg'],
                    "Product Name" => $v['nama_brg'],
                    "Qty Budget" => $v['qty'],
                    "Unit Price" => $v['harga'],
                    "Total" => $v['total'],
                    "Currency" => $v['val_kode'],
                    "Qty Spend" => $v['qty_spend'],
                    "Unit Price Spend" => $v['price_spend'],
                    "Total Spend" => $v['totalRequests'],
                    "Currency Spend" => $v['val_kode_spend'],
                    
                );
                $no++;
            }
            
            
            $newData[] = array(
                "No" => $no,
                "Product ID" => "",
                "Product Name" => "",
                "Qty Budget" => "",
                "Unit Price" => "",
                "Total" => "",
                "Currency" => "",
                "Qty Spend" => "",
                "Unit Price Spend" => "",
                "Total Spend" => "",
                "Currency Spend" => "",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Spend Budget Report " . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
    }

     public function showmonthlyprogressAction() {
        
    }
    
    public function monthlyprogressAction() {
    
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $notitle = $this->getRequest()->getParam("notitle");
        $height = $this->getRequest()->getParam("height");
        $width = $this->getRequest()->getParam("width");
        $notitle = (isset($notitle)) ? 'true' : '';
        $height = (isset($height)) ? intval($height) : 600;
        $width = (isset($width)) ? intval($width) : 900;
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

        $this->view->notitle = $notitle;
        $this->view->height = $height;
        $this->view->width = $width;
        $cronBudget = new Default_Models_CronBudget();


        if($sitKode !=''|| $sitKode!=null){
            $sql = "SELECT * FROM erpdb.cron_budget WHERE prj_kode = ? AND sit_kode = ?";
            $data = $cronBudget->fetchRow(array("prj_kode = '$prjKode'", "sit_kode = '$sitKode'"));
        } else {
            $sql = "SELECT * FROM erpdb.cron_budget WHERE prj_kode = ? AND sit_kode = ?" ;
            $data = $cronBudget->fetchRow(array("prj_kode = '$prjKode'", "sit_kode = ''"));
        }
        
        $data = $data->toArray();
        
        $dedata = Zend_Json::decode($data['data']);
        $start = ($dedata['START_DATE_BOQ3'] == '' || $dedata['START_DATE_BOQ3'] == null) ? 'Null' : date("d M Y", strtotime($dedata['START_DATE_BOQ3']));
        $end = ($dedata['END_DATE_BOQ3'] == '' || $dedata['END_DATE_BOQ3'] == null) ? 'Null' : date("d M Y", strtotime($dedata['END_DATE_BOQ3']));
        $this->view->periode = $start . '-' . $end;
        $this->view->budget = $dedata['BOQ3_TOTAL'];
 
        
        $newdata = array();
        $count = 0;
        
        foreach($dedata['BASECOST_SCURVE'] as $key => $value){
            if($value['week'] % 4 == 0 && $value['week'] != 0){
                $newdata[$count]['BOQ3'] = $value['BOQ3'];
                $newdata[$count]['DUE'] = $value['DUE'];
                $newdata[$count]['INVOICE'] = $value['INVOICE'];
                $newdata[$count]['MIP'] = $value['MIP'];
                $newdata[$count]['ACTUAL'] = $value['ACTUAL'];
                $newdata[$count]['PROGRESS'] = $value['PROGRESS'];
                $newdata[$count]['week'] = $value['week'];
            
                $count++;
            }
            
        }
        
        

        if (!$print) {
            $this->view->result = $newdata;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;
            $month = 1;
            $count = 0;
            $count2 = 0;
            $count3 = 0;
            $total1 = 0;
            $total2 = 0;
            $total3 = 0;
            
            foreach ($newdata as $key => $val) {
                $minus = $val['PROGRESS'] - $count;
                $minus2 = $val['INVOICE'] - $count2; 
                $inv = $minus2 * $this->view->budget;
                $minus3 = $val['ACTUAL'] - $count3; 
                $cash = $minus3 * $this->view->budget;
                $progressvalue = $minus * $this->view->budget;
                $newData[] = array(
                    "No" => $no,
                    "Periode" => "Bulan ".$month,
                    "Previous Month" => $count,
                    "Current Month" => $minus,
                    "Month To Date" => $val2['PROGRESS'],
                    "Progress Value" => $progressvalue,
                    "Invoice" => $inv,
                    "Cash Out" => $cash,
                    );
                $no++;
                $month++;
                $count++;
                $count2++;
                $count3++;
            }
             
            $newData[] = array(
                "No" => "",
                "Periode" => "",
                "Month" => "",
                "This Month" => "",
                "Until Now" => "",
                "Progress Value" => "",
                "Invoice" => "",
                "Cash Out" => "",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Monthly Progress Report" . $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                    ))
                    ->write($newData)->toExcel5Stream();
        }
        
    
    
    }
}

?>
