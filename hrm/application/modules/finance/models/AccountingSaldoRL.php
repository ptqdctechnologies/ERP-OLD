<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingSaldoRL extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_saldo_rl';
    public $name;
    public $db;
    protected $const;
    public $limitCoa;

    public function __construct()
    {
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

    public function updateSaldoBalance($params=array())
    {
        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($coaKode == '' || $perKode == '' || $perTahun == '')
            return false;

        $sql = "UPDATE
            {$this->_name}
            SET total = total $operand $total
            WHERE
                coa_kode = '$coaKode'
                AND periode = '$perKode'
                AND tahun = '$perTahun'";
        $this->db->query($sql);

        return true;
    }

    public function getSaldo($params=array())
    {
        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($coaKode == '' || $perBulan == '' || $perTahun == '')
            return false;

        $saldo = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
        if ($saldo)
            return $saldo['total'];

        return false;
    }

    public function insertProfitLoss($params=array())
    {
        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($coaKode == '' || $total == '' || $perBulan == '' || $perTahun == '')
            return false;

        $cekCoa = substr($coaKode,0,1);

        if (in_array($cekCoa,$this->limitCoa))
        {
            $arrayInsert = array(
                "coa_kode" => $coaKode,
                "coa_nama" => $coaNama,
                "total" => $total,
                "periode" => $perBulan,
                "tahun" => $perTahun
            );
            $saldo = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($saldo) //Jika sudah ada di saldo RL
            {
                $id = $saldo['id'];
                $this->update($arrayInsert,"id = $id");
            }
            else
            {
                $this->insert($arrayInsert);
            }
        }
    }

    /**
     * @param string $type
     * @return bool|string
     *
     * FUngsi buat mendapatkan header coa yang akan dipakai di profit loss
     */
    public function getLimitCoa($type='')
    {
        switch($type)
        {
            case "income":
                $type = $this->limitCoa['income'];
                break;
            case "costOfSales":
                $type = $this->limitCoa['costOfSales'];
                break;
            case "expense":
                $type = $this->limitCoa['expense'];
                break;
            case "generalAdmExpense":
                $type = $this->limitCoa['generalAdmExpense'];
                break;
            case "otherIncomeExpense":
                $type = $this->limitCoa['otherIncomeExpense'];
                break;
            case "taxIncomeExpense":
                $type = $this->limitCoa['taxIncomeExpense'];
                break;
            default:
                return false;
                break;
        }

        return $type;
    }
}