<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/20/11
 * Time: 10:16 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_PettyCashIn extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_petty_cash_in';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}