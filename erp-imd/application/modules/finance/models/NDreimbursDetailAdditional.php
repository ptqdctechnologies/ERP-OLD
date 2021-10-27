<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/27/11
 * Time: 10:57 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_NDreimbursDetailAdditional extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_nd_reimbursementd_additional';

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