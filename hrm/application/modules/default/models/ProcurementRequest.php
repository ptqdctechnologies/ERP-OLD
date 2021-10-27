<?php
class Default_Models_ProcurementRequest extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_prd';

    protected $db;
    protected $const;

    protected $_primary = 'trano';

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

    public function getPrSum($prj_kode='',$sit_kode='')
    {
        if ($sit_kode != '')
    		$where = "AND a.sit_kode = '$sit_kode'";
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
                    SUM(p.totalIDR) as total_IDR,
                    SUM(p.totalUSD) as total_USD,       
                    p.qty,
		    p.harga,                  
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
                            a.val_kode,
                            a.qty,
                            a.harga,
                           (CASE a.val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                           (CASE a.val_kode WHEN 'USD'  THEN (a.harga*a.qty) ElSE 0.00 END) AS totalUSD,
                           (SELECT uid FROM master_login WHERE master_login = b.user) as pc_nama                     
                        FROM
                            procurement_prd a
                        LEFT JOIN
                            procurement_prh b
                        ON
                            a.trano = b.trano
                        WHERE
                            a.prj_kode = '$prj_kode'
                            $where
                        ) p
                GROUP BY p.trano
                ORDER BY p.trano ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getPrDetail($trano='')
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
                    qty,
                    harga,
                   (CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS total_IDR,
                   (CASE val_kode WHEN 'USD'  THEN (harga*qty) ElSE 0.00 END) AS total_USD,
                    val_kode
                FROM
                    procurement_prd
                WHERE             
                    trano = '$trano'
                ORDER BY
                    trano";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
}
?>