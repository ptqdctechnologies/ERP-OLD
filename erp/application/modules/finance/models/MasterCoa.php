<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/27/11
 * Time: 1:33 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_MasterCoa extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_coa';

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

    public function viewcoa ()
    {
        $data = $this->fetchAll()->toArray();
        return $data;
    }

    public function getcoa ()
    {
        $query = "SELECT * FROM master_coa where coa_kode = '1-4500'";
        $data = $this->db->query($query);
        $return = $data->fetchAll();

        return $return;
    }

    public function __name()
    {
        return $this->_name;
    }
}