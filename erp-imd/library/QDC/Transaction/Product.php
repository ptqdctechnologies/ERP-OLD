<?php

Class QDC_Transaction_Product
{
    private $models;
    private $DEFAULT;

    private $workidMsc;

    public function __construct($params = '')
    {

        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }

        $this->models = array(
            "MasterBarang"
        );
        $this->DEFAULT = $this->getConfig($this->models);
        $this->workidMsc = array(
            1100,2100,3100,4100,5100,6100,7100,8100,9100
        );
    }

    public static function getConfig($models=array())
    {
        $reps = QDC_Model_Default::init($models);
        return $reps;
    }

    /**
     * @static
     * @param $params
     * @return QDC_Transaction_Number
     *
     * Method factory dipanggil apabila QDC_TRANSACTION_NUMBER di inisialisasi secara statik
     */
    public static function factory($params)
    {
        return new self($params);
    }

    public function isMsc()
    {
        if ($this->workid)
        {
            return in_array($this->workid,$this->workidMsc);
        }

        return null;
    }

}
?>