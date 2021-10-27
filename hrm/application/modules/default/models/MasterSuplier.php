<?php

 class Default_Models_MasterSuplier extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_suplier';

    protected $_primary = 'sup_kode';
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

     public function getLastSupkode()
    {
        $query = "SELECT CONVERT(MAX(SUBSTRING(sup_kode, 4)), SIGNED INTEGER) AS last FROM master_suplier where CONVERT(substring(sup_kode,4), SIGNED INTEGER) > 0";

        $fetch = $this->db->query($query);
    	$last = $fetch->fetch();

        return $last;
    }

     public function updateaktif($trano = '')
     {
        $query = "UPDATE master_suplier set aktif = 'Y' WHERE sup_kode = '$trano'";
        $fetch = $this->db->query($query);
     }
}
?>
