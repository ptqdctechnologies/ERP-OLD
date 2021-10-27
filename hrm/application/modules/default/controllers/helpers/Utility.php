<?php

class Zend_Controller_Action_Helper_Utility extends
                Zend_Controller_Action_Helper_Abstract
{

    private $db;
    function  __construct() {
        $this->db = Zend_Registry::get('db');
    }    

    function getGpsDecimal($exifCoord, $hemi) {

        $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return floatval($flip * ($degrees +($minutes/60)+($seconds/3600)));

    }

    function gps2Num($coordPart) {

        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }

    function getGps($exifCoord)
    {
      $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
      $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
      $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

      //normalize
      $minutes += 60 * ($degrees - floor($degrees));
      $degrees = floor($degrees);

      $seconds += 60 * ($minutes - floor($minutes));
      $minutes = floor($minutes);

      //extra normalization, probably not necessary unless you get weird data
      if($seconds >= 60)
      {
        $minutes += floor($seconds/60.0);
        $seconds -= 60*floor($seconds/60.0);
      }

      if($minutes >= 60)
      {
        $degrees += floor($minutes/60.0);
        $minutes -= 60*floor($minutes/60.0);
      }

      return array('degrees' => $degrees, 'minutes' => $minutes, 'seconds' => $seconds);
    }

    function array_compare($array1, $array2, $bothSide = true) {
        $diff = false;
        // Left-to-right
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key,$array2)) {
                $diff[0][$key] = $value;
            } elseif (is_array($value)) {
                 if (!is_array($array2[$key])) {
                        $diff[0][$key] = $value;
                        $diff[1][$key] = $array2[$key];
                 } else {
                        $new = $this->array_compare($value, $array2[$key]);
                        if ($new !== false) {
                             if (isset($new[0])) $diff[0][$key] = $new[0];
                             if (isset($new[1])) $diff[1][$key] = $new[1];
                        };
                 };
            } elseif ($array2[$key] !== $value) {
                 $diff[0][$key] = $value;
                 $diff[1][$key] = $array2[$key];
            };
        };
        if ($bothSide)
        {
             // Right-to-left
             foreach ($array2 as $key => $value) {
                    if (!array_key_exists($key,$array1)) {
                         $diff[1][$key] = $value;
                    };
                    // No direct comparsion because matching keys were compared in the
                    // left-to-right loop earlier, recursively.
             };
        }
         return $diff;
    }

    public function getExchangeRate()
    {
         $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";

       $fetch = $this->db->query($sql);
       $data = $fetch->fetch();

       $rate = abs($data['rateidr']);

        return $rate;
    }

    public function buildSearchQuery($fields,$search)
    {
        $return = '';
        foreach($fields as $k => &$v)
        {
            $v = $v . " LIKE '%$search%'";
        }

        $return = implode(" OR ",$fields);

        return $return;
    }

    function microtime_float()
    {
        list ($msec, $sec) = explode(' ', microtime());
        $microtime = (float)$msec + (float)$sec;
        return $microtime;
    }

    function convert_number($number)
    {
        if (($number < 0) || ($number > 999999999999))
        {
            throw new Exception("Number is out of range");
        }

        $Bn = floor($number / 1000000000); /* Billions */
        $number -= $Bn * 1000000000;
        $Gn = floor($number / 1000000);  /* Millions (giga) */
        $number -= $Gn * 1000000;
        $kn = floor($number / 1000);     /* Thousands (kilo) */
        $number -= $kn * 1000;
        $Hn = floor($number / 100);      /* Hundreds (hecto) */
        $number -= $Hn * 100;
        $Dn = floor($number / 10);       /* Tens (deca) */
        $n = $number % 10;               /* Ones */

        $res = "";

        if ($Bn)
        {
            $res .= $this->convert_number($Bn) . " Billion";
        }
        if ($Gn)
        {
            $res .= (empty($res) ? "" : " ") . $this->convert_number($Gn) . " Million";
        }

        if ($kn)
        {
            $res .= (empty($res) ? "" : " ") .
                $this->convert_number($kn) . " Thousand";
        }

        if ($Hn)
        {
            $res .= (empty($res) ? "" : " ") .
                $this->convert_number($Hn) . " Hundred";
        }

        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six",
            "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen",
            "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eightteen",
            "Nineteen");
        $tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty",
            "Seventy", "Eigthy", "Ninety");

        if ($Dn || $n)
        {
            if (!empty($res))
            {
                $res .= " and ";
            }

            if ($Dn < 2)
            {
                $res .= $ones[$Dn * 10 + $n];
            }
            else
            {
                $res .= $tens[$Dn];

                if ($n)
                {
                    $res .= "-" . $ones[$n];
                }
            }
        }

        if (empty($res))
        {
            $res = "zero";
        }

        return $res;
    }

    function dates_diff($date1, $date2)
    {
       if ($date1<$date2)
       {
           $dates_range[]=$date1;
           $date1=strtotime($date1);
           $date2=strtotime($date2);
           while ($date1!=$date2)
           {
//               $date1=mktime(0, 0, 0, date("m", $date1), date("d", $date1)+1, date("Y", $date1));
               $date1 = strtotime("+1 days", $date1);
               $dates_range[]=date('Y-m-d', $date1);
           }
       }
       return count($dates_range);
    }
}