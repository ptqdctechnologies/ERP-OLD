<?php
class Default_Models_ProcurementPoh extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_poh';

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

    public function getPoPpn($tgl1='',$tgl2='')
    {
	$sql = "
	    SELECT
            trano,
            DATE_FORMAT(tgl,'%m/%d/%Y') as tgl_trans,
			prj_kode,
			prj_nama,
            sup_kode,
            sup_nama,
            jumlah,
			total,
			val_kode,
			ppn,
			(IF(rateidr = 0 AND val_kode != 'IDR', (SELECT rateidr FROM exchange_rate ORDER BY tgl DESC LIMIT 1), rateidr)) as rateidr
		 FROM
			procurement_poh
		 WHERE
		 	tgl
		 BETWEEN
			'$tgl1'
		 AND
			'$tgl2' ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getPohDetail($trano)
    {
        $sql = "SELECT * FROM procurement_poh WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
    public function changePODate($trano)
    {
        $this->update(
            array(
                "tgl" => date("Y-m-d")
            ),
            "trano = '$trano'"
        );

        $pod = new Default_Models_ProcurementPod();

        $pod->update(
            array(
                "tgl" => date("Y-m-d")
            ),
            "trano = '$trano'"
        );

        $data = $this->fetchRow("trano = '$trano'");
        if ($data)
        {
            $supKode = $data['sup_kode'];
            $supNama = $data['sup_nama'];
        }

        $data = $pod->fetchAll("trano = '$trano'");
        if ($data)
        {
            $data = $data->toArray();
            $histori = new Default_Models_BarangHistories();
            $barang = new Default_Models_MasterBarang();
            foreach ($data as $k => $v)
            {
                $arrayInsert = array(
                    "tra_no" => $trano,
                    "tgl" => date("Y-m-d"),
                    "sup_kode" => $supKode,
                    "sup_nama" => $supNama,
                    "harga" => $v['harga'],
                    "brg_kode" => $v['kode_brg'],
                    "brg_nama" => $v['nama_brg'],
                    "val_kode" => $v['val_kode'],
                    "sat_kode" => $v['sat_kode']
                );

                $histori->insert($arrayInsert);
                $harga = floatval($v['harga']);
                if ($harga > 0)
                    $barang->update(
                        array(
                            "harga_beli" => $harga,
                            "hargaavg" => $harga,
                            "val_kode" => $v['val_kode']
                        ),
                        "kode_brg = '{$v['kode_brg']}'"
                    );

            }
        }

    }
}

?>