<?php
class ProjectManagement_Models_ProjectProgress extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_project_progress';
    protected $_primary = 'id';

    private $db;
    private $ALLOWED_ROLE = array(
        802, // Project Manager
        811, // Project Control Senior
    );
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getSumProjectProgress($prj_kode,$returnFormatted=true)
    {
        $sql = "SELECT COALESCE(AVG(progress),0) as average_progress FROM " . $this->_name . " WHERE prj_kode = '$prj_kode'";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        if ($returnFormatted)
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
        $sql = "SELECT COALESCE(progress,0) as last_progress, date FROM " . $this->_name . " WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' $sqlDate ORDER BY date DESC LIMIT 1";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

        $data['last_progress'] = number_format($data['last_progress'],2);

        return $data;
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
        $sql = "SELECT * FROM projectmanagement_project_progress WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' ORDER BY date DESC";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetchAll();

        foreach ($data as $key => $val)
        {
            $data[$key]['progress'] = number_format($val['progress'],2);
        }

        return $data;
    }
    
    public function getSiteProgressV2($prj_kode,$sit_kode,$startDate='',$endDate='')
    {
         //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl_progress BETWEEN '$startDate' AND '$endDate')";
        }

        $sql = "SELECT id,prj_kode,prj_nama,sit_kode,sit_nama,COALESCE(progress,0) AS progress,tgl_progress,ket,date,uid FROM projectmanagement_project_progress WHERE prj_kode = '$prj_kode' AND sit_kode = '$sit_kode' $sqlDate ORDER BY date DESC LIMIT 1";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetch();

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

    public function getLastDate($prj_kode='')
    {
        $select = $this->db->select()
            ->from(array($this->_name),array(
                "max_date" => "MAX(date)"
            ))
            ->where("prj_kode",$prj_kode);

        $data = $this->db->fetchRow($select);
        if ($data)
        {
            $date = $data['max_date'];

            $totalDays = 0;
            if ($date != '')
            {
                $now = new DateTime();
                $last = new DateTime($date);
                $diff = $now->diff($last);
                $totalDays = intval($diff->format('%a'));
                $date = date("d M Y",$date);
            }
        }
        else
            $totalDays = -1; //No progress inputed yet

        return array("total_days" => $totalDays, "last_date" => $date);
    }
    
     public function getSiteProgressPerDate($prj_kode='',$sit_kode='')
    {
        $sitKode = ($sit_kode !='' || $sit_kode != null) ? " AND sit_kode = '$sit_kode' " : '' ;
        
        $sql = "SELECT tgl_progress AS tgl, progress FROM projectmanagement_project_progress WHERE prj_kode = '$prj_kode' $sitKode ORDER BY date ASC";
        $fetch  = $this->db->query($sql);
        $data = $fetch->fetchAll();
        return $data;
    }

    public function isAllowed($prj_kode='')
    {
        $m = new Admin_Models_Masterrole();
        $uid = QDC_User_Session::factory()->getCurrentUID();
        $roles = $m->getRoleUidProject($uid,$prj_kode);

        if ($roles)
        {
            $found = false;
            foreach($roles as $k => $v)
            {
                if (in_array($v['id_role'],$this->ALLOWED_ROLE))
                {
                    $found = true;
                    break;
                }
            }

            return $found;
        }

        return false;
    }
}
?>