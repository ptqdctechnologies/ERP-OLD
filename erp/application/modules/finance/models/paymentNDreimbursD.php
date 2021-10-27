<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/10/11
 * Time: 10:44 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_paymentNDreimbursD extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_reimbursementd_nd';

    protected $_primary = 'trano';
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

    public function __name()
    {
        return $this->_name;
    }
}