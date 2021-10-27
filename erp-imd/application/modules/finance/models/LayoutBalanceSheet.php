<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/23/11
 * Time: 11:19 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_LayoutBalanceSheet extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_layout_neraca';

    protected $_primary = 'id';
    protected $db;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

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

    public function cekExist($coaKode='')
    {
        if (!$coaKode)
            return false;
        $cek = $this->fetchRow("coa_kode = '$coaKode'");
        if($cek)
            return true;

        return false;
    }


    public function __name()
    {
        return $this->_name;
    }
}