<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/15/11
 * Time: 3:22 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_BankReceiveMoney extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_bank_in';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}