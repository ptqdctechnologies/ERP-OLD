<?php
class Zend_Controller_Action_Helper_Model extends
                Zend_Controller_Action_Helper_Abstract
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
                $newClass = new Default_Models_ProcurementArfh();
                break;
            case 'ARFO':
                $newClass = new Default_Models_ProcurementArfh();
                break;
            case 'ASF':
                $newClass = new Default_Models_AdvanceSettlementFormH();
                break;
            case 'ASFO':
                $newClass = new Default_Models_AdvanceSettlementFormH();
                break;
             case 'iLOV':
                $newClass = new Logistic_Models_LogisticMaterialReturnH();
                break;
            case 'iCAN':
                $newClass = new Logistic_Models_LogisticMaterialCancelH();
                break;
            case 'iSUP':
                $newClass = new Logistic_Models_LogisticInputSupplierH();
                break;
    		case 'PR':
    			$newClass = new Default_Models_ProcurementRequestH();
    			break;
            case 'PRO':
    			$newClass = new Default_Models_ProcurementRequestH();
    			break;
    		case 'DOR':
    			$newClass = new Logistic_Models_LogisticDorh();
    			break;
            case 'DO':
    			$newClass = new Logistic_Models_LogisticDoh();
    			break;
            case 'PBOQ3':
    			$newClass = new Default_Models_PieceMealH();
    			break;
            case 'AFE':
    			$newClass = new ProjectManagement_Models_AFEh();
    			break;
    		case 'RPI':
    			$newClass = new Default_Models_RequestPaymentInvoiceH();
    			break;
            case 'RPIO':
    			$newClass = new Default_Models_RequestPaymentInvoiceH();
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
    			$newClass = new Default_Models_ReimbursH();
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
            case 'BOQ2':
                $newClass = new Default_Models_MasterBoq2();
                break;
            case 'PRACO':
                $newClass = new Sales_Models_Praco();
                break;
            case 'APRACO':
                $newClass = new Sales_Models_AddPraco();
                break;
    		default:
                $newClass = false;
                break;
    	}
    	
    	return $newClass;
    	
    }
    

}
?>