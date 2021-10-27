<?php

Class QDC_Finance_BalanceSheet {

    public $FINANCE;
    private $db;
    private $models;
    private $arrayBL; //Array balance sheet
    private $margin = '&nbsp;';
    private $blank = '';
    private $marginSpaces = ' ';
    private $month;
    private $year;

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
            "LayoutBalanceSheet",
            "MasterCoa",
            "AccountingSaldoRLSummary"
        );
        $this->FINANCE = $this->getConfig($this->models);
        $this->arrayBL = array();

        $this->depth = $this->maxLevel();

        $this->db = Zend_Registry::get("db");
    }

    public static function getConfig($models = array()) {
        $reps = QDC_Model_Finance::init($models);
        return $reps;
    }

    /**
     * @static
     * @param $params
     * @return QDC_Finance_BalanceSheet
     *
     * Method factory dipanggil apabila QDC_FINANCE_BALANCESHEET di inisialisasi secara statik
     */
    public static function factory($params = array()) {
        return new self($params);
    }

    public function maxLevel() {
        $sql = "
            SELECT
                MAX(level) as maxlevel
            FROM
                master_layout_neraca
        ";

        $fetch = $this->FINANCE->db->query($sql);

        $fetch = $fetch->fetch();

        return intval($fetch['maxlevel']);
    }

    public function generateBalanceSheet() {
        $this->arrayBL = $this->getLayoutBalanceSheet();

        if ($this->preview == true) {
            $where = null;
            if ($this->start_date) {
                if ($this->end_date) {
                    $where = "tgl BETWEEN '{$this->start_date} 00:00:00' AND '{$this->end_date} 23:59:59'";
                } else {
                    $where = "tgl BETWEEN '{$this->start_date} 00:00:00' AND '{$this->start_date} 23:59:59'";
                }
            }
            QDC_Finance_Jurnal::factory(array(
                "useShape" => true,
                "useTempTable" => true,
                "tempTableName" => "all_jurnal"
            ))->getAllJurnal($where);
        }
        if ($this->preview == true)
            $this->arrayBL = $this->fillBalanceSheetPreview($this->arrayBL);
        else
            $this->arrayBL = $this->fillBalanceSheet($this->arrayBL);

        $this->arrayBL = $this->arrangeBalanceSheet($this->arrayBL);
        $this->arrayBL = $this->filterLevel($this->arrayBL, $this->leveldepth);

        if ($this->number_format) {
            $this->arrayBL = $this->numberFormat($this->arrayBL);
        }
        return $this->arrayBL;
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
                if (is_numeric($v['total_last'])) {
                    if ($v['total_last'] < 0) { //Minus, kasi tanda "(x)"..
                        $minus = '(';
                        $minus2 = ")";
                        $v['total_last'] = -1 * $v['total_last'];
                    }
                    $array[$k]['total_last'] = $minus . number_format($v['total_last'], 2) . $minus2;
                }
                if (is_numeric($v['total_preview'])) {
                    if ($v['total_preview'] < 0) { //Minus, kasi tanda "(x)"..
                        $minus = '(';
                        $minus2 = ")";
                        $v['total_preview'] = -1 * $v['total_preview'];
                    }
                    $array[$k]['total_preview'] = $minus . number_format($v['total_preview'], 2) . $minus2;
                }
            } else {
                if ($v['grandtotal'] < 0) { //Minus, kasi tanda "(x)"..
                    $minus = '(';
                    $minus2 = ")";
                    $v['grandtotal'] = -1 * $v['grandtotal'];
                }
                $array[$k]['grandtotal'] = $minus . number_format($v['grandtotal'], 2) . $minus2;

                if ($v['grandtotal_last'] < 0) { //Minus, kasi tanda "(x)"..
                    $minus = '(';
                    $minus2 = ")";
                    $v['grandtotal_last'] = -1 * $v['grandtotal_last'];
                }
                $array[$k]['grandtotal_last'] = $minus . number_format($v['grandtotal_last'], 2) . $minus2;

                if ($v['grandtotal_preview'] < 0) { //Minus, kasi tanda "(x)"..
                    $minus = '(';
                    $minus2 = ")";
                    $v['grandtotal_preview'] = -1 * $v['grandtotal_preview'];
                }
                $array[$k]['grandtotal_preview'] = $minus . number_format($v['grandtotal_preview'], 2) . $minus2;
            }
        }

        return $array;
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

    /**
     * @param int $indeksPotong, indeks array yang akan disisipkan
     * @param array $dest @param array $sourceArray, array yang akan disisipkan
     * Array, array tujuan
     * @return array
     *
     * Fungsi untuk menggabungkan 2 buah array dengan cara menyisipkan array ditengah2 indeks yang dituju
     * contoh: array1 : 0,1,2 akan disisipkan ke array2 : x,y,z setelah indeks y
     * hasilnya:  x,y,0,1,2,z
     */
    private function mergeArray($indeksPotong = 0, $sourceArray = array(), $destArray = array()) {
        $jumlahArrayAsli = count($destArray);
        $jumlahArrayInsert = count($sourceArray);

        $arrayTemp1 = array();
        $arrayTemp2 = array();

        //Ambil array sebelum indeks yang akan disisipkan
        for ($i = 0; $i <= $indeksPotong; $i++) {
            $arrayTemp1[$i] = $destArray[$i];
        }
        if ($indeksPotong < ($jumlahArrayAsli - 1)) {
            //Ambil array sesudah indeks yang akan disisipkan
            for ($i = ($indeksPotong + 1); $i < $jumlahArrayAsli; $i++) {
                $arrayTemp2[] = $destArray[$i];
            }
        }

        //gabung array sebelum dan sesudah dengan array yg disisipkan
        $newArray = array_merge($arrayTemp1, $sourceArray);
        if (count($arrayTemp2) > 0)
            $newArray = array_merge($newArray, $arrayTemp2);

        return $newArray;
    }

    private function fillBalanceSheet($array = array()) {
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
            $totalDetail_conversi = 0;
            $total = 0;
            $total_conversi = 0;
            if ($level == $this->leveldepth) {
                $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                if ($fetch) {
                    if ($fetch['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                        $total = floatval($fetch['total']);
                        $total_conversi = floatval($fetch['total'] * $fetch['rateidr']);
                    } else
                        $total = floatval($fetch['total']);
                }

                if ($v['hd'] == 'Header') {
                    /*
                     * todo rubah ke bentuk zend db select
                     * known issue di zend db select : hasil tidak menampilkan 1 row total, tapi seluruh row
                     * */

                    $select = $this->db->select()
                            ->from(array($this->FINANCE->LayoutBalanceSheet->__name()))
                            ->where("urut LIKE '$urut.%'");

                    $select = $this->db->select()
                            ->from(array("a" => $select))
                            ->joinLeft(array("b" => $this->FINANCE->AccountingSaldoCoa->__name()), "a.coa_kode=b.coa_kode", array(
                                "total" => new Zend_Db_Expr("SUM(b.total)"),
                                "rateidr"))
                            ->where("periode = ?", $this->month)
                            ->where("tahun = ?", $this->year);
                    ;

//                    $sql = "
//                    SELECT SUM(b.total) AS total
//                    FROM master_layout_neraca a
//                    LEFT JOIN accounting_saldo_coa b
//                    ON a.coa_kode = b.coa_kode
//                    WHERE a.urut LIKE '$urut.%'
//                    ";
//                    $select = $this->FINANCE->db->query($sql);
//                    $detail = $select->fetch();

                    $detail = $this->FINANCE->db->fetchRow($select);
                    if ($detail) {
                        if ($detail['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                            $totalDetail = floatval($detail['total']);
                            $totalDetail_conversi = floatval($detail['total'] * $fetch['rateidr']);
                        } else
                            $totalDetail = floatval($detail['total']);
                    }
                }
            } else {
                if ($v['hd'] == 'Detail') {

                    $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                    if ($fetch) {
                        if ($fetch['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                            $total = floatval($fetch['total']);
                            $total_conversi = floatval($fetch['total'] * $fetch['rateidr']);
                        } else
                            $total = floatval($fetch['total']);
                    }
                }
            }
            $array[$k]['total'] = $total + $totalDetail;
            $array[$k]['total_conversi'] = $total_conversi + $totalDetail_conversi;

            if ($v['coa_kode'] != '' && ($v['urut'] == '1.6' || strpos($v['urut'], '1.6.') !== false)) { //TODO: Another hardcode .. please fix it... T_T
                if ($array[$k]['total'] > 0)
                    $array[$k]['total'] = -1 * $array[$k]['total'];
            }


            if ($v['coa_kode'] == '3-3000') {
                $array[$k]['total'] = $this->FINANCE->AccountingSaldoRLSummary->getSaldo($this->month, $this->year);
//                $array[$k]['total'] = QDC_Finance_ProfitLoss::factory()->getProfitLoss(array(
//                    "perBulan" => $this->month,
//                    "perTahun" => $this->year
//                ));
//                 $params = array(
//                "year" => $this->year,
//                "month" => $this->month,
//                "number_format" => true
//            );
//                $data = QDC_Finance_ProfitLoss::factory($params)->generateProfitLoss();
            }

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
                $jumusd = 0;
                foreach ($array as $k2 => $v2) {
                    if ($v2['coa_kode'] != '') {
                        if (strpos($v2['urut'], $urut . ".") !== false && $level == ($v2['level'] - 1)) {
                            if (strpos($v2['coa_nama'], 'Exchang') > 0)
                                continue;
                            if ($v2['total_conversi'] == 0)
                                $jum += floatval($v2['total']);
                            else
                                $jum += floatval($v2['total_conversi']);

                            if ($v2['val_kode'] != 'IDR' || !$v2['val_kode'])
                                $jumusd+=floatval($v2['total']);
                        }
                    }
                }

                $arrayTot[$k]['grandtotal'] = $jum;
                $arrayTot[$k]['grandtotal_usd'] = $jumusd;
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
                        $arrayTot[$k]['grandtotal_usd'] = floatval($arrayTot[$k]['grandtotal_usd']);
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
                    $array[$k2]['grandtotal_usd'] = $v['grandtotal_usd'];
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

    private function sumArrayLast($array = array(), $level = 0, $urut = '') {
        $jum = 0;
        foreach ($array as $k => $v) {
            if ($v['level'] == $level && strpos($v['totalfor'], $urut) !== false)
                $jum += floatval($v['grandtotal_last']);
        }

        return $jum;
    }

    private function sumArrayPreview($array = array(), $level = 0, $urut = '') {
        $jum = 0;
        foreach ($array as $k => $v) {
            if ($v['level'] == $level && strpos($v['totalfor'], $urut) !== false)
                $jum += floatval($v['grandtotal_preview']);
        }

        return $jum;
    }

    /**
     * @param array $array
     * @return array|bool
     *
     * Fungsi buat menentukan yg mana Assets, Liabilities atau Equity
     *
     * Rumus : Net Assets = total tipe assets - total tipe Liabilities
     */
    private function arrangeBalanceSheet($array = array()) {
        if (count($array) == 0)
            return false;

        $newArray = array();

        //Ambil yg header level 1 saja...
        foreach ($array as $k => $v) {
            if ($v['level'] == 1) {
                if ($v['tipe'] != '') {
                    if ($v['tipe'] == 'Equity') { //Terpaksa hardcode, mudah2an tipe COA tidak berubah
                        $indeksPotong = $k - 1;
                    }
                    if ($v['tipe'] == 'Asset') {
                        $urutAssets = $v['urut'];
                    }
                    if ($v['tipe'] == 'Liability') {
                        $urutLiability = $v['urut'];
                    }
                } else {
                    if ($urutAssets != '') {
                        if ($v['totalfor'] == $urutAssets)
                            $jumAssets = floatval($v['grandtotal']);
                    }
                    if ($urutLiability != '') {
                        if ($v['totalfor'] == $urutLiability)
                            $jumLiability = floatval($v['grandtotal']);
                    }
                }
            }
        }

        //Net Assets
        $netAssets[] = array(
            "text" => "<b>Net Assets</b>",
            "grandtotal" => ($jumAssets - $jumLiability)
        );

        $newArray = $this->mergeArray($indeksPotong, $netAssets, $array);

        //Saldo RL
//        $totalSaldoRL = $this->FINANCE->AccountingSaldoRLSummary->getSaldo($this->month, $this->year);
//        $saldoRL[] = array(
//            "text" => "<b>Net Profit / (Loss)</b>",
//            "grandtotal" => floatval($totalSaldoRL)
//        );
//        $indeksPotongRL = count($newArray) - 1;
//        $newArray = $this->mergeArray($indeksPotongRL, $saldoRL, $newArray);
        //Kasih margin untuk setiap header/detail bedasarkan level na
        foreach ($newArray as $k => $v) {
            if ($v['level'] > 1) {
                if ($v['coa_nama'])
                    $newArray[$k]['coa_nama'] = $this->makeMargin($v['level']) . $v['coa_nama'];
                else
                    $newArray[$k]['text'] = $this->makeMargin($v['level']) . $v['text'];
            }
        }

        return $newArray;
    }

    private function makeMargin($level = 0) {
        if (!$this->spaces)
            $m = $this->margin;
        else
            $m = $this->marginSpaces;

        $margin = $m;
        for ($i = 1; $i <= $level; $i++) {
            $margin .= $m . $m;
        }

        return $margin;
    }

    private function filterLevel($array = array(), $level = 0) {
        foreach ($array as $k => $v) {
            if (intval($v['level']) > $level)
                unset($array[$k]);
        }

        if ($level == 1) {
            foreach ($array as $k => $v) {
                if ($v['text'] != '' && strpos($v['text'], 'Net Assets') === false) {
                    if (strpos($v['text'], 'Total Assets') !== false)
                        $jumAssets = $v['grandtotal'];
                    if (strpos($v['text'], 'Total Liabilities') !== false)
                        $jumLiability = $v['grandtotal'];
                    if (strpos($v['text'], 'Total Equity') !== false)
                        $jumEquity = $v['grandtotal'];

                    unset($array[$k]);
                }
            }

            foreach ($array as $k => $v) {
                if ($v['coa_nama'] == 'Assets')
                    $array[$k]['total'] = $jumAssets;
                if ($v['coa_nama'] == 'Liabilities')
                    $array[$k]['total'] = $jumLiability;
                if ($v['coa_nama'] == 'Equity')
                    $array[$k]['total'] = $jumEquity;
            }
        }
        else {
            foreach ($array as $k => $v) {
                if ($v['hd'] == 'Header' && $v['total'] == 0) {
                    $array[$k]['total'] = ($this->spaces) ? $this->blank : $this->margin;
                }
            }
        }

        return $array;
    }

    public function getLayoutBalanceSheet() {
        $theArray = array();
        for ($i = 1; $i <= $this->leveldepth; $i++) {
            if ($i == 1) {
                $fetch = $this->FINANCE->LayoutBalanceSheet->fetchAll("hd = 'Header' AND level = 1", array("urut ASC"));
                if ($fetch) {
                    $fetch = $fetch->toArray();
                    foreach ($fetch as $k => $v) {
                        $coaKode = $v['coa_kode'];
                        $valuta = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' and val_kode != ''");
                        if ($valuta) {
                            $valuta = $valuta->toArray();
                            $v['val_kode'] = $valuta['val_kode'];
                        }
                        $v['total'] = 0;
                        $theArray[] = $v;
                        $theArray[] = array(
                            "level" => $i,
                            "totalfor" => $v['urut'],
                            "grandtotal" => 0,
                            "text" => "<b>Total " . $v['coa_nama'] . "</b>"
                        );
                    }
                }
            } else {
                $prevArray = $this->searchArray($theArray, array("level" => $i - 1));
                $jumInsert = 0;
                foreach ($prevArray as $k => $v) {
                    $urut = $v['urut'];
                    $arrayInsert = array();
                    $jumTotal = 0;
                    $fetch2 = $this->FINANCE->LayoutBalanceSheet->fetchAll("level = $i AND urut LIKE '$urut.%'", array("LPAD(urut, 30, '0') ASC"));
                    if ($fetch2) {
                        $fetch2 = $fetch2->toArray();
                        foreach ($fetch2 as $k2 => $v2) {
                            $v2['total'] = 0;
                            $coaKode = $v2['coa_kode'];
                            $valuta = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' and val_kode != ''");
                            if ($valuta) {
                                $valuta = $valuta->toArray();
                                $v2['val_kode'] = $valuta['val_kode'];
                            }


                            $arrayInsert[] = $v2;
                            if ($i != $this->leveldepth && $v2['hd'] == 'Header') {
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
                    $theArray = $this->mergeArray(($k + $jumInsert), $arrayInsert, $theArray);

                    $jumInsert += count($fetch2) + $jumTotal;
                }
            }
        }

        return $theArray;
    }

    private function getTotalTempTable($where = null, $conversion = false) {
        $select = $this->FINANCE->db->select()
                ->from(array("a" => $this->FINANCE->LayoutBalanceSheet->__name()), array(
                    "coa_kode",
                    "coa_nama",
                    "dk",
                    "b.rateidr"
                ))
                ->joinLeft(array("b" => "all_jurnal"), "a.coa_kode = b.coa_kode", array(
            "debit",
            "credit"
        ));

        if ($where)
            $select = $select->where($where);

        $select = $this->FINANCE->db->select()
                ->from(array("c" => $select), array(
            "coa_kode",
            "coa_nama",
            "rateidr",
            "debit" => new Zend_Db_Expr("IF(c.debit > 0 AND c.dk = 'Credit', -1*c.debit, c.debit)"),
            "credit" => new Zend_Db_Expr("IF(c.credit > 0 AND c.dk = 'Debit', -1*c.credit, c.credit)"),
        ));

        $select = $this->FINANCE->db->select()
                ->from(array("d" => $select), array(
                    "total" => "SUM(d.debit+d.credit)"
                ))
                ->group(array("coa_kode"));

        $data = $this->FINANCE->db->fetchRow($select);
        if ($conversion)
            return ($data['total'] == '') ? 0 : floatval($data['total'] * $data['rateidr']);
        else
            return ($data['total'] == '') ? 0 : floatval($data['total']);
    }

    private function fillBalanceSheetPreview($array = array()) {
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
            $totalDetail_conversi = 0;
            $total = 0;
            $total_conversi = 0;
            if ($level == $this->leveldepth) {
                $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                if ($fetch) {
                    if ($fetch['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                        $total = floatval($fetch['total']);
                        $total_conversi = floatval($fetch['total'] * $fetch['rateidr']);
                    } else
                        $total = floatval($fetch['total']);
                }

                if ($v['hd'] == 'Header') {
                    if ($detail['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                        $totalDetail = $this->getTotalTempTable("a.urut LIKE '%$urut.%'");
                        $totalDetail_conversi = $this->getTotalTempTable("a.urut LIKE '%$urut.%'", true);
                    } else
                        $totalDetail = $this->getTotalTempTable("a.urut LIKE '%$urut.%'");
                    //                    $sql = "select
//                        d.coa_kode,d.coa_nama,SUM(d.debit),SUM(d.credit),SUM((d.debit+d.credit)) as total
//                        from (
//                        select c.coa_kode,c.coa_nama,IF(c.debit > 0 AND c.dk = 'Credit',-1*c.debit,c.debit) as debit,IF(c.credit > 0 AND c.dk = 'Debit',-1*c.credit,c.credit) as credit,c.dk FROM (SELECT a.coa_kode,a.coa_nama,a.debit,a.credit,b.dk from accounting_close_ap a left JOIN master_layout_neraca b ON a.coa_kode = b.coa_kode WHERE b.id IS NOT NULL) c) d GROUP by d.coa_kode";
                }
            } else {
                if ($v['hd'] == 'Detail') {
                    $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                    if ($fetch) {
                        if ($fetch['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang')) {
                            $total = floatval($fetch['total']);
                            $total_conversi = floatval($fetch['total'] * $fetch['rateidr']);
                        } else
                            $total = floatval($fetch['total']);
                    }

                    $totalDetail = $this->getTotalTempTable("a.coa_kode = '$coaKode'");
                    if ($v['val_kode'] != 'IDR' && !strpos($v['coa_nama'], 'Exchang'))
                        $totalDetail_conversi = ($this->getTotalTempTable("a.coa_kode = '$coaKode'") * $v['rateidr']);
                }
            }

            $array[$k]['total'] = $total + $totalDetail;
            $array[$k]['total_conversi'] = $total_conversi + $totalDetail_conversi;

            $array[$k]['total_last'] = $total;
            $array[$k]['total_preview'] = $totalDetail;

            if ($v['coa_kode'] != '' && ($v['urut'] == '1.6' || strpos($v['urut'], '1.6.') !== false)) { //TODO: Another hardcode .. please fix it... T_T
//                $array[$k]['total'] = -1 * $array[$k]['total'];
//                $array[$k]['total_last'] = -1 * $array[$k]['total_last'];
//                $array[$k]['total_preview'] = -1 * $array[$k]['total_preview'];
            }

            if ($v['coa_kode'] == '3-3000') {
//                $array[$k]['total'] = QDC_Finance_ProfitLoss::factory()->getProfitLoss(array(
//                    "perBulan" => $this->month,
//                    "perTahun" => $this->year
//                ));
                $array[$k]['total'] = $this->FINANCE->AccountingSaldoRLSummary->getSaldo($this->month, $this->year);
            }

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
                $jumusd = $jum = $jumLast = $jumPreview = 0;
                foreach ($array as $k2 => $v2) {
                    if ($v2['coa_kode'] != '') {
                        if (strpos($v2['urut'], $urut . ".") !== false && $level == ($v2['level'] - 1)) {
                            if (strpos($v2['coa_nama'], 'Exchang') > 0)
                                continue;
                            if ($v2['total_conversi'] == 0)
                                $jum += floatval($v2['total']);
                            else
                                $jum += floatval($v2['total_conversi']);

                            if ($v2['val_kode'] != 'IDR' || !$v2['val_kode'])
                                $jumusd+=floatval($v2['total']);

                            $jumLast += floatval($v2['total_last']);
                            $jumPreview += floatval($v2['total_preview']);
                        }
                    }
                }

                $arrayTot[$k]['grandtotal'] = $jum;
                $arrayTot[$k]['grandtotal_usd'] = $jumusd;
                $arrayTot[$k]['grandtotal_last'] = $jumLast;
                $arrayTot[$k]['grandtotal_preview'] = $jumPreview;
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

//                        $jumusd = $this->sumArray($arrayTot, $levelAnak, $urutAnak);
                        $arrayTot[$k]['grandtotal_usd'] = floatval($arrayTot[$k]['grandtotal_usd']);

                        $jumLast = $this->sumArray($arrayTot, $levelAnak, $urutAnak);
                        $arrayTot[$k]['grandtotal_last'] = floatval($arrayTot[$k]['grandtotal_last']) + $jumLast;

                        $jumP = $this->sumArray($arrayTot, $levelAnak, $urutAnak);
                        $arrayTot[$k]['grandtotal_preview'] = floatval($arrayTot[$k]['grandtotal_preview']) + $jumP;
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
                    $array[$k2]['grandtotal_usd'] = $v['grandtotal_usd'];
                    $array[$k2]['grandtotal_last'] = $v['grandtotal_last'];
                    $array[$k2]['grandtotal_preview'] = $v['grandtotal_preview'];
                }
            }
        }

        return $array;
    }

}

?>