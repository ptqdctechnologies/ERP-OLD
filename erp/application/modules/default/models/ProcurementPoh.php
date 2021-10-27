<?php

class Default_Models_ProcurementPoh extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_poh';
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

    public function getPoPpn($tgl1 = '', $tgl2 = '') {
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
			-- (IF(rateidr = 0 AND val_kode != 'IDR', (SELECT rateidr FROM exchange_rate ORDER BY tgl DESC LIMIT 1), rateidr)) as rateidr
                        (IF(rateidr = 0 AND val_kode != 'IDR', (SELECT rateidr FROM finance_exchange_rate ORDER BY tgl DESC LIMIT 1), rateidr)) as rateidr
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

    public function getPohDetail($trano) {
        $sql = "SELECT * FROM procurement_poh WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }

    public function changePODate($trano) {
        $this->update(
                array(
            "tgl" => date("Y-m-d")
                ), "trano = '$trano'"
        );

        $pod = new Default_Models_ProcurementPod();

        $pod->update(
                array(
            "tgl" => date("Y-m-d")
                ), "trano = '$trano'"
        );

        $data = $this->fetchRow("trano = '$trano'");
        if ($data) {
            $supKode = $data['sup_kode'];
            $supNama = $data['sup_nama'];
        }

        $data = $pod->fetchAll("trano = '$trano'");
        if ($data) {
            $data = $data->toArray();
            $histori = new Default_Models_BarangHistories();
            $barang = new Default_Models_MasterBarang();
            foreach ($data as $k => $v) {
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
//                        "hargaavg" => $harga,
                        "val_kode" => $v['val_kode']
                            ), "kode_brg = '{$v['kode_brg']}'"
                    );
            }
        }
    }

    public function getDetail($trano = '', $prjKode = '', $sitKode = '', $workid = '', $kodeBrg = '') {
        $select = $this->db->select()
                ->from(array($this->_name));

        if ($trano)
            $select->where("trano=?", $trano);
        if ($prjKode)
            $select->where("prj_kode=?", $prjKode);
        if ($sitKode)
            $select->where("sit_kode=?", $sitKode);
        if ($workid)
            $select->where("sit_kode=?", $workid);
        if ($kodeBrg)
            $select->where("kode_brg=?", $kodeBrg);

        $this->select = $select;
        return $this->db->fetchAll($select);
    }

    public function __name() {
        return $this->_name;
    }

    public function finalpolist($offset = 0, $limit = 0, $dir = 'DESC', $sort = 'trano', $search) {
        if ($search != "") {
            $where = " AND $search";
//            $where2= " AND PO.$search";
            $where2 = " WHERE PO.$search";
            $where3 = " AND siPO.$search";
        }

        $query = "
                DROP TEMPORARY TABLE IF EXISTS final_workflow;
                CREATE TEMPORARY TABLE final_workflow
                    SELECT final,item_id from workflow_trans
                    WHERE final = 1;";
        $this->db->query($query);

        $query = "CREATE INDEX idx_item ON final_workflow(`item_id`);";
        $this->db->query($query);

        $query = "DROP TEMPORARY TABLE IF EXISTS po_to_rpi;
                CREATE TEMPORARY TABLE po_to_rpi
                    SELECT PO.trano,PO.tgl,PO.prj_kode,PO.prj_nama,PO.petugas,PO.sup_kode,PO.sup_nama,PO.val_kode,PO.total
                    FROM procurement_poh PO
                    $where2
                    /*GROUP BY PO.trano
                    ORDER BY PO.trano desc*/;";
        $this->db->query($query);
        $query = "CREATE INDEX idx_trano ON po_to_rpi(`trano`);";
        $this->db->query($query);


        $query = "SELECT SQL_CALC_FOUND_ROWS siPO.* FROM po_to_rpi siPO LEFT JOIN final_workflow FW
                ON siPO.trano = FW.item_id WHERE item_id is not null $where3
                GROUP BY siPO.trano ORDER BY $sort $dir LIMIT $offset,$limit ;";

        $fetch = $this->db->query($query);
        $data['data'] = $fetch->fetchAll();
        $data['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        return $data;
    }

}

?>