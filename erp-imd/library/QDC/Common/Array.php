<?php
class QDC_Common_Array
{
    public function __construct($params = '')
    {
        if ($params != '')
        foreach($params as $k => $v)
        {
            $temp = $k;
            $this->{"$temp"} = $v;
        }
    }

    /**
     * @static
     * @param $params
     * @return QDC_Common_Array
     *
     * Method factory dipanggil apabila QDC_COMMON_ARRAY di inisialisasi secara statik
     */
    public static function factory($params='')
    {
        return new self($params);
    }

    /**
     * @param array $params
     *
     * Menggabungkan beberapa array menjadi satu array
     * Tidak bergantung pada isi array..
     */
    public function merge($params=array())
    {
        if (count($params) == 0)
            return false;

        $newArrays = array();
        foreach($params as $k => $v)
        {
            if ($v == null || $v == '')
                continue;
            $newArrays = array_merge($newArrays,$v);
        }

        return $newArrays;
    }

    /**
     * @param int $indeksPotong, indeks array yang akan disisipkan
     * @param array $sourceArray, array yang akan disisipkan
     * @param array $destArray, array tujuan
     * @return array
     *
     * Fungsi untuk menggabungkan 2 buah array dengan cara menyisipkan array ditengah2 indeks yang dituju
     * contoh: array1 : 0,1,2 akan disisipkan ke array2 : x,y,z setelah indeks y
     * hasilnya:  x,y,0,1,2,z
     */
    public function insert($indeksPotong=0,$sourceArray=array(),$destArray=array())
    {
        $jumlahArrayAsli = count($destArray);
        $jumlahArrayInsert = count($sourceArray);

        $arrayTemp1 = array();
        $arrayTemp2 = array();

        //Ambil array sebelum indeks yang akan disisipkan
        for($i=0;$i<=$indeksPotong;$i++)
        {
            $arrayTemp1[$i] = $destArray[$i];
        }
        if ($indeksPotong < ($jumlahArrayAsli-1))
        {
            //Ambil array sesudah indeks yang akan disisipkan
            for($i=($indeksPotong+1);$i<$jumlahArrayAsli;$i++)
            {
                $arrayTemp2[] = $destArray[$i];
            }
        }

        //gabung array sebelum dan sesudah dengan array yg disisipkan
        $newArray = array_merge($arrayTemp1,$sourceArray);
        if (count($arrayTemp2) > 0)
            $newArray = array_merge($newArray,$arrayTemp2);

        return $newArray;
    }

    public function combine($array1=array(),$array2=array())
    {
        return array_diff_key($array1, $array2) + $array2;
    }

    public function GetArrKey( $findArr, $key_arr, $depth=0 )
    {
        if( count($key_arr) <= $depth || !array_key_exists($key_arr[$depth], $findArr) )
            return NULL;
        else if( count($key_arr) == $depth+1 )
            return $findArr[$key_arr[$depth]];

        return self::GetArrKey( $findArr[$key_arr[$depth]], $key_arr, $depth+1 );
    }

    public function searchAll($haystack, $needle, $index = null)
    {
        $aIt     = new RecursiveArrayIterator($haystack);
        $it    = new RecursiveIteratorIterator($aIt);
        $resultkeys;

        while($it->valid()) {
            if (is_array($it->current()))
            {
                $res = $this->searchAll($it->current(),$needle,$index);
                if ($res != '')
                {
                    $resultkeys[] = $aIt->key();
                    $it->next();
                    continue;
                }
            }
            if (is_string($it->current())) {
            if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND (strpos(strtolower($it->current()), $needle)!==false)) { //$it->current() == $needle
                $resultkeys[]=$aIt->key(); //return $aIt->key();
            }
            }
            $it->next();
        }
        $result = $this->returnFromSearch($haystack,$resultkeys);
        return $result;

    }

    public function searchFirst($haystack, $needle, $index = null)
    {
        $aIt     = new RecursiveArrayIterator($haystack);
        $it    = new RecursiveIteratorIterator($aIt);
        $resultkeys;

        while($it->valid()) {
            if (is_array($it->current()))
            {
                $res = $this->searchAll($it->current(),$needle,$index);
                if ($res != '')
                {
                    $resultkeys = $aIt->key();
                    $it->next();
                    break;
                }
            }
            if (is_string($it->current())) {
                if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND (strpos(strtolower($it->current()), $needle)!==false)) { //$it->current() == $needle
                    $resultkeys=$aIt->key(); //return $aIt->key();
                }
            }
            $it->next();
        }
//        return $resultkeys;  // return all finding in an array

        $result = $this->returnFromSearch($haystack,$resultkeys);
        return $result;
    }

    private function returnFromSearch($haystack,$result)
    {
        $data = array();
        if (is_array($result))
        {
            foreach($result as $k => $v)
            {
                $data[] = $haystack[$v];
            }
        }
        else
            $data = $haystack[$result];
        return $data;
    }

    public function deep_ksort(&$arr) {
        ksort($arr);
        foreach ($arr as &$a) {
            if (is_array($a) && !empty($a)) {
                $this->deep_ksort($a);
            }
        }
    }

    public function normalize($array) {
        $tmp = array();
        foreach ($array as $k => $v)
        {
            $tmp[] = $v;
        }

        return $tmp;
    }

    public function searchSimple($haystack=array(),$needle='')
    {
        $found = false;
        foreach($haystack as $k => $v)
        {
            if (strpos(strtolower($v),$needle) !== false)
            {
                $found = !$found;
                break;
            }
        }
        return $found;
    }
}

?>