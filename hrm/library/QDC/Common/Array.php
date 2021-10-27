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
}

?>