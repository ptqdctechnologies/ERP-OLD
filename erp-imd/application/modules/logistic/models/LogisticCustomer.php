<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/10/11
 * Time: 2:01 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Model_LogisticCustomer extends Zend_Db_Table_Abstract
{
	protected $_name ='master_customer';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}