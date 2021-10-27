<?php
class Default_Models_PieceMealH extends Zend_Db_Table_Abstract
{
    protected $_name = 'boq_hboqpasang';
    protected $_primary = 'notran';
    protected $db;
    protected $const;

    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
    $this->const = Zend_Registry::get('constant');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
}
?>
 
