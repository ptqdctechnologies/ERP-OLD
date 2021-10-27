<?php

Class QDC_Finance_BalanceSheet
{
    public $FINANCE;
    private $models;
    private $arrayBL; //Array balance sheet

    public function __construct($params = '')
    {

        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->models = array(
            "MasterPeriode",
            "AccountingSaldoCoa",
            "LayoutBalanceSheet",
            "MasterCoa"
        );
        $this->FINANCE = $this->getConfig($this->models);
        $this->arrayBL = array();

        $this->depth = $this->maxLevel();
    }

    public static function getConfig($models=array())
    {
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
    public static function factory($params=array())
    {
        return new self($params);
    }

    private function maxLevel()
    {
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

    public function generateBalanceSheet()
    {
        for($i=1;$i<=$this->depth;$i++)
        {
            if ($i == 1)
            {
                $fetch = $this->FINANCE->LayoutBalanceSheet->fetchAll("hd = 'Header' AND level = 1",array("urut ASC"));
                if ($fetch)
                {
                    $fetch = $fetch->toArray();
                    foreach($fetch as $k => $v)
                    {
                        $v['total'] = 0;
                        $this->arrayBL[] = $v;
                        $this->arrayBL[] = array(
                            "level" => $i,
                            "totalfor" => $v['urut'],
                            "grandtotal" => 0,
                            "text" => "<b>Total " . $v['coa_nama'] . "</b>"
                        );
                    }
                }
            }
            else
            {
                $prevArray = $this->searchArray($this->arrayBL,array("level" => $i-1));
                $jumInsert = 0;
                foreach($prevArray as $k => $v)
                {
                    $urut = $v['urut'];
                    $arrayInsert = array();
                    $jumTotal = 0;
                    $fetch2 = $this->FINANCE->LayoutBalanceSheet->fetchAll("level = $i AND urut LIKE '$urut.%'",array("LPAD(urut,30,'0') ASC"));
                    if ($fetch2)
                    {
                        $fetch2 = $fetch2->toArray();
                        foreach($fetch2 as $k2 => $v2)
                        {
                            $v2['total'] = 0;
                            $arrayInsert[] = $v2;
                            if ($i != $this->depth && $v2['hd'] == 'Header')
                            {
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
                    $this->arrayBL = $this->mergeArray(($k+$jumInsert),$arrayInsert,$this->arrayBL);

                    $jumInsert += count($fetch2) + $jumTotal;
                }
            }

        }

        $this->arrayBL = $this->fillBalanceSheet($this->arrayBL);
        $this->arrayBL = $this->arrangeBalanceSheet($this->arrayBL);
        $this->arrayBL = $this->filterLevel($this->arrayBL,$this->leveldepth);

        if ($this->number_format)
        {
            $this->arrayBL = $this->numberFormat($this->arrayBL);
        }
        return $this->arrayBL;
    }

    private function numberFormat($array=array())
    {
        foreach($array as $k => $v)
        {
            $minus = '';$minus2 = '';
            if ($v['total'] != '')
            {
                if  (is_numeric($v['total']))
                {
                    if ($v['total'] < 0) //Minus, kasi tanda "(x)"..
                    {
                        $minus = '(';
                        $minus2 = ")";
                        $v['total'] = -1 * $v['total'];
                    }
                    $array[$k]['total'] = $minus . number_format($v['total'],2) . $minus2;
                }


            }
            else
            {
                if ($v['grandtotal'] < 0) //Minus, kasi tanda "(x)"..
                {
                    $minus = '(';
                    $minus2 = ")";
                    $v['grandtotal'] = -1 * $v['grandtotal'];
                }
                $array[$k]['grandtotal'] = $minus . number_format($v['grandtotal'],2). $minus2;
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
    private function searchArray($array=array(),$params)
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }
        $newArray = array();
        foreach($array as $k => $v)
        {
            if ($v['level'] == $level)
            {
                $newArray[$k] = $v;
            }
        }

        return $newArray;

    }

    /**
     * @param int $indeksPotong, indeks array yang akan disisipkan
     * @param array $dest @param array $sourceArray, array yang akan disisipkan
     *Array, array tujuan
     * @return array
     *
     * Fungsi untuk menggabungkan 2 buah array dengan cara menyisipkan array ditengah2 indeks yang dituju
     * contoh: array1 : 0,1,2 akan disisipkan ke array2 : x,y,z setelah indeks y
     * hasilnya:  x,y,0,1,2,z
     */
    private function mergeArray($indeksPotong=0,$sourceArray=array(),$destArray=array())
    {
        $jumlahArrayAsli = count($destArray);
        $jumlahArrayInsert = count($sourceArray);

        $arrayTemp1 = array();
        $arrayTemp2 = array();

        //Ambil array sebelum indeks yang akan disisipkan
        for($i=0;$i<=$indeksPotong;$i++)
        {
            $arrayTemp1[$i] = $destArray[$i];
        }
        if ($indeksPotong < ($jumlahArrayAsli-1))
        {
            //Ambil array sesudah indeks yang akan disisipkan
            for($i=($indeksPotong+1);$i<$jumlahArrayAsli;$i++)
            {
                $arrayTemp2[] = $destArray[$i];
            }
        }

        //gabung array sebelum dan sesudah dengan array yg disisipkan
        $newArray = array_merge($arrayTemp1,$sourceArray);
        if (count($arrayTemp2) > 0)
            $newArray = array_merge($newArray,$arrayTemp2);

        return $newArray;
    }

    private function fillBalanceSheet($array=array())
    {
        if (count($array) == 0)
            return false;

        $arrayTot = array();

        foreach ($array as $k => $v)
        {
            $coaKode = $v['coa_kode'];
            if (!$coaKode)
            {
                //Array penampung total dari masing2 header COA...
                $arrayTot[] = $v;
                continue;
            }
            $urut = $v['urut'];
            $level = $v['level'];
            $totalDetail = 0;
            $total = 0;
            if ($level == $this->depth)
            {
                $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                if ($fetch)
                {
                    $total = floatval($fetch['total']);
                }

                if ($v['hd'] == 'Header')
                {
                    /*
                     * todo rubah ke bentuk zend db select
                     * known issue di zend db select : hasil tidak menampilkan 1 row total, tapi seluruh row
                     * */
                    $sql = "
                    SELECT SUM(b.total) AS total
                    FROM master_layout_neraca a
                    LEFT JOIN accounting_saldo_coa b
                    ON a.coa_kode = b.coa_kode
                    WHERE a.urut LIKE '$urut.%'
                    ";
                    $select = $this->FINANCE->db->query($sql);
                    $detail = $select->fetch();
                    if ($detail)
                    {
                        $totalDetail = floatval($detail['total']);
                    }
                }
            }
            else
            {
                if ($v['hd'] == 'Detail')
                {

                    $fetch = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '$coaKode' AND periode = '{$this->month}' AND tahun = '{$this->year}'");
                    if ($fetch)
                    {
                        $total = floatval($fetch['total']);
                    }
                }
            }

            $array[$k]['total'] = $total + $totalDetail;

            if ($v['coa_kode'] != '' && ($v['urut'] == '1.6' || strpos($v['urut'],'1.6.') !== false)) //TODO: Another hardcode .. please fix it... T_T
            {
                $array[$k]['total'] = -1 * $array[$k]['total'];
            }

            if ($v['coa_kode'] == '3-3000')
            {
                $array[$k]['total'] = QDC_Finance_ProfitLoss::factory()->getProfitLoss(array(
                    "perBulan" => $this->month,
                    "perTahun" => $this->year
                ));
            }

//            $arrayTot[$urut] = $total + $totalDetail;
        }

        //Pengisian untuk total masing2 header
        //TODO : masih mbulet bin ribet... cari cara lain gimana biar lebih efesien @_@

        $levelHeader = 0;
        foreach($arrayTot as $k => $v)
        {
            if ($v['totalfor'] != '')
            {
                $urut = $v['totalfor'];
                $level = $v['level'];

                if ($levelHeader < $level)
                    $levelHeader = $level;
                $jum = 0;
                foreach ($array as $k2 => $v2)
                {
                    if ($v2['coa_kode'] != '')
                    {
                        if (strpos($v2['urut'],$urut . ".") !== false && $level == ($v2['level'] - 1))
                        {
                            $jum += floatval($v2['total']);
                        }
                    }
                }

                $arrayTot[$k]['grandtotal'] = $jum;
            }
        }

        $levelHeader--;

        if ($levelHeader>0)
        {
            for($i=$levelHeader;$i>=1;$i--)
            {
                foreach($arrayTot as $k => $v)
                {
                    if ($v['level'] == $i)
                    {
                        $levelAnak = $i+1;
                        $urutAnak = $v['totalfor'] . ".";

                        $jum = $this->sumArray($arrayTot,$levelAnak,$urutAnak);
                        $arrayTot[$k]['grandtotal'] = floatval($arrayTot[$k]['grandtotal']) + $jum;
                    }
                }
            }
        }

        foreach($arrayTot as $k => $v)
        {
            $urut = $v['totalfor'];
            $level = $v['level'];
            foreach($array as $k2 => $v2)
            {
                if ($v2['totalfor'] == $urut && $v2['level'] == $level)
                {
                    $array[$k2]['grandtotal'] = $v['grandtotal'];
                }
            }
        }

        return $array;
    }

    private function sumArray($array=array(),$level=0,$urut='')
    {
        $jum = 0;
        foreach($array as $k => $v)
        {
            if ($v['level'] == $level && strpos($v['totalfor'],$urut) !== false)
                $jum += floatval($v['grandtotal']);
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
    private function arrangeBalanceSheet($array=array())
    {
        if (count($array) == 0)
            return false;

        $newArray = array();

        //Ambil yg header level 1 saja...
        foreach ($array as $k => $v)
        {
            if ($v['level'] == 1)
            {
                if ($v['tipe'] != '')
                {
                    if($v['tipe'] == 'Equity') //Terpaksa hardcode, mudah2an tipe COA tidak berubah
                    {
                        $indeksPotong = $k-1;
                    }
                    if($v['tipe'] == 'Asset')
                    {
                        $urutAssets = $v['urut'];
                    }
                    if($v['tipe'] == 'Liability')
                    {
                        $urutLiability = $v['urut'];
                    }
                }
                else
                {
                    if ($urutAssets != '')
                    {
                        if ($v['totalfor'] == $urutAssets)
                            $jumAssets = floatval($v['grandtotal']);
                    }
                    if ($urutLiability != '')
                    {
                        if ($v['totalfor'] == $urutLiability)
                            $jumLiability = floatval($v['grandtotal']);
                    }
                }

            }
        }

        $netAssets[] = array(
            "text" => "<b>Net Assets</b>",
            "grandtotal" => ($jumAssets - $jumLiability)
        );

        $newArray = $this->mergeArray($indeksPotong,$netAssets,$array);

        //Kasih margin untuk setiap header/detail bedasarkan level na
        foreach($newArray as $k => $v)
        {
            if ($v['level'] > 1)
            {
                if ($v['coa_nama'])
                    $newArray[$k]['coa_nama'] = $this->makeMargin($v['level']) . $v['coa_nama'];
                else
                    $newArray[$k]['text'] = $this->makeMargin($v['level']) . $v['text'];
            }
        }

        return $newArray;
    }

    private function makeMargin($level=0)
    {
        $margin='&nbsp;';
        for($i=1;$i<=$level;$i++)
        {
            $margin .= "&nbsp;&nbsp;";
        }

        return $margin;
    }

    private function filterLevel($array=array(),$level=0)
    {
        foreach($array as $k => $v)
        {
            if (intval($v['level']) > $level)
                unset($array[$k]);
        }

        if ($level == 1)
        {
            foreach($array as $k => $v)
            {
                if ($v['text'] != '' && strpos($v['text'],'Net Assets') === false)
                {
                    if (strpos($v['text'],'Total Assets') !== false)
                        $jumAssets = $v['grandtotal'];
                    if (strpos($v['text'], 'Total Liabilities') !== false)
                        $jumLiability = $v['grandtotal'];
                    if (strpos($v['text'], 'Total Equity') !== false)
                        $jumEquity = $v['grandtotal'];

                    unset($array[$k]);
                }
            }

            foreach($array as $k => $v)
            {
                if ($v['coa_nama'] == 'Assets')
                    $array[$k]['total'] = $jumAssets;
                if ($v['coa_nama'] == 'Liabilities')
                    $array[$k]['total'] = $jumLiability;
                if ($v['coa_nama'] == 'Equity')
                    $array[$k]['total'] = $jumEquity;
            }
        }
        else
        {
            foreach($array as $k => $v)
            {
                if ($v['hd'] == 'Header' && $v['total'] == 0)
                {
                    $array[$k]['total'] = '&nbsp';
                }
            }
        }

        return $array;
    }

}
?>