<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/16/11
 * Time: 10:33 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_MasterTypeCOA extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_tipe_coa';

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