<?php

class Default_Models_MasterFinance extends Zend_Db_Table_Abstract {

    protected $_name = 'master_login';
    protected $db;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function getUid() {
        $sql = "SELECT
                b.uid
                from master_role a
                RIGHT JOIN master_login b
                on a.id_user = b.id
                LEFT JOIN master_role_type c
                on c.id = a.id_role
                WHERE c.display_name = 'Project Manager' OR c.display_name = 'Sitac Coordinator'
                GROUP BY a.id_user";

        $return = $this->db->fetchAll($sql);

        return $return;
    }

    public function getFinance() {
        $sql = "SELECT
                b.uid
                from master_role a
                RIGHT JOIN master_login b
                on a.id_user = b.id
                LEFT JOIN master_role_type c
                on c.id = a.id_role
                WHERE c.display_name LIKE '%Finance%' 
                GROUP BY a.id_user";

        $return = $this->db->fetchAll($sql);

        return $return;
    }

    public function getUidByParams($columnName = '', $columnValue = '') {
        $sql = "SELECT
                b.uid
                from master_role a
                RIGHT JOIN master_login b
                on a.id_user = b.id
                RIGHT JOIN master_role_type c
                on a.id_role = c.id
                WHERE (c.display_name LIKE '%Finance%')";
        if ($columnName)
            $sql .= "  AND b.$columnName LIKE '%$columnValue%' ";

        $sql .= "group by b.uid";

        $return = $this->db->fetchAll($sql);
        return $return;
    }

    public function getMgr() {
        $sql = "SELECT
                mgr_kode AS uid,
                mgr_nama AS nama
                FROM master_manager";

        $return = $this->db->fetchAll($sql);

        return $return;
    }

}
?>


