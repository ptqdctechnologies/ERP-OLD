<?php
class Zend_Controller_Action_Helper_Model extends Zend_Controller_Action_Helper_Abstract
{
	
    private $db;

    private $modelList;
    
    function  __construct() {
        $this->db = Zend_Registry::get('db');
        
    }   

    function getModelClass($modelName='')
    {
    	if ($modelName == '')
    		return false;
    	
    	switch($modelName)
    	{
            case 'PO':
            case 'POO':
    		$newClass = new Default_Models_ProcurementPoh();
    		break;
            case 'ARF':
                $newClass = new Default_Models_AdvanceRequestFormD();
                break;
            case 'ARFP':
                $newClass = new Default_Models_ProcurementArfh();
                break;
            case 'ARFO':
                $newClass = new Default_Models_AdvanceRequestFormD('O');
                break;
            case 'ASF':
                $newClass = new Default_Models_AdvanceSettlementFormD();
                break;
            case 'ASFP':
                $newClass = new Default_Models_AdvanceSettlementFormH();
                break;
            case 'ASFO':
                $newClass = new Default_Models_AdvanceSettlementFormD();
                break;
             case 'iLOV':
                $newClass = new Logistic_Models_LogisticMaterialReturn();
                break;
            case 'iCAN':
                $newClass = new Logistic_Models_LogisticMaterialCancel();
                break;
            case 'iSUP':
                $newClass = new Logistic_Models_LogisticInputSupplier();
                break;
            case 'PR':
    		$newClass = new Default_Models_ProcurementRequest();
    		break;
            case 'PRO':
    		$newClass = new Default_Models_ProcurementRequest();
    		break;
            case 'DOR':
    		$newClass = new Logistic_Models_LogisticDord();
    		break;
            case 'DO':
    		$newClass = new Logistic_Models_LogisticDoh();
    		break;
            case 'PBOQ3':
    		$newClass = new Default_Models_PieceMeal();
    		break;
            case 'AFE':
    		$newClass = new ProjectManagement_Models_AFE();
    		break;
            case 'RPI':
    		$newClass = new Default_Models_RequestPaymentInvoice();
    		break;
            case 'RPIO':
    		$newClass = new Default_Models_RequestPaymentInvoice();
    		break;
            case 'TSHEET':
    		$newClass = new ProjectManagement_Models_Timesheet();
    		break;
            case 'PRABOQ3':
    		$newClass = new ProjectManagement_Models_TemporaryBOQ3h();
    		break;
            case 'PRABOQ2':
    		$newClass = new Default_Models_Praco();
    		break;
            case 'PRAOHP':
    		$newClass = new HumanResource_Models_TemporaryOHP();
    		break;
            case 'REM':
    		$newClass = new Default_Models_ReimbursD();
    		break;
            case 'SUPP':
    		$newClass = new Default_Models_MasterSuplier();
    		break;
            case 'PRABGO':
    		$newClass = new ProjectManagement_Models_TemporaryOverHeadBOQ3h();
                break;
            case 'RINV':
                $newClass = new Finance_Models_RequestInvoice();
                break;
            case 'INV':
                $newClass = new Finance_Models_Invoice();
                break;
            case 'CI':
                $newClass = new Finance_Models_AccountingCancelInvoice();
                break;
            case 'BOQ2':
                $newClass = new Default_Models_MasterBoq2();
                break;
            case 'PRACO':
                $newClass = new Sales_Models_Praco();
                break;
            case 'APRACO':
                $newClass = new Sales_Models_AddPraco();
                break;
            case 'BRF':
                $newClass = new Procurement_Models_BusinessTripDetail();
                break;
            case 'BRFP':
                $newClass = new Procurement_Models_BusinessTripPayment();
                break;
            case 'BPV':
                $newClass = new Finance_Models_BankPaymentVoucher();
                break;
            case 'IA':
                $newClass = new Logistic_Models_InventoryAdjustment();
                break;
            case 'PPNREM':
                $newClass = new Finance_Models_PpnReimbursementH();
                break;
            case 'PPNSET':
                $newClass = new Finance_Models_PpnReimbursementSettleH();
                break;
            case 'OCA':
                $newClass = new Finance_Models_TemporaryCharging();
                break;
            case 'BSF':
                $newClass = new Default_Models_AdvanceSettlementFormD();
                break;
            case 'CRPI':
                $newClass = new Finance_Models_AccountingCancelRPI();
                break;
            case 'UHB':
                $newClass = new Logistic_Models_LogisticTemporaryBarang();
                break;
            case 'TBOQ':
                $newClass = new Sales_Models_TemporaryTransferBudget();
                break;
            default:
                $newClass = false;
                break;
    	}
    	
    	return $newClass;
    	
    }
    

}
?>