<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/14/11
 * Time: 4:20 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_PaymentPpnReimbursementD extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_ppn_reimbursementd';

    protected $_primary = 'trano';
    protected $db;

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
