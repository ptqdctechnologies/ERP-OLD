<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingSaldoRLSummary extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_saldo_rl_summary';
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

    public function insertNewSummary($totalProfitLoss = 0, $periode = '', $tahun = '') {

//        $totalProfitLoss = QDC_Finance_ProfitLoss::factory()->getProfitLoss(array(
//            "perBulan" => $periode,
//            "perTahun" => $tahun,
//            "fromArray" => false
//        ));
        $arrayInsert = array(
            "total" => $totalProfitLoss,
            "periode" => $periode,
            "tahun" => $tahun,
            "tgl_close" => new Zend_Db_Expr("NOW()")
        );
        $saldo = $this->fetchRow("periode = '$periode' AND tahun = '$tahun'");
        if ($saldo) { //Jika sudah ada di saldo RL
            $id = $saldo['id'];
            $this->update($arrayInsert, "id = $id");
        } else {
            $this->insert($arrayInsert);
        }
    }

    public function getSaldo($periode = '', $tahun = '') {

        if ($periode == '' || $tahun == '')
            return false;

        $saldo = $this->fetchRow("periode = '$periode' AND tahun = '$tahun'");

        if ($saldo)
            return $saldo['total'];

        return false;
    }

}
