<?php

class QDC_Common_Number {

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

    public function addOrdinalNumberSuffix($num) {
        if (!in_array(($num % 100),array(11,12,13))){
            switch ($num % 10) {
                // Handle 1st, 2nd, 3rd
                case 1:  return $num.'st';
                case 2:  return $num.'nd';
                case 3:  return $num.'rd';
            }
        }
        return $num.'th';
    }
}

?>