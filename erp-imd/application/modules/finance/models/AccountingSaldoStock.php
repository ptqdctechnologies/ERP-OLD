<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/16/12
 * Time: 10:42 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingSaldoStock extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_saldo_stock';
    public $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

}