<?php

class Default_Models_RequestPaymentInvoiceH extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_rpih';
    protected $_primary = 'trano';
    protected $db;
    protected $const;
    public $select;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function finalpolist($offset = 0, $limit = 0, $dir = 'DESC', $sort = 'trano', $search) {
        if ($search != "") {
            $where = " WHERE a.$search";
        }

        $sql = "DROP TEMPORARY TABLE IF EXISTS rpi;";
        $this->db->query($sql);

        $sql = "CREATE TEMPORARY TABLE rpi select a.trano,a.tgl,a.prj_kode,a.prj_nama,a.petugas, a.sup_kode,
                a.sup_nama,a.val_kode,a.total from procurement_rpih a $where AND a.deleted=0 order by trano;";
        $this->db->query($sql);

        $sql = "drop temporary table if exists rpifinal;";
        $this->db->query($sql);

        $sql = "create temporary table rpifinal select a.* from rpi a join 
                workflow_trans b on a.trano = b.item_id where b.item_type in 
                ('RPI','RPIO') group by item_id;";
        $this->db->query($sql);
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS a.* from rpifinal a ORDER BY $sort $dir LIMIT $offset,$limit ;";
      
        $fetch = $this->db->query($sql);
        $data['data'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }

}

?>