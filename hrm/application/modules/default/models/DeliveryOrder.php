<?php

class Default_Models_DeliveryOrder extends Zend_Db_Table_Abstract
{
    protected $_name = 'do';

    protected $db;
    protected $const;
	
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getDoDetail($trano='')
    {
	$sql = "SELECT
                    b.trano,
                    DATE_FORMAT(b.tgl, '%m/%d/%Y') as tgl,
                    b.prj_kode,
                    b.prj_nama,
                    b.sit_kode,
                    b.sit_nama,
                    b.workid,
                    b.workname,
                    b.kode_brg,
                    b.nama_brg,
                    b.qty,
                    a.namatransporter,
                    a.deliveryfrom
                FROM
                    procurement_whod b
                LEFT JOIN
                    procurement_whoh a
                ON
                    b.trano = a.trano
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
