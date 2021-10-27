<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingCloseAR extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_close_ar';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getjurnalar($trano='',$ref_number='')
    {
        $where = "trano LIKE '%$trano%'";
        if ($ref_number)
            $where .= " AND ref_number LIKE '%$ref_number%'";

        $fetch = $this->fetchAll($where);
        if ($fetch)
        {
            $fetch = $fetch->toArray();
        }
        else
            $fetch = array();

        return $fetch;
    }

    public function __name(){
        return $this->_name;
    }
}