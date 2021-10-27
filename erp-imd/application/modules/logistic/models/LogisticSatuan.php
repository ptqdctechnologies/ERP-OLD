<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/7/11
 * Time: 9:47 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Model_LogisticSatuan extends Zend_Db_Table_Abstract
{
	protected $_name ='master_satuan';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}