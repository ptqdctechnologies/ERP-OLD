<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 10/11/11
 * Time: 11:23 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Sales_Models_Regisco extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_registerco';

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }
}