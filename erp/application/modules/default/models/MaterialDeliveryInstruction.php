<?php

class Default_Models_MaterialDeliveryInstruction extends Zend_Db_Table_Abstract
{
    protected $_name = 'pointd';

    protected $db;
    protected $const;
	
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }


    public function getMdiDetail($trano='')
    {
	$sql = "SELECT
	p.trano_mdi,
        p.tgl_mdi,
        p.prj_kode,
        p.prj_nama,
        p.sit_kode,
        p.sit_nama,
        p.workid,
        p.workname,
        p.kode_brg,
        p.nama_brg,
        p.qty,
        p.trano_mdo,
        p.tgl_mdo,
        p.trano_do,
	p.tgl_do,
	x.petugas,
	(SELECT uid FROM master_login WHERE master_login = x.petugas) as pc_nama
FROM
	(SELECT DISTINCT
                    a.trano as trano_mdi,
                    DATE_FORMAT(a.tgl, '%m/%d/%Y') as tgl_mdi,
                    a.prj_kode,
                    a.prj_nama,
                    a.sit_kode,
                    a.sit_nama,
                    a.workid,
                    a.workname,
                    a.kode_brg,
                    a.nama_brg,
                    if(a.sts_internal = 0, a.qty, a.qty_int)qty,
                    b.trano as trano_mdo,
                    DATE_FORMAT(b.tgl, '%m/%d/%Y') as tgl_mdo,
                    c.trano as trano_do,
                    DATE_FORMAT(c.tgl, '%m/%d/%Y') as tgl_do
                FROM
                    procurement_pointd a
                LEFT JOIN
                    procurement_mdod b
                ON
                    a.trano = b.mdi_no
                LEFT JOIN
                    procurement_whod c
                ON
                    b.trano = c.mdo_no
                WHERE       
                    a.trano = '$trano'
              ) p
LEFT JOIN
	procurement_pointh x
ON
	p.trano_mdi = x.trano

ORDER BY
	p.trano_mdi";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
}

?>
