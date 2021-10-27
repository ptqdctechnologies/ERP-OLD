<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 3/6/12
 * Time: 3:08 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Sales_Models_HistoryRequestPrice extends Zend_Db_Table_Abstract
{
    protected $_name = 'sales_requestprice_history';
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