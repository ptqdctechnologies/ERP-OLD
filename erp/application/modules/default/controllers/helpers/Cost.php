<?php

class Zend_Controller_Action_Helper_Cost extends Zend_Controller_Action_Helper_Abstract
{

    private $sal;
    private $budgetCFS;
    
    function  __construct() {
        $this->sal = new HumanResource_Models_SalaryD();
        $this->budgetCFS = new Default_Models_BudgetCFS();
        
    } 
    
    public function recordedCost($prjKode, $sitKode,$start_date='', $end_date='') {
        if($start_date=''){$start_date='1970-01-01';}
        
        if($end_date=''){$end_date=date('Y-m-d');}
        
        //Salary
        $sal = new HumanResource_Models_SalaryD();
        $salary = $sal->getSalarySummaryForCFSv2($prjKode,$sitKode,$start_date, $end_date);
        
        $budgetCFS = new Default_Models_BudgetCFS();

        //RPI
        $budgetCFS->getRpidWork('1999-01-01', $end_date);
        $rpi = $budgetCFS->getRpidV2('summary', $prjKode, $sitKode, '', $start_date, $end_date);
        $rpi_approved_IDR = floatval($rpi['totalIDR']);
        $rpi_approved_USD = floatval($rpi['totalUSD']);
        $rpi_approved_USDRate = floatval($rpi['totalUSDRate']);

        //ASF
        $budgetCFS->getAsfdWork('1999-01-01', $end_date);
        $asf = $budgetCFS->getAsfdV2('summary', $prjKode, $sitKode, '', $start_date, $end_date);
        $asfc = $budgetCFS->getAsfdcancelV2('summary', $prjKode, $sitKode, '', $start_date, $end_date);
        $asf_approved_IDR = floatval($asf['totalIDR']);
        $asf_approved_USD = floatval($asf['totalUSD']);
        $asf_approved_USDRate = floatval($asf['totalUSDRate']);
        $asfcancel_approved_IDR = floatval($asfc['totalIDR']);
        $asfcancel_approved_USD = floatval($asfc['totalUSD']);
        $asfcancel_approved_USDRate = floatval($asfc['totalUSDRate']);
        $asf_IDR = floatval($asf_approved_IDR - $asfcancel_approved_IDR);
        $asf_USD = floatval($asf_approved_USD - $asfcancel_approved_USD);
        $asf_USDRate = floatval($asf_approved_USDRate - $asfcancel_approved_USDRate);

        //Piecemeal / PBOQ
        $piecemeal = $budgetCFS->getPiecemealV2('summary', $prjKode, $sitKode, $start_date, $end_date);
        $pmeal = floatval($piecemeal['total']);

        //Material return, cancel
        $LeftOver = $budgetCFS->getLeftOverV2('summary', $prjKode, $sitKode, $start_date, $end_date);
        $totalLeftOver_IDR = floatval($LeftOver['totalIDR']);
        $totalLeftOver_USD = floatval($LeftOver['totalUSD']);
        $totalLeftOver_USDRate = floatval($LeftOver['totalUSDRate']);

        $Cancel = $budgetCFS->getCancelV2('summary', $prjKode, $sitKode, $start_date, $end_date);
        $totalCancel_IDR = floatval($Cancel['totalIDR']);
        $totalCancel_USD = floatval($Cancel['totalUSD']);
        $totalCancel_USDRate = floatval($Cancel['totalUSDRate']);

        $material_return_IDR = $totalLeftOver_IDR + $totalCancel_IDR;
        $material_return_USD = $totalLeftOver_USD + $totalCancel_USD;
        $material_return_USDRate = $totalLeftOver_USDRate + $totalCancel_USDRate;

        //DO-PO
        $dopo = $budgetCFS->getDOPO($prjKode,$sitKode,$start_date, $end_date);
        $fdopoIDR = floatval($dopo['totalIDR']);
        $fdopoUSD = floatval($dopo['totalUSD']);
        $fdopoUSDRate = floatval($dopo['totalUSDRate']);
        //var_dump($fdopoUSD);die;

        $cost['mip_currentIDR'] = $salary[$sitKode]['current'] + $rpi_approved_IDR + $asf_IDR + $pmeal + $fdopoIDR - $material_return_IDR;
        $cost['mip_currentHargaUSD'] = $rpi_approved_USD + $asf_USD + $fdopoUSD - $material_return_USD;
        $cost['mip_currentHargaUSDRate'] = $rpi_approved_USDRate + $asf_USDRate + $fdopoUSDRate - $material_return_USDRate;

        $cost['salary_IDR']= $salary[$sitKode]['current'];
        $cost['salary_USD'] = 0;
        $cost['rpi_approved_IDR'] = $rpi_approved_IDR;
        $cost['rpi_approved_USD']  = $rpi_approved_USD;
        $cost['asf_IDR']  = $asf_IDR;
        $cost['asf_USD'] =  $asf_USD;
        $cost['pmeal_IDR'] = $pmeal;
        $cost['pmeal_USD'] = 0;
        $cost['fdopoIDR'] = $fdopoIDR;
        $cost['fdopoUSD'] = $fdopoUSD;
        $cost['material_return_IDR'] = $material_return_IDR;
        $cost['material_return_USD'] = $material_return_USD;

        return $cost;
    }
}

