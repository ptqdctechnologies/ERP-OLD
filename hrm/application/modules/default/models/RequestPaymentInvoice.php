<?php

class Default_Models_RequestPaymentInvoice extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_rpid';
    protected $_primary = 'trano';
    protected $db;
    protected $const;
	
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getrpiSum($prj_kode='',$sit_kode='',$sup_kode='')
    {
//    	if ($sit_kode != '')
//    		$where = "AND b.sit_kode = '$sit_kode'";
        if ($prj_kode != '')
    		$where = "a.prj_kode = '$prj_kode'";
    	if ($sit_kode != '')
    		$where .= " AND a.sit_kode = '$sit_kode'";
    	if ($sup_kode != '')
        {
            if ($where == '')
    		    $where = "a.sup_kode = '$sup_kode'";
            else
                $where .= " AND a.sup_kode = '$sup_kode'";
        }
	$sql = "

                        SELECT
                            b.trano,
                            DATE_FORMAT(b.tgl, '%d/%m/%Y') as tgl,
                            b.prj_kode,
                            b.prj_nama,
                            b.sit_kode,
                            b.sit_nama,
                            b.workid,
                            b.workname,
                            b.val_kode,
                            SUM((CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END)) AS total_IDR,
                            SUM((CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty) ElSE 0.00 END)) AS total_USD,
                           (SELECT uid FROM master_login WHERE master_login =  a.user) as pc_nama,
                            a.sup_kode,
                            a.sup_nama
                        FROM
                            procurement_rpih a
                        LEFT JOIN
                           	 procurement_rpid b
                        ON
                            a.trano = b.trano
                        WHERE
                            $where
                        GROUP BY b.trano
                        ORDER BY b.trano
                        ";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getrpiDetail($trano='')
    {
	$sql = "
                    SELECT
                            b.trano,
                            DATE_FORMAT(b.tgl, '%d/%m/%Y') as tgl,
                            b.prj_kode,
                            b.prj_nama,
                            b.sit_kode,
                            b.sit_nama,
                            b.workid,
                            b.workname,
                            b.val_kode,
                            b.kode_brg,
                            b.nama_brg,
                            b.qty,
                            b.harga,
                            (CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END) AS total_IDR,
                            (CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty) ElSE 0.00 END) AS total_USD,
                            (SELECT uid FROM master_login WHERE master_login = a.user) as pc_nama
                        FROM
                            procurement_rpih a
                        LEFT JOIN
                           	 procurement_rpid b
                        ON
                            a.trano = b.trano
                        WHERE
                            b.trano = '$trano'
            ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getDetailForPayment($trano)
    {
        $sql= "SELECT
                SUM(qty*harga) AS total,prj_kode,prj_nama,sit_kode,sit_nama
               FROM
                procurement_rpid
               WHERE
                trano = '$trano'
               GROUP BY
                prj_kode,sit_kode
                ";

        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }
    
}

?>
