<?php

class Admin_Models_Userrole extends Zend_Db_Table_Abstract {

    protected $_name = 'master_role';
    protected $db;
    private $ADMIN;
    private $DEFAULT;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $model = array(
            'Masterlogin',
            'Masterroletype'
        );
        $this->ADMIN = QDC_Model_Admin::init($model);
        $model = array(
            'MasterProject'
        );
        $this->DEFAULT = QDC_Model_Default::init($model);
    }

    public function getRoleType($roleName = '', $sort = '', $dir = '', $limit = '', $offset = '', $prjKode = '', $sitKode = '', $roleID = '', $group = '') {
//		$sql = $this->db->select()
//									->from(array('mr' => 'master_role'))
//									->join(array('ml' => 'master_login'),
//											'mr.id_user = ml.id',
//											array('ml.Name' => 'fullname')
//											)
//									->join(array('mrt' => 'master_role_type'),
//											 'mr.id_role = mrt.id',
//											array('mrt.display_name' => 'display_name'))		
//									->where('mr.active=1')		
//									->where('mrt.role_name= ?',$roleName)		
//						            ->order(array($sort . ' ' . $dir))
//						            ->limit($limit,$offset);
//    	
        if ($roleName != '')
            $where = "mrt.role_name='$roleName'";
        if ($roleID != '')
            $where = "mr.id_role=$roleID";
        $sql = "SELECT SQL_CALC_FOUND_ROWS mr.*,(ml.Name) as fullname, (mrt.display_name) as display_name FROM
    			master_role mr
    			INNER JOIN master_login ml
    				ON ml.id = mr.id_user
    			INNER JOIN master_role_type mrt
    				ON mr.id_role = mrt.id
    			WHERE
    				mr.active=1
    			AND
    				$where
    			$group	
    			ORDER BY $sort $dir
    			LIMIT $offset,$limit";
        $return['posts'] = $this->db->fetchAll($sql);
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $return;
    }

    public function getAllUser($name = '', $sort = '', $dir = '', $limit = '', $offset = '') {
        $sql = "SELECT SQL_CALC_FOUND_ROWS id,uid,Name AS name,id_privilege FROM
					master_login
				WHERE 
					Name LIKE '%$name%'	
				ORDER BY $sort $dir
    			LIMIT $offset,$limit";
        $return['posts'] = $this->db->fetchAll($sql);

        $ldap = new Default_Models_Ldap();

        foreach ($return['posts'] as $key => $val) {
            if ($val['uid'] != '') {
//				$account = $ldap->getAccount($val['uid']);
//				$return['posts'][$key]['name'] = $account['displayname'][0];
                $return['posts'][$key]['name'] = QDC_User_Ldap::factory(array("uid" => $val['uid']))->getName();
            }
        }

        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $return;
    }

    public function getUserRoleByUserID($userID = '', $prjKode = '', $sitKode = '') {
//		$sql = $this->db->select()
//									->from(array('mr' => 'master_role'))
//									->join(array('ml' => 'master_login'),
//											'mr.id_user = ml.id',
//											array('ml.Name' => 'fullname')
//											)
//									->join(array('mrt' => 'master_role_type'),
//											 'mr.id_role = mrt.id',
//											array('mrt.display_name' => 'display_name'))		
//									->where('mr.active=1')		
//									->where('mrt.role_name= ?',$roleName)		
//						            ->order(array($sort . ' ' . $dir))
//						            ->limit($limit,$offset);
//    	
        if ($userID != '') {
            if ($prjKode != '')
                $where = " AND mr.prj_kode = '$prjKode'";
            $sql = "SELECT SQL_CALC_FOUND_ROWS mr.*, (mrt.display_name) as display_name,mrt.role_name FROM
	    			master_role mr
	    			INNER JOIN master_login ml
	    				ON ml.id = mr.id_user
	    			INNER JOIN master_role_type mrt
	    				ON mr.id_role = mrt.id
	    			WHERE
	    				ml.id=$userID
	    				$where
	    			";
        }
        else {
            $sql = "SELECT SQL_CALC_FOUND_ROWS mr.*, (mrt.display_name) as display_name,mrt.role_name FROM
	    			master_role mr
	    			INNER JOIN master_login ml
	    				ON ml.id = mr.id_user
	    			INNER JOIN master_role_type mrt
	    				ON mr.id_role = mrt.id
	    			";
        }
        $return['posts'] = $this->db->fetchAll($sql);
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $return;
    }

    public function createUserRole($dataArray, $addArray = '') {
        $this->db = Zend_Registry::get('db');
        $rv = array_filter($dataArray, 'is_array');
        if (count($rv) > 0) {
            foreach ($dataArray as $key => $val) {
                $fields = array();
                $values = array();
                if (is_array($val)) {
                    foreach ($val as $key2 => $val2) {
                        $fields[] = $key2;
                        if (!is_numeric($val2)) {
                            $values[] = "'" . $val2 . "'";
                        } else
                            $values[] = $val2;
                    }
                    if (is_array($addArray)) {
                        foreach ($addArray as $add => $valAdd) {
                            $fields[] = $add;
                            $values[] = $valAdd;
                        }
                    }
                    $fieldsSql = implode(',', $fields);
                    $valuesSql = implode(',', $values);

                    $sql = "INSERT INTO
			    				master_role
			    				( $fieldsSql )
			    			VALUES
			    				( $valuesSql );";
                    $this->db->query($sql);
                    $lastID = $this->db->lastInsertId();
                    $dataArray[$key]['id'] = $lastID;
                    $roletype = new Admin_Models_Masterroletype();
                    $rolename = $roletype->getNameByID($dataArray[$key]['id_role']);
                    $dataArray[$key]['display_name'] = $rolename['display_name'];
                }
            }
        } else {
            $fields = array();
            $values = array();
            foreach ($dataArray as $key => $val) {
                $fields[] = $key;
                if (!is_numeric($val)) {
                    $values[] = "'" . $val . "'";
                } else
                    $values[] = $val;
            }

            if (is_array($addArray)) {
                foreach ($addArray as $add => $valAdd) {
                    $fields[] = $add;
                    $values[] = $valAdd;
                }
            }
            $fieldsSql = implode(',', $fields);
            $valuesSql = implode(',', $values);

            $sql = "INSERT INTO
	    				master_role
	    				( $fieldsSql )
	    			VALUES
	    				( $valuesSql );";
            $this->db->query($sql);
            $lastID = $this->db->lastInsertId();
            $dataArray['id'] = $lastID;
            $roletype = new Admin_Models_Masterroletype();
            $rolename = $roletype->getNameByID($dataArray['id_role']);
            $dataArray['display_name'] = $rolename['display_name'];
        }

        return $dataArray;
    }

    public function deleteUserRole($id) {
        $sql = "DELETE FROM master_role WHERE id=$id";
        $this->db->query($sql);
    }

    public function updateUserRole($dataArray, $criteriaArray) {
        $metadata = $this->db->describeTable('master_role');
        $columnNames = array_keys($metadata);
        $values = array();
        $updateArray = $dataArray;

        $rv = array_filter($dataArray, 'is_array');
        if (count($rv) > 0) {
            foreach ($updateArray as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $key2 => $val2) {

                        if (!in_array($key2, $columnNames))
                            continue;
                        if (!is_numeric($val)) {

                            if ($key2 == 'id') {
                                $criteriaArray[] = "id=$val";
                                unset($updateArray[$key]['id']);
                                continue;
                            }
                            $values[] = "$key2 = '" . $val2 . "'";
                        } else {
                            if ($key2 == 'id') {
                                $criteriaArray[] = "id=$val";
                                unset($updateArray[$key]['id']);
                                continue;
                            }
                            $values[] = "$key2 = $val2";
                        }
                    }

                    $criteriasSql = implode(' AND ', $criteriaArray);
                    $valuesSql = implode(',', $values);

                    $sql = "UPDATE
			    				master_role
			    				SET $valuesSql 
			    			WHERE
			    				( $criteriasSql )";
                    $this->db->query($sql);
                    $roletype = new Admin_Models_Masterroletype();
                    $rolename = $roletype->getNameByID($dataArray[$key]['id_role']);
                    $dataArray[$key]['display_name'] = $rolename['display_name'];
                }
            }
        } else {
            $values = array();
            foreach ($updateArray as $key => $val) {
                if (!in_array($key, $columnNames))
                    continue;
                if (!is_numeric($val)) {
                    if ($key == 'id') {
                        $criteriaArray[] = "id=$val";
                        unset($updateArray['id']);
                        continue;
                    }
                    $values[] = "$key = '" . $val . "'";
                } else {
                    if ($key == 'id') {
                        $criteriaArray[] = "id=$val";
                        unset($updateArray['id']);
                        continue;
                    }
                    $values[] = "$key = '" . $val . "'";
                }
            }

            $valuesSql = implode(',', $values);

            $criteriasSql = implode(' AND ', $criteriaArray);
            $valuesSql = implode(',', $values);

            $sql = "UPDATE
	    				master_role
	    				SET $valuesSql 
	    			WHERE
	    				( $criteriasSql )";
            $this->db->query($sql);
            $roletype = new Admin_Models_Masterroletype();
            $rolename = $roletype->getNameByID($dataArray['id_role']);
            $dataArray['display_name'] = $rolename['display_name'];
        }
        return $dataArray;
    }

    public function getMenuByUserID($userID = '') {
        $sql = "SELECT * FROM 
  					menu_privilege
  				WHERE user_id=$userID";
        $return = $this->db->fetchAll($sql);
        return $return;
    }

    public function getSubMenuByUserID($userID = '') {
        $sql = "SELECT * FROM
  					submenu_privilege
  				WHERE user_id=$userID";
        $return = $this->db->fetchAll($sql);
        return $return;
    }

    public function getCurrentProject($userID = '', $statOpen = false) {
        if (!$statOpen) {
            $sql = "SELECT * FROM
    				master_role
    			WHERE
    				active=1
    			AND
    				id_user=$userID
    			GROUP BY
    				prj_kode
    			ORDER BY 
    				prj_kode ASC";
        } else {
            $sql = "SELECT a.*,b.Prj_Nama AS prj_nama FROM (SELECT * FROM
                    master_role
                WHERE
                    active=1
                AND
                    id_user=$userID
                GROUP BY
                    prj_kode
                ORDER BY
                    prj_kode ASC
                ) a LEFT JOIN
                master_project b
                ON a.prj_kode = b.Prj_Kode
                WHERE
                b.stsclose = 0
                GROUP BY a.prj_kode
            ";
        }
        $result = $this->db->fetchAll($sql);

        return $result;
    }

    public function listMyProject($uid = '') {
        $select = $this->db->select()
                ->from(array("ur" => $this->_name), array(
                    "prj_kode"
                        )
                )
                ->joinLeft(array("mp" => $this->DEFAULT->MasterProject->__name()), "ur.prj_kode = mp.Prj_Kode", array(
                    "prj_nama" => "mp.Prj_Nama",
                    "COALESCE(stsclose,0) AS stsclose"
                        )
                )
                ->joinLeft(array("ml" => $this->ADMIN->Masterlogin->__name()), "ur.id_user = ml.id", array(
                    "uid"
                        )
                )
                ->where("ml.uid = ?", $uid)
                ->group(array("ur.prj_kode"))
                ->order(array("ur.prj_kode DESC"));

        $data = $this->db->fetchAll($select);

        return $data;
    }

    public function getRoleGrouped($uid = '') {
        $user = $this->ADMIN->Masterlogin->fetchRow("uid = '$uid'");
        if ($user) {
            $idUser = $user['id'];
            $select = $this->db->select()
                    ->from(array($this->_name))
                    ->where("id_user = $idUser")
                    ->group(array("id_role"));
            $select2 = $this->db->select()
                    ->from(array("a" => $select))
                    ->joinLeft(
                            array("b" => $this->ADMIN->Masterroletype->__name()), "a.id_role = b.id", array(
                        "b.display_name",
                        "b.role_name"
                            )
                    )
                    ->order(array("b.id ASC"));

            $roles = $this->db->fetchAll($select2);
        }

        return $roles;
    }

    public function __name() {
        return $this->_name;
    }

}
