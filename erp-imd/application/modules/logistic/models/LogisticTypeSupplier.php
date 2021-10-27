<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/10/11
 * Time: 1:39 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Model_LogisticTypeSupplier extends Zend_Db_Table_Abstract
{
	protected $_name ='master_jenissupler';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}