 <?php

class Default_Models_BarangHistories extends Zend_Db_Table_Abstract
{
    protected $_name = 'transmaster_tmaterial_2009';

    protected $_primary = 'brg_kode';
    protected $_kode_brg;
    protected $_nama_brg;

	protected $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }
    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    public function getLastPrice($productID = '')
    {
    	$sql = "SELECT harga, val_kode,tgl FROM " . $this->_name . " WHERE brg_kode = '" . $productID . "' ORDER BY tgl DESC LIMIT 1";
    	$fetch = $this->db->query($sql);
    	
    	$result = $fetch->fetchAll();
    	return $result[0];
    }

    public function getAllDistinct($code,$name,$offset=0,$limit=100)
    {
    	if ($code == '')
    	{
    		$query = "brg_nama LIKE '%$name%'";
    	}
    	else 
    	{
    		$query = "brg_kode='$code'";
    	}
    	$sql = "SELECT SQL_CALC_FOUND_ROWS a.*,b.master_kota FROM " . $this->_name . " a LEFT JOIN master_suplier b ON a.sup_kode = b.sup_kode WHERE $query GROUP BY tra_no ORDER BY tgl DESC LIMIT $offset,$limit";
    	$fetch = $this->db->query($sql);
    	
    	$result['posts'] = $fetch->fetchAll();
        $result['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');

    	return $result;
    }
}
?>

