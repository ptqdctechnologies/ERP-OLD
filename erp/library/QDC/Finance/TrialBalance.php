<?php

Class QDC_Finance_TrialBalance
{
    public $FINANCE;
    private $models;

    public function __construct($params = '')
    {

        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        $this->models = array(
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingJurnalBank",
            "AccountingSaldoCoa",
            "AccountingSaldoRL",
            "MasterCoa",
            "AdjustingJournal"
        );
        $this->FINANCE = $this->getConfig($this->models);
    }

    public static function getConfig($models=array())
    {
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
    public static function factory($params=array())
    {
        return new self($params);
    }

    public function getData($periode)
    {
        if (!$periode)
            return false;

        $tglAwal = $periode['tgl_awal'] . " 00:00:00";
        $tglAkhir = $periode['tgl_akhir'] . " 23:59:59";

        $ap = $this->FINANCE->AccountingCloseAP->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
        if ($ap)
            $ap = $ap->toArray();

        $ar = $this->FINANCE->AccountingCloseAR->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
        if ($ar)
            $ar = $ar->toArray();

        $bank = $this->FINANCE->AccountingJurnalBank->fetchAll("tglpost BETWEEN '$tglAwal' AND '$tglAkhir' AND stspost = 1 AND stsclose = 0");
        if ($bank)
            $bank = $bank->toArray();

        $general = $this->FINANCE->AdjustingJournal->fetchAll("tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND stsclose = 0");
        if ($general)
            $general = $general->toArray();

        $coaArray = QDC_Common_Array::factory()->merge(array($ap,$ar,$bank,$general));

        $data = array();
        foreach($coaArray as $k => $v)
        {
            $coaKode = $v['coa_kode'];
            $coaNama = $v['coa_nama'];
            $cek = $this->FINANCE->MasterCoa->fetchRow("coa_kode = '$coaKode'");

            $data[$coaKode]['coa_nama'] = $coaNama;
            $data[$coaKode]['coa_kode'] = $coaKode;
            $operand = '+';
            $totInsert = 0;
//            $data[$coaKode]['debit'] += $v['debit'];
//            $data[$coaKode]['credit'] += $v['credit'];

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

            if ($cek['dk'] == 'Debit')
                $data[$coaKode]['debit'] = floatval($data[$coaKode]['debit']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);

            if ($cek['dk'] == 'Credit')
                $data[$coaKode]['credit'] = floatval($data[$coaKode]['credit']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);

        }

        ksort($data);

//        $select = $this->FINANCE->db->select()
//            ->from(array($this->FINANCE->MasterCoa->__name()),array(
//                "coa_kode"
//            ))
//            ->order(array("coa_kode ASC"));
//        $coas = $this->FINANCE->db->fetchAll($select);
//        if ($coas)
//        {
//            foreach($coas as $k => $v)
//            {
//                $coas[$k] = $data[$v];
//            }
//        }


        return $data;
    }
}
?>