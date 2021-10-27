<?php

class Default_Models_ProcurementPod extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_pod';

    protected $_primary = 'trano';
    
    protected $_trano;
    protected $_prj_kode;
    protected $_prj_nama;
    protected $_work_id;
    protected $_workname;
    protected $_tgl;

    protected $db;
    protected $const;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getPoSum($prj_kode='',$sit_kode='',$sup_kode='',$tgl1='',$tgl2='')
    {
        if ($prj_kode != '')
    		$where = "a.prj_kode = '$prj_kode'";
    	if ($sit_kode != '')
    		$where .= " AND a.sit_kode = '$sit_kode'";
    	if ($sup_kode != '')
        {
            if ($where == '')
    		    $where = "b.sup_kode = '$sup_kode'";
            else
                $where .= " AND b.sup_kode = '$sup_kode'";
        }

        if ($tgl1 != '')
        {
            if ($tgl2 != '')
            {
                if ($where == '')
                    $where = "b.tgl BETWEEN '$tgl1' AND '$tgl2' ";
                else
                    $where .= " AND (b.tgl BETWEEN '$tgl1' AND '$tgl2')";
            }
            else
            {
                if ($where == '')
                    $where = "b.tgl = '$tgl1'";
                else
                    $where .= " AND b.tgl = '$tgl1'";
            }

        }


	$sql = "SELECT
                    p.trano,
                    p.tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname,
                    p.val_kode,
                    SUM(p.totalIDR) as total_IDR,
                    SUM(p.totalUSD) as total_USD,
                    p.pc_nama,
                    p.rateidr,
                    p.sup_kode,
                    p.sup_nama,
                    SUM(p.totalUSD*p.rateidr) as totalUSD_IDR
                FROM(
                        SELECT
                            a.trano,
                            DATE_FORMAT(a.tgl, '%d/%m/%Y') as tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname,
                            a.val_kode,
                            a.qty,
                            a.harga,
                            b.rateidr,                        
                            (CASE a.val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                            (CASE a.val_kode WHEN 'USD' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalUSD,                            
                            (SELECT uid FROM master_login WHERE master_login = b.user) as pc_nama,
                            b.sup_kode,
                            b.sup_nama
                        FROM
                            procurement_pod a
                        LEFT JOIN
                            procurement_poh b
                        ON
                            a.trano = b.trano
                        WHERE
                            $where
                        ) p
                GROUP BY p.trano
                ORDER BY p.trano";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getPoDetail($trano='')
    {
	$sql = "SELECT
          a.trano,
          DATE_FORMAT(a.tgl, '%m/%d/%Y') as tgl,
          a.prj_kode,
          a.prj_nama,
          a.sit_kode,
          a.sit_nama,
          a.workid,
          a.workname,
          a.kode_brg,
          a.ket AS ket2,
	  (SELECT
	         sat_kode
	   FROM
	         master_barang_project_2009
	   WHERE
	         kode_brg = a.kode_brg) as oum,
          a.nama_brg,
          a.qty,
          b.ppn,
          a.harga,
          (CASE a.val_kode WHEN 'IDR' THEN (a.harga*a.qty)
	   ElSE
              0.00 END) AS total_IDR,
          (CASE a.val_kode WHEN 'USD' THEN (a.harga*a.qty)
	   ElSE
	      0.00 END) AS total_USD,
          a.val_kode,
	  a.myob,
	  b.sup_kode,
	  b.sup_nama,
	  b.budgettype,
	  b.user,
	  b.originofpo,
	  b.tgldeliesti,
	  b.ket,
	  (SELECT uid FROM master_login WHERE master_login = b.user) as pic_nama
          FROM
                procurement_pod a
	  LEFT JOIN
		procurement_poh b
	  ON
		a.trano = b.trano
         WHERE
                a.trano = '$trano'
          ORDER BY
                a.trano";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
}

?>