<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/6/12
 * Time: 4:20 PM
 * To change this template use File | Settings | File Templates.
 */

class Logistic_Models_MasterKondisi extends Zend_Db_Table_Abstract
{
	protected $_name ='master_kondisi';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}