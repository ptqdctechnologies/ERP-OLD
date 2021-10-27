<?php

class Default_Models_MasterCustomer extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_customer';

    protected $_primary = 'cus_kode';
    protected $_cus_kode;
    protected $_cus_nama;
	protected $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function getCustomerFromProject($prjKode='')
    {
    	$sql = "SELECT
    	 			b.*
    			FROM
    				master_project a
    			LEFT JOIN
    				master_customer b
    			ON
    				a.cus_kode = b.cus_kode
    			WHERE
    				a.prj_kode = '$prjKode'";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetch();
    	
    	return $result;
    }
}
?>

