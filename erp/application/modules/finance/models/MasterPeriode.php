<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_MasterPeriode extends Zend_Db_Table_Abstract {

    protected $_name = 'master_periode';
    protected $_primary = 'perkode';
    public $db;
    public $name;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function viewallrequest($offset = 0, $limit = 0, $dir = 'ASC', $sort = 'trano', $search) {

        if ($search != "")
            $where = "WHERE $search";

        $query = "SELECT SQL_CALC_FOUND_ROWS trano,riv_no,coa_kode,tgl,cus_kode,prj_kode,sit_kode,prj_nama,sit_nama,(COALESCE(total,0)) as total FROM " . $this->_name . " $where
                    ORDER BY $sort $dir LIMIT $offset,$limit";
        $fetch = $this->db->query($query);

        $data['data'] = $fetch->fetchAll();
        $data['total'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

        return $data;
    }

    public function getLastPeriode($date='')
    {
        if ($date)
            $date = date("Y-m-d",strtotime($date));
        $select = $this->db->select()
            ->from(array($this->_name))
            ->where("tgl_awal < '$date'")
            ->order(array("tahun DESC","bulan DESC"))
            ->limit(1,0);

        $data = $this->db->fetchRow($select);

        return $data;
    }
    
    public function checkdatetransaction($date) {

        $where = "";

        if ($date) {
            $where .=" WHERE ";
            $where .= "'$date' between tgl_awal and tgl_akhir";
            $where .= " AND stsclose = 1";
        }

        $select = $this->db->select()
            ->from(array($this->_name),array(
                new Zend_Db_Expr("COUNT(*)")
            ))
            ->where("'$date' between tgl_awal and tgl_akhir")
            ->where("stsclose=1");
        $find = $this->db->fetchOne($select);

//        $query = "SELECT COUNT(*) AS find from " . $this->_name . $where;
//        $fetch = $this->db->query($query);
//        $find = $fetch->fetchOne();
        
//        return $find['find'];
        return $find;

    }
}

?>
