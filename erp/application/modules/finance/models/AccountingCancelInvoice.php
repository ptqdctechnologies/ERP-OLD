<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 5/1/12
 * Time: 1:47 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_AccountingCancelInvoice extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_invoice_ci';
    public $name;
    public $db;
    protected $const;
    protected $_primary = 'trano';

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }
    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

}