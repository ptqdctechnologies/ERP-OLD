<?php
class Admin_Model_Masterlogin extends Zend_Db_Table_Abstract
{
	protected $_name ='master_login';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
	
}
?>