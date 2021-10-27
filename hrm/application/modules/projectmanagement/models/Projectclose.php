<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/13/11
 * Time: 10:09 AM
 * To change this template use File | Settings | File Templates.
 */

class ProjectManagement_Models_Projectclose extends Zend_Db_Table_Abstract
{
	protected $_name ='master_project';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}
