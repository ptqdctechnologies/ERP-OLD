<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/27/11
 * Time: 3:42 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Model_MasterBrand extends Zend_Db_Table_Abstract
{
	protected $_name ='master_merk';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}