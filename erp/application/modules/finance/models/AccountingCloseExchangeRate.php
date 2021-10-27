<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingCloseExchangeRate extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_closing_exchange_rate';

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
}