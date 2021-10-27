<?php
class Admin_Model_CBOQ3n extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_kboq3d';
    private $db;
     protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }


}
?>