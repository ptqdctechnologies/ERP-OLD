<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/5/11
 * Time: 8:37 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_ReimbursDetail extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_reimbursementd';

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

    public function ViewReimbursItemList ($trano)
    {
        $query = "select trano,kode_brg,nama_brg,qty,harga,val_kode from procurement_reimbursementd where trano = '$trano'";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }



}
 
