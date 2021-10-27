<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 11/24/11
 * Time: 9:48 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_AccountingSaldoRL extends Zend_Db_Table_Abstract {

    protected $_name = 'accounting_saldo_rl';
    public $name;
    public $db;
    protected $const;
    public $limitCoa;
    public $coa;

    public function __construct() {
        parent::__construct($this->_option);
        $this->name = $this->_name;
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
        $this->coa = new Finance_Models_MasterCoa();

//Header Coa yang digunakan di profit loss
        $this->limitCoa = array(
            "income" => '4',
            "costOfSales" => '5',
            "generalAdmExpense" => '6',
            "otherIncomeExpense" => '8',
            "taxIncomeExpense" => '9'
        );
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


        $masterRL = new Finance_Models_LayoutProfitloss();
        $data = $masterRL->fetchAll();
        if (!$data)
            return false;

//Hapus saldo yang sudah ada...
        $this->delete("periode = '$perBulan' AND tahun = '$perTahun'");

        foreach ($data as $k => $v) {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($cek == null) {
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

    public function insertNewSaldo($params = array()) {

        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if ($perBulan == '' || $perTahun == '')
            return false;

        $masterRL = new Finance_Models_LayoutProfitloss();
        $saldoCoa = new Finance_Models_AccountingSaldoCoa();

//query untuk mengambil semua coa berdasarkan layout RL beserta header dr coa tsb
        $subselect = $this->db->select()
                ->from(array("a" => $saldoCoa->name()), array(
                    "a.coa_kode",
                    "a.coa_nama",
                    "total" => "if(b.dk = 'Debit',
                                    (coalesce(a.totaldebit,0)-coalesce(a.totalkredit,0)),
                                    (coalesce(a.totalkredit,0)-coalesce(a.totaldebit,0)))",
                    "a.periode",
                    "a.tahun",
                    "b.dk",
                    "b.hd",
                    "b.urut",
                    "b.level",
                    "coa_header" => "(select coa_kode 
                                        from master_layout_rl 
                                        where urut = SUBSTRING_INDEX(b.urut,'.',b.level-1) 
                                        and hd = 'header')"
                ))
                ->joinLeft(array("b" => $masterRL->name()), "a.coa_kode = b.coa_kode")
                ->where("a.periode = '$perBulan' and a.tahun = '$perTahun' and level > 1")
                ->order(array("b.hd", "b.urut"));

        $fetch = $this->db->fetchAll($subselect);


        $totalincome = 0;
        $totalcost = 0;

        foreach ($fetch as $k => $v) {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($cek != null) {

//hitung total income dan cost berdasarkan nilai dr detail
                if ($v['hd'] == 'Detail') {
                    if ($v['dk'] == 'Debit')
                        $totalcost = $totalcost + $v['total'];

                    if ($v['dk'] == 'Credit')
                        $totalincome = $totalincome + $v['total'];

                    $arrayUpdate = array(
                        "coa_kode" => $coaKode,
                        "coa_nama" => $coaNama,
                        "total" => $v['total'],
                        "periode" => $perBulan,
                        "tahun" => $perTahun
                    );

                    $this->update($arrayUpdate, "coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
                }

//hitung total header berdasarkan nilai detail
                $coaheader = $v['coa_header'];

                $totalSebelumnya = 0;
                $otal_current = $v['total'];
                $saldo_coa_header = $this->fetchRow("coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");

                if ($saldo_coa_header) {

                    if ($v['hd'] == 'Header') {

                        $saldo_coa_sub_header = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                        $otal_current = $saldo_coa_sub_header['total'];
                    }

                    $totalSebelumnya = $saldo_coa_header['total'];

                    $totalBaru = $otal_current + $totalSebelumnya;

                    $arrayUpdate = array(
                        "total" => floatval($totalBaru)
                    );

                    $this->update($arrayUpdate, "coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                    $saldo_coa_sub_header = $this->fetchRow("coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                } else {
                    $coa = $this->coa->fetchRow("coa_kode = '$coaheader'");

                    $arrayUpdate = array(
                        "coa_kode" => $coaheader,
                        "coa_nama" => $coa['coa_nama'],
                        "total" => $v['total'],
                        "periode" => $perBulan,
                        "tahun" => $perTahun
                    );

                    $this->insert($arrayUpdate);
                }
            }
        }

        $totalpnl = floatval($totalincome) - floatval($totalcost);

        $rls = new Finance_Models_AccountingSaldoRLSummary();
        $rls->insertNewSummary($totalpnl, $perBulan, $perTahun);
    }

    public function insertSaldoFromOtherClosing($params = array()) {

        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if ($perBulan == '' || $perTahun == '' || !$model)
            return false;

        $masterRL = new Finance_Models_LayoutProfitloss();
        $rlDetail = new Finance_Models_AccountingSaldoRLDetail();


//query untuk mengambil semua coa berdasarkan layout RL beserta header dr coa tsb
        $subselect = $this->db->select()
                ->from(array("a" => $model->__name()), array(
                    "trano" => "a.prj_kode",
                    "a.coa_kode",
                    "a.coa_nama",
                    "a.val_kode",
                    "a.rateidr",
                    "total" => "sum(total)",
                    "a.periode",
                    "a.tahun",
                    "b.dk",
                    "b.hd",
                    "b.urut",
                    "b.level",
                    "coa_header" => "(select coa_kode 
                                        from master_layout_rl 
                                        where urut = SUBSTRING_INDEX(b.urut,'.',b.level-1) 
                                        and hd = 'header')"
                ))
                ->joinLeft(array("b" => $masterRL->name()), "a.coa_kode = b.coa_kode")
                ->where("a.periode = '$perBulan' and a.tahun = '$perTahun' and level > 1")
                ->group(array("a.coa_kode"))
                ->order(array("b.hd", "b.urut"));

        $fetch = $this->db->fetchAll($subselect);

        $totalincome = 0;
        $totalcost = 0;
        $totalPrevious = 0;
        $first = true;

        foreach ($fetch as $k => $v) {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];

            $cek = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
            if ($cek != null) {
                if ($cek['total'] > 0)
                    $totalPrevious = $cek['total'];


                //hitung total income dan cost berdasarkan nilai dr detail
                if ($v['hd'] == 'Detail') {
                    if ($v['dk'] == 'Credit')
                        $totalcost = $totalcost + $v['total'];

                    if ($v['dk'] == 'Debit')
                        $totalincome = $totalincome + $v['total'];


                    $arrayUpdate = array(
                        "coa_kode" => $coaKode,
                        "coa_nama" => $coaNama,
                        "total" => $v['total'],
                        "periode" => $perBulan,
                        "tahun" => $perTahun
                    );

                    $this->update($arrayUpdate, "coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
                }

//hitung total header berdasarkan nilai detail
                $coaheader = $v['coa_header'];

                $totalSebelumnya = 0;
                $otal_current = $v['total'];
                $saldo_coa_header = $this->fetchRow("coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");

                if ($saldo_coa_header) {

                    if ($v['hd'] == 'Header') {

                        $saldo_coa_sub_header = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                        $otal_current = $saldo_coa_sub_header['total'];
                    }

                    $totalSebelumnya = ($saldo_coa_header['total'] - $totalPrevious);

                    $totalBaru = $otal_current + $totalSebelumnya;

                    $arrayUpdate = array(
                        "total" => floatval($totalBaru)
                    );

                    $this->update($arrayUpdate, "coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                    $saldo_coa_sub_header = $this->fetchRow("coa_kode = '$coaheader' AND periode = '$perBulan' AND tahun = '$perTahun' ");
                } else {
                    $coa = $this->coa->fetchRow("coa_kode = '$coaheader'");

                    $arrayUpdate = array(
                        "coa_kode" => $coaheader,
                        "coa_nama" => $coa['coa_nama'],
                        "total" => $v['total'],
                        "periode" => $perBulan,
                        "tahun" => $perTahun
                    );

                    $this->insert($arrayUpdate);
                }

                $rls = new Finance_Models_AccountingSaldoRLSummary();
                $data = $rls->fetchRow("periode = '$perBulan' and tahun ='$perTahun'");
                $saldo = 0;


                if ($data) {
                    if ($v['dk'] == 'Credit')
                        $saldo = $data['total'] + $v['total'];
                    else
                        $saldo = $data['total'] - $v['total'];

                    $arrayUpdate = array(
                        "total" => $saldo,
                    );

                    $rls->update($arrayUpdate, "periode = '$perBulan' and tahun ='$perTahun'");
                }
            }
        }



        //RLDetail
        $ConstPrj = $model->fetchAll("periode = '$perBulan' AND tahun = '$perTahun'")->toArray();

        $first = true;
        $credit = 0;
        $debit = 0;
        if ($ConstPrj) {
            foreach ($ConstPrj as $k => $v) {

                $coa_kode = $v['coa_kode'];
                $coa = $masterRL->fetchRow("coa_kode = '$coa_kode'");

                $detail = $rlDetail->fetchRow("periode = '$perBulan' AND tahun = '$perTahun' and coa_kode ='$coa_kode'");

                if ($detail['origin_table'] != $model->__name())
                    $rlDetail->delete("periode = '$perBulan' AND tahun = '$perTahun' and coa_kode ='$coa_kode'");

                
                if ($coa['dk'] == 'Credit')
                    $credit = $v['total'];
                else
                    $debit = $v['total'];

                $arrayInsert = array(
                    "coa_kode" => $v['coa_kode'],
                    "coa_nama" => $v['coa_nama'],
                    "debit" => $debit,
                    "credit" => $credit,
                    "periode" => $v['periode'],
                    "tahun" => $v['tahun'],
                    "perkode" => $v['perkode'],
                    "rateidr" => $v['rateidr'],
                    "origin_table" => $model->__name(),
                    "trano" => $v['prj_kode'],
                    "val_kode" => $v['val_kode']
                );

                $rlDetail->insert($arrayInsert);
            }
        }
    }

//    public function insertProfitLoss($params=array())
//    {
//        if ($params != '')
//        foreach($params as $k => $v)
//        {
//            $temp = $k;
//            ${"$temp"} = $v;
//        }
//
//        if ($coaKode == '' || $total == '' || $perBulan == '' || $perTahun == '')
//            return false;
//        
//        
//        $masterRL = new Finance_Models_LayoutProfitloss();
//        $data = $masterRL->fetchAll();
//        
//$model
//        $cekCoa = substr($coaKode,0,1);
//
//        if (in_array($cekCoa,$this->limitCoa))
//        {
//            $arrayInsert = array(
//                "coa_kode" => $coaKode,
//                "coa_nama" => $coaNama,
//                "total" => $total,
//                "periode" => $perBulan,
//                "tahun" => $perTahun
//            );
//            $saldo = $this->fetchRow("coa_kode = '$coaKode' AND periode = '$perBulan' AND tahun = '$perTahun'");
//            if ($saldo) //Jika sudah ada di saldo RL
//            {
//                $id = $saldo['id'];
//                $this->update($arrayInsert,"id = $id");
//            }
//            else
//            {
//                $this->insert($arrayInsert);
//            }
//        }
//    }

    /**
     * @param string $type
     * @return bool|string
     *
     * FUngsi buat mendapatkan header coa yang akan dipakai di profit loss
     */
    public function getLimitCoa($type = '') {
        switch ($type) {
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

    public function filterCoaProfitLoss($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if ($data_coa == '')
            return false;

        $newSaldo = array();
        $masterCoa = new Finance_Models_MasterCoa();
        foreach ($data_coa as $k => $v) {
            $cekCoa = substr($v['coa_kode'], 0, 1);

            if (in_array($cekCoa, $this->limitCoa)) {
                $coaKode = $v['coa_kode'];
                $cek = $masterCoa->fetchRow("coa_kode = '$coaKode'");

                $operand = '+';
                $totInsert = 0;
                if ($v['credit'] != '' && $v['credit'] > 0) {
                    if ($cek['dk'] == 'Debit')
                        $operand = '-';
                    $totInsert = floatval($v['credit']);
                }
                else if ($v['debit'] != '' && $v['debit'] > 0) {
                    if ($cek['dk'] == 'Credit')
                        $operand = '-';
                    $totInsert = floatval($v['debit']);
                }

                $newSaldo[$coaKode]['total'] = floatval($newSaldo[$coaKode]['total']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);
            }
        }

        return $newSaldo;
    }

    public function filterProfitLoss($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if ($data_coa == '')
            return false;

        $newSaldo = array();
        $masterCoa = new Finance_Models_MasterCoa();
        $masterRL = new Finance_Models_LayoutProfitloss();
        $coa_layout = $masterRL->fetchAll(null, array("hd ASC", "urut ASC"))->toArray();

        $urut = '';
        $dk = '';
        $hd = '';
        $level = '';
        $coa_header = '';



        foreach ($coa_layout as $key => $val) {
            $coaKode = $val['coa_kode'];
            $urut = $val['urut'];
            $level = $val['level'];

            $query = "select coa_kode from master_layout_rl 
                      where urut = SUBSTRING_INDEX('$urut','.',$level-1) 
                      and hd = 'header' order by urut ";
            $fetch = $this->db->query($query);
            if ($fetch)
                $fetch = $fetch->fetchAll();

            $newSaldo[$coaKode]['coa_kode'] = $coaKode;
            $newSaldo[$coaKode]['coa_nama'] = $val['coa_nama'];
            $newSaldo[$coaKode]['urut'] = $val['urut'];
            $newSaldo[$coaKode]['dk'] = $val['dk'];
            $newSaldo[$coaKode]['hd'] = $val['hd'];
            $newSaldo[$coaKode]['level'] = $val['level'];
            $newSaldo[$coaKode]['coa_header'] = $fetch[0]['coa_kode'];
        }

        $operand = '+';
        $totInsert = 0;
        foreach ($newSaldo as $key => $val) {
            $coaKode = $val['coa_kode'];
            $coaHeader = $val['coa_header'];
            $urut = $val['urut'];
            $level = $val['level'];

            foreach ($data_coa as $k => $v) {
                $operand = '+';
                $totInsert = 0;
                if ($val['hd'] == 'Detail') {

                    if ($coaKode == $v['coa_kode']) {
                        $cek = $masterCoa->fetchRow("coa_kode = '$coaKode'");
                        if ($v['credit'] != '' && $v['credit'] > 0) {
                            if ($cek['dk'] == 'Debit')
                                $operand = '-';
                            $totInsert = floatval($v['credit']);
                        }
                        else if ($v['debit'] != '' && $v['debit'] > 0) {
                            if ($cek['dk'] == 'Credit')
                                $operand = '-';
                            $totInsert = floatval($v['debit']);
                        }
                        $newSaldo[$coaKode]['total'] = floatval($newSaldo[$coaKode]['total']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);
                        $newSaldo[$coaHeader]['total'] = floatval($newSaldo[$coaHeader]['total']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);
                    }
                }
            }

            if ($val['hd'] == 'Header' && $level > 1)
                $newSaldo[$coaHeader]['total'] += floatval($newSaldo[$coaKode]['total']);
        }

        usort($newSaldo, $this->build_sorter('urut'));

        return $newSaldo;
    }

    public function name() {
        return $this->_name;
    }

    private function build_sorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }

    public function updateCoaToFilteredSaldo($exist_data = array(), $new_data = array(), $detail) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
}
        if (!$exist_data && !$new_data)
            return false;

        $total_before = 0;
        $coaHeader = '';
        $first = true;
        foreach ($exist_data as $k => $v) {
            $operand = '+';
            $totInsert = 0;
            if ($v['hd'] == 'Detail') {

                foreach ($new_data as $k2 => $v2) {
                    $coakode = $v2['coa_kode'];

                    if ($coakode == $v['coa_kode']) {
                        
                        // update nilai coa
                        // kurangi dengan nilai yang terdahulu sebelum menambahkan nilai baru
                        if ($first)
                            $exist_data[$k]['total'] = $exist_data[$k]['total'] - $v['total'];

                        $exist_data[$k]['total'] += floatval($v2['totals']);
                        $coaHeader = $v['coa_header'];

                        $key = $this->searchArrayKey2D($coaHeader, $exist_data);
                        
                        // update nilai header coa
                        // nilai total dkurangi nilai coa sebelumnya yg akan diupdate
                        if ($first) {
                            $total_before = $v['total'];
                            $exist_data[$key]['total'] = floatval($exist_data[$key]['total'] - $total_before);
                            $first = false;
                        }

                        $exist_data[$key]['total'] = floatval($exist_data[$key]['total'] + $v2['totals']);
                    }
                }
            }
            
            //for coa detail transaction 
            if ($detail) {
                foreach ($new_data as $k2 => $v2) {
                    $coakode = $v2['coa_kode'];

                    if ($coakode == $v['coa_kode']) {
                        if ($v['debit'] == 0)
                            $exist_data[$k]['credit'] = floatval($v2['totals']);
                        else
                            $exist_data[$k]['debit'] = floatval($v2['totals']);

                        $exist_data[$k]['trano'] = $v2['prj_kode'];
                        $exist_data[$k]['tgl'] = date("d-M-Y", strtotime($v2['tgl_close']));
                    }
                }
            }
            $first = true;
        }

        return $exist_data;
    }

    function searchArrayKey2D($value, $array) {
        for ($i = 0, $l = count($array); $i < $l; ++$i) {
            if (in_array($value, $array[$i]))
                return $i;
        }
        return false;
    }

}
