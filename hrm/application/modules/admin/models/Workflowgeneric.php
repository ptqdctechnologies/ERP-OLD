<?php
class Admin_Model_Workflowgeneric extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow_generic';
	protected $db;
    protected $ldap;
    protected $session;
    protected $memcacheWork;
    protected $user;

	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->memcacheWork = Zend_Registry::get('MemcacheWorkflow');
        $this->user = new Admin_Model_User();
        $this->ldap = new Default_Models_Ldap();
	}

    public function getTreeRouteGroupNew($workflowItemName='')
	{

		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_name = '$workflowItemName'
				GROUP BY ws.level
				ORDER BY ws.level ASC";

    	$return = $this->db->fetchAll($sql);
        return $return;
	}

    public function getTreeRouteNew($workflowItemname='',$level='')
	{
		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_name = '$workflowItemname'
					AND
					ws.level = $level
				GROUP BY ws.role_id
				ORDER BY ws.level ASC";
    	$return = $this->db->fetchAll($sql);
        return $return;
	}

    public function getTreeRouteGroupNewByOne($workflowItemID='')
	{

		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_id = $workflowItemID
				GROUP BY ws.level
				ORDER BY ws.level ASC";

    	$return = $this->db->fetchAll($sql);
        return $return;
	}

    public function getTreeRouteNewByOne($workflowItemID='',$level='')
	{
		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_id = $workflowItemID
					AND
					ws.level = $level
				GROUP BY ws.role_id
				ORDER BY ws.level ASC";
    	$return = $this->db->fetchAll($sql);
        return $return;
	}


    public function getTreeRouteGroup($workflowItemTypeId='')
	{

		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_type_id = $workflowItemTypeId
				GROUP BY ws.level
				ORDER BY ws.level ASC";

    	$return = $this->db->fetchAll($sql);
        return $return;
	}

    public function getTreeRoute($workflowItemTypeId='',$level='')
	{
		$sql = "SELECT ws.* FROM workflow_generic ws
				WHERE
					ws.workflow_item_type_id = $workflowItemTypeId
					AND
					ws.level = $level
				GROUP BY ws.workflow_item_type_id, ws.role_id
				ORDER BY ws.level ASC";
    	$return = $this->db->fetchAll($sql);
        return $return;
	}

    public function getWorkflowByUserID($userID='',$workflowItemType='',$query='')
    {

        $user = $this->user->fetchRow("id=" . $userID)->toArray();

        $uid = $user['uid'];
        $hashQuery = md5($query);
        $cacheID = "WORKFLOW_$workflowItemType_$hashQuery_$uid";

//        if (!$this->memcacheWork->test($cacheID))
//        {

            $sql = "SELECT * FROM workflow_item_type
                    WHERE
                        name = '$workflowItemType'
                    AND
                        generic = 1";
            $fetch = $this->db->query($sql);
            $fetch = $fetch->fetch();
            $workflowID = $fetch['workflow_item_type_id'];
            $sql = "SELECT mrt.id,mrt.display_name FROM master_login ml
                    LEFT JOIN master_role mr
                    ON
                        ml.id = mr.id_user
                    LEFT JOIN master_role_type mrt
                    ON
                        mr.id_role = mrt.id
                    WHERE
                        ml.id = '$userID'
                    AND
                        mrt.id IS NOT NULL
                    GROUP BY mrt.id
                    ";
            $fetch = $this->db->query($sql);
            if ($fetch)
            {
                $result = array();
                $fetch = $fetch->fetchAll();
                foreach($fetch as $key => $val)
                {
                    $roleID = $val['id'];
                    if ($roleID == '' || $workflowID == '')
                        continue;
                    $sql = "SELECT * FROM workflow_generic
                            WHERE
                                workflow_item_type_id = $workflowID
                            AND
                                role_id = $roleID
                            $query
                            ";
                    $fetch2 = $this->db->query($sql);
                    if ($fetch2)
                    {
                        $fetch2 = $fetch2->fetchAll();
                        foreach ($fetch2 as $k => $v)
                        {
                            $result[] = $v;
                        }
                    }

                }
            }
//            $this->memcacheWork->save($result,$cacheID,array('WORKFLOW'));
//        }
//        else
//        {
//            $result = $this->memcacheWork->load($cacheID);
//        }

        return $result;
    }

    public function getNextPerson($workflowId='')
    {
        $works = $this->fetchRow("workflow_id = $workflowId");
        if($works)
        {
            $works = $works->toArray();
            $isEnd = (boolean)$works['is_end'];
            if (!$isEnd)
            {
                $nextLevel = (int)$works['level'] + 1;
                $workflowItem = $works['workflow_item_id'];
                $workflowType = $works['workflow_item_type_id'];
                $prjKode = $works['prj_kode'];
                $nextWork = $this->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItem AND workflow_item_type_id = $workflowType AND prj_kode = '$prjKode'");
                if ($nextWork)
                {
                    $return = array();
                    $roleID = $nextWork['role_id'];
                    $masterRole = new Admin_Model_Masterrole();
                    $users = $masterRole->getUserFromRoleAndProject($roleID,$prjKode);
                    if ($users)
                    {
                        foreach($users as $k => $v)
                        {
                            $return = array(
                            
                            );
                        }
                    }
                }
                else
                    return false;
            }
            else
                return '';
        }
        else
            return false;
    }

    public function getNextWorkflow($workflowId='',$workflowTransId='')
    {
        $works = $this->fetchRow("workflow_id = $workflowId");
        if($works)
        {
            $works = $works->toArray();
            $isEnd = (boolean)$works['is_end'];
            $isStart = (boolean)$works['is_start'];
            $workflowItem = $works['workflow_item_id'];
            $workflowType = $works['workflow_item_type_id'];
            $prjKode = $works['prj_kode'];
            $masterRole = new Admin_Model_Masterrole();
            $workTrans = new Admin_Models_Workflowtrans();
            $myUid = $this->session->userName;
            $return = array();
            if (!$isStart)
            {
                $prev = $workTrans->fetchRow("workflow_trans_id = $workflowTransId AND uid_next = '$myUid'","date DESC",0,1);
                if ($prev)
                {
                    $prev = $prev->toArray();
                }
            }
            if (!$isEnd)
            {
                $nextLevel = $works['level'] + 1;
                $nextWork = $this->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItem AND workflow_item_type_id = $workflowType AND prj_kode = '$prjKode'");
                if ($nextWork)
                {
                    $nextWork = $nextWork->toArray();
                    if(count($nextWork) == 1)
                    {
                        $nextWork = $nextWork[0];
                        $roleID = $nextWork['role_id'];
                        $masterRole = new Admin_Model_Masterrole();
                        $users = $masterRole->getUserFromRoleAndProject($roleID,$prjKode);
                        if ($users)
                        {
                            if (count($users) == 1)
                                $next = $users[0];
                        }
                    }
                }
            }
            $return = array(
                "uid" => $this->session->userName,
                "workflow_item_id" => $workflowItem,
                "uid_next" => $next['uid'],
                "uid_prev" => $prev['uid'],
                "is_start" => $works['is_start'],
                "is_end" => $works['is_end'],
                "is_final" => $works['is_final'],
                "is_executor" => $works['is_executor'],
                "prj_kode" => $prjKode,
                "master_role_id" => $works['role_id']
            );

            return $return;
        }
        else
            return false;
    }

    public function getMyWorkflow($workflowId='',$workflowTransId='',$currentFlow=false)
    {
        $works = $this->fetchRow("workflow_id = $workflowId");
        if($works)
        {
            $works = $works->toArray();
            $workflowItem = $works['workflow_item_id'];
            $workflowType = $works['workflow_item_type_id'];
            $prjKode = $works['prj_kode'];
            $masterRole = new Admin_Model_Masterrole();
            $masterRoleType = new Admin_Model_Masterroletype();
            $workTrans = new Admin_Models_Workflowtrans();
            $myUid = $this->session->userName;
            $return = array();
            $prev = $workTrans->fetchRow("workflow_trans_id = $workflowTransId AND uid_next = '$myUid'","date DESC",0,1);
            if ($prev)
            {
                $prev = $prev->toArray();
            }
            $myLevel = $works['level'] + 1;
            $myWork = $this->fetchRow("level = $myLevel AND workflow_item_id = $workflowItem AND workflow_item_type_id = $workflowType AND prj_kode = '$prjKode'");
            if ($currentFlow)
                return $myWork;
            $isEnd = (boolean)$myWork['is_end'];
            $isStart = (boolean)$myWork['is_start'];
            if (!$isEnd)
            {
                if ($myWork)
                {
                    $nextLevel = $myWork['level'] + 1;
                    $nextWork = $this->fetchAll("level = $nextLevel AND workflow_item_id = $workflowItem AND workflow_item_type_id = $workflowType AND prj_kode = '$prjKode'");
                    if ($nextWork)
                    {
                        $nextWork = $nextWork->toArray();
                        $masterRole = new Admin_Model_Masterrole();
                        if(count($nextWork) == 1)
                        {
                            $nextWork = $nextWork[0];
                            $roleID = $nextWork['role_id'];
                            $users = $masterRole->getUserFromRoleAndProject($roleID,$prjKode);
//                            $users = $masterRole->getUserFromRoleAndProject($roleID);
                            if ($users)
                            {
                                if (count($users) == 1)
                                    $next = $users[0];
                                else
                                {
                                    $nextPerson = array();
                                    foreach ($users as $k => $v)
                                    {
                                        $roles = $masterRoleType->fetchRow("id=$roleID");
                                        $nextUser = $this->ldap->getAccount($v['uid']);
                                        $nextName = $nextUser['displayname'][0];
                                        $nextPerson['person'][] = array(
                                            "prj_kode" => $nextWork['prj_kode'],
                                            "uid_next" => $v['uid'],
                                            "uid_prev" => $prev['uid'],
                                            "trano" => $prev['item_id'],
                                            "name"  => $nextName,
                                            "role_name" => $roles['display_name'],
                                            "workflow_item_name" => $nextWork['workflow_item_name'],
                                            "workflow_item_id" => $nextWork['workflow_item_id'],
                                            "workflow_item_type_id" => $nextWork['workflow_item_type_id'],
                                            "workflow_id" => $myWork['workflow_id']
                                        );
                                    }
                                    return $nextPerson;
                                }
                            }
                        }
                        else
                        {
                            $nextPerson = array();
                            foreach($nextWork as $k => $v)
                            {
                                $roleID = $v['role_id'];
    //                            $users = $masterRole->getUserFromRoleAndProject($roleID,$prjKode);
                                $users = $masterRole->getUserFromRoleAndProject($roleID,$prjKode);
                                if ($users)
                                {
                                    if (count($users) == 1)
                                    {
                                        $roles = $masterRoleType->fetchRow("id=$roleID");
                                        $nextUser = $this->ldap->getAccount($users[0]['uid']);
                                        $nextName = $nextUser['displayname'][0];
                                        $nextPerson['person'][] = array(
                                            "prj_kode" => $v['prj_kode'],
                                            "uid_next" => $users[0]['uid'],
                                            "uid_prev" => $prev['uid'],
                                            "trano" => $prev['item_id'],
                                            "name"  => $nextName,
                                            "role_name" => $roles['display_name'],
                                            "workflow_item_name" => $v['workflow_item_name'],
                                            "workflow_item_id" => $v['workflow_item_id'],
                                            "workflow_item_type_id" => $v['workflow_item_type_id'],
                                            "workflow_id" => $myWork['workflow_id']
                                        );
                                    }
                                    else
                                    {
                                        foreach ($users as $k2 => $v2)
                                        {
                                            $roles = $masterRoleType->fetchRow("id=$roleID");
                                            $nextUser = $this->ldap->getAccount($v2['uid']);
                                            $nextName = $nextUser['displayname'][0];
                                            $nextPerson['person'][] = array(
                                                "prj_kode" => $v['prj_kode'],
                                                "uid_next" => $v2['uid'],
                                                "uid_prev" => $prev['uid'],
                                                "trano" => $prev['item_id'],
                                                "name"  => $nextName,
                                                "role_name" => $roles['display_name'],
                                                "workflow_item_name" => $v['workflow_item_name'],
                                                "workflow_item_id" => $v['workflow_item_id'],
                                                "workflow_item_type_id" => $v['workflow_item_type_id'],
                                                "workflow_id" => $myWork['workflow_id']
                                            );
                                        }
                                    }
                                }
                            }
                            return $nextPerson;
                        }
                    }
                }
            }
            $return = array(
                "uid" => $this->session->userName,
                "workflow_id" => $myWork['workflow_id'],
                "workflow_item_id" => $workflowItem,
                "uid_next" => $next['uid'],
                "uid_prev" => $prev['uid'],
                "is_start" => $myWork['is_start'],
                "is_end" => $myWork['is_end'],
                "is_final" => $myWork['is_final'],
                "is_executor" => $myWork['is_executor'],
                "prj_kode" => $prjKode,
                "master_role_id" => $myWork['role_id']
            );
            
            return $return;
        }
        else
            return false;
    }
}
?>