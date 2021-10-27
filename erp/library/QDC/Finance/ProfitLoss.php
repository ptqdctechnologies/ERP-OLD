<?php

Class QDC_Finance_ProfitLoss {

    public $FINANCE;
    private $models;
    private $arrayPL; //Array pl
    private $limitCoa;
    private $db;

    public function __construct($params = '') {

        if ($params != '') {
            foreach ($params as $k => $v) {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->models = array(
            "MasterPeriode",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "LayoutProfitloss",
            "MasterCoa"
        );
        $this->FINANCE = $this->getConfig($this->models);
        $this->arrayPL = array();

        $this->depth = $this->maxLevel();
        $this->limitCoa = $this->FINANCE->AccountingSaldoRL->limitCoa;
    }

    public static function getConfig($models = array()) {
        $reps = QDC_Model_Finance::init($models);
        return $reps;
    }

    /**
     * @static
     * @param $params
     * @return QDC_Finance_ProfitLoss
     *
     * Method factory dipanggil apabila QDC_FINANCE_PROFITLOSS di inisialisasi secara statik
     */
    public static function factory($params = array()) {
        return new self($params);
    }

    private function maxLevel() {
        $sql = "
            SELECT
                MAX(level) as maxlevel
            FROM
                master_layout_rl
        ";

        $fetch = $this->FINANCE->db->query($sql);

        $fetch = $fetch->fetch();

        return intval($fetch['maxlevel']);
    }

    public function generateProfitLoss() {
        for ($i = 1; $i <= $this->depth; $i++) {
            if ($i == 1) {
                $fetch = $this->FINANCE->LayoutProfitloss->fetchAll("hd = 'Header' AND level = 1", array("urut ASC"));
                if ($fetch) {
                    $fetch = $fetch->toArray();
                    foreach ($fetch as $k => $v) {
                        $v['total'] = 0;
                        $this->arrayPL[] = $v;
                        $this->arrayPL[] = array(
                            "level" => $i,
                            "totalfor" => $v['urut'],
                            "grandtotal" => 0,
                            "text" => "<b>Total " . $v['coa_nama'] . "</b>"
                        );
                    }
                }
            } else {
                $prevArray = $this->searchArray($this->arrayPL, array("level" => $i - 1));
                $jumInsert = 0;
                foreach ($prevArray as $k => $v) {
                    $urut = $v['urut'];
                    $arrayInsert = array();
                    $jumTotal = 0;
                    $fetch2 = $this->FINANCE->LayoutProfitloss->fetchAll("level = $i AND urut LIKE '$urut.%'", array("LPAD(urut,30,'0') ASC"));
                    if ($fetch2) {
                        $fetch2 = $fetch2->toArray();
                        foreach ($fetch2 as $k2 => $v2) {
                            $v2['total'] = 0;
                            $arrayInsert[] = $v2;
                            if ($i != $this->depth && $v2['hd'] == 'Header') {
                                $arrayInsert[] = array(
                                    "level" => $i,
                                    "totalfor" => $v2['urut'],
                                    "grandtotal" => 0,
                                    "text" => "<b>Total " . $v2['coa_nama'] . "</b>"
                                );
                                $jumTotal++;
                            }
                        }
                    }
                    $this->arrayPL = QDC_Common_Array::factory()->insert(($k + $jumInsert), $arrayInsert, $this->arrayPL);

                    $jumInsert += count($fetch2) + $jumTotal;
                }
            }
        }

        $this->arrayPL = $this->fillProfitLoss($this->arrayPL);
        $this->arrayPL = $this->arrangeProfitLoss($this->arrayPL);

        if ($this->number_format) {
            $this->arrayPL = $this->numberFormat($this->arrayPL);
        }
        return $this->arrayPL;
    }

    private function numberFormat($array = array()) {
        foreach ($array as $k => $v) {
            $minus = '';
            $minus2 = '';
            if ($v['total'] != '') {
                if (is_numeric($v['total'])) {
                    if ($v['total'] < 0) { //Minus, kasi tanda "(x)"..
                        $minus = '(';
                        $minus2 = ")";
                        $v['total'] = -1 * $v['total'];
                    }
                    $array[$k]['total'] = $minus . number_format($v['total'], 2) . $minus2;
                }
            } else {
                if ($v['grandtotal'] < 0) { //Minus, kasi tanda "(x)"..
                    $minus = '(';
                    $minus2 = ")";
                    $v['grandtotal'] = -1 * $v['grandtotal'];
                }
                $array[$k]['grandtotal'] = $minus . number_format($v['grandtotal'], 2) . $minus2;
            }
        }

        return $array;
    }
    
    /*
     *  format numerik berdasarkan single paramater
     */
    
    public function numberFormatByValue($value) {

        if ($value < 0)
            $value = '(' . number_format($value, 2) . ')';
        else
            $value = number_format($value, 2);

        return str_replace("-", "", $value);
    }

    /**
     * @param array $array, array yang dicari
     * @param $params, parameter yang menjadi kondisi
     * @return array
     *
     * Fungsi untuk mencari array dengan isi tertentu
     */
    private function searchArray($array = array(), $params) {
        foreach ($params as $k => $v) {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $newArray = array();
        foreach ($array as $k => $v) {
            if ($v['level'] == $level) {
                $newArray[$k] = $v;
            }
        }

        return $newArray;
    }

    private function fillProfitLoss($array = array(), $fromArray = false, $arrayRL = array()) {
        if (count($array) == 0)
            return false;

        $arrayTot = array();

        foreach ($array as $k => $v) {
            $coaKode = $v['coa_kode'];
            if (!$coaKode) {
                //Array penampung total dari masing2 header COA...
                $arrayTot[] = $v;
                continue;
            }
            $urut = $v['urut'];
            $level = $v['level'];
            $totalDetail = 0;
            $total = 0;
            if (!$fromArray) {
                $fetch = $this->FINANCE->AccountingSaldoRL->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                if ($fetch) {
                    $total = floatval($fetch['total']);
                }
            } else {
                $total = floatval($arrayRL[$coaKode]['total']);
            }


            $array[$k]['total'] = $total + $totalDetail;

//            if ($v['coa_kode'] != '' && ($v['urut'] == '1.6' || strpos($v['urut'],'1.6.') !== false)) //TODO: Another hardcode .. please fix it... T_T
//            {
//                $array[$k]['total'] = -1 * $array[$k]['total'];
//            }
//            $arrayTot[$urut] = $total + $totalDetail;
        }

        //Pengisian untuk total masing2 header
        //TODO : masih mbulet bin ribet... cari cara lain gimana biar lebih efesien @_@

        $levelHeader = 0;
        foreach ($arrayTot as $k => $v) {
            if ($v['totalfor'] != '') {
                $urut = $v['totalfor'];
                $level = $v['level'];

                if ($levelHeader < $level)
                    $levelHeader = $level;
                $jum = 0;
                foreach ($array as $k2 => $v2) {
                    if ($v2['coa_kode'] != '') {
                        if (strpos($v2['urut'], $urut . ".") !== false && $level == ($v2['level'] - 1)) {
                            $jum += floatval($v2['total']);
                        }
                    }
                }

                $arrayTot[$k]['grandtotal'] = $jum;
            }
        }

        $levelHeader--;

        if ($levelHeader > 0) {
            for ($i = $levelHeader; $i >= 1; $i--) {
                foreach ($arrayTot as $k => $v) {
                    if ($v['level'] == $i) {
                        $levelAnak = $i + 1;
                        $urutAnak = $v['totalfor'] . ".";

                        $jum = $this->sumArray($arrayTot, $levelAnak, $urutAnak);
                        $arrayTot[$k]['grandtotal'] = floatval($arrayTot[$k]['grandtotal']) + $jum;
                    }
                }
            }
        }

        foreach ($arrayTot as $k => $v) {
            $urut = $v['totalfor'];
            $level = $v['level'];
            foreach ($array as $k2 => $v2) {
                if ($v2['totalfor'] == $urut && $v2['level'] == $level) {
                    $array[$k2]['grandtotal'] = $v['grandtotal'];
                }
            }
        }

        return $array;
    }

    private function sumArray($array = array(), $level = 0, $urut = '') {
        $jum = 0;
        foreach ($array as $k => $v) {
            if ($v['level'] == $level && strpos($v['totalfor'], $urut) !== false)
                $jum += floatval($v['grandtotal']);
        }

        return $jum;
    }

    /**
     * @param array $array
     * @return array|bool
     *
     * Fungsi buat menyisipkan total Gross Profit, Operating Profit, & Net Profit / loss
     *
     */
    private function arrangeProfitLoss($array = array(), $fromArray = false, $arrayRL = array()) {
        if (count($array) == 0)
            return false;

        $newArray = array();

        $indeksPotong = $this->getIndeksPotongTotalCoa($array, '2', 1, false, false);

        if (!$fromArray) {
            $totGrossProfit = $this->getTotalGrossProfit(array(
                "perBulan" => $this->month,
                "perTahun" => $this->year
            ));
        } else {
            $totGrossProfit = $this->getTotalGrossProfit(array(
                "fromArray" => true,
                "arrayData" => $arrayRL
            ));
        }


        $grossProfit[] = array(
            "text" => "<b>Gross Profit</b>",
            "grandtotal" => $totGrossProfit
        );

        $newArray = QDC_Common_Array::factory()->insert($indeksPotong, $grossProfit, $array);

        $indeksPotong = $this->getIndeksPotongTotalCoa($array, '3', 1, false, true);
        if (!$fromArray) {
            $totOperatingProfit = $this->getTotalOperatingProfit(array(
                "perBulan" => $this->month,
                "perTahun" => $this->year
            ));
        } else {
            $totOperatingProfit = $this->getTotalOperatingProfit(array(
                "fromArray" => true,
                "arrayData" => $arrayRL
            ));
        }

        $operatingProfit[] = array(
            "text" => "<b>Operating Profit</b>",
            "grandtotal" => $totOperatingProfit
        );

        $newArray = QDC_Common_Array::factory()->insert($indeksPotong, $operatingProfit, $newArray);

        //Net Profit/Loss = Total Operating Profit + Total Other Income/Expenses
        $totGeneralAdmExpenses = $this->getTotalCoa($newArray, '4');
        $totNetProfit = $totGeneralAdmExpenses + $totOperatingProfit;
        $netProfit[] = array(
            "text" => "<b>Net Profit / (Loss)</b>",
            "grandtotal" => $totNetProfit
        );

        $newArray = QDC_Common_Array::factory()->merge(array($newArray, $netProfit));

        $newArray = $this->formatCoa($newArray);

        return $newArray;
    }

    public function getProfitLoss($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if ($perBulan == '' || $perTahun == '')
            return false;

        /*  Profit/Loss
         *  Operating Profit + Other Income/Expenses
         */

        $operatingProfit = $this->getTotalOperatingProfit($params);
        $otherIncomeExpense = $this->getAllSaldo(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "type" => "otherIncomeExpense"
        ));

        $profitLoss = $operatingProfit + $otherIncomeExpense;
        return $profitLoss;
    }

    public function getAllSaldo($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
            
        if (!$total_already_count) {

            if (!$fromArray) {
                if (($perBulan == '' || $perTahun == '' || $type == ''))
                    return false;

                if ($coaKode != '')
                    $where = " AND coa_kode = '$coaKode'";

                $type = $this->FINANCE->AccountingSaldoRL->getLimitCoa($type);
                if (!$type)
                    return 0;

                $saldo = $this->FINANCE->AccountingSaldoRL->fetchAll("coa_kode LIKE '$type%' AND periode = '$perBulan' AND tahun = '$perTahun' $where");
                $total = 0;
                if ($saldo) {
                    $saldo = $saldo->toArray();
                    foreach ($saldo as $k => $v) {
                        $total += floatval($v['total']);
                    }
                }
            } else {
                $type = $this->FINANCE->AccountingSaldoRL->getLimitCoa($type);
                if (!$type)
                    return 0;

                $total = 0;
                $saldo = array();
                foreach ($arrayData as $key => $val) {
                    if (substr($key, 0, 1) == $type) {
                        $saldo[] = $val;
                        $total += floatval($val['total']);
                    }
                }
            }
        } else {
            
            if (!$total_from_array) {
                                
                if (($perBulan == '' || $perTahun == '' || $type == ''))
                    return false;

                $type = $this->FINANCE->AccountingSaldoRL->getLimitCoa($type);
                if (!$type)
                    return 0;

                $select = $this->FINANCE->AccountingSaldoRL->db->select()
                        ->from(array("a" => $this->FINANCE->AccountingSaldoRL->name()), array(
                            "a.total"
                        ))
                        ->joinLeft(array("b" => $this->FINANCE->LayoutProfitloss->name()), "a.coa_kode = b.coa_kode")
                        ->order(array("b.urut"));
                if ($perTahun)
                    $select->where("tahun = '$perTahun'");
                if ($perBulan)
                    $select->where("periode = '$perBulan'");

                $select->where("a.coa_kode like '$type%'");
                $select->where("b.level = 1 and b.hd = 'Header'");

                $total = $this->FINANCE->AccountingSaldoRL->db->fetchRow($select);

                $total = $total['total'];
               
            }else {
                if ($type == '')
                    return false;
               
                $type = $this->FINANCE->AccountingSaldoRL->getLimitCoa($type);
                
                $coaKode = $this->FINANCE->LayoutProfitloss->fetchRow("coa_kode like '$type%' and hd ='Header' and level = 1")->toArray();
                $total = 0;
             
                foreach ($arrayData as $key => $val) {
                    if ($coaKode['coa_kode'] == $val['coa_kode']) {
                        $total = floatval($val['total']);                               
                    }
                    
                }
            }
        }


        if ($returnAll != '')
            return $saldo;
        else
            return $total;
    }

    public function getTotalGrossProfit($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if (($perBulan == '' || $perTahun == '') && !$fromArray)
            return 0;

        //Gross Profit == Income - Cost Of Sales
        if (!$total_already_count) {
            if (!$fromArray) {
                $income = $this->getAllSaldo(array(
                    "perBulan" => $perBulan,
                    "perTahun" => $perTahun,
                    "type" => "income"
                ));
            } else {
                $income = $this->getAllSaldo(array(
                    "fromArray" => true,
                    "arrayData" => $arrayData,
                    "type" => "income"
                ));
            }
            if (!$fromArray) {
                $costOfSales = $this->getAllSaldo(array(
                    "perBulan" => $perBulan,
                    "perTahun" => $perTahun,
                    "type" => "costOfSales"
                ));
            } else {
                $costOfSales = $this->getAllSaldo(array(
                    "fromArray" => true,
                    "arrayData" => $arrayData,
                    "type" => "costOfSales"
                ));
            }
        } else {
            
            $income = $this->getAllSaldo(array(
                "perBulan" => $perBulan,
                "perTahun" => $perTahun,
                "type" => "income",
                "total_already_count" => $total_already_count,
                "total_from_array" => $total_from_array,
                "arrayData" => $arrayData
            ));
            $costOfSales = $this->getAllSaldo(array(
                "perBulan" => $perBulan,
                "perTahun" => $perTahun,
                "type" => "costOfSales",
                "total_already_count" => $total_already_count,
                "total_from_array" => $total_from_array,
                "arrayData" => $arrayData
            ));
        }

        $grossProfit = $income - $costOfSales;

        return $grossProfit;
    }

    public function getTotalOperatingProfit($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if (($perBulan == '' || $perTahun == '') && !$fromArray)
            return 0;

        $grossProfit = $this->getTotalGrossProfit($params);

        //Operation Profit = Gross Profit - General & Adm Expenses
        if (!$total_already_count) {
            if (!$fromArray) {
                $generalAdmExpense = $this->getAllSaldo(array(
                    "perBulan" => $perBulan,
                    "perTahun" => $perTahun,
                    "type" => "generalAdmExpense"
                ));
            } else {
                $generalAdmExpense = $this->getAllSaldo(array(
                    "fromArray" => true,
                    "arrayData" => $arrayData,
                    "type" => "generalAdmExpense"
                ));
            }
        } else {
            $generalAdmExpense = $this->getAllSaldo(array(
                "perBulan" => $perBulan,
                "perTahun" => $perTahun,
                "type" => "generalAdmExpense",
                "total_already_count" => $total_already_count,
                "total_from_array" => $total_from_array,
                "arrayData" => $arrayData
            ));
        }
        $operationProfit = $grossProfit - $generalAdmExpense;

        return $operationProfit;
    }
    
    //menghitung gross profit, operating profit, dan Net profit
    //menghitung profit berdasarkan data array atau periode
    //$total_already_count sebagai flag kalau total dr masing2 coa telah terhitung didatabase/tidak
    //$total_from_array sebagai flag kalau total berasal dari array/tidak
    //Total akan diletakkan diakhir layout array
    public function getAllTotalGrossOperatingNet($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if (($perBulan == '' || $perTahun == '') && !$dataArray)
            return 0;

        //grossprofit

        $grossprofit = $this->getTotalGrossProfit(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "total_already_count" => $total_already_count,
            "total_from_array" => $total_from_array,
            "arrayData" => $dataArray,
            "fromArray"=>$fromArray
        ));
               
       
        $dataArray[] = array(
            "coa_kode" => "",
            "coa_nama" => "Gross Profit",
            "grandtotal" => $this->numberFormatByValue($grossprofit),
            "hd" => "Header"
        );
        

        //Operating Profit
        $operatingprofit = $this->getTotalOperatingProfit(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "total_already_count" => $total_already_count,
            "total_from_array" => $total_from_array,
            "arrayData" => $dataArray,
            "fromArray"=>$fromArray
        ));
      
        $dataArray[] = array(
            "coa_kode" => "",
            "coa_nama" => "Operating Profit",
            "grandtotal" => $this->numberFormatByValue($operatingprofit),
            "hd" => "Header"
        );

        //otherincome
        $otherincome = $this->getTotalOtherIncome(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "total_already_count" => $total_already_count,
            "total_from_array" => $total_from_array,
            "arrayData" => $dataArray,
            "fromArray"=>$fromArray
        ));


        $netprofit = $otherincome + $operatingprofit;

        $dataArray[] = array(
            "coa_kode" => "",
            "coa_nama" => "Net Profit/(Loss)",
            "grandtotal" => $this->numberFormatByValue($netprofit),
            "hd" => "Header"
        );

        return $dataArray;
    }

    public function getTotalOtherIncome($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        if (($perBulan == '' || $perTahun == '')&& !$fromArray)
            return 0;


        $totalnetprofit = $this->getAllSaldo(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "type" => "otherIncomeExpense",
            "total_already_count" => $total_already_count,
            "total_from_array" => $total_from_array,
            "arrayData" => $arrayData
        ));

//        $operatingprofit = $this->getTotalOperatingProfit($params);
//        $totalnetprofit = $otherincome + $operatingprofit;

        return $totalnetprofit;
    }

    private function getIndeksPotongCoa($array = array(), $type = '', $level = 0, $before = true, $after = false) {
        $tambah = 0;
        if ($before)
            $tambah = -1;
        if ($after)
            $tambah = 1;
        foreach ($array as $k => $v) {
            if ($v['level'] == $level) {
                if ($v['tipe'] != '') {
                    if ($v['tipe'] == $type) {
                        $indeksPotong = $k + $tambah;
                        break;
                    }
                }
            }
        }

        return $indeksPotong;
    }

    private function getIndeksPotongTotalCoa($array = array(), $totalfor = '', $level = 0, $before = true, $after = false) {
        $tambah = 0;
        if ($before)
            $tambah = -1;
        if ($after)
            $tambah = 1;
        foreach ($array as $k => $v) {
            if ($v['level'] == $level) {
                if ($v['totalfor'] != '') {
                    if ($v['totalfor'] == $totalfor) {
                        $indeksPotong = $k + $tambah;
                        break;
                    }
                }
            }
        }

        return $indeksPotong;
    }

    public function getTotalCoa($array = array(), $totalfor = '') {
        $total = 0;
        foreach ($array as $k => $v) {
            if ($v['totalfor'] != '') {
                if ($v['totalfor'] == $totalfor) {
                    $total = $v['grandtotal'];
                    break;
                }
            }
        }

        return $total;
    }

    private function formatCoa($array = array()) {
        foreach ($array as $k => $v) {
            if ($v['coa_kode'] != '' && ($v['level'] == 1 || $v['hd'] == 'Header')) {
                $array[$k]['coa_kode'] = '<b>' . $v['coa_kode'] . '</b>';
                $array[$k]['coa_nama'] = '<b>' . $v['coa_nama'] . '</b>';
            }
        }

        return $array;
    }

    public function generateProfitLossFromArray($array = array()) {
        for ($i = 1; $i <= $this->depth; $i++) {
            if ($i == 1) {
                $fetch = $this->FINANCE->LayoutProfitloss->fetchAll("hd = 'Header' AND level = 1", array("urut ASC"));
                if ($fetch) {
                    $fetch = $fetch->toArray();
                    foreach ($fetch as $k => $v) {
                        $v['total'] = 0;
                        $this->arrayPL[] = $v;
                        $this->arrayPL[] = array(
                            "level" => $i,
                            "totalfor" => $v['urut'],
                            "grandtotal" => 0,
                            "text" => "<b>Total " . $v['coa_nama'] . "</b>"
                        );
                    }
                }
            } else {
                $prevArray = $this->searchArray($this->arrayPL, array("level" => $i - 1));
                $jumInsert = 0;
                foreach ($prevArray as $k => $v) {
                    $urut = $v['urut'];
                    $arrayInsert = array();
                    $jumTotal = 0;
                    $fetch2 = $this->FINANCE->LayoutProfitloss->fetchAll("level = $i AND urut LIKE '$urut.%'", array("LPAD(urut,30,'0') ASC"));
                    if ($fetch2) {
                        $fetch2 = $fetch2->toArray();
                        foreach ($fetch2 as $k2 => $v2) {
                            $v2['total'] = 0;
                            $arrayInsert[] = $v2;
                            if ($i != $this->depth && $v2['hd'] == 'Header') {
                                $arrayInsert[] = array(
                                    "level" => $i,
                                    "totalfor" => $v2['urut'],
                                    "grandtotal" => 0,
                                    "text" => "<b>Total " . $v2['coa_nama'] . "</b>"
                                );
                                $jumTotal++;
                            }
                        }
                    }
                    $this->arrayPL = QDC_Common_Array::factory()->insert(($k + $jumInsert), $arrayInsert, $this->arrayPL);

                    $jumInsert += count($fetch2) + $jumTotal;
                }
            }
        }

        $this->arrayPL = $this->fillProfitLoss($this->arrayPL, true, $array);
        $this->arrayPL = $this->arrangeProfitLoss($this->arrayPL, true, $array);

        if ($this->number_format) {
            $this->arrayPL = $this->numberFormat($this->arrayPL);
        }

        if ($this->no_html_tags) {
            foreach ($this->arrayPL as $k => $v) {
                if ($v['coa_nama'] != '') {
                    $tmp = str_replace("<b>", "", $v['coa_nama']);
                    $tmp = str_replace("</b>", "", $tmp);
                    $this->arrayPL[$k]['coa_nama'] = $tmp;
                }
                if ($v['coa_kode'] != '') {
                    $tmp = str_replace("<b>", "", $v['coa_kode']);
                    $tmp = str_replace("</b>", "", $tmp);
                    $this->arrayPL[$k]['coa_kode'] = $tmp;
                }
                if ($v['text'] != '') {
                    $tmp = str_replace("<b>", "", $v['text']);
                    $tmp = str_replace("</b>", "", $tmp);
                    $this->arrayPL[$k]['text'] = $tmp;
                }
            }
        }

        return $this->arrayPL;
    }

}

?>