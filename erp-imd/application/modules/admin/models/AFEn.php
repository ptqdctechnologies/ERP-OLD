<?php
class Admin_Model_AFEn extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_afed';
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