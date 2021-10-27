<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/27/11
 * Time: 1:33 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_MasterCoaBank extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_coa_bank';

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

    public function getBankFromType($type)
    {
        $b = new Finance_Models_MasterBank();
        $select = $this->db->select()
            ->from(array("a" => $this->_name))
            ->joinLeft(array("b" => $b->__name()),"a.bank_id = b.id")
            ->where("a.trano_type = ?",$type);

        $data = $this->db->fetchRow($select);

        return $data;
    }
}