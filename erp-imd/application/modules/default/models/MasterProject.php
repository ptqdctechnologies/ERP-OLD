<?php

class Default_Models_MasterProject extends Zend_Db_Table_Abstract {

    protected $_name = 'master_project';
    protected $_primary = 'Prj_Kode';
    protected $_prj_kode;
    protected $_prj_nama;
    protected $db;
    private $newProjectId;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');

        $this->newProjectId = Bootstrap::NEW_PROJECT_ID;
    }

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __name() {
        return $this->_name;
    }

    public function getProjectName($prj_kode = '') {
        $select = $this->db->select()
                ->from(array($this->_name), array("Prj_Nama AS prj_nama")
                )
                ->where("Prj_Kode = ?", $prj_kode);

        $data = $this->db->fetchRow($select);
        if ($data) {
            return $data['prj_nama'];
        }

        return false;
    }
    
    public function getMyProject($uid){
        $query ="SELECT prj.prj_kode,prj.prj_nama FROM imderpdb.master_role role
                    INNER JOIN imderpdb.master_project prj ON (prj.Prj_Kode = role.prj_kode)
                    WHERE role.id_user=$uid AND prj.stsclose=0
                    GROUP BY prj.prj_kode
                    ORDER BY prj.prj_kode ASC";
        $data = $this->db->fetchAll($query);
        
        return $data;
    }

    public function getProjectAndCustomer($prj_kode = '', $showAll = '') {
        if ($prj_kode != '')
            $where = " AND prj_kode ='$prj_kode' ";
//        if ($showAll == '') {
//            if ($where != '')
//                $where .= " AND stsclose=0";
//            else
//                $where = " WHERE stsclose=0";
//        }
        $sql = "SELECT
    	 			a.*,b.cus_nama
    			FROM
    				master_project a
    			LEFT JOIN
    				master_customer b
    			ON
    				a.cus_kode = b.cus_kode
    			WHERE  stsclose=0 $where	
    			ORDER BY 
    				a.prj_kode DESC";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        $id = 1;

        foreach ($result as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $newField = strtolower($key2);
                $result2[$key][$newField] = $result[$key][$key2];
            }
            $result2[$key]['id'] = $id;
            $id++;
        }

        return $result2;
    }

    public function getProjectOverhead($showAll = '', $where = '', $sort = '', $dir = '') {
       
        if ($sort && $dir) {
            $order = " ORDER BY $sort $dir";
        }

        $sql = "SELECT * FROM master_project WHERE type = 'O' AND stsclose=0";

//        if ($showAll == '')
//            $sql .=" and stsclose = 0";

        if ($where)
            $sql .= " and $where";
        
        if ($order)
            $sql .= " $order";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getProjectNonOverhead($showAll = '', $where = '', $sort = '', $dir = '') {
//        if ($showAll == '')
//            $where .= " AND stsclose=0";
        if ($sort && $dir) {
            $order = " ORDER BY $sort $dir";
        }

        $sql = "SELECT * FROM master_project WHERE type = 'P' AND stsclose=0 $where $order";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function getcustomer($prj_kode) {
        $query = "SELECT mp.cus_kode,cus_nama,alamat FROM master_project mp
                LEFT JOIN master_customer mc ON mp.cus_kode = mc.cus_kode where Prj_kode = '$prj_kode' ";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }

    public function isNewProject($prjKode = '') {
        $cek = $this->fetchRow("prj_kode = '$prjKode'");
        if ($cek) {
            $cek = $cek->toArray();
            $id = $cek['id'];

            if ($cek['type'] == 'O')
//                return true;
                return false;

            if ($id < $this->newProjectId)
                return false;

            return true;
        }

        return false;
    }

    public function isOverheadProject($prjKode = '') {
        $cek = $this->fetchRow("prj_kode = '$prjKode'");
        if ($cek) {
            $cek = $cek->toArray();
            $id = $cek['id'];

            if ($cek['type'] == 'O')
                return true;
        }
        return false;
    }

}

?>