<?php

class QDC_Common_Date{

    public $hari = array(
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu"
    );

    public $bulan = array(
        "",
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember"
    );

    public function __construct($params = '')
    {
        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
    }

    public static function factory($params='')
    {
        return new self($params);
    }

    public function getDay()
    {
        $d = date("w");
        return $this->hari[$d];
    }

    public function getMonth()
    {
        $d = date("n");
        return $this->bulan[$d];
    }

    public function getDate()
    {
        return date("d") . " " . $this->getMonth() . " " . date("Y");
    }
    
    public function getMonthByParam($date)
    {
        $month = date("n",  strtotime($date));
        return $this->bulan[$month];
    }
    public function formatDateUsingCustomLanguage($format,$date)
    {
        $month = $this->getMonthByParam($date);        
        $tempDate = date($format,  strtotime($date));
        $tempMonth = date("F",  strtotime($tempDate));
        
        $newDate = str_replace($tempMonth,$month,$tempDate);
        
        return $newDate;
    }
}

?>