<?php
class ProjectManagement_Models_TemporaryBOQ3h extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_praboq3h';
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

    public function __name()
    {
        return $this->_name;
    }
}
?>