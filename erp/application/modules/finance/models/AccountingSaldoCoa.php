<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingSaldoCoa extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_saldo_coa';
    public $name;
    public $db;
    protected $const;

    public function __construct() {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function name() {
        return $this->_name;
    }

    public function __name() {
        return $this->_name;
    }

    public function updateSaldoBalance($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
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

    public function getSaldo($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
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

    public function createNewSaldo($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if ($perBulan == '' || $perTahun == '')
            return false;

        $masterCoa = new Finance_Models_MasterCoa();
        $coa = $masterCoa->fetchAll(null, array("coa_kode ASC"));
        if (!$coa)
            return false;

        //Hapus saldo yang sudah ada...
        $this->delete("periode = '$perBulan' AND tahun = '$perTahun'");

        foreach ($coa as $k => $v) {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($cek == null) {
                $arrayInsert = array(
                    "coa_kode" => $coaKode,
                    "coa_nama" => $coaNama,
                    "total" => 0,
                    "totaldebit" => 0,
                    "totalkredit" => 0,
                    "periode" => $perBulan,
                    "tahun" => $perTahun
                );

                $this->insert($arrayInsert);
            }
        }
    }

    public function insertSaldo($array = array(), $rateidr = '') {

        if ($array != '')
            foreach ($array['ket'] as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if (!$rateidr) {
            $exhangeRate = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $rateidr = $exhangeRate['rateidr'];
        }

        if ($perBulan == '' || $perTahun == '' || $perKode == '')
            return false;
        $masterCoa = new Finance_Models_MasterCoa();
//        $previousPeriode = QDC_Finance_Periode::factory()->getPreviousPeriode(array(
//            "bulan" => $perBulan,
//            "tahun" => $perTahun,
//            "perkode" => $perKode
//        ));
//
//        $prevBulan = $previousPeriode['bulan'];
//        $prevTahun = $previousPeriode['tahun'];

        $currentSaldo = array();
        $tmp = $this->fetchAll("periode = '$perBulan' AND tahun = '$perTahun'");
        if ($tmp) {
            $tmp = $tmp->toArray();
            foreach ($tmp as $k => $v) {
                $coa = $v['coa_kode'];
                $currentSaldo[$coa] = $v;
            }
        }

        $newSaldo = array();

        //
        $array['data'] = $this->filterCoa($array['data']);

        foreach ($array['data'] as $k => $v) {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $array['data'][$k]['periode'] = $perBulan;
            $array['data'][$k]['tahun'] = $perTahun;
            $array['data'][$k]['perkode'] = $perKode;
            $array['data'][$k]['rateidr'] = $rateidr;

            if ($v['rateidr'] != 0 || $v['rateidr'] != '')
                $rate_idr = $v['rateidr'];
            else
                $rate_idr = $rateidr;

            $cek = $masterCoa->fetchRow("coa_kode = '$coaKode'");
            $operand = '+';
            $totInsert = 0;
            $total = 0;
            if ($v['credit'] != '' && $v['credit'] > 0) {
                if ($cek['dk'] == 'Debit')
                    $operand = '-';

                if ($cek['convert_idr'] == 1 && ($v['val_kode'] != 'IDR' && $v['val_kode'] != ''))
                    $total = $v['credit'] * $rate_idr;
                else
                if ($v['credit_conversion'] > 0)
                    $total = $v['credit_conversion'];
                else
                    $total = $v['credit'];

                $totInsert = floatval($total);

                $newSaldo[$coaKode]['totalkredit'] += $total;
            }
            else if ($v['debit'] != '' && $v['debit'] > 0) {
                if ($cek['dk'] == 'Credit')
                    $operand = '-';

                if ($cek['convert_idr'] == 1 && ($v['val_kode'] != 'IDR' && $v['val_kode'] != ''))
                    $total = $v['debit'] * $rate_idr;
                else
                if ($v['debit_conversion'] > 0)
                    $total = $v['debit_conversion'];
                else
                    $total = $v['debit'];

                $totInsert = floatval($total);

                $newSaldo[$coaKode]['totaldebit'] += $total;
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

        foreach ($currentSaldo as $k => $v) {
            $saldo = floatval($newSaldo[$k]['total']);

            $coaKode = $v['coa_kode'];
            $totalPrev = $lastSaldo = 0;
            $val_kode = '';
            $concat = $perBulan . $perTahun;
            $select = $this->db->select()
                    ->from(array($this->_name))
                    ->where("coa_kode=?", $coaKode)
                    ->where("(CONCAT(periode,tahun) != '$concat')")
                    ->order(array("tahun DESC", "periode DESC"))
                    ->limit(1);

            if ($perBulan == '01' || $perBulan == '1') {
                $tahun = (string) ($perTahun - 1);
                $select->where("tahun = '$tahun'");
            } else
                $select->where("cast(periode AS SIGNED) < ? ", $perBulan);


            $cek = $this->db->fetchRow($select);
//            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$prevBulan' AND tahun = '$prevTahun'");
            if ($cek != null) {
                $totalPrev = $cek['total'];
                $val_kode = $cek['val_kode'];
                $lastSaldo = $cek['total'];
            }

//            if ($v['total'] > 0)
//            $totalPrev = $v['total'];

            $totalSaldo = floatval($totalPrev) + $saldo;

            $arrayUpdate = array(
                "total" => $totalSaldo,
                "last_total" => $lastSaldo,
                "last_periode" => ($cek['periode'] == '' ? null : $cek['periode']),
                "last_tahun" => ($cek['tahun'] == '' ? null : $cek['tahun']),
                "totalkredit" => $newSaldo[$k]['totalkredit'],
                "totaldebit" => $newSaldo[$k]['totaldebit'],
                "val_kode" => $val_kode,
                "rateidr" => $rateidr,
                "tgl_close" => new Zend_Db_Expr("NOW()")
            );

            $this->update($arrayUpdate, "id = {$v['id']}");

            //insert ke saldo RL
            if ($newSaldo[$k] == '')
                continue;


//            $saldoRL->insertProfitLoss(array(
//                "coaKode" => $coaKode,
//                "coaNama" => $v['coa_nama'],
//                "perBulan" => $perBulan,
//                "perTahun" => $perTahun,
//                "total" => $saldo
//            ));
        }

        $rlDetail = new Finance_Models_AccountingSaldoRLDetail();
        $rlDetail->insertBulk($perBulan, $perTahun, $array['data']);

//        $rls = new Finance_Models_AccountingSaldoRLSummary();
//        $rls->insertNewSummary($perBulan, $perTahun);
    }

    public function getLastSaldo($date = '') {
        if ($date) {
            $bulan = date("m", strtotime($date));
            $tahun = date("Y", strtotime($date));
        }

        $select = $this->db->select()
                ->from(array($this->_name), array(
                    "periode",
                    "tahun"
                ))
                ->where("DATE(CONCAT(tahun,'-',periode,'-','01')) < DATE(CONCAT('$tahun','-','$bulan','-','01'))")
                ->order(array("tahun DESC", "periode DESC"))
                ->limit(1, 0);
        $data = $this->db->fetchRow($select);

        return $data;
    }

    public function filterCoa($dataFiltered) {

        if (count($dataFiltered) < 0)
            return false;

        //define coa n coa bank model object
        $coa_bank = new Finance_Models_MasterCoaBank();

        $arraytemp_debit = array();
        $arraytemp_credit = array();

        foreach ($dataFiltered as $key => $value) {
            $dataFiltered[$key]['debit_conversion'] = 0;
            $dataFiltered[$key]['credit_conversion'] = 0;

            $valuta_coa_bank = '';
            $coa_kode = $value['coa_kode'];
            $trano = $value['trano'];
            $bank_code = substr($trano, 0, 3);
            $data_bank = $coa_bank->fetchRow("trano_type = '$bank_code'");
            if ($data_bank)
                $valuta_coa_bank = $data_bank['val_kode'];

            $valuta = $value['val_kode'];

//
//            if($coa_kode == '1-1890'){
//                if($valuta == 'USD'){
//                var_dump($trano);
//                var_dump($valuta_coa_bank);
//                var_dump($valuta);
//            }
//            }


            if ($valuta != 'IDR' && $valuta != '') {

                $dataFiltered[$key]['filtered'] = false;


                //filter coa exchange -> unset
                //filter coa gain/loss tidak dikonversi USD->IDR

                $findExchange = strpos($value['coa_nama'], 'Exchange');
                $findGain = strpos($value['coa_nama'], 'Gain');
                $findLoss = strpos($value['coa_nama'], 'Loss');
                $memoExchange = strpos($value['memo'], 'Exchange');
                $memoGain = strpos($value['memo'], 'Gain');
                $memoLoss = strpos($value['memo'], 'Loss');

                $ispbpv = strpos($value['trano'], 'BPV');

                if ($findExchange !== false) {

                    if ($findGain !== false || $findLoss !== false) {
                        $dataFiltered[$key]['filtered'] = true;
                        continue;
                    } else if ($memoGain !== false || $memoLoss !== false) {
                        $dataFiltered[$key]['filtered'] = true;
                        continue;
                    } else
                        unset($dataFiltered[$key]);
                }

                //filter coa exchange yang terpola lewat memo
                if ($memoExchange !== false) {

                    if ($memoGain !== false || $memoLoss !== false) {
                        $dataFiltered[$key]['filtered'] = true;
                        continue;
                    } else
                        unset($dataFiltered[$key]);
                }

                if (strpos($value['coa_nama'], 'USD') !== false) {
                    $dataFiltered[$key]['filtered'] = true;
                    continue;
                }

                //jika coa dan bank untuk IDR tetapi digunakan untuk transaksi USD
                if (strpos($value['coa_nama'], 'IDR') !== false && $valuta_coa_bank == 'IDR') {
                    $dataFiltered[$key]['filtered'] = true;
                    continue;
                }

                //bank digunakan oleh bpv
                if (strpos($value['coa_nama'], 'IDR') !== false && $valuta != 'IDR' && $ispbpv !== false) {
                    $dataFiltered[$key]['filtered'] = true;
                    continue;
                }

                //
                if (strpos($value['coa_nama'], 'IDR') !== false && $valuta != 'IDR' && $valuta_coa_bank == 'IDR') {
                    $dataFiltered[$key]['filtered'] = true;
                    continue;
                }

                //yang dikalikan coa-coa banci (bank charge,material used,prepaid tax,dll)
                if ($value['debit'] != 0) {
                    $dataFiltered[$key]['debit_conversion'] = floatval($value['debit'] * $value['rateidr']);
                } else {
                    $dataFiltered[$key]['credit_conversion'] = floatval($value['credit'] * $value['rateidr']);
                }
            }
        }

        foreach ($dataFiltered as $key => $val) {

            $coa_kode = $val['coa_kode'];
            $trano = $val['trano'];

            $rateTotalminus = 0;
            $rateTotalplus = 0;

            if ($val['val_kode'] != 'IDR' && $val['val_kode'] != '') {

                if ($val['filtered'])
                    continue;

                foreach ($dataFiltered as $k2 => $v2) {

                    if ($trano == $v2['trano']) {

                        // filter coa untuk menghilangkan coa2 hasil perkalian 
                        // nilai ori dengan exchange rate dikurangi nilai ori
                        if ($val['debit'] != 0) {

                            $rateTotalminus = round(($v2['debit'] * $v2['rateidr']) - $v2['debit'], 2);
                            $rateTotalplus = round(($v2['debit'] * $v2['rateidr']) + $v2['debit'], 2);
                            $rateTotal = round(($v2['debit'] * $v2['rateidr']), 2);

                            $value = round($val['debit'], 2);

                            if ($value == $rateTotalminus) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotalplus) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotal) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['exception_formula'] = true;
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            }
                        } else {
                            $rateTotalminus = round(($v2['credit'] * $v2['rateidr']) - $v2['credit'], 2);
                            $rateTotalplus = round(($v2['credit'] * $v2['rateidr']) + $v2['credit'], 2);
                            $rateTotal = round(($v2['credit'] * $v2['rateidr']), 2);

                            $value = round($val['credit'], 2);
                            if ($value == $rateTotalminus) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotalplus) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotal) {
                                unset($dataFiltered[$key]);
                                $dataFiltered[$k2]['exception_formula'] = true;
                                $dataFiltered[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$v2['job_number']]['has_exchange'] = true;
                                break;
                            }
                        }
                    }
                }
            }
        }


        //filter untuk menghitung total konversi
        foreach ($dataFiltered as $k => $val) {

            //filter coa in usd 
            if ($val['val_kode'] != 'IDR' && $val['val_kode'] != '') {

                if ($val['filtered'])
                    continue;

                if ($val['debit_conversion'] > 0)
                    $arraytemp_debit[$val['trano']][$val['coa_kode']][$val['job_number']]['totaldebit_conversion'] += $val['debit_conversion'];

                if ($val['credit_conversion'] > 0)
                    $arraytemp_credit[$val['trano']][$val['coa_kode']][$val['job_number']]['totalcredit_conversion'] += $val['credit_conversion'];
            }
        }

        foreach ($dataFiltered as $k => $v) {
            $trano = $v['trano'];
            $coa = $v['coa_kode'];
            $job_number = $v['job_number'];
            $total_debit_convers = 0;
            $total_credit_convers = 0;
            //filter coa in usd
            if ($v['val_kode'] != 'IDR' && $v['val_kode'] != '') {

                if ($v['filtered'])
                    continue;

                if ($v['debit_conversion'] > 0 || $v['credit_conversion'] > 0) {

                    $total_debit_convers = $arraytemp_debit["$trano"]["$coa"]["$job_number"]['totaldebit_conversion'];
                    $total_credit_convers = $arraytemp_credit["$trano"]["$coa"]["$job_number"]['totalcredit_conversion'];

                    if ($v['debit_conversion'] > 0)
                        if ($arraytemp_debit["$trano"]["$coa"]["$job_number"]["has_exchange"])
                            if ($v['debit_conversion'] > ($total_debit_convers - $v['debit_conversion']) && $v['debit_conversion'] != $total_debit_convers && !$v['is_ori'])
                                unset($dataFiltered[$k]);

                    if ($v['credit_conversion'] > 0)
                        if ($arraytemp_credit["$trano"]["$coa"]["$job_number"]["has_exchange"])
                            if ($v['credit_conversion'] > ($total_credit_convers - $v['credit_conversion']) && $v['credit_conversion'] != $total_credit_convers && !$v['is_ori'])
                                unset($dataFiltered[$k]);
                }
            }
        }

        return $dataFiltered;
    }

}
