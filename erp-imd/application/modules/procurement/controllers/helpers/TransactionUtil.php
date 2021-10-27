<?php
/* 
	Created @ Mar 28, 2010 9:37:35 PM
 */

class Zend_Controller_Action_Helper_TransactionUtil extends
                Zend_Controller_Action_Helper_Abstract
{

    private $db;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
    }    
    
    function getLastNumber($type='')
    {
    	switch ($type)
    	{
    		case 'PR':
    				$query = "PRF";
    			break;
    		case 'DO':
					$query = "BOQ3";
 	   			break;
    		case 'DOR':
					$query = "DOR";
 	   			break;
    		case 'ABOQ2':
    			$query="ABOQ2";
    			break;
    	}
    	
    	$sql = "SELECT * FROM master_countertransaksi WHERE tra_no='$query' ORDER BY tra_no, tahun DESC LIMIT 1";
    	$fetch = $this->db->query($sql);
    	$last = $fetch->fetch();
    	
    	return $last['urut'];
    }
}