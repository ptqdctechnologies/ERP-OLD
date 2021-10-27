<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/2/11
 * Time: 1:48 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Default_Models_LogAccountingPosting extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_accounting_posting';

    protected $db;

    public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

}