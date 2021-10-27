<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/14/11
 * Time: 3:04 PM
 * To change this template use File | Settings | File Templates.
 */

class Extjs4_Models_Gantt extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_gantt_task';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }
    
    public function getAllPojectSchedule()
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS all_projects;
        CREATE TEMPORARY TABLE all_projects
        (
            prj_kode varchar(255) DEFAULT '',
            sit_kode varchar(255) DEFAULT '',
            isclosed int(1) DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL

        );";
        $this->db->query($sql);
        
        $sql2 = "INSERT INTO all_projects (prj_kode,sit_kode,isclosed,start_date,end_date)
                 SELECT gantt.prj_kode AS prj_kode, '', master.stsclose AS isclosed, MIN(gantt.start_date) AS start_date, MAX(gantt.end_date) AS end_date 
                 FROM {$this->_name} AS gantt
                 INNER JOIN master_project AS master ON (master.prj_kode = gantt.prj_kode)
                 WHERE master.type='p' GROUP BY gantt.prj_kode; ";  
        $this->db->query($sql2);
        
        $sql1 = "   INSERT INTO all_projects (prj_kode,sit_kode,isclosed,start_date,end_date)
                    SELECT gantt.prj_kode AS prj_kode, gantt.sit_kode AS sit_kode, master.stsclose AS isclosed, gantt.start_date AS start_date, gantt.end_date AS end_date 
                    FROM {$this->_name} AS gantt
                    INNER JOIN master_project AS master ON (master.prj_kode = gantt.prj_kode)
                    WHERE master.type='p' ;";
        $this->db->query($sql1);

        $sql3 = "SELECT * FROM all_projects ORDER BY prj_kode DESC;";
        
        $fetch = $this->db->query($sql3);
        $result = $fetch->fetchAll();

        return $result;
}
    
    public function getPojectSchedule($prj_kode='',$sit_kode='')
    {

        if($sit_kode !='' || $sit_kode != null){$sit_kode=" AND sit_kode='$sit_kode'";}

        $sql = "SELECT MAX(end_date) AS end_date, MIN(start_date) AS start_date FROM {$this->_name} WHERE prj_kode='$prj_kode' $sit_kode ;";
        
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        return $result;
    }
    
    public function getPojectDate($prj_kode='')
    {
        $sql = "SELECT MAX(end_date) AS end_date, MIN(start_date) AS start_date FROM {$this->_name} WHERE prj_kode='$prj_kode' ;";   
        $fetch = $this->db->query($sql);
        $result = $fetch->fetch();

        return $result;
    }
}
