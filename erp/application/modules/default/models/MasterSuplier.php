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

    public function __name()
    {
        return $this->_name;
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

     public function getAll($query,$offset=0,$limit=10)
     {
         $select = $this->db->select()
                ->from(
                    array($this->_name),
                    array(
                        "sup_kode",
                        "sup_nama",
                        "alamat" => "SUBSTR(alamat,1,25)",
                        "kota" => "master_kota"
                    )
                )
                ->order(array("sup_nama ASC"))
                ->limit($limit,$offset);

         if ($query)
             $select = $select->where($query);

         $data = $this->db->fetchAll($select);

         return $data;
     }

     public function getName($supKode='')
     {
         $select = $this->fetchRow("sup_kode = '$supKode'");
         if ($select)
         {
             return $select['sup_nama'];
         }

         return '';
     }

     public function isExist($supKode = '')
     {
         $select = $this->fetchRow("sup_kode = '$supKode'");
         if ($select)
         {
             return true;
         }

         return false;
     }
}
?>