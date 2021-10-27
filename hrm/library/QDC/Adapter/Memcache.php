<?php
class QDC_Adapter_Memcache {

    protected $memcache;
    protected $cacheID;

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

        $this->isJSON = false;

        if (is_array($this->data))
        {
            $this->data = Zend_Json::encode($this->data);
            $this->isJSON = true;
        }

        $this->memcache = Zend_Registry::get('MemcacheGeneral');
    }

    /**
     * @static
     * @param $params
     * @return QDC_Adapter_Memcache
     *
     * Method factory dipanggil apabila QDC_ADAPTER_MEMCACHE di inisialisasi secara statik
     */
    public static function factory($params=array())
    {
        return new self($params);
    }

    public function test($cacheID)
    {
        if (substr($cacheID,-5) != "_SIZE")
            $cacheID . "_SIZE";

        if ($this->memcache->test($cacheID))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $data berbentuk format JSON
     */
    public function split()
    {
        $size =  mb_strlen($this->data, '8bit');
        $mByteSize =  round($size / 1048576, 2);
        $piece = array();

        //kalo ukuran > 1 MB, pecah... *memcache max size for 1 value == 1MB
        if ($mByteSize > 1)
        {
            $jumPart = ceil($size/524288);
            for($i=0;$i<$jumPart;$i++)
            {
                $start = (524288 * $i);
                $end = 524288;
                $piece[] = mb_substr($this->data,$start,$end);
            }
        }
        else
        {
            $piece[] = $this->data;
        }

        return $piece;

    }

    public function save()
    {
        if ($this->test($this->cacheID . "_SIZE"))
        {
            $jum = Zend_Json::decode($this->memcache->load($this->cacheID. "_SIZE"));
            if ($jum['jumlah_part'] > 1)
            {
                for($i=1;$i<=$jum['jumlah_part'];$i++)
                    $this->memcache->remove($this->cacheID . "_$i");
            }
            else
                $this->memcache->remove($this->cacheID . "_1");
            $this->memcache->remove($this->cacheID . "_SIZE");
        }

        $pieces = $this->split();
        $numPieces = count($pieces);
        $i = 1;

        foreach($pieces as $k => $v)
        {
            $this->memcache->save($v,$this->cacheID . "_" . ($i));
        }

        $this->memcache->save(Zend_Json::encode(array(
            "jumlah_part" => $numPieces,
            "json" => $this->isJSON
        )),$this->cacheID . "_SIZE");

    }

    public function load()
    {
        if ($this->test($this->cacheID . "_SIZE"))
        {
            $jum = Zend_Json::decode($this->memcache->load($this->cacheID. "_SIZE"));
            if ($jum['jumlah_part'] > 1)
            {
                $parts = '';
                for($i=1;$i<=$jum['jumlah_part'];$i++)
                {
                    if ($this->memcache->test($this->cacheID . "_$i"))
                    {
                        $parts .= $this->memcache->load($this->cacheID . "_$i");
                    }
                }
            }
            else
                $parts = (($this->memcache->load($this->cacheID . "_1")));

            if ($jum['json'] == 'true' || $jum['json'] == 1)
                return Zend_Json::decode($parts);
            else
                return $parts;
        }

        return false;
    }
}
?>