<?php
class Admin_Model_Workflow extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow';
	protected $db;
    protected $const;
    protected $user;
     protected $memcacheWork;

	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->user = new Admin_Model_User();
        $this->memcacheWork = Zend_Registry::get('MemcacheWorkflow');
	}
	
	public function addWorkflow($dataArray='',$id_workflow='')
	{
		//TODO
		//Find another way to update workflow
		$sql = "DELETE FROM workflow 
				WHERE
					workflow_item_id=$id_workflow";
		$this->db->query($sql);
		
		for ($i=0;$i<count($dataArray);$i++)
		{
			if ($i==0)
			{
				$next = $dataArray[$i+1];
				$sql = "INSERT INTO workflow (workflow_item_id,is_start,is_end,next,master_role_id)
						VALUES
							($id_workflow,1,0,$next,$dataArray[$i])";
				$this->db->query($sql);
			}
			else 
			{
				$end = 0;
				if ($i == (count($dataArray)-1))
				{
					$prev = $dataArray[$i-1];
					$next = 0;
					$end = 1;
				}
				else
				{
					$prev = $dataArray[$i-1];
					$next = $dataArray[$i+1];
				}
				$sql = "INSERT INTO workflow (workflow_item_id,is_end,next,prev,master_role_id)
						VALUES
							($id_workflow,$end,$next,$prev,$dataArray[$i])";
				
				$this->db->query($sql);
			}
		}			
	}
	
	public function getWorkflowByItemId($workflow_item_id='')
	{
		$sql = "SELECT w.*,ml.uid FROM 
					workflow w
					LEFT JOIN
						master_role mr
					ON mr.id = w.master_role_id
					LEFT JOIN 
						master_login ml
					ON ml.id = mr.id_user
				WHERE 
					w.workflow_item_id=$workflow_item_id
				ORDER BY w.workflow_id ASC";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();
		
		return $result;
	}
	
	public function getWorkflowByUserID($userID='',$workflowType='',$paramArray='',$all=false,$addQuery='')
	{
        $user = $this->user->fetchRow("id=" . $userID)->toArray();
        $uid = $user['uid'];
		$criteria = '';

        $hashQuery = md5($addQuery);
        $cacheID = "WORKFLOW_$workflowItemType_$all_$hashQuery_$uid";

//        if (!$this->memcacheWork->test($cacheID))
//        {

            if ($workflowType != '')
            {
                $workflowType = " AND wit.name = '$workflowType'";

            }
    //		if ($paramArray != '')
    //		{
    //			$temp = array();
    //			foreach($paramArray as $key => $val)
    //			{
    //				if ($val != '' && isset($val))
    //				{
    //
    //					$val = "'$val'";
    //					$temp[] = "wi.$key = $val";
    //				}
    //			}
    //			$criteria .= ' AND ' . implode(' AND ',$temp);
    //		}
    //		$sql = "SELECT w.*,wi.prj_kode,wi.sit_kode FROM
    //					workflow w
    //					LEFT JOIN
    //						master_role mr
    //					ON mr.id = w.master_role_id
    //					LEFT JOIN
    //						workflow_item wi
    //					ON wi.workflow_item_id = w.workflow_item_id
    //					LEFT JOIN
    //						workflow_item_type wit
    //					ON wit.workflow_item_type_id = wi.workflow_item_type_id
    //				WHERE
    //					mr.id_user = $userID
    //					$criteria
    //				$addQuery";
            if ($workflowType == '')
            {
                $sql = "SELECT distinct
                            workflow_item_id,
                            is_start,
                            prev,
                            next,
                            is_end,
                            master_role_id,
                            workflow_structure_id,
                            is_final,
                            is_executor,
                            uid,
                            uid_prev,
                            uid_next,
                            prj_kode
                        FROM
                        workflow w
                        WHERE
                        w.uid = '$uid'
                        $addQuery";
            }
            else
            {
                $sql = "SELECT
                            w.*
                        FROM
                        workflow w
                        LEFT JOIN
                            workflow_item wi
                        ON wi.workflow_item_id = w.workflow_item_id
                        LEFT JOIN
                            workflow_item_type wit
                        ON wit.workflow_item_type_id = wi.workflow_item_type_id
                    WHERE
                        w.uid = '$uid'
                        $workflowType
                    $addQuery";
            }
            $fetch = $this->db->query($sql);
            if (!$all)
                $result = $fetch->fetch();
            else
                $result = $fetch->fetchAll();

//            $this->memcacheWork->save($result,$cacheID,array('WORKFLOW'));
//        }
//        else
//        {
//            $result = $this->memcacheWork->load($cacheID);
//        }
		return $result;
	}
	
	public function getSubmittedDoc($workflowID='')
	{
		$sql = "SELECT w.* FROM 
					workflow w
				WHERE 
					w.workflow_id=$workflowID";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetch();
		
		$prev = 0;
		$next = 0;
		$workflow_item_id = '';
		$master_role_id = '';
		
		$prev = $result['prev'];
		$next = $result['next'];
		$workflow_item_id = $result['workflow_item_id'];
		$master_role_id = $result['master_role_id'];
		
		$sql = "SELECT wt.* FROM 
					workflow w
				LEFT JOIN 
					workflow_trans wt
				ON w.workflow_id = wt.workflow_id
				WHERE 
					w.workflow_item_id=$workflow_item_id
				AND
					w.master_role_id = $prev
				AND w.next = $master_role_id
				AND wt.approve = " . $this->const['DOCUMENT_SUBMIT'];
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();
		
		return $result;
	}

    public function getDocument($uid='',$itemType='',$trano='', $count=false,$offset='',$limit='')
    {
        if ($itemType != '')
            $query = " AND item_type LIKE '$itemType%'";
        if ($trano != '')
            $query .= " AND item_id LIKE '%$trano%'";
//        $sql = "DROP TEMPORARY TABLE IF EXISTS my_next_docs3";
//        $fetch = $this->db->query($sql);
//        $sql = "DROP TEMPORARY TABLE IF EXISTS my_next_docs2";
//        $fetch = $this->db->query($sql);
//        $sql = "DROP TEMPORARY TABLE IF EXISTS my_next_docs";
//        $fetch = $this->db->query($sql);
//        $sql = "DROP TEMPORARY TABLE IF EXISTS my_docs";
//        $fetch = $this->db->query($sql);
//        $sql = "DROP TEMPORARY TABLE IF EXISTS my_work";
//        $fetch = $this->db->query($sql);

//         $sql = "
//            DROP TEMPORARY TABLE IF EXISTS my_docs;
//            CREATE TEMPORARY TABLE my_docs
//            SELECT * FROM (
//                SELECT * FROM workflow_trans WHERE uid = '$uid' $query ORDER BY date DESC
//            ) a GROUP BY a.item_id;
//
//            DROP TEMPORARY TABLE IF EXISTS my_next_docs;
//            CREATE TEMPORARY TABLE my_next_docs
//            SELECT * FROM (
//                SELECT * FROM workflow_trans
//                WHERE
//                    uid_next = '$uid' AND approve NOT IN (400,500) $query
//                ORDER BY date DESC
//            ) a GROUP BY a.item_id;
//
//            DROP TEMPORARY TABLE IF EXISTS my_next_docs2;
//            CREATE TEMPORARY TABLE my_next_docs2
//            SELECT b.* FROM ( SELECT * FROM workflow_trans WHERE approve IN (400,500) GROUP BY item_id) a
//            RIGHT JOIN my_next_docs b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE a.item_id is null AND b.item_id is not null;
//
//            DROP TEMPORARY TABLE IF EXISTS my_work;
//            CREATE TEMPORARY TABLE my_work
//            SELECT a.* FROM my_next_docs2 a LEFT JOIN my_docs b on a.item_id = b.item_id WHERE b.item_id is null;
//            INSERT INTO my_work
//            SELECT a.* FROM my_next_docs2 a LEFT JOIN my_docs b on a.item_id = b.item_id WHERE a.date >= b.date AND b.approve NOT IN (400,500);
//
//            DROP TEMPORARY TABLE IF EXISTS my_next_docs3;
//            CREATE TEMPORARY TABLE my_next_docs3
//            SELECT * FROM (
//                SELECT a.item_id
//                FROM my_work a
//                LEFT JOIN workflow_trans b
//                ON a.item_id = b.item_id
//                AND a.workflow_item_id = b.workflow_item_id
//                WHERE b.date > a.date
//                ORDER BY a.date DESC
//            ) c GROUP BY c.item_id;
//
//         ";
        $sql = "
        DROP TEMPORARY TABLE IF EXISTS my_last_docs;
            CREATE TEMPORARY TABLE my_last_docs
            SELECT * FROM (
                SELECT * FROM workflow_trans WHERE uid = '$uid' $query AND final = 0 AND generic = 0 ORDER BY date DESC
            ) a GROUP BY a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs;
            CREATE TEMPORARY TABLE my_next_docs
            SELECT * FROM (
                SELECT * FROM workflow_trans
                WHERE
                    uid_next = '$uid' $query AND approve NOT IN (400,500)
		AND
			final = 0  AND generic = 0 
                ORDER BY date DESC
            ) a GROUP BY a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs2;
            CREATE TEMPORARY TABLE my_next_docs2
            SELECT * FROM (
                SELECT * FROM workflow_trans
                WHERE
                    final = 0  AND generic = 0 
                ORDER BY date DESC
            ) a GROUP BY a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs3;
            CREATE TEMPORARY TABLE my_next_docs3
		SELECT a.* FROM my_next_docs a LEFT JOIN my_last_docs b ON
		a.item_id = b.item_id
		WHERE
			b.item_id is null;
		INSERT INTO my_next_docs3
		SELECT a.* FROM my_next_docs a LEFT JOIN my_last_docs b ON
		a.item_id = b.item_id
		WHERE
			a.date >= b.date AND b.approve NOT IN (400,500);

		DROP TEMPORARY TABLE IF EXISTS my_next_docs4;
            CREATE TEMPORARY TABLE my_next_docs4
            SELECT * FROM (
                SELECT a.item_id
                FROM my_next_docs a
                LEFT JOIN my_next_docs2 b
                ON a.item_id = b.item_id
                WHERE b.date > a.date
                ORDER BY b.date DESC
            ) a GROUP BY a.item_id;
        ";
        $fetch = $this->db->query($sql);
        
        if ($fetch)
        {
            if ($count)
            {
                //Old...
//                $sql = "
//                    SELECT COUNT(*) AS jumlah FROM my_work a LEFT JOIN my_next_docs3 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id;
//                ";
                //New...
                $sql = "
                    SELECT COUNT(*) AS jumlah FROM my_next_docs3 a LEFT JOIN my_next_docs4 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id;
                ";
                $fetch = $this->db->prepare($sql);
                $fetch->execute();
                $hasil = $fetch->fetch();
                $fetch->closeCursor();
                $hasil = $hasil['jumlah'];
            }
            else
            {
                if ($offset !== '' && $limit !== '')
                    $limits = " LIMIT $offset,$limit";
                //old...
//                $sql = "SELECT SQL_CALC_FOUND_ROWS a.* FROM my_work a LEFT JOIN my_next_docs3 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id $limits";
                //new...
                $sql = "SELECT SQL_CALC_FOUND_ROWS a.* FROM my_next_docs3 a LEFT JOIN my_next_docs4 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id $limits;";
                $fetch = $this->db->prepare($sql);
                $fetch->execute();
                $hasil['posts'] = $fetch->fetchAll();
                $hasil['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                $fetch->closeCursor();
            }
        }
        else
        {
            if ($count)
                $hasil = 0;
            else
            {
                $hasil['count'] = 0;
                $hasil['posts'] = '';
            }
        }

        $sql = "
        DROP TEMPORARY TABLE IF EXISTS my_last_docs;
            CREATE TEMPORARY TABLE my_last_docs
            SELECT * FROM (
                SELECT * FROM workflow_trans WHERE uid = '$uid' $query AND final = 0  AND generic = 1 ORDER BY date DESC
            ) a GROUP BY a.workflow_item_id,a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs;
            CREATE TEMPORARY TABLE my_next_docs
            SELECT * FROM (
                SELECT * FROM workflow_trans
                WHERE
                    uid_next = '$uid' $query AND approve NOT IN (400,500)
		AND
			final = 0  AND generic = 1
                ORDER BY date DESC
            ) a GROUP BY a.workflow_item_id,a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs2;
            CREATE TEMPORARY TABLE my_next_docs2
            SELECT * FROM (
                SELECT * FROM workflow_trans
                WHERE
                    final = 0  AND generic = 1
                ORDER BY date DESC
            ) a GROUP BY a.workflow_item_id,a.item_id;

		DROP TEMPORARY TABLE IF EXISTS my_next_docs3;
            CREATE TEMPORARY TABLE my_next_docs3
		SELECT a.* FROM my_next_docs a LEFT JOIN my_last_docs b ON
		a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id
		WHERE
			b.item_id is null;
		INSERT INTO my_next_docs3
		SELECT a.* FROM my_next_docs a LEFT JOIN my_last_docs b ON
		a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id
		WHERE
			a.date >= b.date AND b.approve NOT IN (400,500);

		DROP TEMPORARY TABLE IF EXISTS my_next_docs4;
            CREATE TEMPORARY TABLE my_next_docs4
            SELECT * FROM (
                SELECT
                    a.item_id,
                    a.workflow_item_id
                FROM my_next_docs a
                LEFT JOIN my_next_docs2 b
                ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id
                WHERE b.date > a.date
                ORDER BY b.date DESC
            ) a GROUP BY a.item_id;
        ";
        $fetch = $this->db->query($sql);

        if ($fetch)
        {
            if ($count)
            {
                //Old...
//                $sql = "
//                    SELECT COUNT(*) AS jumlah FROM my_work a LEFT JOIN my_next_docs3 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id;
//                ";
                //New..

                //Bypass for timesheet.
//                $sql = "
//                    SELECT COUNT(*) AS jumlah FROM my_next_docs3 a LEFT JOIN my_next_docs4 b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE b.item_id is null order by a.item_id;
//                ";
                //....
                $sql = "
                    SELECT SQL_CALC_FOUND_ROWS a.* FROM my_next_docs3 a LEFT JOIN my_next_docs4 b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE b.item_id is null order by a.item_id;
                ";
                $fetch = $this->db->prepare($sql);
                $fetch->execute();
//                $hasilGeneric = $fetch->fetch();
                $hasilGeneric = $fetch->fetchAll();
                $fetch->closeCursor();
                $jumTimesheet = $this->filterTimesheet($hasilGeneric);
                $hasilGeneric = floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
                $hasil = floatval($hasil) + floatval($jumTimesheet);
            }
            else
            {
                if ($offset !== '' && $limit !== '')
                    $limits = " LIMIT $offset,$limit";
                //old...
//                $sql = "SELECT SQL_CALC_FOUND_ROWS a.* FROM my_work a LEFT JOIN my_next_docs3 b ON a.item_id = b.item_id WHERE b.item_id is null order by a.item_id $limits";
                //new...
                $sql = "SELECT SQL_CALC_FOUND_ROWS a.* FROM my_next_docs3 a LEFT JOIN my_next_docs4 b ON a.item_id = b.item_id AND a.workflow_item_id = b.workflow_item_id WHERE b.item_id is null order by a.item_id $limits;";
                $fetch = $this->db->prepare($sql);
                $fetch->execute();
                $hasilGeneric = $fetch->fetchAll();
                $time = $this->filterTimesheet($hasilGeneric,false);
                foreach($time as $key => $val)
                {
                    array_push($hasil['posts'],$val);
                }
                $hasilGeneric = floatval($this->db->fetchOne('SELECT FOUND_ROWS()'));
                $hasil['count'] = $hasil['count'] + $hasilGeneric;
//                $hasil['posts'] = $fetch->fetchAll();
//                $hasil['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
                $fetch->closeCursor();
            }
        }

        return $hasil;
    }

    public function getWorkflowProjectByUserID($userID='')
    {
        $sql = "
            SELECT prj_kode FROM workflow WHERE uid = '$userID' GROUP BY prj_kode
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        return $fetch;
    }

    public function filterTimesheet($data='',$count=true)
    {
        if ($data =='')
            return 0;

        $hasil = array();
        $others = 0;
        
        if ($count)
        {
            $reject = 0;
            $others = 0;
            foreach ($data as $k => $v)
            {
                if ($v['item_type'] == 'TSHEET'  && $v['approve'] == 300)
                {
                    $reject++;
                }
                if ($v['item_type'] == 'TSHEET'  && $v['approve'] != 300)
                {
                     $trano = $v['item_id'];
                     $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'TSHEET')
                {
                    $others++;
                }
            }

            $temp = array();
            $timesheet = new ProjectManagement_Models_Timesheet();
            foreach($hasil as $k => $v)
            {
                $fetch = $timesheet->fetchRow("trano = '$k'");
                if ($fetch)
                {
                    $uid = $fetch['uid'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['uid'];
                    $hasil[$k]['item_id'] = "TIMESHEET";
                    $hasil[$k]['comment'] = "My Timesheet";
                    $temp[$uid] = $hasil[$k];
                }

            }
            return count($temp) + $reject + $others;
        }
        else
        {
            $hasilRet = array();
            foreach ($data as $k => $v)
            {
                if ($v['item_type'] == 'TSHEET'  && $v['approve'] == 300)
                {
                    $hasilRet[] = $v;
                }
                if ($v['item_type'] == 'TSHEET'  && $v['approve'] != 300)
                {
                     $trano = $v['item_id'];
                     $hasil[$trano] = $v;
                }
                if ($v['item_type'] != 'TSHEET')
                {
                    $hasilRet[] = $v;
                }
            }
            $temp = array();
            $timesheet = new ProjectManagement_Models_Timesheet();
            foreach($hasil as $k => $v)
            {
                $fetch = $timesheet->fetchRow("trano = '$k'");
                if ($fetch)
                {
                    $uid = $fetch['uid'];
                    $hasil[$k]['date'] = date("Y-m-d H:i:s");
                    $hasil[$k]['uid'] = $fetch['uid'];
                    $hasil[$k]['item_id'] = "TIMESHEET";
                    $hasil[$k]['comment'] = "My Timesheet";
                    $temp[$uid] = $hasil[$k];
                }

            }

            foreach($temp as $k => $v)
            {
                $hasilRet[] = $v;
            }

            return $hasilRet;
        }
    }
}