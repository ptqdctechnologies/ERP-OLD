<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/21/11
 * Time: 9:17 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AdjustingJournal extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_journal';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}