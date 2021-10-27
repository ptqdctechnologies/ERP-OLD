<?php

class Default_Models_MasterSite extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_site';

    protected $_primary = 'sit_kode';
    protected $_sit_kode;
    protected $_sit_nama;
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

    public function getList($prj_kode = '')
    {
        $select = $this->db->select()
            ->from(array($this->_name),
                array(
                "sit_kode",
                "sit_nama",
                "prj_kode",
                "stsclose"
            ))
            ->order(array("sit_kode ASC"));


        if ($prj_kode)
            $select = $select->where("prj_kode = ?",$prj_kode);

        $data = $this->db->fetchAll($select);

        return $data;
    }

    public function getSiteName($prj_kode='',$sit_kode='')
    {
        $select = $this->db->select()
            ->from(array($this->_name),
                array("sit_nama AS sit_nama")
            )
            ->where("prj_kode = ?",$prj_kode)
            ->where("sit_kode = ?",$sit_kode);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            return $data['sit_nama'];
        }

        return false;
    }
}
?>