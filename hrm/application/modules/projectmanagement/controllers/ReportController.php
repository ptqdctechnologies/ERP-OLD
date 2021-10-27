<?php
class ProjectManagement_ReportController extends Zend_Controller_Action
{
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

    public function init()
    {
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
        $this->quantity = $this->_helper->getHelper('quantity');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->regisco = new Sales_Models_Regisco();
    }

    public function generalAction()
    {
        $prjKode = $this->getRequest()->getParam("prj_kode");
        $sitKode = $this->getRequest()->getParam("sit_kode");
        $print = $this->getRequest()->getParam("print");
        $overhead = $this->getRequest()->getParam("overhead");
        if ($print == '')
            $print = false;
        else
            $print = true;
        if ($overhead == '')
            $overhead = false;
        else
            $overhead = true;

        $today = new DateTime(date("Y-m-d H:i:s"));
        $expire = new DateTime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval("PT30M"));
        $fromCache = false;
        $cacheID = "REPORT_GENERAL_$prjKode" . "_$sitKode";
        $cacheTimeID = $cacheID . "_TIME";

        if (!$this->memcache->test($cacheID))
        {
            if (!$overhead)
                $boq3 = $this->budget->getBoq3('all-current', $prjKode, $sitKode);
            else
                $boq3 = $this->budget->getBudgetOverhead($prjKode, $sitKode);

            $this->memcache->save($boq3,$cacheID,array('REPORT'));
            //cache time generated...
            $time = array(
                "generate" => $today->format("d M Y H:i:s"),
                "expire" => $expire->format("d M Y H:i:s")
            );
            $this->memcache->save($time,$cacheTimeID,array('REPORT'));
        }
        else
        {
            $boq3 = $this->memcache->load($cacheID);
            $time = $this->memcache->load($cacheTimeID);
            $fromCache = true;
        }

        foreach ($boq3 as $key => $val)
        {
            $kodeBrg = $val['kode_brg'];
            $workId = $val['workid'];
            $barang = $this->barang->fetchRow("kode_brg = '$kodeBrg'");
            $boq3[$key]['sat_kode'] = $barang['sat_kode'];

            //PR
            $pr = $this->quantity->getPrQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($pr)
            {
                $boq3[$key]['pr']['qty'] = $pr['qty'];
                $boq3[$key]['pr']['IDR'] = $pr['totalIDR'];
                $boq3[$key]['pr']['USD'] = $pr['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['pr']['qty'] = 0;
                $boq3[$key]['pr']['IDR'] = 0;
                $boq3[$key]['pr']['USD'] = 0;
            }
            //PO
            $po = $this->quantity->getPoQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($po)
            {
                $boq3[$key]['po']['qty'] = $po['qty'];
                $boq3[$key]['po']['IDR'] = $po['totalIDR'];
                $boq3[$key]['po']['USD'] = $po['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['po']['qty'] = 0;
                $boq3[$key]['po']['IDR'] = 0;
                $boq3[$key]['po']['USD'] = 0;
            }
            //RPI
            $rpi = $this->quantity->getRpiQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($rpi)
            {
                $boq3[$key]['rpi']['qty'] = $rpi['qty'];
                $boq3[$key]['rpi']['IDR'] = $rpi['totalIDR'];
                $boq3[$key]['rpi']['USD'] = $rpi['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['rpi']['qty'] = 0;
                $boq3[$key]['rpi']['IDR'] = 0;
                $boq3[$key]['rpi']['USD'] = 0;
            }
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
            $arf = $this->quantity->getArfQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($arf)
            {
                $boq3[$key]['arf']['qty'] = $arf['qty'];
                $boq3[$key]['arf']['IDR'] = $arf['totalIDR'];
                $boq3[$key]['arf']['USD'] = $arf['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['arf']['qty'] = 0;
                $boq3[$key]['arf']['IDR'] = 0;
                $boq3[$key]['arf']['USD'] = 0;
            }
            //ASF
            $asf = $this->quantity->getAsfddQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($asf)
            {
                $boq3[$key]['asf']['qty'] = $asf['qty'];
                $boq3[$key]['asf']['IDR'] = $asf['totalIDR'];
                $boq3[$key]['asf']['USD'] = $asf['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['asf']['qty'] = 0;
                $boq3[$key]['asf']['IDR'] = 0;
                $boq3[$key]['asf']['USD'] = 0;
            }
            //ASF Cancel
            $asfc = $this->quantity->getAsfcancelQuantity($prjKode,$sitKode,$workId,$kodeBrg);
            if ($asfc)
            {
                $boq3[$key]['asfc']['qty'] = $asfc['qty'];
                $boq3[$key]['asfc']['IDR'] = $asfc['totalIDR'];
                $boq3[$key]['asfc']['USD'] = $asfc['totalHargaUSD'];
            }
            else
            {
                $boq3[$key]['asfc']['qty'] = 0;
                $boq3[$key]['asfc']['IDR'] = 0;
                $boq3[$key]['asfc']['USD'] = 0;
            }
        }
        if ($fromCache)
            $this->view->time = $time;
        $this->view->result = $boq3;

        if ($print)
            $this->render('printgeneral');
    }

    public function showgeneralAction()
    {
    }
    public function showgeneraloverheadAction()
    {
    }
    public function showcfsAction()
    {
    }

    public function cfsAction()
    {

        $prjKode = $this->getRequest()->getParam("prj_kode");
        $this->view->prjKode = $prjKode;

        $month = $this->getRequest()->getParam("month");
        $startDate = $this->getRequest()->getParam("start");
        $endDate = $this->getRequest()->getParam("end");
        $useCache = $this->getRequest()->getParam("cache");
        $grandTotal = array();
        
        if ($useCache == '')
            $useCache = true;
        else
            $useCache = $useCache;

        if ($month != '')
        {
            $year = date("Y");
            $startDate = $year . "-" . $month . "-1";
            $limit = strtotime ( '+1 month' , strtotime ( $startDate ) ) ;
            $limit = strtotime ( '-1 day' , $limit ) ;
            $endDate = date("Y-m-d",$limit);
        }
        elseif ($startDate != '' && $endDate != '')
        {
            $startDate = date("Y-m-d",strtotime($startDate));
            $endDate = date("Y-m-d",strtotime($endDate));
        }

        //Get updated exchange rate from database
        $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
                FROM exchange_rate
                WHERE val_kode='USD'
                ORDER BY tgl DESC
                LIMIT 0,1";
        $fetch2 = $this->db->query($sql);
        $data = $fetch2->fetch();
        $rateIDR = floatval($data['rateidr']);


        $this->view->useCache = false;
        $cacheID = "REPORT_CFS_" . $prjKode . str_replace('-','_',$startDate) . str_replace('-','_',$endDate);
//        if ($useCache)
//        {
//            if (!$this->memcache->test($cacheID))
//            {

                $CIP = $this->processCIP($prjKode,$startDate,$endDate);
                $sitGroup = $this->processBudget($prjKode,$startDate,$endDate,$rateIDR);

                $budget = array(
                    "sitGroup" => $sitGroup,
                    "CIP" => $CIP
                );

//                $res = $this->memcacheHelper->save($budget,$cacheID,"REPORT");
//            }
//            else
//            {
//                $cache = $this->memcache->load($cacheID);
//                $sitGroup = $cache['sitGroup'];
//                $CIP = $cache['CIP'];
//                $res = $this->memcache->load($cacheID."_TIME");
//                $this->view->time = $res;
//            }
//
//            $this->view->useCache = true;
//        }

        $this->view->CIP = $CIP;
        
        foreach ($sitGroup as $k => $v)
        {
            //Grand Total...
            $grandTotal['boq2_variations'] += $sitGroup[$k]['total']['boq2_variations'];
            $grandTotal['boq2_ori'] += $sitGroup[$k]['total']['boq2_ori'];
            $grandTotal['boq2_current'] += $sitGroup[$k]['total']['boq2_current'];
            $grandTotal['progress_amount'] += $sitGroup[$k]['total']['progress_amount'];
            $grandTotal['wip'] += $sitGroup[$k]['total']['wip'];
            $grandTotal['outstanding_payment'] += $sitGroup[$k]['total']['outstanding_payment'];
            $grandTotal['cash_expenses'] += $sitGroup[$k]['total']['cash_expenses'];
            $grandTotal['commitment'] += $sitGroup[$k]['total']['commitment'];
            $grandTotal['forecast_final_cost'] += $sitGroup[$k]['total']['forecast_final_cost'];
            $grandTotal['estimated_complete'] += $sitGroup[$k]['total']['estimated_complete'];
            $grandTotal['accrual'] += $sitGroup[$k]['total']['accrual'];
            $grandTotal['invoiced'] += $sitGroup[$k]['total']['invoiced'];
            $grandTotal['received'] += $sitGroup[$k]['total']['received'];
            $grandTotal['current_gross_margin'] += $sitGroup[$k]['total']['current_gross_margin'];
            $grandTotal['final_margin'] += $sitGroup[$k]['total']['final_margin'];
        }
        if ($grandTotal['boq2_current'] <= 0)
            $grandTotal['progress'] = 0;
        else
           $grandTotal['progress'] = ($grandTotal['progress_amount'] /$grandTotal['boq2_current']) * 100;

        if ($grandTotal['boq2_current'] <= 0)
            $grandTotal['final_persen'] = 0;
        else
            $grandTotal['final_persen'] = ($grandTotal['final_margin'] /$grandTotal['boq2_current']) * 100;

        foreach ($grandTotal as $k => $v)
        {
            if ($v == 'N/A')
                continue;
            if ($v < 0)
                $grandTotal[$k] = number_format($v * -1,2);
            else
                $grandTotal[$k] = number_format($v,2);
            if (strpos($k,'persen') >= 0)
            {
                if ($v < 0)
                {
                    $grandTotal[$k] = "(" . $grandTotal[$k] . ")";
                    $grandTotal[$k] = "<font color=\"ff0000\">" . $grandTotal[$k] . "</font>";
                }
            }
        }


        foreach ($sitGroup as $k => $v)
        {
            foreach ($v['data'] as $k2 => $v2)
            {
                foreach($v2 as $k3 => $v3)
                {
                    if ($k3 == 'tglcfs' || $k3 == 'prj_kode' || $k3 == 'sit_kode' || $k3 == 'prj_nama' || $k3 == 'sit_nama' || $k3 == 'stsoverhead' || $k3 == 'tglaw' || $k3 == 'progress_ket')
                        continue;
                    if ($v3 == 'N/A')
                        continue;
                    if ($v3 < 0)
                        $sitGroup[$k]['data'][$k2][$k3] = number_format(-1 * $v3,2);
                    else
                        $sitGroup[$k]['data'][$k2][$k3] = number_format($v3,2);
                    if (strpos($k3,'persen') >= 0)
                    {
                        if ($v3 < 0)
                        {
                            $sitGroup[$k]['data'][$k2][$k3] = "(" . $sitGroup[$k]['data'][$k2][$k3] . ")";
                            $sitGroup[$k]['data'][$k2][$k3] = "<font color=\"ff0000\">" . $sitGroup[$k]['data'][$k2][$k3] . "</font>";
                        }
                    }
                }
            }
            foreach ($v['total'] as $k2 => $v2)
            {
                if ($v2 == 'N/A')
                    continue;
                if ($v2 < 0)
                    $sitGroup[$k]['total'][$k2] = number_format(-1 * $v2,2);
                else
                    $sitGroup[$k]['total'][$k2] = number_format($v2,2);
                if (strpos($k2,'persen') >= 0)
                {
                    if ($v2 < 0)
                    {
                        $sitGroup[$k]['total'][$k2] = "(" . $sitGroup[$k]['total'][$k2] . ")";
                        $sitGroup[$k]['total'][$k2] = "<font color=\"#ff0000\">" . $sitGroup[$k]['total'][$k2] . "</font>";
                    }
                }
            }
        }

        $this->view->result = $sitGroup;
        $this->view->grandTotal = $grandTotal;

        if ($startDate != '' && $endDate != '')
        {
            $this->view->startDate = date("d M Y",strtotime($startDate));
            $this->view->endDate = date("d M Y",strtotime($endDate));
        }


        $this->view->rateidr = $rateIDR;
        $this->view->tgl = $data['tgl_rate'];
    }

    private function processCIP($prjKode,$startDate='',$endDate='',$rateIDR=0)
    {
        $arfcip = $this->budgetCFS->getArfd('summary', $prjKode,'','',$startDate,$endDate);
        $asfddcip = $this->budgetCFS->getAsfdd('summary', $prjKode,'','',$startDate,$endDate);
        $asfddcancelcip = $this->budgetCFS->getAsfddcancel('summary', $prjKode,'','',$startDate,$endDate);

        $arftotCIP = (floatval($arfcip['totalIDR']) + (floatval($arfcip['totalHargaUSD']) * floatval($rateIDR)));
        $asftotCIP = (floatval($asfddcip['totalIDR']) + (floatval($asfddcip['totalHargaUSD']) * floatval($rateIDR)));
        $asfctotCIP = (floatval($asfddcancelcip['totalIDR']) + (floatval($asfddcancelcip['totalHargaUSD']) * floatval($rateIDR)));

        return ($arftotCIP - $asftotCIP) + $asfctotCIP;
    }

    private function processBudget($prjKode,$startDate='',$endDate='',$rateIDR=0)
    {
        $fetch = $this->budgetCFS->getBudgetProject(false,$prjKode,'',false,0,true,$startDate,$endDate);

        $sal = new HumanResource_Models_SalaryD();
        $salary = $sal->getSalarySummaryForCFS($prjKode);
        
        $progress = new ProjectManagement_Models_ProjectProgress();
        $sitGroup = array();

        foreach($fetch as $k => $v)
        {
            $sitKode = $v['sit_kode'];
            $variations = (floatval($v['boq2_currentIDR']) + (floatval($v['boq2_currentHargaUSD']) * floatval($rateIDR))) - (floatval($v['boq2_oriIDR']) + (floatval($v['boq2_oriHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['boq2_variations'] = $variations;
            $fetch[$k]['boq2_ori'] = (floatval($v['boq2_oriIDR']) + (floatval($v['boq2_oriHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['boq2_current'] = (floatval($v['boq2_currentIDR']) + (floatval($v['boq2_currentHargaUSD']) * floatval($rateIDR)));

            //Progress
            $pro = $progress->getSumSiteProgress($prjKode,$sitKode,$startDate,$endDate);
            $remarkProgress = $progress->getSumSiteProgressRemark($prjKode,$sitKode,$startDate,$endDate);
            $fetch[$k]['progress'] = $pro;
            $fetch[$k]['progress_ket'] = $remarkProgress;

            $fetch[$k]['progress_amount'] = ($pro / 100) * $fetch[$k]['boq2_current'];
            $fetch[$k]['retention'] = 0;
            $fetch[$k]['wip'] = $fetch[$k]['progress_amount'] - $fetch[$k]['invoiced'];
            //Invoiced
            $inv = $this->budgetCFS->getInvoice('summary', $prjKode, $sitKode);
            $fetch[$k]['invoicedUSD'] = 0;
            if (floatval($inv['totalHargaUSD']) > 0)
            {
                $fetch[$k]['invoicedUSD'] = floatval($inv['totalHargaUSD']);
            }
            $fetch[$k]['invoicedIDR'] = floatval($inv['totalHargaIDR']);
            $fetch[$k]['invoiced'] = (floatval($inv['totalHargaIDR']) + (floatval($inv['totalHargaUSD']) * floatval($rateIDR)));
            //Received
            $pinv = $this->budgetCFS->getPaymentInvoice('summary', $prjKode, $sitKode);
            $fetch[$k]['received'] = (floatval($pinv['totalHargaIDR']) + (floatval($pinv['totalHargaUSD']) * floatval($rateIDR)));
            //outstanding payment
            $fetch[$k]['outstanding_payment'] = $fetch[$k]['invoiced'] - $fetch[$k]['received'];

            //Salaries
            $gaji = 0;
            if ($salary[$sitKode] != '')
            {
                $gaji = $salary[$sitKode];
                $fetch[$k]['salary_exist'] = $gaji;
                unset($salary[$sitKode]);
            }


            $outs = $fetch[$k]['invoiced'] - $fetch[$k]['received'];

            //Cash Expenses
            //RPI
            $this->budgetCFS->getRpidWork($startDate,$endDate);
            $rpi = $this->budgetCFS->getRpid('summary', $prjKode, $sitKode,'',$startDate,$endDate);
            $fetch[$k]['rpi_approved'] = (floatval($rpi['totalIDR']) + (floatval($rpi['totalHargaUSD']) * floatval($rateIDR)));
            //ASF
            $this->budgetCFS->getAsfdWork($startDate,$endDate);
            $asf = $this->budgetCFS->getAsfd('summary', $prjKode, $sitKode,'',$startDate,$endDate);
            $asfc = $this->budgetCFS->getAsfdcancel('summary', $prjKode, $sitKode,'',$startDate,$endDate);
            $fetch[$k]['asf_approved'] = (floatval($asf['totalIDR']) + (floatval($asf['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['asfcancel_approved'] = (floatval($asfc['totalIDR']) + (floatval($asfc['totalHargaUSD']) * floatval($rateIDR)));
            //Piecemeal / PBOQ
            $piecemeal = $this->budgetCFS->getPiecemeal('summary',$prjKode,$sitKode);
            $fetch[$k]['piecemeal'] = (floatval($piecemeal['totalPieceMeal']));
            //Material return, cancel
            $LeftOver = $this->budgetCFS->getLeftOver('summary', $prjKode, $sitKode);
	        $totalLeftOver = (floatval($LeftOver['totalIDR']) + (floatval($LeftOver['totalHargaUSD']) * floatval($rateIDR)));
            $Cancel = $this->budgetCFS->getCancel('summary', $prjKode, $sitKode);
	        $totalCancel = (floatval($Cancel['totalIDR']) + (floatval($Cancel['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['material_return'] = $totalLeftOver + $totalCancel;


            $fetch[$k]['rpi_asf_approved'] = floatval((($fetch[$k]['rpi_approved'] + $fetch[$k]['asf_approved']) - $fetch[$k]['asfcancel_approved']));
            $fetch[$k]['cash_expenses'] = $fetch[$k]['rpi_asf_approved'] + $fetch[$k]['piecemeal'] + $gaji - $fetch[$k]['material_return'];;

            //Commitment
            $po = $this->budgetCFS->getPod('summary', $prjKode, $sitKode,'',$startDate,$endDate);
            $fetch[$k]['po'] = (floatval($po['totalIDR']) + (floatval($po['totalHargaUSD']) * floatval($rateIDR)));
            $arf = $this->budgetCFS->getArfd('summary', $prjKode, $sitKode,'',$startDate,$endDate);
            $fetch[$k]['arf'] = (floatval($arf['totalIDR']) + (floatval($arf['totalHargaUSD']) * floatval($rateIDR)));
            $fetch[$k]['commitment'] = $fetch[$k]['po'] + $fetch[$k]['arf'];

            //Cost Forecast
            $boq3 = $this->budgetCFS->getBoq3('summary-current', $prjKode, $sitKode,$startDate,$endDate);
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


            $prefix = substr($sitKode,0,1);
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

        if (count($salary) > 0)
        {
            foreach($salary as $k => $v)
            {
                $data = array();
                $prefix = substr($k,0,1);
                $prefix = $prefix . "00";
                $data['sit_kode'] = $k;
                $data['sit_nama'] = 'Overhead (Salaries)';
                $data['boq2_variations'] = 'N/A';
                $data['boq2_ori'] = 'N/A';
                $data['boq2_current'] = 'N/A';
                $data['progress_amount'] = 'N/A';
                $data['progress'] = 'N/A';
                $data['wip'] = 'N/A';
                $data['outstanding_payment']= 'N/A';
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

    public function regiscoAction ()
    {
        
    }

    public function getregiscoAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 0;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $data['data'] = $this->regisco->fetchAll(null,array($sort . " " . $dir))->toArray();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function showscurveAction()
    {

    }

    public function scurvecostAction()
    {

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

        $cek = $cronBudget->test($prjKode,$sitKode);
        if ($cek)
        {
            $data = $cronBudget->fetchRow("prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
            $this->view->time = date("d M Y H:i:s",strtotime($data['tgl']));
        }
    }
}
?>