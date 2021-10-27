<?php
//Use Zend_Db_Table Only for SELECT & DELETE only, not recommended for INSERT & UPDATE
class ProjectManagement_Models_Timesheet extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_timesheet';
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

    public function getLastTimesheet($uid='',$month)
    {
//        $sql = "
//            DROP TEMPORARY TABLE IF EXISTS my_timesheet;
//            CREATE TEMPORARY TABLE my_timesheet
//                SELECT trano,prj_kode,is_submit FROM projectmanagement_timesheet
//                WHERE
//                    uid = '$uid'
//                    AND
//                    (MONTH(start) = $month OR MONTH(end) = $month)
//                GROUP BY
//                    trano
//                ORDER BY trano ASC;
//        ";
//        $this->db->query($sql);
//        $sql = "
//            DROP TEMPORARY TABLE IF EXISTS work_timesheet;
//            CREATE TEMPORARY TABLE work_timesheet
//            SELECT * FROM (
//                SELECT item_id,approve,prj_kode FROM workflow_trans
//                WHERE item_type = 'TSHEET'
//                ORDER BY date DESC
//            ) a GROUP BY item_id,prj_kode;
//
//        ";
//        $this->db->query($sql);
//        $sql = "
//            SELECT b.* FROM my_timesheet a LEFT JOIN work_timesheet b
//             ON a.trano = b.item_id AND a.prj_kode = b.prj_kode
//             WHERE
//                b.item_id IS NOT NULL;
//        ";
//        $fetch = $this->db->query($sql);
//        if ($fetch)
//        {
//            $fetch = $fetch->fetchAll();
//            $return = array();
//
//            foreach($fetch as $k => $v)
//            {
//                $trano = $v['item_id'];
//                $t = $this->fetchAll("trano = '$trano' AND uid = '$uid'");
//                if ($t)
//                {
//                    $t = $t->toArray();
//                    foreach($t as $k2 => $v2)
//                    {
//                        $t[$k2]['approve'] = $v['approve'];
//                        $return[] = $t[$k2];
//                    }
//                }
//            }
//            return $return;
//        }
//        else
//            return '';
         $sql = "
                SELECT * FROM projectmanagement_timesheet
                WHERE
                        uid = '$uid'
                    AND
                        (MONTH(start) = $month OR MONTH(end) = $month)
                ORDER BY trano ASC;
        ";
        $fetch = $this->db->query($sql);
        if ($fetch)
        {
            $return = $fetch->fetchAll();
            return $return;
        }
        else
            return '';
    }

    public function setFinalApprove($trano='')
    {
        $cek = $this->fetchAll("trano = '$trano'");
        if ($cek)
        {
            $this->update(array("is_final_approve" => 1),"trano = '$trano'");
        }
    }

    public function getSummaryTimesheetPerProject($prjKode='',$onlySubmitted=true,$final=true)
    {
        if ($onlySubmitted)
            $where = " AND is_submit = 1";
        else
            $where = " AND is_submit = 0";

        if ($final)
            $where .= " AND is_final_approve = 1";
        else
            $where .= " AND is_final_approve = 0";

        $sql = "SELECT COALESCE(SUM(hour),0) as total
                FROM
                    projectmanagement_timesheet
                WHERE
                    prj_kode = '$prjKode'
                    $where";
        $fetch = $this->db->query($sql);
        if ($fetch)
        {
            $return = $fetch->fetch();
            return $return;
        }
        else
            return 0;
    }

}
?>