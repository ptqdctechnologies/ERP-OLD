<?php
class HumanResource_Models_OHPH extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_ohph';
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