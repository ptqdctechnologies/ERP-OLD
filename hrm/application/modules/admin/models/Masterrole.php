<?php
class Admin_Model_Masterrole extends Zend_Db_Table_Abstract
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

    public function cekUserInRole($roleID='',$userID='',$prjKode='')
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
					b.id_role = $roleID
                AND
                    a.uid = '$userID'
                    $where
                GROUP BY uid";
		$user = $this->db->query($sql);
		$result = $user->fetchAll();

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
                    mrt.id
                    mr.prj_kode,
                    mrt.display_name
                FROM master_login ml
                LEFT JOIN master_role mr
                ON ml.id = mr.id_user
                LEFT JOIN master_role_type mrt
                ON mr.id_role = mrt.id
                WHERE
                    ml.uid = $uid
                AND mrt.display_name is not null
                AND (mr.prj_kode is not null AND mr.prj_kode  != '')
                $groupBy
                ORDER BY mr.prj_kode";

        $result = $this->db->query($sql);
		$result = $result->fetchAll();

        return $result;
    }
}
?>