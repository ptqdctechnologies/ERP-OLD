<?php

class Logistic_Models_LogisticDoh extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_whoh';

    protected $_primary = 'trano';

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

    public function __name()
    {
        return $this->_name;
    }
}
?>
