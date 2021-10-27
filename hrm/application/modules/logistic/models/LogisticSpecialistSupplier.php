<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/11/11
 * Time: 9:29 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Model_LogisticSpecialistSupplier extends Zend_Db_Table_Abstract
{
	protected $_name ='master_jenissupliersub';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}