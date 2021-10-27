<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/28/12
 * Time: 1:58 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Sales_Models_RequestPrice extends Zend_Db_Table_Abstract
{
    protected $_name = 'sales_requestprice';
    protected $db;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }
}