<?php

Class QDC_Log_Accounting
{
    public $LOG;
    public $FINANCE;
    protected $USER;

    public function __construct($params = '')
    {

        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }

        $this->FINANCE = $this->getFinance();
        $this->LOG = $this->getLog();
        $this->USER = new QDC_User_Session();
    }

    public static function getFinance($models=array())
    {
        $reps = QDC_Model_Finance::init($models);
        return $reps;
    }

    public static function getLog($models=array())
    {
        $reps = QDC_Model_Log::init($models);
        return $reps;
    }
    /**
     * @static
     * @param $params
     * @return QDC_Log_Accounting
     *
     * Method factory dipanggil apabila QDC_Log_Accounting di inisialisasi secara statik
     */
    public static function factory($params=array())
    {
        return new self($params);
    }

    public function insert($data=array())
    {
        $lastTotal = 0;
        if ($this->trans == 'BALANCESHEET')
            $last = $this->FINANCE->AccountingSaldoCoa->fetchRow("coa_kode = '{$data['coa_kode']}' AND periode = '{$data['bulan']}' AND tahun = '{$data['tahun']}'");
        if ($this->trans == 'PROFITLOSS')
            $last = $this->FINANCE->AccountingSaldoRL->fetchRow("coa_kode = '{$data['coa_kode']}' AND periode = '{$data['bulan']}' AND tahun = '{$data['tahun']}'");

        if ($last)
        {
            $lastTotal = $last['total'];
        }
        $arrayInsert = array(
            "tgl" => date("Y-m-d H:i:s"),
            "uid" => $this->USER->getCurrentUID(),
            "last_total" => $lastTotal,
            "total" => $data['total'],
            "new_total" => (floatval($lastTotal) + floatval($data['total'])),
            "coa_kode" => $data['coa_kode'],
            "coa_nama" => $data['coa_nama'],
            "perkode" => $data['perkode'],
            "bulan" => $data['bulan'],
            "tahun" => $data['tahun'],
            "ref_number" => $data['ref_number'],
            "val_kode" => $data['val_kode'],
            "action" => $data['action'],
            "type" => $data['type']
        );
        $this->LOG->LogAccountingClosing->insert($arrayInsert);
    }
}

?>