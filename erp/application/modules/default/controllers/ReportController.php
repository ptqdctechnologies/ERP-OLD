<?php

/*
  Created @ Mar 22, 2010 10:49:11 AM
 */

class ReportController extends Zend_Controller_Action {

    private $db;
    private $const;
    private $budget;
    private $transaction;
    private $project;
    private $ARF;
    private $ASF;
    private $memcache;
    private $logtrans;
    private $DEFAULT;
    private $utility;
    private $quantityHelper;

    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $session = new Zend_Session_Namespace('login');
        $this->const = Zend_Registry::get('constant');
        $this->budget = new Default_Models_Budget();
        $this->transaction = $this->_helper->getHelper('transaction');
        $this->project = $this->_helper->getHelper('project');
        $this->ARF = new Default_Models_AdvanceRequestForm();
        $this->ASF = new Default_Models_AdvanceSettlementForm();
        $this->logtrans = new Procurement_Model_Logtransaction();

        $models = array(
            "MasterUser",
            "AdvanceRequestFormD",
            "AdvanceRequestFormH",
            "AdvanceSettlementFormD",
            "AdvanceSettlementForm",
            "AdvanceSettlementFormCancel",
            "AdvanceSettlementFormH"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
        $this->utility = $this->_helper->getHelper("utility");
        $this->quantityHelper = $this->_helper->getHelper("quantity");
    }

    public function showboq3Action() {
        
    }

    public function boq3Action() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->getBoq3('all-ori-by-workid', $prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function showboq3revisiAction() {
        
    }

    public function boq3revisiAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->getBoq3('all-current-by-workid', $prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function showbudgetAction() {
        $this->view->columnHeader = $columnView;
    }

    public function compareboqAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->compareBoq($prjKode, $sitKode);
        $this->view->result = $result;

        //Get updated exchange rate from database
        $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			-- FROM exchange_rate
                        FROM finance_exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";

        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();

        $this->view->rateidr = $data['rateidr'];
        $this->view->tgl = $data['tgl_rate'];
    }

    public function showcompareboqAction() {
        
    }

    public function budgetAction() {
        //$this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $formula = $request->getParam('formula');

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $offset = ($currentPage - 1) * 20;
        $allSite = false;

        $all = $request->getParam('all');


        $detail = false;
        $show = $request->getParam('show');
        if ($show == 'Detail')
            $detail = true;
        $rate = $request->getParam('userate');
        if ($rate == '')
            $rate = false;

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));

        $fromCache = false;

        if ($formula == 'OLD' || $formula == '') {
            if (!$all) {
                $cacheID = "REPORT_BUDGET_$prjKode" . "_$sitKode" . "_" . $offset;
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $result = $this->budget->getBudgetProject(false, $prjKode, $sitKode, $detail, $offset);
                    $resultAll = $result;
                    $this->memcache->save($result, $cacheID, array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            } else {
                $cacheID = "REPORT_BUDGET_ALL_$prjKode" . "_$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $result = $this->budget->getBudgetProject(true, '', '', $detail, $offset);
                    $this->memcache->save($result, $cacheID, array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }


            if ($sitKode == '') {
                $siteCount = $this->project->getSiteCount($prjKode);
                $allSite = true;
                $cacheID = "REPORT_BUDGET_$prjKode" . "_ALL_SITE";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $resultAll = $this->budget->getBudgetProjectAll($prjKode, true);
                    $this->memcache->save($resultAll, $cacheID, array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else
                    $resultAll = $this->memcache->load($cacheID);
            }
            else {
                $siteCount = 1;
            }
        } else if ($formula == 'CFS') {
            if (!$all) {
                $cacheID = "REPORT_BUDGET_CFS_$prjKode" . "_$sitKode" . "_" . "$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $result = $this->budget->getBudgetProjectCFS(false, $prjKode, $sitKode, $detail, $offset);
                    $resultAll = $result;
                    $this->memcache->save($result, $cacheID, array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            } else {
                $cacheID = "REPORT_BUDGET_CFS_ALL_$prjKode" . "_$offset";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $result = $this->budget->getBudgetProjectCFS(true, '', '', $detail, $offset);
                    $this->memcache->save($result, $cacheID, array('REPORT'));
                    //cache time generated...
                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else {
                    $result = $this->memcache->load($cacheID);
                    $time = $this->memcache->load($cacheTimeID);
                    $resultAll = $result;
                    $fromCache = true;
                }
            }


            if ($sitKode == '') {

                $siteCount = count($this->budget->getBoq3All('all-current-by-cfskode', $prjKode));
                $allSite = true;
                $cacheID = "REPORT_BUDGET_CFS_$prjKode" . "_ALL_SITE";
                $cacheTimeID = $cacheID . "_TIME";
                if (!$this->memcache->test($cacheID)) {
                    $resultAll = $this->budget->getBudgetProjectAllCFS($prjKode);
                    $this->memcache->save($resultAll, $cacheID, array('REPORT'));
                    //cache time generated...

                    $time = array(
                        "generate" => $today->format("d M Y H:i:s"),
                        "expire" => $expire->format("d M Y H:i:s")
                    );
                    $this->memcache->save($time, $cacheTimeID, array('REPORT'));
                } else
                    $resultAll = $this->memcache->load($cacheID);
            }
            else {
                $siteCount = 1;
            }

            $this->view->isCFS = true;
        }
        //Get updated exchange rate from database
        $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			-- FROM exchange_rate
                        FROM finance_exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();

        if ($fromCache)
            $this->view->time = $time;

        $this->view->rateidr = $data['rateidr'];
        $this->view->tgl = $data['tgl_rate'];
        $this->view->result = $result;
        $this->view->resultAll = $resultAll;
        $this->view->detail = $detail;
        $this->view->rate = $rate;
        $this->view->formula = $formula;
        $this->view->limitPerPage = 20;
        $this->view->totalResult = $siteCount;
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function budgetbyperprojectAction() {
        
    }

    public function showprAction() {
        
    }

    public function showoutprpoAction() {
        
    }

    public function showoutprpodetailAction() {
        
    }

    public function showoutprpoprjAction() {
        
    }

    public function showoutprpoprjdetailAction() {
        
    }

    public function showlbarangAction() {
        
    }

    public function showrmdiAction() {
        
    }

    public function showdorAction() {
        
    }

    public function arfasfAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        
        $result = array();
        $datas = array();
                
        $result = $this->transaction->getArfasf($prjKode, $sitKode);
        
        if ($result) {
            foreach ($result as $k => $v) {
                if($v['stat_arf'] == 300 && $v['stat_revarf'] == 0){
                    continue;
                }
                
                $trano = $v['arf_num'];
                $kodeBrg = $v['kode_brg'];
                
                switch ($v['stat_asf']) {
                    case 100:
                        $v['stat_asf'] = "Submitted";
                        break;
                    case 150:
                        $v['stat_asf'] = "Resubmitted";
                        break;
                    case 200:
                        $v['stat_asf'] = "On Going";
                        break;
                    case 300:
                        $v['stat_asf'] = "Rejected";
                        break;
                    case 400:
                        $v['stat_asf'] = "Final Approval";
                        break;
                    default:
                        $v['stat_asf'] = "Not yet Created";
                }
                $arr = array(
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "arf_num" => $v['arf_num'],
                    "tgl_arf" => $v['tgl_arf'],
                    "workid" => $v['workid'],
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $v['nama_brg'],
                    "val_kode" => $v['val_kode'],
                    "requester" => $v['requester'],
                    "asf_num" => $v['asf_num'],
                    "tgl_asf" => $v['tgl_asf'],
                    "total_arf" => $v['total_arf'],
                    "total_asf" => $v['total_asf'],
                    "aging_arf_days" => $v['aging_arf_days'],
                    "total_asfcancel" => $v['total_asfcancel'],
                    "stat_asf" => $v['stat_asf'],
                    "tglstat_asf" => $v['tglstat_asf'],
                    "asf_desc" => $v['asf_desc'],
                    "total_payment" => $v['total_payment'],
                    "balance" => $v['balance'],
                );

                $datas[$trano][$kodeBrg][] = $arr;
            }
        }
        $sitKode = ($sitKode == null) ? "All" : $request->getParam('sit_kode');
        
        if(!$print){
            $this->view->result = $datas;
            $this->view->prjKode = $prjKode;
            $this->view->sitKode = $sitKode;
        } else {
            $newData = array();
            $no = 1;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCanIDR = 0;
            $totalASFCanUSD = 0;
            $totalBalIDR = 0;
            $totalBalUSD = 0;
            $totalPayIDR = 0;
            $totalPayUSD = 0;
            $totalBalpIDR = 0;
            $totalBalpUSD = 0;
            
            foreach ($datas as $k => $v)
            {
                $row = 1;
                $totalRow = 0;
                
                foreach($v as $k0 => $v0){
                    $totalRow += count($v[$k0]);
                }
                
                foreach($v as $k1 => $v1)
                {
                    $row2 = 1;
                    $totalASFperARF = 0;
                    
                    $totalRow2 = count($v[$k1]);
                    
                    foreach($v1 as $k2 => $v2){
                        
                        if($v2['stat_asf'] == 'Rejected'){
                            continue;
                        }
                        
                        $totalASFperARF += $v2['total_asf'];
                        $totalASFperARF += $v2['total_asfcancel'];
                    }
                    
                    foreach($v1 as $k3 => $v3)
                    {
                        $noarf = '-';
                        $tglarf = '-';
                        $requester = '-';
                        $ttlarf = 0;
                        $balance = 0;
                        $payment = 0;
                        
                        if (($row== 1)&&($row2== 1)){
                            $noarf = $v3['arf_num'];
                            $tglarf = ($v3['tgl_arf'] != '-') ? date('d-m-Y', strtotime($v3['tgl_arf'])) : '-';
                        }
                        
                        if ($row2== 1) {
                            if ($v3['val_kode'] != 'USD') {
                                $totalARFIDR += $v3['total_arf'];
                            } else {
                                $totalARFUSD += $v3['total_arf'];
                            }
                            
                            $requester = $v3['requester'];
                            $ttlarf = $v3['total_arf'];
                            $payment = $v3['total_payment'];
                            
                            $balance = $v3['total_arf'] - $totalASFperARF;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalIDR += $balance;
                            } else {
                                $totalBalUSD += $balance;
                            }
                        }
                        
                        if ($v3['val_kode'] != 'USD') {
                            $totalASFIDR += $v3['total_asf'];
                            $totalASFCanIDR += $v3['total_asfcancel'];
                        } else {
                            $totalASFUSD += $v3['total_asf'];
                            $totalASFCanUSD += $v3['total_asfcancel'];
                        }
                        
                        $newData[] = array(
                            "No" => $no,
                            "ARF Number" => $noarf,
                            "ARF Date" => $tglarf,
                            "Requester" => $requester,
                            "ARF Total" => $ttlarf,
                            "Payment" => $payment,
                            "ASF Number" => $v3['asf_num'],
                            "ASF Date" => ($v3['tgl_asf'] != '-') ? date('d-m-Y', strtotime($v3['tgl_asf'])) : '-',
                            "Expense Claim" => $v3['total_asf'],
                            "RPC" => $v3['total_asfcancel'],
                            "ASF Status" => $v3['stat_asf'],
                            "ASF Status Date" => $v3['tglstat_asf'],
                            "ASF Description" => $v3['asf_desc'],
                            "ARF Aging" => $v3['aging_arf_days'],
                            "Balance ARF to ASF" => $balance,
                            "Balance ARF to Payment" => $v3['balance'],
                        );
                        
                        $no++;
                        $row2++;
                    }
                    $row++;
                }
            }
            
            $newData[] = array(
                "No" => '',
                "ARF Number" => '',
                "ARF Date" => '',
                "Requester" => '<b>Total IDR</b>',
                "ARF Total" => '<b>'.$totalARFIDR.'</b>',
                "ASF Number" => '',
                "ASF Date" => '',
                "ASF Total" => '<b>'.$totalASFIDR.'</b>',
                "ASF Cancel Total" => '<b>'.$totalASFCanIDR.'</b>',
                "ASF Status" => '',
                "ASF Status Date" => '',
                "ASF Description" => '',
                "ARF Aging" => '',
                "Balance ARF to ASF" => '<b>'.$totalBalIDR.'</b>',
                "Payment" => '<b>'.$totalPayIDR.'</b>',
                "Balance ARF to Payment" => '<b>'.$totalBalpIDR.'</b>',
            );
            
            $newData[] = array(
                "No" => '',
                "ARF Number" => '',
                "ARF Date" => '',
                "Requester" => '<b>Total USD</b>',
                "ARF Total" => '<b>'.$totalARFUSD.'</b>',
                "ASF Number" => '',
                "ASF Date" => '',
                "ASF Total" => '<b>'.$totalASFUSD.'</b>',
                "ASF Cancel Total" => '<b>'.$totalASFCanUSD.'</b>',
                "ASF Status" => '',
                "ASF Status Date" => '',
                "ASF Description" => '',
                "ARF Aging" => '',
                "Balance ARF to ASF" => '<b>'.$totalBalIDR.'</b>',
                "Payment" => '<b>'.$totalPayIDR.'</b>',
                "Balance ARF to Payment" => '<b>'.$totalBalpIDR.'</b>',
            );

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "ARF to ASF ". $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function getdataarfasfAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'prj_kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getArfasf($prjKode, $sitKode);

        $jum = 0;
        $prevARF_no = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $totalARFIDR = 0;
        $totalASFIDR = 0;
        $totalBalanceIDR = 0;
        $prevBalance = 0;

        foreach ($hasil as $key => $val) {
            if ($key >= $offset && $jum < $limit) {
                if (!isset($val['kode_brg2'])) {
                    $cekASF = $this->ASF->getasfDetailByArfNo($val['arf_num'], $val['prj_kode'], $val['sit_kode']);
                    if (count($cekASF) > 0) {
                        $no_asf = '';
                        foreach ($cekASF as $key2 => $val2) {
                            $no_asf = $val2['trano'];
                            $cekASF2 = $this->ASF->getasfDetail($no_asf);
                            $val['asf_no'] = $cekASF2[0]['trano'];
                            $val['arf_nbr'] = $cekASF2[0]['arf_no'];
                            $val['tgl_asf'] = $cekASF2[0]['tgl'];
                            $val['totalasf'] = ($cekASF2[0]['qty'] * $cekASF2[0]['harga']);
                            $val['balance'] = $val['total'] - ($val['totalasf'] + $val['totalasfcancel']);
                            $val['invalid'] = true;
                            $val['invalid_ket'] = 'ARF is not match with ASF, this ARF (' . $val['arf_num'] . ') was paid with different ASF (' . $val['asf_no'] . ')!';

                            if ($val['asfcancel_num'] != '') {
                                $val['cancel_ket'] = 'This ARF (' . $val['arf_num'] . ') was cancelled (' . $val['asfcancel_num'] . ')!';
                            }
                            if ($prevARF_no != '' && $prevKode_brg != '' && $prevARF_no == $val['arf_num'] && $prevKode_brg == $val['kode_brg']) {
                                if ($prevTotal_IDR == $val['total_arf']) {
                                    $val['total_arf'] = '';
                                    $val['balance'] = $prevBalance - $val['total_arf'] - $val['total_asf'] - $val['total_asfcancel'];
                                    $prevBalance = $val['balance'];
                                }
                            }

                            $prevARF_no = $val['arf_num'];
                            $prevKode_brg = $val['kode_brg'];
                            if ($val['total_arf'] != '') {
                                $prevTotal_IDR = $val['total_arf'];
                                $prevBalance = $val['balance'];
                            }

                            $result['posts'][] = $val;
                        }
                        continue;
                    } else {
                        $val['invalid'] = true;
                        $val['invalid_ket'] = 'This ARF doesn\'t have ANY ASF!!';
                    }
                }
                if ($val['asfcancel_num'] != '') {
                    $val['cancel_ket'] = 'This ARF (' . $val['arf_num'] . ') was cancelled (' . $val['asfcancel_num'] . ')!';
                }
                if ($prevARF_no != '' && $prevKode_brg != '' && $prevARF_no == $val['arf_num'] && $prevKode_brg == $val['kode_brg']) {
                    if ($prevTotal_IDR == $val['total_arf']) {
                        $val['total_arf'] = '';
                        $val['balance'] = $prevBalance - $val['total_arf'] - $val['total_asf'] - $val['total_asfcancel'];
                        $prevBalance = $val['balance'];
                    }
                }

                $prevARF_no = $val['arf_num'];
                $prevKode_brg = $val['kode_brg'];
                if ($val['total_arf'] != '') {
                    $prevTotal_IDR = $val['total_arf'];
                    $prevBalance = $val['balance'];
                }
//        		echo $val['arf_num'] . " = " . $prevARF_no . " | " . $val['total_arf'] . " > " . $prevTotal_IDR . " > ". $val['total_asf'] . " > ". $val['total_asfcancel']  . " | " . $val['balance'] . " >> " .$prevBalance . "<br/>";

                if ($val['kode_brg'] != $val['kode_brg2'] && $val['invalid'] == '') {
                    $val['invalid'] = true;
                    $val['invalid_ket'] = 'ARF Product ID is not match with ASF, this Product ID (' . $val['kode_brg'] . ') was paid with different Product ID (' . $val['kode_brg2'] . ')!';
                }
                $val['requestor'] = QDC_User_Ldap::factory(array("uid" => $val['requestor']))->getName();
                $result['posts'][] = $val;
                $jum++;
            }
//        	$totalARFIDR += $val['total_arf'];
//        	$totalASFIDR += $val['total_asf'];
//        	$totalASFcancelIDR += $val['total_asfcancel'];
//        	$totalBalanceIDR += $val['balance'];
        }

        $resultArf = $this->budget->getArfd('summary', $prjKode);
        $totalARFIDR = $resultArf['totalARF'];


        $resultAsf = $this->budget->getAsfdd('summary', $prjKode);
        $totalASFIDR = $resultAsf['totalASFDD'];


        $resultAsfcancel = $this->budget->getAsfddCancel('summary', $prjKode);
        $totalASFcancelIDR = $resultAsfcancel['totalAsfddCancel'];


        $totalBalanceIDR = $totalARFIDR - ($totalASFIDR + $totalASFcancelIDR);

        if (count($result['posts']) > 0) {
            $result['posts'][0]['gTotalArf'] = $totalARFIDR;
            $result['posts'][0]['gTotalAsf'] = $totalASFIDR;
            $result['posts'][0]['gTotalAsfcancel'] = $totalASFcancelIDR;
            $result['posts'][0]['gTotalBalance'] = $totalBalanceIDR;
        }
        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function porpiAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $param = $request->getParam('param');

        $result = $this->transaction->getPorpi($prjKode, $sitKode, $param);
        $this->view->result = $result;
    }

    public function porpisumdtlAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano = $request->getParam('trano');

        $param = $request->getParam('param');

        if ($prjKode != '' && $sitKode != '')
            $this->view->url = "/default/report/getdataporpi/prj_kode/$prjKode/sit_kode/$sitKode/param/detail-rpi";
        if ($supKode != '')
            $this->view->url = "/default/report/getdataporpi/sup_kode/$supKode/param/detail-rpi";
        if ($trano != '')
            $this->view->url = "/default/report/getdataporpi/trano/$trano/param/detail-rpi";
            }
            
   public function porpisumdtlnewAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano_ = $request->getParam('trano_kode');
        $cod = ($request->getParam("cod") == "true") ? true : false;
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        $result = array();
        $datas = array();
        $result = $this->transaction->getPorpi2($prjKode, $sitKode, $supKode, $trano_, $cod);
        

        if ($result) {
            foreach ($result as $k => $v) {
                $trano = $v['trano_PO'];
                $tranorpi = $v['trano_RPI'];
                $arr = array(
                    "trano_PO" => $v['trano_PO'],
                    "trano_RPI" => $v['trano_RPI'],
                    "total_PO" => $v['total_PO'] ,
                    "val_kode" => $v['val_kode'] ,
                    "total_RPI" => $v['total_RPI'],
                    "total_netRPI" => $v['total_netRPI'],
                    "tgl_PayRPI" => $v['tgl_PayRPI'],
                    "total_PayRPI" => $v['total_PayRPI'],
                    "total_PayPO" => $v['total_PayPO']
                );

                $datas[$trano][$tranorpi][] = $arr;
            }
        }
        


        if (!$print) {
            $this->view->result = $datas;
            $this->view->cod = $cod;
        } else {
            $newData = array();
            $no = 1;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalNetRPIIDR = 0;
            $totalNetRPIUSD = 0;
            $totalPaymentIDR = 0;
            $totalPaymentUSD = 0;
            $totalBalRPIPOIDR = 0;
            $totalBalRPIPOUSD = 0;
            $totalBalPayRPIIDR = 0;
            $totalBalPayRPIUSD = 0;
            
            foreach ($datas as $k => $v) 
            {
                $row = 1;
                $totalRow = 0;
                $totalRPIperPO = 0;
                                
                foreach($v as $k0 => $v0){
                    $totalRow += count($v0);
                    $totalRPIperPO += $v0[0]['total_RPI'];
                }
                
                foreach($v as $k1 => $v1)
                {
                    $row2 = 1;
                    $totalPayperRPI = 0;
                    
                    $totalRow2 = count($v1);
                    
                    foreach($v1 as $k2 => $v2){
                        $totalPayperRPI += $v2['total_PayRPI'];
                    }
                    
                    foreach($v1 as $k3 => $v3)
                    {
                        $nopo = '-';
                        $totalpo = 0;
                        $valkode = '-';
                        $norpi = '-';
                        $totalrpi = 0;
                        $totalnetrpi = 0;
                        $balrpipo = 0;
                        $balpayrpi = 0;
                        
                        if (($row == 1)&&($row2 == 1)){
                            $totalPOIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_PO'] : 0;
                            $totalPOUSD += ($v3['val_kode'] == 'USD') ? $v3['total_PO'] : 0;
                            $nopo = $v3['trano_PO'];
                            $totalpo = $v3['total_PO'];
                            $valkode = $v3['val_kode'];
                            
                            $balrpipo = $v3['total_PO'] - $totalRPIperPO;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalRPIPOIDR += $balrpipo;
                            } else {
                                $totalBalRPIPOUSD += $balrpipo;
                            }
                        }
                        
                        if ($row2 == 1) {
                            $totalRPIIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_RPI'] : 0;
                            $totalRPIUSD += ($v3['val_kode'] == 'USD') ? $v3['total_RPI'] : 0;
                            $totalNetRPIIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_netRPI'] : 0;
                            $totalNetRPIUSD += ($v3['val_kode'] == 'USD') ? $v3['total_netRPI'] : 0;
                            
                            $norpi = $v3['trano_RPI'];
                            $totalrpi = $v3['total_RPI'];
                            $totalnetrpi = $v3['total_netRPI'];
                            
                            $balpayrpi = $v3['total_netRPI'] - $totalPayperRPI;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalPayRPIIDR += $balpayrpi;
                            } else {
                                $totalBalPayRPIUSD += $balpayrpiI;
                            }
                            
                        }
                        
                        $totalPaymentIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_PayRPI'] : 0;
                        $totalPaymentUSD += ($v3['val_kode'] == 'USD') ? $v3['total_PayRPI'] : 0;
                        
                        $newData[] = array(
                            "No" => $no,
                            "PO Number" => $nopo,
                            "PO Total" => $totalpo,
                            "Valuta" => $valkode,
                            "RPI Number" => $norpi,
                            "RPI Total" => $totalrpi,
                            "Balance PO - RPI" => $balrpipo,
                            "Net RPI Total" => $totalnetrpi,
                            "Payment" => $v3['total_PayRPI'],
                            "Payment Date" => ($v3['tgl_PayRPI'] != '-') ? date('d-m-Y', strtotime($v3['tgl_PayRPI'])) : '-',
                            "Balance Net RPI - Pay" => $balpayrpi,
                        );
                        
                        $no++;
                        $row2++;
                    }
                    $row++;
                }
            }
                        
            $newData[] = array(
                "No" => '',
                "PO Number" => '<b>Total IDR</b>',
                "PO Total" => '<b>'.$totalPOIDR.'<b>',
                "Valuta" => '',
                "RPI Number" => '',
                "RPI Total" => '<b>'.$totalRPIIDR.'<b>',
                "Balance PO - RPI" => '<b>'.$totalBalRPIPOIDR.'<b>',
                "Net RPI Total" => '<b>'.$totalNetRPIIDR.'<b>',
                "Payment" => '<b>'.$totalPaymentIDR.'<b>',
                "Payment Date" => '',
                "Balance Net RPI - Pay" => '<b>'.$totalBalPayRPIIDR.'<b>',
            );
            
            $newData[] = array(
                "No" => '',
                "PO Number" => '<b>Total USD</b>',
                "PO Total" => '<b>'.$totalPOUSD.'<b>',
                "Valuta" => '',
                "RPI Number" => '',
                "RPI Total" => '<b>'.$totalRPIUSD.'<b>',
                "Balance PO - RPI" => '<b>'.$totalBalRPIPOUSD.'<b>',
                "Net RPI Total" => '<b>'.$totalNetRPIUSD.'<b>',
                "Payment" => '<b>'.$totalPaymentUSD.'<b>',
                "Payment Date" => '',
                "Balance Net RPI - Pay" => '<b>'.$totalBalPayRPIUSD.'<b>',
            );
            
            $keter = '';

            if($prjKode != '' || $prjKode != null){
                if($sitKode != '' || $sitKode != null){
                    $keter = $prjKode . " - " . $sitKode;
                } else {
                    $keter = $prjKode;
                }
            }
            
            if($supKode != '' || $supKode != null){
                if($prjKode != '' || $prjKode != null){
                    $keter .= " - " . $supKode; 
                } else {
                    $keter = $supKode; 
                }
            }
            
            if($trano_ != '' || $trano_ != null){
                if(($prjKode != '' || $prjKode != null) || ($supKode != '' || $supKode != null)){
                    $keter .= " - " . $trano_;
                } else {
                    $keter = $trano_;
                }
            }
            
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "PO to RPI". $keter
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }        
 
    public function mdimdosumdtlAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
        //$this->view->param = $param;
    }

    public function mdodosumdtlAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
    }

    public function getdataporpiAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano = $request->getParam('trano');
        $param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getPorpi($prjKode, $sitKode, $supKode, $trano, $param);

        $jum = 0;
        $prevPO_no = '';
        $prevWorkid = '';
        $prevKode_brg = '';
        $prevTotal_IDR = 0;
        $prevTotal_USD = 0;
        $totalPOIDR = 0;
        $totalPOUSD = 0;
        $totalRPIIDR = 0;
        $totalRPIUSD = 0;

        $jumPO = 0;
        $indeks = 0;
        foreach ($hasil as $key => $val) {
            if ($prevPO_no != '' && $prevKode_brg != '' && $prevPO_no == $val['po_no'] && $prevKode_brg == $val['kode_brg']) {
                if ($prevTotal_IDR == $val['totalPO_IDR']) {
                    $val['totalPO_IDR'] = '';
//        				echo $val['po_no'] . " " . $prevTotal_IDR . " RPI:" .  $val['totalRPI_IDR'] . " prevbalance:" . $prevBalance_IDR . "->" . $val['balanceIDR'] . "<br/>" ; 
                } elseif ($prevTotal_USD == $val['totalPO_USD']) {
                    $val['totalPO_USD'] = '';
                }
            }
            if ($prevPO_no != $val['po_no'])
                $jumPO++;

            $prevPO_no = $val['po_no'];
            $prevWorkid = $val['workid'];
            $prevKode_brg = $val['kode_brg'];
            if ($val['totalPO_IDR'] != '') {
                $prevTotal_IDR = $val['totalPO_IDR'];
            }
            if ($val['totalPO_USD'] != '') {
                $prevTotal_USD = $val['totalPO_USD'];
            }
            if ($key >= $offset && $jum < $limit) {
                $val['id'] = $indeks + 1;
                $result['posts'][] = $val;
                $jum++;
                $indeks++;
            }


//        	$totalPOIDR += $val['totalPO_IDR'];
//        	$totalPOUSD += $val['totalPO_USD'];
//        	$totalRPIIDR += $val['totalRPI_IDR'];
//        	$totalRPIUSD += $val['totalRPI_USD'];
//        	$totalBalanceIDR += $val['balanceIDR'];
//        	$totalBalanceUSD += $val['balanceUSD'];	
        }

        $resultPod = $this->budget->getPodByOne('summary', $prjKode, $sitKode, $supKode, $trano);
        $totalPOIDR = $resultPod['totalIDR'];
        $totalPOUSD = $resultPod['totalHargaUSD'];


        $resultRpid = $this->budget->getRpidByOne('summary', $prjKode, $sitKode, $supKode, '', $trano);
        $totalRPIIDR = $resultRpid['totalIDR'];
        $totalRPIUSD = $resultRpid['totalHargaUSD'];

        if (count($result['posts']) > 0) {
            $result['posts'][0]['gtotalPO_IDR'] = $totalPOIDR;
            $result['posts'][0]['gtotalPO_USD'] = $totalPOUSD;
            $result['posts'][0]['gtotalRPI_IDR'] = $totalRPIIDR;
            $result['posts'][0]['gtotalRPI_USD'] = $totalRPIUSD;
        }
//        die;
//        echo '<pre>' . var_dump($result['posts']) . '</pre>';
        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    //mdimdo for grouping
    public function getdatamdimdoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getmdimdo($prjKode, $sitKode);

        $jum = 0;
        $prevMDI_no = '';
        $prevKode_brg = '';
        $qty_mdi = 0;
        $qty_mdo = 0;
        $balance = 0;

        foreach ($hasil as $key => $val) {
            if ($prevMDI_no != '' && $prevKode_brg != '' && $prevMDI_no == $val['mdi_no'] && $prevKode_brg == $val['kode_brg']) {
                if ($qty_mdi == $val['qty_mdi']) {
                    $val['qty_mdi'] = '';
                } elseif ($qty_mdo == $val['qty_mdo']) {
                    $val['qty_mdo'] = '';
                }
            }
            $prevMDI_no = $val['mdi_no'];
            $prevKode_brg = $val['kode_brg'];
            $qty_mdi = $val['qty_mdi'];
            $qty_mdo = $val['qty_mdo'];
            $balance = $val['balance'];

            if ($key >= $offset && $jum < $limit) {
                $result['posts'][] = $val;
                $jum++;
            }
            $qty_mdi += $val['qty_mdi'];
            $qty_mdo += $val['qty_mdo'];
            $balance += $val['balance'];
        }

        if (count($result['posts']) > 0) {
            $result['posts'][0]['gqty_mdi'] = $qty_mdi;
            $result['posts'][0]['gqty_mdo'] = $qty_mdo;
            $result['posts'][0]['gbalance'] = $balance;
        }

        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    //mdodo for grouping
    public function getdatamdodoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$param = $request->getParam('param');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getmdodo($prjKode, $sitKode);

        $jum = 0;
        $prevMDO_no = '';
        $prevKode_brg = '';
        $qty_mdo = 0;
        $qty_do = 0;
        $balance = 0;

        foreach ($hasil as $key => $val) {
            if ($prevMDO_no != '' && $prevKode_brg != '' && $prevMDO_no == $val['mdo_no'] && $prevKode_brg == $val['kode_brg']) {
                if ($qty_mdi == $val['qty_mdo']) {
                    $val['qty_mdo'] = '';
                } elseif ($qty_mdo == $val['qty_do']) {
                    $val['qty_do'] = '';
                }
            }
            $prevMDI_no = $val['mdo_no'];
            $prevKode_brg = $val['kode_brg'];
            $qty_mdi = $val['qty_mdo'];
            $qty_mdo = $val['qty_do'];
            $balance = $val['balance'];

            if ($key >= $offset && $jum < $limit) {
                $result['posts'][] = $val;
                $jum++;
            }
            $qty_mdo += $val['qty_mdo'];
            $qty_do += $val['qty_do'];
            $balance += $val['balance'];
        }

        if (count($result['posts']) > 0) {
            $result['posts'][0]['gqty_mdi'] = $qty_mdo;
            $result['posts'][0]['gqty_mdo'] = $qty_do;
            $result['posts'][0]['gbalance'] = $balance;
        }

        $result['count'] = count($hasil);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showporpisummaryAction() {
        
    }
    
    public function showporpisummarynewAction() {
        
    }

    public function outstandingporpiAction() {
        $prevPO_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
        $prevPO = '';
        $prevTotal_IDR = '';
        $prevTotal_USD = '';
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $start = $request->getParam('startdate');
        $end = $request->getParam('enddate');
        $export = ($this->_getParam("export") != '') ? true : false;
        $result = array();
//        if ($sitKode != '') {
        $result = $this->transaction->getOutPoRpi($prjKode, $sitKode, $start, $end);

//        } else {
//            $hasil = array();
//            $sql = "SELECT sit_kode, sit_nama
//        			FROM master_site
//        			WHERE prj_kode ='$prjKode'";
//
//            $fetch = $this->db->query($sql);
//            $master = $fetch->fetchAll();
//            foreach ($master as $key => $val) {
//                $sitKode = $val['sit_kode'];
//                $hasil[$sitKode] = $this->transaction->getPorpi($prjKode, $sitKode, 'detail-rpi');
//            }
//        }
//        $i = 0;
//
//        foreach ($hasil as $key => $val) {
//            foreach ($val as $key2 => $val2) {
//                $po = $val2['po_no'];
//
//                $result[$po]['po_no'] = $val2['po_no'];
//
//                $result[$po]['totalPO_IDR'] = $result[$po]['totalPO_IDR'] + $val2['totalPO_IDR'];
//                $result[$po]['totalPO_USD'] = $result[$po]['totalPO_USD'] + $val2['totalPO_USD'];
//
//                $result[$po]['totalRPI_IDR'] = $result[$po]['totalRPI_IDR'] + $val2['totalRPI_IDR'];
//                $result[$po]['totalRPI_USD'] = $result[$po]['totalRPI_USD'] + $val2['totalRPI_USD'];
//
//                $i++;
//            }
//        }
        if (!$export) {
            $this->view->result = $result;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            $valueIDR = '';
            $valueUSD = '';
            $ppnIDR = '';
            $ppnUSD = '';

            foreach ($result as $k => $v) {


                $newData[] = array(
                    "No" => $no,
                    "Trano" => $v['po_no'],
                    "PO Date" => date('d - M - Y', strtotime($v['tglpo'])),
                    "Project Code" => $v['prj_kode'],
                    "Total PO (IDR)" => number_format($v['totalPO_IDR'], 2),
                    "Total PO (USD)" => number_format($v['totalPO_USD'], 2),
                    "Total RPI(IDR)" => number_format($v['totalRPI_IDR'], 2),
                    "Total RPI(USD)" => number_format($v['totalRPI_USD'], 2),
                    "Balance (IDR)" => number_format(($v['totalPO_IDR'] - $v['totalRPI_IDR']), 2),
                    "Balance (USD)" => number_format(($v['totalPO_USD'] - $v['totalRPI_USD']), 2),
                    "Aging" => $v['delay'],
                );
                $no++;

//                $valueIDR = '';
//                $valueUSD = '';
//                $ppnIDR = '';
//                $ppnUSD = '';
            }



            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Outstanding PO to RPI"
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function outstandingmdimdoAction() {
        $prevMDI_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
        $prevMDI = '';
        $qty = '';
        $balance = '';
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = array();
        $result = $this->transaction->getdortodo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function viewoutstandingmdimdoAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = array();
        $result = $this->transaction->getdortodo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function mdimdoAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getMdimdo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function outstandingmdodoAction() {
        $prevMDO_no = '';
        $prevKode_brg = '';
        $prevWorkid = '';
        $prevMDO = '';
        $qty = '';
        $balance = '';
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = array();
        if ($sitKode != '') {
            $hasil[$sitKode] = $this->transaction->getMdodo($prjKode, $sitKode);
        } else {
            $hasil = array();
            $sql = "SELECT sit_kode, sit_nama
        			FROM master_site
        			WHERE prj_kode ='$prjKode' ";

            $fetch = $this->db->query($sql);
            $master = $fetch->fetchAll();
            foreach ($master as $key => $val) {
                $sitKode = $val['sit_kode'];
                $hasil[$sitKode] = $this->transaction->getMdodo($prjKode, $sitKode);
            }
        }

        $i = 0;
        foreach ($hasil as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $mdi = $val2['mdo_no'];
                if ($prevMDI_no == '') {
                    $result[$mdi]['mdo_no'] = $val2['mdo_no'];
                    $result[$mdi]['qty_mdo'] = $val2['qty_mdo'];
                    $result[$mdi]['qty_do'] = $val2['qty_do'];
                    $result[$mdi]['balance'] = $val2['balance'];
                }
                if ($prevMDO_no != '') {
                    if ($prevMDO_no != $val2['mdo_no']) {
                        $result[$po]['mdo_no'] = $val2['mdo_no'];
                        $result[$po]['qty_mdo'] = $val2['qty_mdo'];
                        $result[$po]['qty_do'] = $val2['qty_do'];
                        $result[$po]['balance'] = $val2['balance'];
                    }
                }

                $prevMDO_no = $val2['mdo_no'];
                $prevKode_brg = $val2['kode_brg'];
                $prevWorkid = $val2['workid'];
                $qty_mdo = $val2['qty_mdo'];
                $i++;
            }
        }

        $result = $this->transaction->getMdodo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function mdodoAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getMdodo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function arfsummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $kode_brg = $request->getParam('kode_brg');
        $workid = $request->getParam('workid');

        $requestor = $request->getParam('requestor');


        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $arf = new Default_Models_AdvanceRequestForm();
        $result = $arf->getArfSum($prjKode, $sitKode, $kode_brg, $workid, $requestor);
        $this->view->limitPerPage = 100;
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function asfsummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $asf = new Default_Models_AdvanceSettlementForm();
        $result = $asf->getasfSum($prjKode, $sitKode);
        $this->view->limitPerPage = 100;
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function arfdetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;

        $arf = new Default_Models_AdvanceRequestForm();
        $result = $arf->getArfDetail($noTrans);
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    public function invoicedetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $finance = new Finance_Models_InvoiceDetail();
        $invoicePerDate = $finance->getTotalInvoicePerItem($prj_kode, $sit_kode, $start_date, $end_date);
        $this->view->result = $invoicePerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function viewinvoicedetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $finance = new Finance_Models_InvoiceDetail();
        $invoicePerDate = $finance->getTotalInvoicePerItem($prj_kode, $sit_kode,$start_date,$end_date);
        $this->view->result = $invoicePerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
    
    public function paidcostdetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $paidCostPerDate = $cost->paidCostPerDate($prj_kode, $sit_kode, $start_date, $end_date);
        $this->view->result = $paidCostPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
    
    public function viewpaidcostdetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $rpiPerDate = $cost->paidCostPerDate($prj_kode, $sit_kode, $start_date, $end_date);
        $this->view->result = $rpiPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function committeddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $committedPerDate = $cost->committedCostPerDate($prj_kode, $sit_kode, $start_date, $end_date);

        $this->view->result = $committedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function recordeddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $recordedPerDate = $cost->recordedCostPerDate($prj_kode, $sit_kode, $start_date, $end_date);

        $this->view->result = $recordedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function previousrecordeddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $recordedPerDate = $cost->recordedCostPerDate($prj_kode, $sit_kode, $start_date, $end_date);
        $tgl = explode('-', $end_date);
        $previousrecordedPerDate = array();

        foreach ($recordedPerDate As $index => $data) {
            if ($data['tgl'] < $tgl[0] . '-' . $tgl[1] . '-' . '01') {
                $previousrecordedPerDate[$index] = $recordedPerDate[$index];
            }
        }

        $this->view->result = $previousrecordedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function viewcommitteddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $committedPerDate = $cost->committedCostPerDate($prj_kode, $sit_kode,$start_date,$end_date);
 
        $this->view->result = $committedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
    
    public function viewrecordeddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $recordedPerDate = $cost->recordedCostPerDate($prj_kode, $sit_kode,$start_date,$end_date);

        $this->view->result = $recordedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
    
    public function viewpreviousrecordeddetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $cost = new Default_Models_Cost();
        $recordedPerDate = $cost->recordedCostPerDate($prj_kode, $sit_kode,$start_date,$end_date);
        $tgl = explode('-', $end_date);
        $previousrecordedPerDate = array();
 
        foreach($recordedPerDate As $index => $data)
        {
            if($data['tgl'] < $tgl[0].'-'.$tgl[1].'-'.'01') 
            {
                $previousrecordedPerDate[$index] =  $recordedPerDate[$index] ;
            }
        }

        $this->view->result = $previousrecordedPerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }
    
    public function receivedetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $finance = new Finance_Models_PaymentInvoice();
        $invoicePerDate = $finance->getTotalPaymentInvoicePerDateNoPPN($prj_kode, $sit_kode, $start_date, $end_date);
        $this->view->result = $invoicePerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function viewreceivedetailAction() {
        $request = $this->getRequest();

        $prj_kode = $request->getParam('prj_kode');
        $sit_kode = $request->getParam('sit_kode');
        $start_date = $request->getParam('start_date');
        $end_date = $request->getParam('end_date');

        $finance = new Finance_Models_PaymentInvoice();
        $invoicePerDate = $finance->getTotalPaymentInvoicePerDateNoPPN($prj_kode, $sit_kode,$start_date,$end_date);
        $this->view->result = $invoicePerDate;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;
    }

    public function asfdetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $asf = new Default_Models_AdvanceSettlementForm();
        $result = $asf->getasfDetail($noTrans);
        $this->view->result = $result;
    }

    public function prsummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $kode_brg = $request->getParam('kode_brg');
        $workid = $request->getParam('workid');

        if ($request->getParam('tgl1') != '')
            $tgl1 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl1'))));
        if ($request->getParam('tgl2') != '')
            $tgl2 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl2'))));

        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $pr = new Default_Models_ProcurementRequest();
        $result = $pr->getPrSum($prjKode, $sitKode, $kode_brg, $workid, $tgl1, $tgl2);

        $this->view->limitPerPage = 100;
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    public function posummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        if ($request->getParam('tgl1') != '')
            $tgl1 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl1'))));
        if ($request->getParam('tgl2') != '')
            $tgl2 = date("Y-m-d", strtotime(urldecode($request->getParam('tgl2'))));
        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $po = new Default_Models_ProcurementPod();

        $result = $po->getPoSum($prjKode, $sitKode, $supKode, $tgl1, $tgl2);

        $this->view->limitPerPage = 100;
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    //RPI Summary Report
    public function rpisummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $ppn_ref_number = $request->getParam('ppn_ref_number');
        $ppn_ref_number = str_replace("_", "/", $ppn_ref_number);
        $export = ($this->_getParam("export") != '') ? true : false;

        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $rpi = new Default_Models_RequestPaymentInvoice();
        $result = $rpi->getrpiSum($prjKode, $sitKode, $supKode, $ppn_ref_number);
        $totalIDR = 0;
        $totalUSD = 0;
        $index = count($result);

        foreach ($result as $k => $val) {
            $totalIDR = $totalIDR + $val['total_IDR'];
            $totalUSD = $totalUSD + $val['total_USD'];
            if ($k == ($index - 1)) {
                $result[$k + 1]['trano'] = '';
                $result[$k + 1]['tgl'] = '';
                $result[$k + 1]['prj_kode'] = '';
                $result[$k + 1]['prj_nama'] = '';
                $result[$k + 1]['sit_kode'] = '';
                $result[$k + 1]['sit_nama'] = '';
                $result[$k + 1]['prj_nama'] = '';
                $result[$k + 1]['workid'] = '';
                $result[$k + 1]['workname'] = '';
                $result[$k + 1]['val_kode'] = '';
                $result[$k + 1]['po_no'] = 'Total';
                $result[$k + 1]['total_IDR'] = floatval($totalIDR);
                $result[$k + 1]['total_USD'] = floatval($totalUSD);
                $result[$k + 1]['pc_nama'] = '';
                $result[$k + 1]['sup_kode'] = '';
                $result[$k + 1]['sup_nama'] = '';
                $result[$k + 1]['ppn_ref_number'] = '';
            }
        }


        if (!$export) {

            $this->view->limitPerPage = 100;
            $this->view->result = $result;
            $this->view->totalResult = (count($result) - 1);
            $this->view->current = $current;
            $this->view->currentPage = $currentPage;
            $this->view->requested = $requested;
            $this->view->pageUrl = $this->view->url();
        } else {
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "RPI Summary"
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        ),
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($result)->toExcel5Stream();
        }
    }

    public function mdosummaryAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $mdo = new Default_Models_MaterialDeliveryOrder();
        $result = $mdo->getMdoSum($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function prdetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;
        $pr = new Default_Models_ProcurementRequest();
        $result = $pr->getPrDetail($noTrans);
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    public function podetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;
        $po = new Default_Models_ProcurementPod();
        $poh = new Default_Models_ProcurementPoh();
        $result = $po->getPoDetail($noTrans);
        $etc = $poh->getPohDetail($noTrans);
        $this->view->result = $result;
        $this->view->etc = $etc;
        $this->view->popup = $popup;
    }

    //PO PPN Report
    public function poppnAction() {
        $request = $this->getRequest();

        $tgla = $request->getParam('tgl1');
        $tglb = $request->getParam('tgl2');

        $current = $request->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $request->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;
        $requested = $request->getParam('requested');
        if ($requested == '')
            $requested = 0;

        $ppn = new Default_Models_ProcurementPoh();
        $result = $ppn->getPoPpn($tgla, $tglb);
        $gtotal = 0;
        $gtotalAll = 0;
        $gtotalPpn = 0;
        foreach ($result as $key => $val) {
            if ($val['val_kode'] != 'IDR') {
                $gtotalPpn += ($val['ppn'] * $val['rateidr']);
                $gtotal += ($val['jumlah'] * $val['rateidr']);
            } else {
                $gtotalPpn += $val['ppn'];
                $gtotal += $val['jumlah'];
            }
        }
        $gtotalAll += ($gtotal + $gtotalPpn);

        $this->view->grandTotal = $gtotal;
        $this->view->grandTotalPpn = $gtotalPpn;
        $this->view->grandTotalAll = $gtotalAll;
        $this->view->limitPerPage = 100;
        $this->view->result = $result;
        $this->view->totalResult = count($result);
        $this->view->current = $current;
        $this->view->currentPage = $currentPage;
        $this->view->requested = $requested;
        $this->view->pageUrl = $this->view->url();
    }

    //RPI Detail Report
    public function rpidetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $rpi = new Default_Models_RequestPaymentInvoice();
        $result = $rpi->getrpiDetail($noTrans);
        $this->view->result = $result;
    }

    public function mdidetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $mdi = new Default_Models_MaterialDeliveryInstruction();
        $result = $mdi->getMdiDetail($noTrans);
        $this->view->result = $result;
    }

    public function mdodetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $mdo = new Default_Models_MaterialDeliveryOrder();
        $result = $mdo->getMdoDetail($noTrans);
        $this->view->result = $result;
    }

    public function dodetailAction() {
        $request = $this->getRequest();

        $noTrans = $request->getParam('trano');

        $do = new Default_Models_DeliveryOrder();
        $result = $do->getDoDetail($noTrans);
        $this->view->popup = $this->_getParam("popup");
        $this->view->isprinted = $this->_getParam("isprinted");
        $this->view->result = $result;
    }

    public function dodetailfromJournalAction() {
        $request = $this->getRequest();
        // maunya satu pintu untuk semua transaksi report detail journal, tapi dikejar waktu..sorry bro


        $noTrans = $request->getParam('trano');
//        $jenis_jurnal = $request->getParam('jenis_jurnal');

        $do = new Default_Models_DeliveryOrder();
        $dojournal = new Finance_Models_AccountingInventoryOut();
        $result = $do->getDoDetail($noTrans);
        $datadojournal = $dojournal->fetchAll("ref_number = '$noTrans'");
        if ($datadojournal->toArray())
            $datadojournal = $datadojournal->toArray();

        $this->view->popup = $this->_getParam("popup");
        $this->view->isprinted = $this->_getParam("isprinted");
        $this->view->result = $result;
        $this->view->rateidr = $datadojournal[0]['rateidr'];
    }

    public function showpodetailAction() {
        
    }

    public function showarfasfAction() {
        
    }

    public function showarfsummaryAction() {
        
    }

    public function showasfsummaryAction() {
        
    }

    public function showarfdetailAction() {
        
    }

    public function showasfdetailAction() {
        
    }

    public function showprsummaryAction() {
        
    }

    public function showposummaryAction() {
        
    }

    public function showpoppnAction() {
        
    }

    public function showrpisummaryAction() {
        
    }

    public function showrpidetailAction() {
        
    }

    public function showprdetailAction() {
        
    }

    public function showporpiAction() {
        
    }

    public function showmdimdoAction() {
        
    }

    public function showmdodoAction() {
        
    }

    public function showwhreturnAction() {
        
    }

    public function showmdosummaryAction() {
        
    }

    public function whreturnAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $trano = $request->getParam('trano');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
        // $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));

        $result = $this->transaction->getWhreturn($prjKode, $sitKode, $trano);
        $this->view->result = $result;
    }

    public function showwhbringbackAction() {
        
    }

    public function whbringbackAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $trano = $request->getParam('trano');
        //$stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
        // $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));

        $result = $this->transaction->getWhbringback($prjKode, $sitKode, $trano);
        $this->view->result = $result;
    }

    public function showwhsupplierAction() {
        
    }

    public function showwhsupplierprjAction() {
        
    }

    public function whsupplierprjAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $result = $this->transaction->getWhsupplierprj($prjKode);
        $this->view->result = $result;
    }

    public function whsupplierAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano = $request->getParam('trano');
        $tgl = $request->getParam('tgl');
        $param = $request->getParam('param');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
//        $result = $this->transaction->getWhsupplier($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getWhsupplier($prjKode, $sitKode, $supKode, $tgl, $param, $trano);
        $this->view->result = $result;
    }

    public function showdetailprojectbudgetAction() {
        
    }

    public function detailprojectbudgetAction() {
        $request = $this->getRequest();

        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->budget->getBoq3('boq3-gabung', $prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function detailprAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getDetailpr($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function summaryprAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->transaction->getDetailpr($prjKode, $sitKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
    }

    public function outprpoAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $sitKode = $request->getParam('sit_kode');
        $result = $this->transaction->getOutprpo($prjKode, $sitKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

//             var_dump('Project dan Site');die();
//             $result = $this->transaction->getOutprpoprj($prjKode);
//             $this->view->result = $result;
//             $this->view->prjKode = $prjKode;     
    }

    public function outprpodetailAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        //$sitKode = $request->getParam('sit_kode');
        $result = $this->transaction->getOutprpodetail2($prjKode, $sitKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;

//             var_dump('Project dan Site');die();
//             $result = $this->transaction->getOutprpoprj($prjKode);
//             $this->view->result = $result;
//             $this->view->prjKode = $prjKode;     
    }

    public function outprpoprjAction() {

        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $result = $this->transaction->getOutprpoprj($prjKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
    }
    
    public function outprpoprjdetailAction() {

        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $result = $this->transaction->getOutprpoprjdetail($prjKode);
        $this->view->result = $result;
        $this->view->prjKode = $prjKode;
    }
    
    public function showprdorAction() {
        
    }
    
    public function prdorAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $tranos = $request->getParam('trano_kode');
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        $result = array();
        $result = $this->transaction->getPrdor($prjKode, $sitKode, $tranos, $start, $end);
      

        if ($result) {
            foreach ($result as $k => $v) {
                $trano = $v['pr_no'];
                $arr = array(
                    "pr_no" => $v['pr_no'],
                    "kode_brg_pr" =>$v['kode_brg_pr'],
                    "qty_pr" =>$v['qty_pr'],
                    "dor_no" => $v['dor_no'],
                    "kode_brg_dor" =>$v['kode_brg_dor'],
                    "qty_dor" =>$v['qty_dor'],
                );

                $datas[$trano][] = $arr;
            }
        }
       
        

        if (!$print) {
            $this->view->result = $datas;
        } else {

            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;


            foreach ($result as $k => $v) {


                $newData[] = array(
                    "No" => $no,
                    "PR Number" => $v['pr_no'],
                    "Kode Barang" =>$v['kode_brg_pr'],
                    "Qty PR" =>number_format($v['qty_pr'],2),
                    "DOR Number" => $v['dor_no'],
                    "Kode barang" =>$v['kode_brg_dor'],
                    "Qty DOR" =>number_format($v['qty_dor'],2),
                    
                );
                $no++;
            }

            $newData[] = array(
                "No" => '',
                "PR Number" => "",
                "Kode Barang" =>"",
                "Qty" =>"",
                "DOR Number" =>"",
                "Kode barang" =>"",
                "Qty" =>"",
            );


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "PR to DOR". $prjKode . "_" . $sitKode
                    ))
                   
                    ->write($newData)->toExcel5Stream();
        }
    }    
    

    public function mdiAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
//        $result = $this->transaction->getMdi($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getMdi($prjKode, $sitKode);
        $this->view->result = $result;
//        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;
    }

    public function dorAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $export = ($this->_getParam("export") != '') ? true : false;

        $result = $this->transaction->getDor($prjKode, $sitKode);
        if (!$export) {
            $this->view->result = $result;
        } else {
            $this->_helper->viewRenderer->setNoRender();
            $newData = array();
            $no = 1;

            foreach ($result as $k => $v) {
                $newData[] = array(
                    "No" => $no,
                    "Project Code" => $v['prj_kode'],
                    "Project Name" => $v['prj_nama'],
                    "Date" => $v['tgl_return'],
                    "Work ID" => $v['workid'],
                    "Work Name" => $v['workname'],
                    "Product ID" => $v['kode_brg'],
                    "Description" => $v['nama_brg'],
                    "Qty" => $v['qty'],
                    "UoM" => $v['uom']
                );
                $no++;
            }


            QDC_Adapter_Excel::factory(array(
                        "fileName" => "DOR Summary"
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function showdoAction() {
        
    }

    public function showdosummaryAction() {
        
    }

    public function dosummaryAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $result = $this->transaction->getDo($prjKode, $sitKode);
        $this->view->result = $result;
    }

    public function showmdidetailAction() {
        
    }

    public function showmdodetailAction() {
        
    }

    public function showdodetailAction() {
        
    }

    public function doAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
//        $stgl1 = date('Y-m-d',strtotime($request->getParam('tgl1')));
//        $stgl2 = date('Y-m-d',strtotime($request->getParam('tgl2')));
//        $result = $this->transaction->getMdi($prjKode,$sitKode,$stgl1,$stgl2);
        $result = $this->transaction->getDo($prjKode, $sitKode);
        $this->view->result = $result;
//        $this->view->prjKode = $prjKode;
//        $this->view->sitKode = $sitKode;
    }

    public function showbarangAction() {
        
    }

    public function showformbarangAction() {
        
    }

    public function showoutporpiAction() {
        
    }

    public function showoutprpodetAction() {
        
    }
    

   public function showoutprpodetnewAction() {
        
    }

    public function outprpodetAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
    }
    
    public function outprpodetnewAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $this->view->prjKode = $prjKode;
        $this->view->sitKode = $sitKode;
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        
        $hasil = $this->transaction->getoutprponew($prjKode, $sitKode);
        
        if ($hasil) {
            foreach ($hasil as $k => $v) {
                $tranopr = $v['no_pr'];
                $kodebrg = $v['kode_brg'];
                $v['nama_brg'] = wordwrap($v['nama_brg'], 30, "<br>");

                $datas[$tranopr][$kodebrg][] = $v;
            }
        }
        
        if(!$print){
            $this->view->result = $datas;
//            $this->view->limitPerPage = $limit;
//            $this->view->totalResult = $count;
//            $this->view->current = $current;
//            $this->view->currentPage = $currentPage;
//            $this->view->requested = $requested;
//            $this->view->pageUrl = $this->view->url();
        } else {
            $newData = array();
            $no = 1;
            $totalPRIDR = 0;
            $totalPRUSD = 0;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalBalIDR = 0;
            $totalBalUSD = 0;
            
            foreach ($datas as $k => $v) 
            {
                $row = 1;
                $totalRow = 0;
                
                foreach($v as $k0 => $v0){
                    $totalRow += count($v[$k0]);
                }
                
                foreach($v as $k1 => $v1)
                {
                    $row2 = 1;
                    $totalPOperPR = 0;
                    
                    $totalRow2 = count($v[$k1]);
                    
                    foreach($v1 as $kpopr => $vpopr){
                        $totalPOperPR += $vpopr['jumlah_po'];
                    } 
                    
                    foreach($v1 as $k3 => $v3)
                    {
                        $nopr = '-';
                        $tglpr = '-';
                        $kodebrg = '-';
                        $namabrg = '-';
                        $jumlahpr = 0;
                        $valkode = '-';
                        $balance = 0;
                        
                        if (($row== 1)&&($row2== 1)){
                            $nopr = $v3['no_pr'];
                            $tglpr = ($v3['tgl_pr'] != '-') ? date('d-m-Y', strtotime($v3['tgl_pr'])) : '-';
                        }
                        
                        if ($row2== 1) {
                            if ($v3['val_kode'] != 'USD') {
                                $totalPRIDR += $v3['jumlah_pr'];
                            } else {
                                $totalPRUSD += $v3['jumlah_pr'];
                            }
                            
                            $kodebrg = $v3['kode_brg'];
                            $namabrg = $v3['nama_brg'];
                            $jumlahpr = $v3['jumlah_pr'];
                            $valkode = $v3['val_kode'];
                            
                            $balance = $v3['jumlah_pr'] - $totalPOperPR;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalIDR += $balance;
                            } else {
                                $totalBalUSD += $balance;
                            }
                        }
                        
                        if ($v3['val_kode'] != 'USD') {
                            $totalPOIDR += $v3['jumlah_po'];
                        } else {
                            $totalPOUSD += $v3['jumlah_po'];
                        }
                        
                        $newData[] = array(
                            "No" => $no,
                            "PR Number" => $nopr,
                            "PR Date" => $tglpr,
                            "Product ID" => $kodebrg,
                            "Description" => $namabrg,
                            "PR Total" => $jumlahpr,
                            "Valuta" => $valkode,
                            "PO Number" => $v3['no_po'],
                            "PO Date" => ($v3['tgl_po'] != '-') ? date('d-m-Y', strtotime($v3['tgl_po'])) : '-',
                            "PO Qty" => $v3['qty_po'],
                            "PO Total" => $v3['jumlah_po'],
                            "Balance" => $balance,
                        );
                        
                        $no++;
                        $row2++;
                    }
                    $row++;
                }
            }
            
            $newData[] = array(
                "No" => '',
                "PR Number" => '',
                "PR Date" => '',
                "Product ID" => '',                    
                "Description" => '<b>Total IDR</b>',
                "PR Total" => '<b>'.$totalPRIDR.'</b>',
                "Valuta" => '',
                "PO Number" => '',
                "PO Date" => '',
                "PO Qty" => '',                    
                "PO Total" => '<b>'.$totalPOIDR.'</b>',
                "Balance Total" => '<b>'.$totalBalIDR.'</b>',
            );
            
            $newData[] = array(
                "No" => '',
                "PR Number" => '',
                "PR Date" => '',
                "Product ID" => '',                    
                "Description" => '<b>Total USD</b>',
                "PR Total" => '<b>'.$totalPRUSD.'</b>',
                "Valuta" => '',
                "PO Number" => '',
                "PO Date" => '',
                "PO Qty" => '',                    
                "PO Total" => '<b>'.$totalPOUSD.'</b>',
                "Balance Total" => '<b>'.$totalBalUSD.'</b>',
            );

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "PR to PO ". $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        9 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        11 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }

    public function getdataprpodetAction() {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'Prj_Kode';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $hasil = $this->transaction->getoutprpo($prjKode, $sitKode);
        $result['posts'] = $hasil;
        $result['count'] = count($result['posts']);
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);
//        $json = str_replace("\\",'',$json);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showoutmdimdoAction() {
        
    }

    public function showoutmdodoAction() {
        
    }

    public function historypriceAction() {
        $request = $this->getRequest();

        $kodeBrg = $request->getParam('kode_brg');
        $popup = $request->getParam('popup');

        if ($popup == '')
            $popup = false;

        $sql = "SELECT DATE_FORMAT(tgl,'%Y-%m-%d') as tgl,kode_brg,nama_brg,prj_kode,prj_nama,sit_kode,sit_nama,harga,petugas,sup_kode,sup_nama,val_kode FROM procurement_pod where kode_brg = '$kodeBrg' order by tgl desc LIMIT 5";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();
        $this->view->result = $result;
        $this->view->popup = $popup;
    }

    function super_unique($data) {
        $result = array_map("unserialize", array_unique(array_map("serialize", $data)));

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->super_unique($value);
            }
        }

        return $result;
    }

    public function historypoAction() {
        $trano = $this->getRequest()->getParam('trano');

        $this->view->trano = $trano;
        $log = $this->logtrans->fetchAll("trano = '$trano'", array("tgl ASC"));

        if ($log) {
            $log = $log->toArray();
        }

//        var_dump($log);die;

        $hitung = 0;
        ;

        foreach ($log as $key => $val) {
            $json = $val['data_before'];
            $data2 = Zend_Json::decode($json);

            foreach ($data2['po-detail-before'] as $key3 => $val3) {
                $ygtampil[$hitung][] = $val3;
            }
            if ($hitung > 0) {
                $json = $val['data_after'];
                $data2 = Zend_Json::decode($json);

                foreach ($data2['po-detail-after'] as $key2 => $val2) {
                    $ygtampil[$hitung][] = $val2;
                }
            } else {
                
            }

            $hitung++;
        }
    }

    public function showArfAgingAction() {
        
    }

    public function arfDetailAgingAction() {
        $this->view->trano = $this->getRequest()->getParam("trano");
        $this->view->prj_kode = $this->getRequest()->getParam("prj_kode");
        $this->view->sit_kode = $this->getRequest()->getParam("sit_kode");
        $this->view->type = $this->getRequest()->getParam("type");

        if (!$this->view->trano) {
            $arf = $this->quantityHelper->getArfQuantity($this->view->prj_kode, $this->view->sit_kode);
            $this->view->totalARFIDR = $arf['totalIDR'];
            $this->view->totalARFUSD = $arf['totalHargaUSD'];

            $asf = $this->quantityHelper->getAsfddQuantity($this->view->prj_kode, $this->view->sit_kode);
            $this->view->totalASFIDR = $asf['totalIDR'];
            $this->view->totalASFUSD = $asf['totalHargaUSD'];

            $asfcancel = $this->quantityHelper->getAsfcancelQuantity($this->view->prj_kode, $this->view->sit_kode);
            $this->view->totalASFCancelIDR = $asfcancel['totalIDR'];
            $this->view->totalASFCancelUSD = $asfcancel['totalHargaUSD'];

            $this->view->balanceIDR = $arf['totalIDR'] - ($asf['totalIDR'] + $asfcancel['totalIDR']);
            $this->view->balanceUSD = $arf['totalHargaUSD'] - ($asf['totalHargaUSD'] + $asfcancel['totalHargaUSD']);
        }
    }

    public function getDataArfDetailAgingAction() {

        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");
        $prj_kode = $this->getRequest()->getParam("prj_kode");
        $sit_kode = $this->getRequest()->getParam("sit_kode");
        $type = $this->getRequest()->getParam("type");
        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;

        $result = array();
        if ($trano) {
            $data = $this->DEFAULT->AdvanceRequestFormH->fetchRow("trano = '$trano'");
            if ($data) {
                $data = $data->toArray();
                $totalARF = $this->DEFAULT->AdvanceRequestFormD->totalSummary($trano);
                $totalASF = $this->DEFAULT->AdvanceSettlementForm->totalSummary($trano);
                $totalASFCancel = $this->DEFAULT->AdvanceSettlementFormCancel->totalSummary($trano);
                $tglSettle = $this->DEFAULT->AdvanceSettlementFormD->settleDateFirst($trano);

                if ($tglSettle) {
                    $totalDays = $this->utility->dates_diff($data['tgl'], $tglSettle);
                } else
                    $totalDaysARF = $this->utility->dates_diff($data['tgl'], date("Y-m-d"));
                $result[] = array(
                    "trano" => $trano,
                    "prj_kode" => $data['prj_kode'],
                    "sit_kode" => $data['sit_kode'],
                    "tgl" => $data['tgl'],
                    "tgl_settle" => $tglSettle,
                    "aging" => $totalDays,
                    "agingARF" => $totalDaysARF,
                    "description" => $data['ketin'],
                    "uid" => $data['user'],
                    "val_kode" => $data['val_kode'],
                    "rateidr" => $data['rateidr'],
                    "total_bayar" => $totalARF,
                    "total_settle" => $totalASF,
                    "total_settle_cancel" => $totalASFCancel,
                    "balance" => $totalARF - ($totalASF + $totalASFCancel),
                    "username" => QDC_User_Ldap::factory(array("uid" => $data['user']))->getName(),
                );
            }
        }
        else {
            if ($sit_kode)
                $where = " AND sit_kode = '$sit_kode'";

            if ($type == 'all') {
                $data = $this->DEFAULT->AdvanceRequestFormH->fetchAll("prj_kode = '$prj_kode' $where", array("trano DESC"), $limit, $offset);
                $count = $this->DEFAULT->AdvanceRequestFormH->fetchAll("prj_kode = '$prj_kode' $where")->count();
            } else {

                if ($type == 'settle')
                    $where2 = " AND b.arf_no IS NOT NULL";
                if ($type == 'issued')
                    $where2 = " AND b.arf_no IS NULL";

                if ($sit_kode)
                    $where = " AND a.sit_kode = '$sit_kode'";

                $select = $this->db->select()
                        ->from(
                                array("a" => $this->DEFAULT->AdvanceRequestFormH->__name()), array(
                            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS a.id"),
                            "trano",
                            "prj_kode",
                            "sit_kode",
                            "prj_nama",
                            "sit_nama",
                            "tgl",
                            "user",
                            "rateidr",
                            "val_kode"
                                )
                        )
                        ->joinLeft(
                                array("b" => $this->DEFAULT->AdvanceSettlementFormD->__name()), "a.trano = b.arf_no", array("arf_no")
                        )
                        ->where("a.prj_kode = '$prj_kode' $where2 $where")
                        ->group(array("a.trano"))
                        ->order(array("a.trano DESC"))
                        ->limit($limit, $offset);

                $data = $this->db->fetchAll($select);
                $count = $this->db->fetchOne('SELECT FOUND_ROWS()');
            }

            if ($data) {
                foreach ($data as $k => $v) {
                    $totalDays = null;
                    $totalDaysARF = null;
                    $trano = $v['trano'];
                    $totalARF = $this->DEFAULT->AdvanceRequestFormD->totalSummary($trano);
                    $totalASF = $this->DEFAULT->AdvanceSettlementForm->totalSummary($trano);
                    if ($type == 'settle')
                        $totalASFCancel = $this->DEFAULT->AdvanceSettlementFormCancel->totalSummary($trano);
                    $tglSettle = $this->DEFAULT->AdvanceSettlementFormD->settleDateFirst($trano);
                    if ($tglSettle) {
                        $totalDays = $this->utility->dates_diff($v['tgl'], $tglSettle);
                    } else
                        $totalDaysARF = $this->utility->dates_diff($v['tgl'], date("Y-m-d"));
                    $result[] = array(
                        "trano" => $trano,
                        "prj_kode" => $v['prj_kode'],
                        "sit_kode" => $v['sit_kode'],
                        "tgl" => $v['tgl'],
                        "tgl_settle" => $tglSettle,
                        "aging" => $totalDays,
                        "agingARF" => $totalDaysARF,
                        "description" => $v['ketin'],
                        "uid" => $v['user'],
                        "val_kode" => $v['val_kode'],
                        "rateidr" => $v['rateidr'],
                        "total_bayar" => $totalARF,
                        "total_settle" => $totalASF,
                        "total_settle_cancel" => $totalASFCancel,
                        "balance" => $totalARF - ($totalASF + $totalASFCancel),
                        "username" => QDC_User_Ldap::factory(array("uid" => $v['user']))->getName(),
                    );
                }
            }
        }

        $json = Zend_Json::encode(array("data" => $result, "total" => $count));
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function myApprovedDocumentAction() {
        
    }

    public function showMyApprovedDocumentAction() {
        $trano = $this->getRequest()->getParam('trano');
        $print = ($this->getRequest()->getParam('print') == '') ? false : true;
        $type = $this->getRequest()->getParam('type');
        $prj_kode = $this->getRequest()->getParam('prj_kode');
        $order = $this->getRequest()->getParam('orderby');
        $start_date = ($this->getRequest()->getParam('start_date') != '') ? date("Y-m-d", strtotime($this->getRequest()->getParam('start_date'))) : false;
        $end_date = ($this->getRequest()->getParam('end_date') != '') ? date("Y-m-d", strtotime($this->getRequest()->getParam('end_date'))) : false;

        if ($order == '')
            $order = 'item_id';

        $primaryKey = 'item_id';

        if ($type == 'ARF Pulsa') {
            $primaryKey = 'caption_id';
            $whereGeneric = "AND caption_id IS NOT NULL";
            $type = 'ARFP';
            $isGeneric = true;
        } else
            $whereGeneric = "AND caption_id IS NULL";

        $ldap = new Default_Models_Ldap();

        $uid = QDC_User_Session::factory()->getCurrentUID();

        $limit = 30;
        $current = $this->getRequest()->getParam('current');
        if ($current == '')
            $current = 1;
        $currentPage = $this->getRequest()->getParam('currentPage');
        if ($currentPage == '')
            $currentPage = 1;

        $offset = $current - 1;

        if ($trano == '' && $type == '' && $prj_kode == '') {
            $sql = "
                CREATE TEMPORARY TABLE my_submit
                SELECT * FROM
                (
                    SELECT
                      *
                    FROM workflow_trans
                    WHERE uid = '$uid'
                    AND approve IN (200,300,400)
                    $whereGeneric
                    ORDER BY date DESC
                ) a
                GROUP BY a.$primaryKey
                ORDER BY a.$order DESC, a.date desc
                ";
        } else {
            if ($trano != '')
                $query = "AND $primaryKey LIKE '%$trano%'";
            if ($type != '')
                $query .= " AND item_type = '$type'";
            if ($prj_kode != '')
                $query .= " AND prj_kode LIKE '%$prj_kode%'";

            if ($start_date) {
                if ($end_date)
                    $query .= " AND date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                else
                    $query .= " AND date BETWEEN '$start_date 00:00:00' AND '$start_date 23:59:59'";
            }


            $sql = "
                CREATE TEMPORARY TABLE my_submit
                SELECT * FROM
                (
                    SELECT
                      *
                    FROM workflow_trans
                    WHERE uid = '$uid'
                    AND approve IN (200,300,400)
                    $whereGeneric
                    $query
                    ORDER BY date DESC
                ) a
                GROUP BY a.$primaryKey
                ORDER BY a.$order DESC, a.date desc";
        }
//        }
        $fetch = $this->db->query($sql);

        if (count($where) > 0) {
            $kriteria = "WHERE " . implode(" AND ", $where);
        }

        $sql = "
            CREATE TEMPORARY TABLE my_submit_approval
              SELECT *,MAX(z.date) AS lastApproveDate, MIN(z.date) AS submitDate FROM (
              SELECT b.* FROM my_submit a
              LEFT JOIN workflow_trans b
              ON a.item_id = b.item_id
              ORDER BY b.date DESC
            ) z
            GROUP BY z.item_id
            ";
        $fetch = $this->db->query($sql);

        if (!$print)
            $uselimit = "LIMIT $offset,$limit";

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS a.*,b.approve AS lastApprove,b.lastApproveDate ,b.uid AS uidApprove, b.submitDate, b.uid_next AS uidNextApprove FROM my_submit a
            LEFT JOIN my_submit_approval b
            ON a.$primaryKey = b.$primaryKey
            $kriteria
            $uselimit
            ";
        $fetch = $this->db->query($sql);

        if ($fetch) {
            $hasil = $fetch->fetchAll();
            $totalResult = $this->db->fetchOne('SELECT FOUND_ROWS()');

            $po = new Default_Models_ProcurementPod();
            $rpi = new Default_Models_RequestPaymentInvoice();

            foreach ($hasil as $key => $val) {
                $tranos = $val['item_id'];

                if ($type == 'POO' || $type == 'PO' || $type == 'RPI' || $type == 'RPIO' || $type == 'SUPP') {
                    $models = $this->_helper->getHelper('model');
                    $newModel = $models->getModelClass($type);
                    $PK = $newModel->getPrimaryKey();
                    $fetch = $newModel->fetchRow("$PK = '$tranos'");
                    if ($fetch) {
                        $hasil[$key]['supplier'] = $fetch['sup_nama'];
                        $this->view->supplier = true;
                    }
                }
                $hasil[$key]['date'] = date('d M Y H:i:s', strtotime($val['date']));
                $hasil[$key]['date_last_approve'] = date('d M Y H:i:s', strtotime($val['lastApproveDate']));
                $hasil[$key]['date_submit'] = date('d M Y H:i:s', strtotime($val['submitDate']));
                $hasil[$key]['comment'] = str_replace("\"", '', $val['comment']);
                $hasil[$key]['comment'] = str_replace("\n", '', $val['comment']);
                $hasil[$key]['comment'] = str_replace("\t", '', $val['comment']);
                $hasil[$key]['comment'] = str_replace("\r", '', $val['comment']);
                $hasil[$key]['comment'] = str_replace("'", '', $val['comment']);

                if ($val['lastApprove'] == 300)
                    $next = $val['uidNextApprove'];
                else
                    $next = $val['uidNextApprove'];

                $hasil[$key]['next_person'] = QDC_User_Ldap::factory(array("uid" => $next))->getName();

                $lastApprove = $val['lastApprove'];
                $lastUid = $val['uidNextApprove'];
                $uidapprove = $val['uidApprove'];
//                $othername = $ldap->getAccount($lastUid);
//                $nextName = $othername['displayname'][0];
                $nextName = QDC_User_Ldap::factory(array("uid" => $lastUid))->getName();
                $uidName = QDC_User_Ldap::factory(array("uid" => $uidapprove))->getName();
                if ($lastApprove == $this->const['DOCUMENT_APPROVE']) {
                    $hasil[$key]['msg'] = "Awaiting approval from $nextName.";
                } else if ($lastApprove == $this->const['DOCUMENT_FINAL'] || $lastApprove == $this->const['DOCUMENT_EXECUTE']) { {
                        $hasil[$key]['msg'] = "Final Approval By $uidName.";
                        $hasil[$key]['next_person'] = '';
                    }
                } else if ($lastApprove == $this->const['DOCUMENT_REJECT']) {
                    $hasil[$key]['msg'] = "Rejected By $uidName.";
                }

                $hasil[$key]['item_id'] = $val[$primaryKey];
                if ($isGeneric) {
                    $hasil[$key]['prj_kode'] = '';
                    $hasil[$key]['is_generic'] = true;
                }

                switch ($type) {
                    case 'PO':
                    case 'POO':
                        $select = $this->db->select()
                                ->from(array(
                            "a" => $this->db->select()
                            ->from(array($rpi->__name()), array(
                                "trano"
                            ))
                            ->where("po_no = ?", $tranos)
                            ->group(array("trano", "po_no"))
                                ), array(
                            "trano" => new Zend_db_Expr("GROUP_CONCAT(a.trano)")
                        ));

                        $rpis = $this->db->fetchRow($select);
                        if ($rpis) {
                            $hasil[$key]['rpi_no'] = $rpis['trano'];
                        }
                        break;
                    case 'PR':
                    case 'PRO':
                        $select = $this->db->select()
                                ->from(array(
                            "a" => $this->db->select()
                            ->from(array($po->__name()), array(
                                "trano"
                            ))
                            ->where("pr_no = ?", $tranos)
                            ->group(array("trano", "pr_no"))
                                ), array(
                            "trano" => new Zend_db_Expr("GROUP_CONCAT(a.trano)")
                        ));

                        $pos = $this->db->fetchRow($select);
                        if ($pos) {
                            $hasil[$key]['po_no'] = $pos['trano'];
                        }
                        break;
                }
            }
        }

        if (!$print) {
            $this->view->encoded_params = $this->_getParam("encoded_params");

            $this->view->totalResult = $totalResult;
            $this->view->current = $current;
            $this->view->limit = $limit;
            $this->view->currentPage = $currentPage;
            $this->view->totalPage = ceil($totalResult / $limit);
            $this->view->result = $hasil;

            switch ($type) {
                case 'PO':
                case 'POO':
                    $this->render('approved-document/po');
                    break;
                case 'PR':
                case 'PRO':
                    $this->render('approved-document/pr');
                    break;
                default:
                    $this->render('approved-document/default');
                    break;
            }
        } else {
            $newData = array();
            $i = 1;
            foreach ($hasil as $k => $v) {
                $tmp = array(
                    "No." => $i,
                    "Trano" => $v['item_id'],
                    "Project" => $v['prj_kode'],
                );

                switch ($type) {
                    case 'PO':
                    case 'POO':
                        $tmp['Supplier'] = $v['supplier'];
                        $tmp['RPI No.'] = $v['rpi_no'];
                        break;
                    case 'PR':
                    case 'PRO':
                        $tmp['PO No.'] = $v['po_no'];
                        break;
                    default:
                        break;
                }

                $tmp['Submit Date'] = $v['date_submit'];
                $tmp['Approval Date'] = $v['date'];
                $tmp['Next Person'] = $v['next_person'];
                $tmp['Status'] = $v['msg'];
                $tmp['Comment'] = $v['comment'];

                $newData[] = $tmp;
                $i++;
            }

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "Approved Document " . $type
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }
    
    public function showporpisummaryrseAction() {
        
    }
    
     public function porpisumdtlrseAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $supKode = $request->getParam('sup_kode');
        $trano_ = $request->getParam('trano_kode');
        $cod = ($request->getParam("cod") == "true") ? true : false;
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        $result = array();
        $datas = array();
        $result = $this->transaction->getPorpirse($prjKode, $sitKode, $supKode, $trano_, $cod);
        

        if ($result) {
            foreach ($result as $k => $v) {
                $lastApprove = $v['lastApprove'];
                $lastUid = $v['uidApprove'];
                $nextName = QDC_User_Ldap::factory(array("uid" => $lastUid))->getName();
                if ($lastApprove == $this->const['DOCUMENT_APPROVE']) {
                    $nextApproveUID = $v['uidNextApprove'];
                    $nextApproveName = QDC_User_Ldap::factory(array("uid" => $nextApproveUID))->getName();
                    $msg = "Awaiting approval from $nextApproveName.";
                } else if ($lastApprove == $this->const['DOCUMENT_FINAL'] || $lastApprove == $this->const['DOCUMENT_EXECUTE']) {
                    $msg = "Final Approval By $nextName.";
                } else if ($lastApprove == $this->const['DOCUMENT_REJECT']) {
                    $msg = "Rejected By $nextName.";
                } elseif ($lastApprove == $this->const['DOCUMENT_SUBMIT'] || $lastApprove == $this->const['DOCUMENT_RESUBMIT']) {
                    $nextName = QDC_User_Ldap::factory(array("uid" => $v['uid_next']))->getName();
                    $msg = "Awaiting approval from $nextName.";
                }
                
                
                
                $trano = $v['trano_PO'];
                $tranorpi = $v['trano_RPI'];
                $arr = array(
                    "trano_PO" => $v['trano_PO'],
                    "trano_RPI" => $v['trano_RPI'],
                    "total_PO" => $v['total_PO'] ,
                    "val_kode" => $v['val_kode'] ,
                    "msg" => $msg ,
                    "total_RPI" => $v['total_RPI'],
                    "total_netRPI" => $v['total_netRPI'],
                    "tgl_PayRPI" => $v['tgl_PayRPI'],
                    "total_PayRPI" => $v['total_PayRPI'],
                );

                $datas[$trano][$tranorpi][] = $arr;
            }
        }
        


        if (!$print) {
            $this->view->result = $datas;
            $this->view->cod = $cod;
        } else {
            $newData = array();
            $no = 1;
            $totalPOIDR = 0;
            $totalPOUSD = 0;
            $totalRPIIDR = 0;
            $totalRPIUSD = 0;
            $totalNetRPIIDR = 0;
            $totalNetRPIUSD = 0;
            $totalPaymentIDR = 0;
            $totalPaymentUSD = 0;
            $totalBalRPIPOIDR = 0;
            $totalBalRPIPOUSD = 0;
            $totalBalPayRPIIDR = 0;
            $totalBalPayRPIUSD = 0;
            
            foreach ($datas as $k => $v) 
            {
                $row = 1;
                $totalRow = 0;
                $totalRPIperPO = 0;
                                
                foreach($v as $k0 => $v0){
                    $totalRow += count($v0);
                    $totalRPIperPO += $v0[0]['total_RPI'];
                }
                
                foreach($v as $k1 => $v1)
                {
                    $row2 = 1;
                    $totalPayperRPI = 0;
                    
                    $totalRow2 = count($v1);
                    
                    foreach($v1 as $k2 => $v2){
                        $totalPayperRPI += $v2['total_PayRPI'];
                    }
                    
                    foreach($v1 as $k3 => $v3)
                    {
                        $nopo = '-';
                        $totalpo = 0;
                        $valkode = '-';
                        $norpi = '-';
                        $totalrpi = 0;
                        $totalnetrpi = 0;
                        $balrpipo = 0;
                        $balpayrpi = 0;
                        
                        if (($row == 1)&&($row2 == 1)){
                            $totalPOIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_PO'] : 0;
                            $totalPOUSD += ($v3['val_kode'] == 'USD') ? $v3['total_PO'] : 0;
                            $nopo = $v3['trano_PO'];
                            $totalpo = $v3['total_PO'];
                            $valkode = $v3['val_kode'];
                            
                            $balrpipo = $v3['total_PO'] - $totalRPIperPO;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalRPIPOIDR += $balrpipo;
                            } else {
                                $totalBalRPIPOUSD += $balrpipo;
                            }
                        }
                        
                        if ($row2 == 1) {
                            $totalRPIIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_RPI'] : 0;
                            $totalRPIUSD += ($v3['val_kode'] == 'USD') ? $v3['total_RPI'] : 0;
                            $totalNetRPIIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_netRPI'] : 0;
                            $totalNetRPIUSD += ($v3['val_kode'] == 'USD') ? $v3['total_netRPI'] : 0;
                            
                            $norpi = $v3['trano_RPI'];
                            $totalrpi = $v3['total_RPI'];
                            $totalnetrpi = $v3['total_netRPI'];
                            
                            $balpayrpi = $v3['total_netRPI'] - $totalPayperRPI;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalPayRPIIDR += $balpayrpi;
                            } else {
                                $totalBalPayRPIUSD += $balpayrpiI;
                            }
                            
                        }
                        
                        $totalPaymentIDR += ($v3['val_kode'] == 'IDR') ? $v3['total_PayRPI'] : 0;
                        $totalPaymentUSD += ($v3['val_kode'] == 'USD') ? $v3['total_PayRPI'] : 0;
                        
                        $newData[] = array(
                            "No" => $no,
                            "PO Number" => $nopo,
                            "PO Total" => $totalpo,
                            "Valuta" => $valkode,
                            "RPI Number" => $norpi,
                            "RPI Total" => $totalrpi,
                            "Balance PO - RPI" => $balrpipo,
                            "Net RPI Total" => $totalnetrpi,
                            "Payment" => $v3['total_PayRPI'],
                            "Payment Date" => ($v3['tgl_PayRPI'] != '-') ? date('d-m-Y', strtotime($v3['tgl_PayRPI'])) : '-',
                            "Balance Net RPI - Pay" => $balpayrpi,
                        );
                        
                        $no++;
                        $row2++;
                    }
                    $row++;
                }
            }
                        
            $newData[] = array(
                "No" => '',
                "PO Number" => '<b>Total IDR</b>',
                "PO Total" => '<b>'.$totalPOIDR.'<b>',
                "Valuta" => '',
                "RPI Number" => '',
                "RPI Total" => '<b>'.$totalRPIIDR.'<b>',
                "Balance PO - RPI" => '<b>'.$totalBalRPIPOIDR.'<b>',
                "Net RPI Total" => '<b>'.$totalNetRPIIDR.'<b>',
                "Payment" => '<b>'.$totalPaymentIDR.'<b>',
                "Payment Date" => '',
                "Balance Net RPI - Pay" => '<b>'.$totalBalPayRPIIDR.'<b>',
            );
            
            $newData[] = array(
                "No" => '',
                "PO Number" => '<b>Total USD</b>',
                "PO Total" => '<b>'.$totalPOUSD.'<b>',
                "Valuta" => '',
                "RPI Number" => '',
                "RPI Total" => '<b>'.$totalRPIUSD.'<b>',
                "Balance PO - RPI" => '<b>'.$totalBalRPIPOUSD.'<b>',
                "Net RPI Total" => '<b>'.$totalNetRPIUSD.'<b>',
                "Payment" => '<b>'.$totalPaymentUSD.'<b>',
                "Payment Date" => '',
                "Balance Net RPI - Pay" => '<b>'.$totalBalPayRPIUSD.'<b>',
            );
            
            $keter = '';

            if($prjKode != '' || $prjKode != null){
                if($sitKode != '' || $sitKode != null){
                    $keter = $prjKode . " - " . $sitKode;
                } else {
                    $keter = $prjKode;
                }
            }
            
            if($supKode != '' || $supKode != null){
                if($prjKode != '' || $prjKode != null){
                    $keter .= " - " . $supKode; 
                } else {
                    $keter = $supKode; 
                }
            }
            
            if($trano_ != '' || $trano_ != null){
                if(($prjKode != '' || $prjKode != null) || ($supKode != '' || $supKode != null)){
                    $keter .= " - " . $trano_;
                } else {
                    $keter = $trano_;
                }
            }
            
            QDC_Adapter_Excel::factory(array(
                        "fileName" => "PO to RPI". $keter
                    ))
                    ->setCellFormat(array(
                        2 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        5 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        6 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        10 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }
    
    
    
    public function showarfasfrseAction() {
        
    }
    
    public function arfasfrseAction() {
        $request = $this->getRequest();
        $prjKode = $request->getParam('prj_kode');
        $sitKode = $request->getParam('sit_kode');
        $print = ($this->getRequest()->getParam("export") == "true") ? true : false;
        
        $result = array();
        $datas = array();
                
        $result = $this->transaction->getArfasfrse($prjKode, $sitKode);
        
        if ($result) {
            foreach ($result as $k => $v) {
                
                $lastApprove = $v['lastApprove'];
                $lastUid = $v['uidApprove'];
                $nextName = QDC_User_Ldap::factory(array("uid" => $lastUid))->getName();
                if ($lastApprove == $this->const['DOCUMENT_APPROVE']) {
                    $nextApproveUID = $v['uidNextApprove'];
                    $nextApproveName = QDC_User_Ldap::factory(array("uid" => $nextApproveUID))->getName();
                    $msg = "Awaiting approval from $nextApproveName.";
                } else if ($lastApprove == $this->const['DOCUMENT_FINAL'] || $lastApprove == $this->const['DOCUMENT_EXECUTE']) {
                    $msg = "Final Approval By $nextName.";
                } else if ($lastApprove == $this->const['DOCUMENT_REJECT']) {
                    $msg = "Rejected By $nextName.";
                } elseif ($lastApprove == $this->const['DOCUMENT_SUBMIT'] || $lastApprove == $this->const['DOCUMENT_RESUBMIT']) {
                    $nextName = QDC_User_Ldap::factory(array("uid" => $v['uid_next']))->getName();
                    $msg = "Awaiting approval from $nextName.";
                }
                
                if($v['stat_arf'] == 300 && $v['stat_revarf'] == 0){
                    continue;
                }
                
                $trano = $v['arf_num'];
                $kodeBrg = $v['kode_brg'];
                
                switch ($v['stat_asf']) {
                    case 100:
                        $v['stat_asf'] = "Submitted";
                        break;
                    case 150:
                        $v['stat_asf'] = "Resubmitted";
                        break;
                    case 200:
                        $v['stat_asf'] = "On Going";
                        break;
                    case 300:
                        $v['stat_asf'] = "Rejected";
                        break;
                    case 400:
                        $v['stat_asf'] = "Final Approval";
                        break;
                    default:
                        $v['stat_asf'] = "Not yet Created";
                }
                $arr = array(
                    "prj_kode" => $v['prj_kode'],
                    "sit_kode" => $v['sit_kode'],
                    "arf_num" => $v['arf_num'],
                    "tgl_arf" => $v['tgl_arf'],
                    "msg" => $msg,
                    "workid" => $v['workid'],
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $v['nama_brg'],
                    "val_kode" => $v['val_kode'],
                    "requester" => $v['requester'],
                    "asf_num" => $v['asf_num'],
                    "tgl_asf" => $v['tgl_asf'],
                    "total_arf" => $v['total_arf'],
                    "total_asf" => $v['total_asf'],
                    "aging_arf_days" => $v['aging_arf_days'],
                    "total_asfcancel" => $v['total_asfcancel'],
                    "stat_asf" => $v['stat_asf'],
                    "tglstat_asf" => $v['tglstat_asf'],
                );

                $datas[$trano][$kodeBrg][] = $arr;
            }
        }
        $sitKode = ($sitKode == null) ? "All" : $request->getParam('sit_kode');
        
        if(!$print){
            $this->view->result = $datas;
            $this->view->prjKode = $prjKode;
            $this->view->sitKode = $sitKode;
        } else {
            $newData = array();
            $no = 1;
            $totalARFIDR = 0;
            $totalARFUSD = 0;
            $totalASFIDR = 0;
            $totalASFUSD = 0;
            $totalASFCanIDR = 0;
            $totalASFCanUSD = 0;
            $totalBalIDR = 0;
            $totalBalUSD = 0;
            
            foreach ($datas as $k => $v) 
            {
                $row = 1;
                $totalRow = 0;
                
                foreach($v as $k0 => $v0){
                    $totalRow += count($v[$k0]);
                }
                
                foreach($v as $k1 => $v1)
                {
                    $row2 = 1;
                    $totalASFperARF = 0;
                    
                    $totalRow2 = count($v[$k1]);
                    
                    foreach($v1 as $k2 => $v2){
                        
                        if($v2['stat_asf'] == 'Rejected'){
                            continue;
                        }
                        
                        $totalASFperARF += $v2['total_asf'];
                        $totalASFperARF += $v2['total_asfcancel'];
                    } 
                    
                    foreach($v1 as $k3 => $v3)
                    {
                        $noarf = '-';
                        $tglarf = '-';
                        $requester = '-';
                        $ttlarf = 0;
                        $balance = 0;
                        
                        if (($row== 1)&&($row2== 1)){
                            $noarf = $v3['arf_num'];
                            $tglarf = ($v3['tgl_arf'] != '-') ? date('d-m-Y', strtotime($v3['tgl_arf'])) : '-';
                        }
                        
                        if ($row2== 1) {
                            if ($v3['val_kode'] != 'USD') {
                                $totalARFIDR += $v3['total_arf'];
                            } else {
                                $totalARFUSD += $v3['total_arf'];
                            }
                            
                            $requester = $v3['requester'];
                            $ttlarf = $v3['total_arf'];
                            
                            $balance = $v3['total_arf'] - $totalASFperARF;

                            if ($v3['val_kode'] != 'USD') {
                                $totalBalIDR += $balance;
                            } else {
                                $totalBalUSD += $balance;
                            }
                        }
                        
                        if ($v3['val_kode'] != 'USD') {
                            $totalASFIDR += $v3['total_asf'];
                            $totalASFCanIDR += $v3['total_asfcancel'];
                        } else {
                            $totalASFUSD += $v3['total_asf'];
                            $totalASFCanUSD += $v3['total_asfcancel'];
                        }
                        
                        $newData[] = array(
                            "No" => $no,
                            "ARF Number" => $noarf,
                            "ARF Date" => $tglarf,
                            "Requester" => $requester,
                            "ARF Total" => $ttlarf,
                            "ASF Number" => $v3['asf_num'],
                            "ASF Date" => ($v3['tgl_asf'] != '-') ? date('d-m-Y', strtotime($v3['tgl_asf'])) : '-',
                            "ASF Total" => $v3['total_asf'],
                            "ASF Cancel Total" => $v3['total_asfcancel'],
                            "ASF Status" => $v3['stat_asf'],
                            "ASF Status Date" => $v3['tglstat_asf'],
                            "ARF Aging" => $v3['aging_arf_days'],
                            "Balance" => $balance,
                        );
                        
                        $no++;
                        $row2++;
                    }
                    $row++;
                }
            }
            
            $newData[] = array(
                "No" => '',
                "ARF Number" => '',
                "ARF Date" => '',
                "Requester" => '<b>Total IDR</b>',
                "ARF Total" => '<b>'.$totalARFIDR.'</b>',
                "ASF Number" => '',
                "ASF Date" => '',
                "ASF Total" => '<b>'.$totalASFIDR.'</b>',
                "ASF Cancel Total" => '<b>'.$totalASFCanIDR.'</b>',
                "ASF Status" => '',
                "ASF Status Date" => '',
                "ARF Aging" => '',
                "Balance" => '<b>'.$totalBalIDR.'</b>',
            );
            
            $newData[] = array(
                "No" => '',
                "ARF Number" => '',
                "ARF Date" => '',
                "Requester" => '<b>Total USD</b>',
                "ARF Total" => '<b>'.$totalARFUSD.'</b>',
                "ASF Number" => '',
                "ASF Date" => '',
                "ASF Total" => '<b>'.$totalASFUSD.'</b>',
                "ASF Cancel Total" => '<b>'.$totalASFCanUSD.'</b>',
                "ASF Status" => '',
                "ASF Status Date" => '',
                "ARF Aging" => '',
                "Balance" => '<b>'.$totalBalUSD.'</b>',
            );

            QDC_Adapter_Excel::factory(array(
                        "fileName" => "ARF to ASF ". $prjKode . "_" . $sitKode
                    ))
                    ->setCellFormat(array(
                        4 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        7 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        8 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->setCellFormat(array(
                        12 => array(
                            "cell_type" => "numeric",
                            "cell_operation" => "setFormatCode",
                            "var" => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
                        )
                    ))
                    ->write($newData)->toExcel5Stream();
        }
    }
}

?>