<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 1/11/12
 * Time: 11:16 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingJurnalBank extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_jurnal_bank';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function name()
    {
        return $this->_name;
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getjurnalbank($trano='',$ref_number='',$type='')
    {
        $where = 'deleted=0 ';
        if ($trano)
        {
            if ($where)
                $where .= " AND trano LIKE '%$trano%'";
            else
                $where = " AND trano LIKE '%$trano%'";
        }
        if ($ref_number)
        {
            if ($where)
                $where .= " AND ref_number LIKE '%$ref_number%'";
            else
                $where = " AND ref_number LIKE '%$ref_number%'";
        }

        if ($type)
        {
            if ($where)
                $where .= " AND type = '$type'";
            else
                $where = " AND type = '$type'";
        }

        $fetch = $this->fetchAll($where);
        if ($fetch)
        {
            $fetch = $fetch->toArray();
        }
        else
            $fetch = array();

        return $fetch;
    }
}