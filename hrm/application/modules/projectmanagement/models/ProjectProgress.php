<?php
class ProjectManagement_Models_ProjectProgress extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_project_progress';
    protected $_primary = 'id';

    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getSumProjectProgress($prj_kode)
    {
        $sql = "SELECT COALESCE(AVG(progress),0) as average_progress FROM " . $this->_name . " WHERE prj_kode = '$prj_kode'";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        $data['average_progress'] = number_format($data['average_progress'],2);
        
        return $data['average_progress'];
    }

    public function getSumSiteProgress($prj_kode,$sit_kode,$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl_progress BETWEEN '$startDate' AND '$endDate')";
        }
        $sql = "SELECT COALESCE(progress,0) as last_progress FROM " . $this->_name . " WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' $sqlDate ORDER BY date DESC LIMIT 1";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        $data['last_progress'] = number_format($data['last_progress'],2);

        return $data['last_progress'];
    }

    public function getSumSiteProgressForEdit($prj_kode,$sit_kode,$date)
    {
        $sql = "SELECT COALESCE(progress,0) as last_progress FROM " . $this->_name . " WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND date < '$date' ORDER BY date DESC LIMIT 1";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        $data['last_progress'] = number_format($data['last_progress'],2);

        return $data['last_progress'];
    }

    public function getAllSiteProgress($prj_kode)
    {
        $sql = "SELECT a.sit_kode,a.sit_nama,COALESCE((SELECT COALESCE(progress,0) FROM projectmanagement_project_progress where prj_kode = a.prj_kode AND sit_kode = a.sit_kode ORDER BY date DESC LIMIT 1),0) as progress,b.ket FROM master_site a LEFT JOIN projectmanagement_project_progress b ON a.sit_kode = b.sit_kode and a.prj_kode = b.prj_kode WHERE a.prj_kode = '$prj_kode' GROUP BY a.sit_kode";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetchAll();

        $id = 1;
        foreach ($data as $key => $val)
        {
            $data[$key]['id'] = $id;
            $data[$key]['progress'] = number_format($val['progress'],2);
            $id++;
        }

        return $data;
    }

    public function getSiteProgress($prj_kode,$sit_kode)
    {
        $sql = "SELECT * FROM projectmanagement_project_progress WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' ORDER BY date ASC";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetchAll();

        foreach ($data as $key => $val)
        {
            $data[$key]['progress'] = number_format($val['progress'],2);
        }

        return $data;
    }

    public function getSiteProgressForEdit($prj_kode,$sit_kode,$date)
    {
        $sql = "SELECT * FROM projectmanagement_project_progress WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' AND date < '$date' ORDER BY date ASC";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetchAll();

        foreach ($data as $key => $val)
        {
            $data[$key]['progress'] = number_format($val['progress'],2);
        }

        return $data;
    }

    public function getLastForEdit($prjKode, $sitKode)
    {
        $sql = "SELECT * FROM projectmanagement_project_progress WHERE prj_kode = '$prjKode' AND sit_kode = '$sitKode' ORDER BY date DESC LIMIT 1";

        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();


       $data['progress'] = number_format($data['progress'],2);

        return $data;
    }

    public function getSumSiteProgressRemark($prj_kode,$sit_kode,$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl_progress BETWEEN '$startDate' AND '$endDate')";
        }
        $sql = "SELECT ket as ket_progress FROM " . $this->_name . " WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' $sqlDate ORDER BY date DESC LIMIT 1";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        return $data['ket_progress'];
    }
}
?>