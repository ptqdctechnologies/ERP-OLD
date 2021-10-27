<?php
class Admin_Model_Logtransaction extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_transaction';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');

    }

}
?>