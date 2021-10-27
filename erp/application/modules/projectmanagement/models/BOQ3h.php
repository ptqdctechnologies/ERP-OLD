<?php
class ProjectManagement_Models_BOQ3h extends Zend_Db_Table_Abstract
{
	protected $_name = 'transengineer_boq3h';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
	
	
}
?>