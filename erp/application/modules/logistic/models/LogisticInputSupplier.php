<?php

class Logistic_Models_LogisticInputSupplier extends Zend_Db_Table_Abstract {

    protected $_name = 'procurement_whsupplierd';
    protected $_primary = 'trano';
    protected $db;
    public $select;
    public $isSelect;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __name() {
        return $this->_name;
    }

    public function getSelect() {
        $this->isSelect = true;
        return $this;
    }

    function getPoIsuppQuantity($trano = '', $prjKode = '', $sitKode = '', $workid = '', $kodeBrg = '') {
        $select = $this->db->select()
                ->from(
                array($this->_name), array(
            "total_qty" => "COALESCE(SUM(qty),0)",
            "total_harga" => "COALESCE(SUM(qty*harga),0)"
                )
        );

        if ($trano)
            $select->where("po_no=?", $trano);
        if ($prjKode)
            $select->where("prj_kode=?", $prjKode);
        if ($sitKode)
            $select->where("sit_kode=?", $sitKode);
        if ($workid)
            $select->where("workid=?", $workid);
        if ($kodeBrg)
            $select->where("kode_brg=?", $kodeBrg);

        $this->select = $select;
        if ($this->isSelect)
            return $this->select;
        else
            return $this->db->fetchRow($select);
    }

    function getDoIsuppQuantity($trano = '', $prjKode = '', $sitKode = '', $workid = '', $kodeBrg = '') {
        $select = $this->db->select()
                ->from(
                array($this->_name), array(
            "total_qty" => "COALESCE(SUM(qty),0)",
            "total_harga" => "COALESCE(SUM(qty*harga),0)"
                )
        );

        if ($trano)
            $select->where("do_no=?", $trano);
        if ($prjKode)
            $select->where("prj_kode=?", $prjKode);
        if ($sitKode)
            $select->where("sit_kode=?", $sitKode);
        if ($workid)
            $select->where("workid=?", $workid);
        if ($kodeBrg)
            $select->where("kode_brg=?", $kodeBrg);

        $this->select = $select;
        if ($this->isSelect)
            return $this->select;
        else
            return $this->db->fetchRow($select);
    }

    public function setAvgPrice($trano) {
        if (!$trano)
            return false;

        //periode
        $periode = QDC_Finance_Periode::factory()->getCurrentPeriode();
        $perkode = $periode['perkode'];

        $barang = new Default_Models_MasterBarang();
        $data = $this->fetchAll("trano = '$trano'");
        if ($data->toArray())
            $dataIsupp = $data->toArray();


        if (!$dataIsupp[0]['po_no'] || $dataIsupp[0]['po_no'] == '')
            return false;

        foreach ($dataIsupp as $index => $val) {

            $kodebarang = $val['kode_brg'];
            $gudangKode = $val['gdg_kode'];
            $data = QDC_Inventory_Stock::factory()->getStock(array(
                "kode_brg" => $kodebarang,
                "gdg_kode" => $gudangKode,
                "periode" => $perkode
            ));

            if ($data) {

                $arrayupdate = array(
                    "hargaavg" => $data['hargaavg']
                );
                $barang->update($arrayupdate, "kode_brg = '$kodebarang'");
            } else {
                $arrayupdate = array(
                    "hargaavg" => $val['harga']
                );
                $barang->update($arrayupdate, "kode_brg = '$kodebarang'");
            }
        }
    }

}

?>
