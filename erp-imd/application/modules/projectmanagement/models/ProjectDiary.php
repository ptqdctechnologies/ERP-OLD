<?php
class ProjectManagement_Models_ProjectDiary extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_diary';
    private $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getDiaryAttachment($idDiary='')
    {
        $sql = "SELECT * FROM projectmanagement_diary_files
                WHERE
                    diary_id = $idDiary
                ORDER BY filename ASC";
        $fetch = $this->db->query($sql)->fetchAll();

        return $fetch;
    }
}
?>