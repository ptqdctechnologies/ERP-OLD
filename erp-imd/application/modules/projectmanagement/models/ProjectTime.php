<?php

class ProjectManagement_Models_ProjectTime extends Zend_Db_Table_Abstract
{
    protected $_name = 'transengineer_projecttime';
    private $db;
    protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    
    public function startEndDateProject()
    {
        $sql = "SELECT gantt.prj_kode AS prj_kode, gantt.sit_kode AS sit_kode, master.stsclose AS isclosed, gantt.start_date AS start_date, gantt.end_date AS end_date 
                    FROM {$this->_name} AS gantt
                    INNER JOIN master_project AS master ON (master.prj_kode = gantt.prj_kode)
                    WHERE master.type='p'  $prj_kode $sit_kode ";
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();
        return $data;
}
}
?>