<?php

class Zend_Controller_Action_Helper_Project extends Zend_Controller_Action_Helper_Abstract
{
	private $db;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
    }   

    function getProjectDetail($prj_kode='')
    {
    	if ($prj_kode == '')
    		return -1;
    	
    	$sql = "SELECT
    			*
    			FROM master_project
    			WHERE prj_kode='$prj_kode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	return $result;
    }            	
    
	function getSiteDetail($prj_kode='',$sit_kode='')
    {
    	if ($sit_kode == '' || $prj_kode == '')
    		return -1;
    	
    	$sql = "SELECT
    			*
    			FROM master_site
    			WHERE sit_kode='$sit_kode'
    			AND prj_kode='$prj_kode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	return $result;
    }
    
    function getWorkDetail($workID='')
    {
    	if ($workID == '')
    		return -1;
    	
    	$sql = "SELECT
    			*
    			FROM masterengineer_work
    			WHERE workid='$workID'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	return $result;
    }
    
    function getCustomerDetail($cus_kode='')
	{
    	if ($cus_kode == '')
    		return -1;
    	
    	$sql = "SELECT
    			*
    			FROM master_customer
    			WHERE cus_kode='$cus_kode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	return $result;
    }
    
    function getSiteCount($prjKode='')
    {
    	$sql = "SELECT
    			*
    			FROM master_site
    			WHERE prj_kode='$prjKode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetchAll();
    	return count($result);
    }
}

?>