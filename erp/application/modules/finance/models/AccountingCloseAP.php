<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingCloseAP extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_close_ap';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name(){
        return $this->_name;
    }

    public function getJurnalAp($trano='',$ref_number='')
    {
        $where = "trano LIKE '%$trano%' AND deleted=0";
        if ($ref_number)
            $where .= " AND ref_number LIKE '%$ref_number%' AND deleted=0";

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