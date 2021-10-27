<?php

Class QDC_Finance_Periode {

    public $FINANCE;
    private $models;
    private $rateidr;

    public function __construct($params = '') {

        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        $this->models = array(
            "MasterPeriode",
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingJurnalBank",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "MasterCoa",
            "LayoutBalanceSheet",
            "LayoutProfitloss",
            "AdjustingJournal"
        );
        $this->FINANCE = $this->getConfig($this->models);
    }

    public static function getConfig($models = array()) {
        $reps = QDC_Model_Finance::init($models);
        return $reps;
    }

    /**
     * @static
     * @param $params
     * @return QDC_Finance_Periode
     *
     * Method factory dipanggil apabila QDC_FINANCE_PERIODE di inisialisasi secara statik
     */
    public static function factory($params = array()) {
        return new self($params);
    }

    public function getPeriode($perkode = '') {
        $fetch = $this->FINANCE->MasterPeriode->fetchRow("perkode = '$perkode'");

        if ($fetch)
            return $fetch->toArray();
        else
            return false;
    }

    public function getCurrentPeriode() {
        if ($this->notClose)
            $where = " AND stsclose = 0";

        if ($this->inventoryNotClose)
            $where = " AND stscloseinventory = 0";
        $fetch = $this->FINANCE->MasterPeriode->fetchRow("aktif = 1 $where");

        if ($fetch)
            return $fetch->toArray();
        else
            return false;
    }

    public function checkPeriode($tgl = '') {
        $tgl = date("Y-m-d", strtotime($tgl));

        if ($tgl == '1970-01-01')
            return false;

        $fetch = $this->FINANCE->MasterPeriode->fetchRow("tgl_awal <= '$tgl' AND tgl_akhir >= '$tgl'");
        if ($fetch)
            return $fetch->toArray();
        else
            return false;
    }

    public function getAllYearPeriode() {
        $select = $this->FINANCE->MasterPeriode->db
                ->select()
                ->from(array('a' => $this->FINANCE->MasterPeriode->name), array("a.tahun"))
                ->group("a.tahun")
                ->order("a.tahun DESC");
        $fetch = $this->FINANCE->MasterPeriode->db->fetchAll($select);

        if ($fetch)
            return $fetch;
        else
            return false;
    }

    public function getAllMonthPeriode($year = '') {
        $select = $this->FINANCE->MasterPeriode->db
                ->select()
                ->from(array('a' => $this->FINANCE->MasterPeriode->name), array(
                    "a.bulan",
                    "a.tahun",
                    new Zend_Db_Expr("MONTHNAME(CONCAT(a.tahun,'-',a.bulan,'-01')) AS nama_bulan")
                        )
                )
                ->where($this->FINANCE->MasterPeriode->db->quoteInto("a.tahun = ?", $year))
                ->order("a.bulan ASC");
        $fetch = $this->FINANCE->MasterPeriode->db->fetchAll($select);

        if ($fetch)
            return $fetch;
        else
            return array();
    }

    public function getPreviousPeriode($currentPeriode = array()) {
        if (count($currentPeriode) == 0) {
            $cek = $this->getCurrentPeriode();
            if (!$cek)
                return false;
            $currentPeriode = $cek;
        }

        $fetch = $this->FINANCE->MasterPeriode->fetchRow("(perkode != '{$currentPeriode['perkode']}' AND bulan != '{$currentPeriode['bulan']}') OR tahun != '{$currentPeriode['tahun']}'", array("bulan desc", "tahun desc"));

        if ($fetch)
            return $fetch->toArray();
        else
            return false;
    }

    public function closing($perkode = '') {
        if (!$perkode)
            return false;

        $periode = $this->getPeriode($perkode);
        $tglAwal = $periode['tgl_awal'] . " 00:00:00";
        $tglAkhir = $periode['tgl_akhir'] . " 23:59:59";
        $perBulan = $periode['bulan'];
        $perTahun = $periode['tahun'];

        if (!$this->rateidr) {
            $e = QDC_Common_ExchangeRate::factory(array("valuta" => "USD"))->getExchangeRate();
            $rateidr = $e['rateidr'];
        }

        $arrayKet = array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun,
            "periode" => $periode,
            "rateidr" => $this->rateidr,
            "perkode" => $perkode
        );

//        $ap = $this->FINANCE->AccountingCloseAP->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
//        if ($ap)
//        {
//            $ap = $ap->toArray();
//            $ap = $this->setOriginTable($ap,'accounting_close_ap');
//        }
//
//        $ar = $this->FINANCE->AccountingCloseAR->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
//        if ($ar)
//        {
//            $ar = $ar->toArray();
//            $ar = $this->setOriginTable($ar,'accounting_close_ar');
//        }
//
//        $bank = $this->FINANCE->AccountingJurnalBank->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
//        {
//            $bank = $bank->toArray();
//            $bank = $this->setOriginTable($ap,'accounting_jurnal_bank');
//        }
//
//        $general = $this->FINANCE->AdjustingJournal->fetchAll("tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND stsclose = 0");
//        if ($general)
//        {
//            $general = $general->toArray();
//            $general = $this->setOriginTable($general,'accounting_journal');
//        }

        $this->FINANCE->AccountingSaldoCoa->createNewSaldo(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun
        ));

        $where = "tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND stsclose = 0";
        QDC_Finance_Jurnal::factory(array(
            "useShape" => true,
            "useTempTable" => true,
            "tempTableName" => "all_jurnal_closing"
        ))->getAllJurnal($where);

        $coaArray = $this->FINANCE->db->fetchAll($this->FINANCE->db->select()->from(array("all_jurnal_closing")));

//        $coaArray = QDC_Common_Array::factory()->merge(array($ap,$ar,$bank,$general));

        $this->FINANCE->AccountingSaldoCoa->insertSaldo(array(
            "ket" => array(
                "perBulan" => $perBulan,
                "perTahun" => $perTahun,
                "perKode" => $perkode
            ),
            "data" => $coaArray
                ), $this->rateidr);

        //Beri status close = 1
        $arrayUpdate = array(
            "stsclose" => 1,
            "uidclose" => QDC_User_Session::factory()->getCurrentUID(),
            "tglclose" => new Zend_Db_Expr("NOW()")
        );

        $models = QDC_Finance_Jurnal::factory()->getAllModel();
        $mName = $this->FINANCE->getModuleName();
        foreach ($models as $v) {
            eval('$nm = new ' . $mName . $v . '();');
            $nm->update($arrayUpdate, $where);
        }

        $this->FINANCE->MasterPeriode->update($arrayUpdate, "id = {$periode['id']}");
        //Profit & Loss
        $this->FINANCE->AccountingSaldoRL->createNewSaldo(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun
        ));
        $this->FINANCE->AccountingSaldoRL->insertNewSaldo(array(
            "perBulan" => $perBulan,
            "perTahun" => $perTahun
        ));

//        $RL = QDC_Finance_ProfitLoss::factory()->getProfitLoss(array(
//            "perBulan" => $perBulan,
//            "perTahun" => $perTahun
//        ));
//        $logInsert =$saldoInsert;
//        $logInsert['ref_number'] = $trano;
//        $logInsert['bulan'] = $periode['bulan'];
//        $logInsert['perkode'] = $periode['perkode'];
//        $logInsert['action'] = 'NEW';
//
//        QDC_Log_Accounting::factory()->insert($logInsert);
    }

    private function setOriginTable($array = array(), $origin = '') {
        foreach ($array as $k => $v) {
            $array[$k]['origin_table'] = $origin;
        }

        return $array;
    }
}

?>