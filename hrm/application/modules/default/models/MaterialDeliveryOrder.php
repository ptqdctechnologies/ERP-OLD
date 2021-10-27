<?php

class Default_Models_MaterialDeliveryOrder extends Zend_Db_Table_Abstract
{
    protected $_name = 'mdo';

    protected $db;
    protected $const;
	
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }


    public function getMdoSum($prj_kode='',$sit_kode='')
    {
	$sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.kode_brg,
                    p.nama_brg,
                    p.qty,
                    p.pc_nama
                FROM(
                        SELECT
                            a.trano,
                            a.tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname, 
                            a.kode_brg,
                            a.nama_brg,
                            a.qty,                                                  
                           (SELECT uid FROM master_login WHERE master_login = a.petugas) as pc_nama
                        FROM
                            procurement_mdod a
                        WHERE
                            a.prj_kode = '$prj_kode'
                        AND
                            a.sit_kode = '$sit_kode') p
                GROUP BY p.trano
                ORDER BY p.trano";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }


    public function getMdoDetail($trano='')
    {
	$sql = "SELECT
                    trano,
                    DATE_FORMAT(tgl, '%m/%d/%Y') as tgl,               
                    prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    workid,
                    workname,
                    kode_brg,
                    nama_brg,
                    qty               
                FROM
                    procurement_mdod
                WHERE       
                    trano = '$trano'
                ORDER BY
                    trano ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
}

?>
