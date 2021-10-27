<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_PaymentInvoice extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_invoice';

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

    public function getpaidlist ($trano)
    {
        $query = "SELECT trano,tgl,uid,total,val_kode,statusppn FROM " . $this->_name . " WHERE inv_no = '$trano'";

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll ();
        return $data;

    }

    public function name()
    {
        return $this->_name;
    }
}
?>