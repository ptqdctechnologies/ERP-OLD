<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 3/20/12
 * Time: 10:56 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingTemporaryJurnalAP extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_temporary_jurnal_ap';
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