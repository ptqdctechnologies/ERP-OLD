<?php

/**
 * Retrive data from GLPI
 *  
 * @author wisu
 * @version 
 */

class Admin_Models_Glpi extends Zend_Db_Table_Abstract
{
	/**
	 * get Asset from http://is.qdc.co.id/xml.php?uid=
	 */
    function getAsset($uid){
    	
    	$source = new Zend_Config_Xml('http://is.qdc.co.id/xml.php?uid=' . $uid);
    	$amount = $source->entry->amount;
    	
    	if ($amount == 0){
    		$asset = array(array(type =>'No IT Asset'));
    		
    	}elseif ($amount == 1){
    		$asset = array($source->entry->asset->toArray());
    		
    	}else{
    		$asset = $source->entry->asset->toArray();
    	}
    	
    	return $asset;
    	
    }
    
}

