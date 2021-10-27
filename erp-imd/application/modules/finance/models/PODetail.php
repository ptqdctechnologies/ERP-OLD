<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/22/11
 * Time: 3:02 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_PODetail extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_pod';

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

}