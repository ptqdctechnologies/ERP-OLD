<?php

class Finance_Models_EbankingBulk extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_ebanking_bulk';

    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getBulkID()
    {
        return md5(date("dmYHisu") . rand(0,10000));
    }

    public function isExist($trano='',$item_type='')
    {
        $select = $this->db->select()
            ->from(array($this->_name))
            ->where("trano = ?",$trano);

        if ($item_type)
            $select = $select->where("item_type = ?",$item_type);

        $cek = $this->db->fetchRow($select);
        return ($cek) ? $cek  : false;
    }
}

?>