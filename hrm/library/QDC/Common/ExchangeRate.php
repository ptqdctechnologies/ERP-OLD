<?php

Class QDC_Common_ExchangeRate
{
    private $models;
    private $valuta;

    public function __construct($params = '')
    {

        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }
        $this->models = new Default_Models_ExchangeRate();
    }

    /**
     * @static
     * @param $params
     * @return QDC_Common_ExchangeRate
     *
     * Method factory dipanggil apabila QDC_COMMON_EXCHANGERATE di inisialisasi secara statik
     */
    public static function factory($params)
    {
        return new self($params);
    }

    public function getExchangeRate()
    {
        $fetch = $this->models->fetchRow("val_kode = '{$this->valuta}'",array("tgl DESC"),1,0);
        if ($fetch)
        {
            $fetch = $fetch->toArray();
            $fetch['tgl'] = date("d-m-Y H:i:s",strtotime($fetch['tgl']));
        }
        else
            $fetch = false;

        return $fetch;
    }
}
?>
