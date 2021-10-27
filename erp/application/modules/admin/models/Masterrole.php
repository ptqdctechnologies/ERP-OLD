<?php
class Admin_Models_Masterrole extends Zend_Db_Table_Abstract
{
	protected $_name ='master_role';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}
	
	public function getUserFromRoleId($roleId='')
	{
		$sql = "SELECT a.id,a.uid,a.id_privilege 
				FROM master_login a 
				LEFT JOIN master_role b
				ON
					b.id_user = a.id
				WHERE
					b.id = $roleId";
		$user = $this->db->query($sql);
		$result = $user->fetch();
		
		return $result;
	}

    public function getRoleFromRoleId($roleId='')
	{
		$sql = "SELECT a.prj_kode,a.sit_kode,b.display_name,b.role_name,b.active
				FROM master_role a
				LEFT JOIN master_role_type b
				ON
					b.id = a.id_role
				WHERE
					a.id = $roleId";
		$user = $this->db->query($sql);
		$result = $user->fetch();

		return $result;
	}

    public function getMyTeamByProject($myId='',$prjKode='')
	{
//		$sql = "SELECT a.id,a.uid,COALESCE(a.id_privilege,'USER') AS privilege,b.id_role
//				FROM master_login a
//				LEFT JOIN master_role b
//				ON
//					b.id_user = a.id
//                WHERE
//					b.id_user != $myId
//                AND
//                    b.prj_kode = '$prjKode'";
        $sql = "SELECT a.id,a.uid,COALESCE(a.id_privilege,'USER') AS privilege,b.id_role
				FROM master_login a
				LEFT JOIN master_role b
				ON
					b.id_user = a.id
                WHERE
                    b.prj_kode = '$prjKode'";
		$user = $this->db->query($sql);
		$result = $user->fetchAll();

		return $result;
	}

    public function getUserFromRoleAndProject($roleTypeId='',$prjKode='')
	{
        if ($prjKode != '')
        {
            $where = "
                AND
                    b.prj_kode = '$prjKode'";
        }
		$sql = "SELECT a.id,a.uid,a.id_privilege
				FROM master_login a
				LEFT JOIN master_role b
				ON
					b.id_user = a.id
				WHERE
					b.id_role = $roleTypeId
                    $where
                GROUP BY uid";
		$user = $this->db->query($sql);
		$result = $user->fetchAll();

		return $result;
	}

    public function cekUserInRole($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

//        if ($prjKode != '')
//        {
//            $where = "
//                AND
//                    b.prj_kode = '$prjKode'";
//        }
        $masterLogin = new Admin_Models_Masterlogin();
        $masterRoleType = new Admin_Models_Masterroletype();
        $select = $this->db->select()
            ->from(array("a" => $masterLogin->__name()),array("id","id_privilege"))
            ->joinLeft(array("b" => $this->_name),"a.id = b.id_user",array("id_role"))
            ->where("a.uid = '$userID'")
            ->group(array("a.uid"));

        if ($prjKode != '')
            $select = $select->where("b.prj_kode = '$prjKode'");

        if ($roleName != '')
        {
            $select->reset(Zend_Db_Select::GROUP);
            $subselect = $this->db->select()
                ->from(array("x" => $select))
                ->joinLeft(array("z" => $masterRoleType->__name()),"x.id_role = z.id",array(
                    "role_name",
                    "display_name"
                ))
                ->where("z.role_name LIKE '%$roleName%'");

            if ($roleDisplayName != '')
            {
                if (!is_array($roleDisplayName))
                    $subselect = $subselect->where("z.display_name LIKE '%$roleDisplayName%'");
                else
                {
                    foreach($roleDisplayName as $k => $v)
                    {
                        $subselect = $subselect->orWhere("z.display_name LIKE '%$roleDisplayName%'");
                    }
                }
            }

            $select = $subselect;
        }
//		$sql = "SELECT a.id,a.uid,a.id_privilege
//				FROM master_login a
//				LEFT JOIN master_role b
//				ON
//					b.id_user = a.id
//				WHERE
//					b.id_role = $roleID
//                AND
//                    a.uid = '$userID'
//                    $where
//                GROUP BY uid";
//		$user = $this->db->query($sql);
//		$result = $user->fetchAll();

        $result = $this->db->fetchAll($select);
        if (count($result) > 0)
            return true;
        else
            return false;
    }

    public function getRoleByProject($prjKode='')
    {
        $sql = "SELECT * FROM (
                    SELECT a.id,a.uid,COALESCE(a.id_privilege,'USER') AS privilege,b.id_role,c.display_name
                    FROM master_login a
                    LEFT JOIN master_role b
                    ON
                        a.id = b.id_user
                    LEFT JOIN master_role_type c
                    ON
                        c.id = b.id_role
                    WHERE
                        b.prj_kode = '$prjKode'
                        AND c.id is not null
                    ORDER BY id_role) d
                GROUP BY d.uid";
        $user = $this->db->query($sql);
		$result = $user->fetchAll();

        return $result;
    }

    public function getAllMyRole($idUser='')
    {
        $sql = "SELECT
                    mr.prj_kode,
                    mrt.display_name
                FROM master_role mr
                LEFT JOIN master_role_type mrt
                ON mr.id_role = mrt.id
                WHERE
                    mr.id_user = $idUser
                AND mrt.display_name is not null
                GROUP BY mr.prj_kode,mr.id_role
                ORDER BY mr.prj_kode";

        $result = $this->db->query($sql);
		$result = $result->fetchAll();

        return $result;
    }

    public function getRoleFromUID($uid='',$groupBy='')
    {
        if ($groupBy != '')
            $groupBy = "GROUP BY $groupBy";
        $sql = "SELECT
                    mrt.id,
                    mr.prj_kode,
                    mrt.display_name
                FROM master_login ml
                LEFT JOIN master_role mr
                ON ml.id = mr.id_user
                LEFT JOIN master_role_type mrt
                ON mr.id_role = mrt.id
                WHERE
                    ml.uid = '$uid'
                AND mrt.display_name is not null
                AND (mr.prj_kode is not null AND mr.prj_kode  != '')
                $groupBy
                ORDER BY mr.prj_kode";

        $result = $this->db->query($sql);
		$result = $result->fetchAll();

        return $result;
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getRoleByName($roleName='')
    {
        $type = new Admin_Models_Masterroletype();
        $select = $this->db->select()
            ->from(array($type->__name()))
            ->where("role_name = '$roleName'");

        $data = $this->db->fetchAll($select);

        return $data;
    }

    public function getRoleUidProject($uid='',$prj_kode='')
    {
        $l = new Admin_Models_Masterlogin();

        $select = $this->db->select()
            ->from(array($this->_name),array(
                "id_role"
            ))
            ->where("id_user=?",$l->getUserId($uid))
            ->where("prj_kode=?",$prj_kode);

        return $this->db->fetchAll($select);
    }
}
?>