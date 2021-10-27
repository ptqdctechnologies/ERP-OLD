<?php
class Admin_Model_PersonalAssistant extends Zend_Db_Table_Abstract
{
	protected $_name ='personal_assistant';
	protected $db;

	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
}