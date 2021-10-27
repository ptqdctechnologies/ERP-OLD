<?php

Class QDC_Finance_Coa
{
    public $FINANCE;
    private $models;

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
            "MasterCoa",
            "MasterCoaBank",
            "LayoutBalanceSheet",
            "LayoutProfitloss"
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
     * @return QDC_Finance_Coa
     *
     * Method factory dipanggil apabila QDC_FINANCE_COA di inisialisasi secara statik
     */
    public static function factory($params=array())
    {
        return new self($params);
    }

    public function getCoa($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($coa_kode == '')
            return false;

        $coa = $this->FINANCE->MasterCoa->fetchRow("coa_kode = '$coa_kode'");
        if ($coa)
        {
            return $coa->toArray();
        }

        return false;
    }

    public function getCoaBank($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($type == '')
            return false;

        $coaBank = $this->FINANCE->MasterCoaBank->fetchRow("trano_type = '$type'");
        if (!$coaBank)
            return false;

        $coaKode = $coaBank['coa_kode'];
        $coas = $this->getCoaDetail($coaKode);
        return $coas;
    }

    public function getCoaLayout($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        if ($coa_kode == '')
            return false;

        $getCoa = $this->FINANCE->LayoutBalanceSheet->fetchRow("coa_kode = '$coa_kode'");

        if ($getCoa)
        {
            return $getCoa->toArray();
        }
        else
        {
            $getCoa = $this->FINANCE->LayoutProfitloss->fetchRow("coa_kode = '$coa_kode'");
            if ($getCoa)
            {
                return $getCoa->toArray();
            }
        }

        return false;

    }

    public function getCoaDetail($coaKode='')
    {
        $cekCoa = $this->FINANCE->MasterCoa->fetchRow("coa_kode = '$coaKode'");
        if (!$cekCoa)
            return false;

        if ($cekCoa['hd'] == 'Header')
        {
            $layout = $this->getCoaLayout(array("coa_kode" => $coaKode));
            if ($layout === false)
                return false;

            $urut = $layout['urut'] . ".";
            $level = intval($layout['level']) + 1;
            $getCoa = $this->FINANCE->LayoutBalanceSheet->fetchAll("level = $level AND urut LIKE '$urut%'");
            if ($getCoa)
            {
                return array(
                    "data" => $getCoa->toArray()
                );
            }
            else
            {
                $getCoa = $this->FINANCE->LayoutProfitloss->fetchAll("level = $level AND urut LIKE '$urut%'");
                if ($getCoa)
                {
                    return array(
                        "data" => $getCoa->toArray()
                    );
                }
            }

            return false;
        }
        else
        {
            return array(
                "data" => array($cekCoa->toArray())
            );
        }
    }
}
?>