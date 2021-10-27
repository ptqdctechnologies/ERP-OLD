<?php

 class Default_Models_MasterCoa extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_coa';

    protected $_primary = 'coa_kode';
    protected $_sup_kode;
    protected $_sup_nama;
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

}
?>
