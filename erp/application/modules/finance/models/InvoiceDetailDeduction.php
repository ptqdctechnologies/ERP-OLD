<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_InvoiceDetailDeduction extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_invoiced_deduction';

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
?>