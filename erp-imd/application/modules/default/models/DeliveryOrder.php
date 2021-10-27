<?php

class Default_Models_DeliveryOrder extends Zend_Db_Table_Abstract {

    protected $_name = 'do';
    protected $db;
    protected $const;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getDoDetail($trano = '') {
        $sql = "SELECT
                    b.trano,
                    DATE_FORMAT(b.tgl, '%d %M %Y') as tgl,
                    b.prj_kode,
                    b.prj_nama,
                    b.sit_kode,
                    b.sit_nama,
                    b.workid,
                    b.workname,
                    b.kode_brg,
                    b.nama_brg,
                    b.qty,
                    b.harga,
                    c.val_kode,
                    a.namatransporter,
                    a.deliveryfrom,
                    b.petugas
                FROM
                    procurement_whod b
                LEFT JOIN
                    procurement_whoh a
                ON
                    b.trano = a.trano
                LEFT JOIN
                    procurement_pointd c
                ON
                    b.mdi_no = c.trano and b.kode_brg = c.kode_brg
                WHERE       
                    b.trano = '$trano'
                ORDER BY
                    trano ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

}

?>
