<?php
class Admin_Model_Masterroletype extends Zend_Db_Table_Abstract
{
	protected $_name ='master_role_type';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
	
	public function getAll($where='')
	{
		$sql = "SELECT id AS id_role,display_name,role_name,active
				FROM
					master_role_type
				$where	
				ORDER BY role_name ASC";
		
		$return = $this->db->fetchAll($sql);
		
		return $return;
	}
	
	public function getNameByID($id_role='')
	{
		$sql = "SELECT id AS id_role,display_name,role_name,active
				FROM
					master_role_type
				WHERE id=$id_role";
		
		$fetch = $this->db->query($sql);
    	$return = $fetch->fetch();
		return $return;
	}
}
?>