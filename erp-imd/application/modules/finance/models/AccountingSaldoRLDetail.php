<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingSaldoRLDetail extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_saldo_rl_detail';
    public $name;
    public $db;
    protected $const;
    public $limitCoa;

    public function __construct() {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');

        //Header Coa yang digunakan di profit loss
        $this->limitCoa = array(
            "income" => '4',
            "costOfSales" => '5',
            "generalAdmExpense" => '6',
            "otherIncomeExpense" => '8',
            "taxIncomeExpense" => '9'
        );
    }

    public function insertBulk($perBulan, $perTahun, $array = array()) {

        $this->delete("periode = '$perBulan' AND tahun = '$perTahun'");
        
        foreach ($array as $k => $v) {
            $arrayInsert = array(
                "coa_kode" => $v['coa_kode'],
                "coa_nama" => $v['coa_nama'],
                "debit" => $v['debit'],
                "credit" => $v['credit'],
                "periode" => $v['periode'],
                "tahun" => $v['tahun'],
                "perkode" => $v['perkode'],
                "rateidr" => $v['rateidr'],
                "origin_table" => $v['origin_table'],
                "trano" => $v['trano'],
                "val_kode" => $v['val_kode']
            );

            $this->insert($arrayInsert);
        }
    }

}
