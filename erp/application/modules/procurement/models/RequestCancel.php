<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/8/11
 * Time: 3:17 PM
 * To change this template use File | Settings | File Templates.
 */

class Procurement_Model_RequestCancel extends Zend_Db_Table_Abstract
{
    protected $_name = 'request_cancel';

    protected $_primary = 'id';
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

}