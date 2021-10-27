<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/16/12
 * Time: 10:42 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingTaxRecon extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_recontax';
    public $name;
    public $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}