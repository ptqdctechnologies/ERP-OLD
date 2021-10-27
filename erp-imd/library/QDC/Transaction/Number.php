<?php

Class QDC_Transaction_Number
{
    private $models;
    private $DEFAULT;

    public function __construct($params = '')
    {

        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }

        $this->models = array(
            "MasterCounter"
        );
        $this->DEFAULT = $this->getConfig($this->models);
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
}
?>