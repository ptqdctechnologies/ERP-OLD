<?php
class ProjectManagement_Models_ProjectDiaryFile extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_diary_files';
    private $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    
}
?>