<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/26/12
 * Time: 1:33 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_MasterKategoriAsset extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_kategori_fa';

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
    public function name()
    {
        return $this->_name;
    }
}