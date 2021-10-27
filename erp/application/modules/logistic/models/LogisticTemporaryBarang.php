<?php

class Logistic_Models_LogisticTemporaryBarang extends Zend_Db_Table_Abstract {

    protected $_name = 'master_barang_temporary';
    protected $_primary = 'kode_brg';
    protected $db;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __name(){
        return $this->_name;
    }

    public function doupdateharga($trano) {

        $data = $this->fetchRow("trano = '$trano'");

        if ($data->toArray()) {
            $data = $data->toArray();

            $kode_brg = $data['kode_brg'];

            $model_master_barang = new Default_Models_MasterBarang();

            $rate = QDC_Common_ExchangeRate::factory(array("valuta" => 'USD'))->getExchangeRate();

            $old = $model_master_barang->fetchAll("kode_brg = '$kode_brg'")->toArray();
            $log['barang-detail-before'] = $old;

            $updatematerial = array(
                "hargaavg" => $data['harga_baru'],
                "uid" => $data['uid'],
                "date" => $data['tgl'],
                "rateidr" => $rate['rateidr']
            );

            $model_master_barang->update($updatematerial, "kode_brg = '$kode_brg'");

            $new = $model_master_barang->fetchAll("kode_brg = '$kode_brg'")->toArray();
            $log2['barang-detail-after'] = $new;

            $return = array("success" => true);

            //Log Transaction
            $logs = new Admin_Models_Logtransaction();
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array(
                "trano" => $kode_brg,
                "uid" => QDC_User_Session::factory()->getCurrentUID(),
                "tgl" => date('Y-m-d H:i:s'),
                "action" => "UPDATE",
                "data_before" => $jsonLog,
                "data_after" => $jsonLog2,
                "ip" => $_SERVER["REMOTE_ADDR"],
                "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $logs->insert($arrayLog);
        } else
            return false;
    }

}

?>
