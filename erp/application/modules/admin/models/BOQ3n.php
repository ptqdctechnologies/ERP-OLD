<?php
class Admin_Model_BOQ3n extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_boq3d';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

	
}
?>