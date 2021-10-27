<?php
class Default_Models_MasterBarang extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_barang_project_2009';

    protected $_primary = 'kode_brg';
    protected $_kode_brg;
    protected $_nama_brg;
	protected $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function __name()
    {
        return $this->_name;
    }

    /**
     * @param string $kodeBrg
     * @return string nama barang, jika tidak ditemukan akan mengembalikan 'NOT FOUND'
     */
    public function getName($kodeBrg='')
    {
        if (!$kodeBrg)
            return 'NOT FOUND';

        $item = $this->fetchRow("kode_brg = '$kodeBrg'");
        if ($item)
        {
            return $item['nama_brg'];
        }
        else
            return 'NOT FOUND';
    }

    public function isExist($kodeBrg='')
    {
        $item = $this->fetchRow("kode_brg = '$kodeBrg'");

        if (!$item)
            return false;

        return true;
    }

    public function isOldMaterial($kodeBrg='')
    {
        $item = $this->fetchRow("kode_brg = '$kodeBrg'");

        if ($item)
        {
            if ($item['is_old'] == 'Y')
                return true;
            else
                return false;
        }

        return false;
    }

    public function getUom($kodeBrg='')
    {
        $item = $this->fetchRow("kode_brg = '$kodeBrg'");

        if ($item)
        {
            return $item['sat_kode'];
        }

        return false;
    }
    
    public function getAvgPrice($kodeBrg='')
    {
        $item = $this->fetchRow("kode_brg = '$kodeBrg'");
        
        if ($item)
        {
            $avg_price = $item['hargaavg'];
            if(!$avg_price || $avg_price == 0)
                return false;
            else
                return $avg_price;
        }

        return false;
    }
}
?>