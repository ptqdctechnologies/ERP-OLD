<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/14/11
 * Time: 4:20 PM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_PpnReimbursementH extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_ppn_reimbursementh';

    protected $_primary = 'trano';
    protected $db;

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

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

}
