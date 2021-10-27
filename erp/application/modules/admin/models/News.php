<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/20/12
 * Time: 11:07 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Admin_Models_News extends Zend_Db_Table_Abstract
{
    protected $_name = 'news';

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