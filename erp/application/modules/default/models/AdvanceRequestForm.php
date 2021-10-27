<?php

class Default_Models_AdvanceRequestForm extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_arfd';

    protected $db;
    protected $const;
	
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getArfSum($prj_kode='',$sit_kode='',$kode_brg = '', $workid = '', $requestor='')
    {
        if ($prj_kode != '')
            $where[] = "a.prj_kode = '$prj_kode'";
        if ($sit_kode != '' && $prj_kode != '')
        	$where[] = " a.sit_kode='$sit_kode' ";
        if ($kode_brg != '')
            $where[] = "a.kode_brg = '$kode_brg'";
        if ($workid != '')
            $where[] = "a.workid = '$workid'";

        if ($requestor != '')
        {
            $where[] = " b.request='$requestor' ";
        }

        if ($where)
            $where = implode(" AND ", $where);

        if (!$where)
            $where = null;
        else
            $where = " WHERE $where ";

	$sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%m/%d/%Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.val_kode,
                    SUM(p.total) as total,
                    p.pc_nama,
                    p.ket
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
                            a.val_kode,
                            a.total,
                            (SELECT uid FROM master_login WHERE id = b.request) as pc_nama,
                            b.ket
                        FROM
                            procurement_arfd a
                        LEFT JOIN
                            procurement_arfh b
                        ON
                           (a.trano = b.trano AND
                            a.prj_kode = b.prj_kode AND
                            a.sit_kode = b.sit_kode)
                        	$where
                            ) p
                GROUP BY p.trano
                ORDER BY p.trano ";
        
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

//    public function getArfDetail($trano='')
//    {
//	$sql = "SELECT
//                    trano,
//                    DATE_FORMAT(tgl, '%m/%d/%Y') as tgl,
//                    prj_kode,
//                    prj_nama,
//                    sit_kode,
//                    sit_nama,
//                    workid,
//                    workname,
//                    kode_brg,
//                    nama_brg,
//                    qty,
//                    harga,
//                    val_kode,
//                    qty * harga as total
//                FROM
//                    procurement_arfd
//                WHERE
//                    trano = '$trano'
//                ORDER BY
//                    kode_brg ";
//
//		$fetch = $this->db->query($sql);
//		$result = $fetch->fetchAll();
//
//		return $result;
//    }
    
    public function getArfDetail($trano='')
    {
   	$sql = "SELECT
                    p.trano,
                    DATE_FORMAT(p.tgl, '%d %M %Y') as tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.kode_brg,
                    p.nama_brg,
                    p.qty,
                    p.harga,
                    p.val_kode,
                    p.total,
                    p.user,
                    p.request,
                    p.ket
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
                            a.harga,
                            a.val_kode,
                            a.qty * a.harga as total,
                            b.request,
                            b.user,
                            b.ket
                        FROM
                            procurement_arfd a
                        LEFT JOIN
                            procurement_arfh b
                        ON
                           (a.trano = b.trano AND
                            a.prj_kode = b.prj_kode AND
                            a.sit_kode = b.sit_kode)
                        WHERE
                            a.trano = '$trano'
                            ) p               
                ORDER BY p.kode_brg";
        
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

}

?>
