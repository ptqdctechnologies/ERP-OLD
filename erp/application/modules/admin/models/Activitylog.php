<?php
class Admin_Models_Activitylog extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_activity';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');

    }

    
}
?>