<?php
class Default_Models_Dboq extends Zend_Db_Table_Abstract
{
    protected $_name = 'boq_dboq';

    protected $db;
    protected $const;

    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
    $this->const = Zend_Registry::get('constant');
    }
}
?>