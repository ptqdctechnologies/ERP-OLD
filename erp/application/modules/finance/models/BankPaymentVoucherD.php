<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/23/11
 * Time: 1:45 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_BankPaymentVoucherD extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_voucherd';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}