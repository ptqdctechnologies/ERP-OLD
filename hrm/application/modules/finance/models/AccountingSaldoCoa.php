<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_AccountingSaldoCoa extends Zend_Db_Table_Abstract
{
    protected $_name = 'accounting_saldo_coa';
    public $name;
    public $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function name()
    {
        return $this->_name;
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

    public function createNewSaldo($params=array())
    {
        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($perBulan == '' || $perTahun == '')
            return false;

        $masterCoa = new Finance_Models_MasterCoa();
        $coa = $masterCoa->fetchAll(null,array("coa_kode ASC"));
        if (!$coa)
            return false;

        //Hapus saldo yang sudah ada...
        $this->delete("periode = '$perBulan' AND tahun = '$perTahun'");

        foreach($coa as $k => $v)
        {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($cek == null)
            {
                $arrayInsert = array(
                    "coa_kode" => $coaKode,
                    "coa_nama" => $coaNama,
                    "total" => 0,
                    "periode" => $perBulan,
                    "tahun" => $perTahun
                );

                $this->insert($arrayInsert);
            }
        }
    }

    public function insertSaldo($array=array())
    {

        if ($array != '')
        foreach($array['ket'] as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
        $rateidr = $exhangeRate['rateidr'];

        if ($perBulan == '' || $perTahun == '' || $perKode == '')
            return false;
        $masterCoa = new Finance_Models_MasterCoa();
        $previousPeriode = QDC_Finance_Periode::factory()->getPreviousPeriode(array(
            "bulan" => $perBulan,
            "tahun" => $perTahun,
            "perkode" => $perKode
        ));

        $prevBulan = $previousPeriode['bulan'];
        $prevTahun = $previousPeriode['tahun'];

        $currentSaldo = array();
        $tmp = $this->fetchAll("periode = '$perBulan' AND tahun = '$perTahun'");
        if ($tmp)
        {
            $tmp = $tmp->toArray();
            foreach($tmp as $k => $v)
            {
                $coa = $v['coa_kode'];
                $currentSaldo[$coa] = $v;
            }
        }

        $newSaldo = array();
        foreach($array['data'] as $k => $v)
        {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $cek = $masterCoa->fetchRow("coa_kode = '$coaKode'");

            $operand = '+';
            $totInsert = 0;
            if ($v['credit'] != '' && $v['credit'] > 0)
            {
                if ($cek['dk'] == 'Debit')
                    $operand = '-';
                $totInsert = floatval($v['credit']);
            }
            else if ($v['debit'] != '' && $v['debit'] > 0)
            {
                if ($cek['dk'] == 'Credit')
                    $operand = '-';
                $totInsert = floatval($v['debit']);
            }

            $newSaldo[$coaKode]['total'] = floatval($newSaldo[$coaKode]['total']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);

//            $logInsert = array(
//                "coa_kode" => $coaKode,
//                "coa_nama" => $coaNama,
//                "total" => (($operand == '-') ? -1 * floatval($totInsert) : $totInsert),
//                "periode" => $perBulan,
//                "tahun" => $perTahun,
//                "val_kode" => $valKode,
//                "rateidr" => $rateidr
//            );
//
//            $logInsert['ref_number'] = $trano;
//            $logInsert['bulan'] = $periode['bulan'];
//            $logInsert['perkode'] = $periode['perkode'];
//            $logInsert['action'] = 'UPDATE';
//
//            QDC_Log_Accounting::factory()->insert($logInsert);

        }

        $saldoRL = new Finance_Models_AccountingSaldoRL();

        foreach($currentSaldo as $k => $v)
        {
            $saldo = floatval($newSaldo[$k]['total']);
            $coaKode = $v['coa_kode'];
            $totalPrev = 0;
            $val_kode = '';
            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$prevBulan' AND tahun = '$prevTahun'");
            if ($cek != null)
            {
                $totalPrev = $cek['total'];
                $val_kode = $cek['val_kode'];
            }

//            if ($v['total'] > 0)
//            $totalPrev = $v['total'];

            $totalSaldo = floatval($totalPrev) + $saldo;

            $arrayUpdate = array(
                "total" => $totalSaldo,
                "val_kode" => $val_kode,
                "rateidr" => $rateidr
            );

            $this->update($arrayUpdate,"id = {$v['id']}");

            //insert ke saldo RL
            if ($newSaldo[$k] == '')
                continue;
            $saldoRL->insertProfitLoss(array(
                "coaKode" => $coaKode,
                "coaNama" => $v['coa_nama'],
                "perBulan" => $perBulan,
                "perTahun" => $perTahun,
                "total" => $saldo
            ));
        }

    }

}