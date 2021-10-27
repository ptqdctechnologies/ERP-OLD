<?php

/**
 * Created by Mr Rius.

 */
class Finance_Models_AccountingJurnalSettlement extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_jurnal_settlement';
    protected $db;
    protected $const;
    protected $jurnalsettle;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function insertJurnal($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }


        $rateidr = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $rateidr = $rateidr['rateidr'];
        $arrayInsert = array(
            "trano" => $trano,
            "ref_number" => $ref_number,
            "job_number" => $job_number,
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => QDC_User_Session::factory()->getCurrentUID(),
            "val_kode" => $val_kode,
            "prj_kode" => $prj_kode,
            "sit_kode" => $sit_kode,
            "rateidr" => $rateidr,
            "type" => $type,
            "description" => $description,
        );

        if ($val_kode != 'IDR') {
            $total = $total * $rateidr;
        }

        if ($debit) {

            //Debit
            $arrayInsert['debit'] = $total;
            $arrayInsert['credit'] = 0;
            $arrayInsert['coa_kode'] = $coa_kode;
            $arrayInsert['coa_nama'] = $coa_nama;

            $this->insert($arrayInsert);
        } else {
        //Credit
            $arrayInsert['credit'] = $total;
            $arrayInsert['debit'] = 0;
            $arrayInsert['coa_kode'] = $coa_kode;
            $arrayInsert['coa_nama'] = $coa_nama;

            $this->insert($arrayInsert);
        }
    }

}
