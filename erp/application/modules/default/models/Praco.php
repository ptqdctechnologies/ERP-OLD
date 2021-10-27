<?php
class Default_Models_Praco extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_praboq2h';

    protected $_primary = 'trano';
	protected $db;
	
    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function updateaktif($trano = '')
     {
        $query = "UPDATE transengineer_praboq2h set statusfinal = 'Y' WHERE trano = '$trano'";
        $fetch = $this->db->query($query);
     }

    public function __name()
    {
        return $this->_name;
    }
}

?>