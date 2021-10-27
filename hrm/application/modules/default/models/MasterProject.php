<?php

class Default_Models_MasterProject extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_project';

    protected $_primary = 'Prj_Kode';
    protected $_prj_kode;
    protected $_prj_nama;
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
    
    public function getProjectAndCustomer($prj_kode='',$showAll='')
    {
    	if ($prj_kode != '')
    		$where = " WHERE prj_kode ='$prj_kode' ";
        if ($showAll == '')
        {
            if ($where != '')
                $where .= " AND stsclose=0";
            else
                $where = " WHERE stsclose=0";
        }
    	$sql = "SELECT
    	 			a.*,b.cus_nama
    			FROM
    				master_project a
    			LEFT JOIN
    				master_customer b
    			ON
    				a.cus_kode = b.cus_kode
    			$where	
    			ORDER BY 
    				a.prj_kode DESC";
    	$fetch = $this->db->query($sql);
    	$result = $fetch->fetchAll();
    	
    	$id = 1;
    	
    	foreach ($result as $key => $val)
    	{
    		foreach ($val as $key2 => $val2)
    		{
    			$newField = strtolower($key2);
    			$result2[$key][$newField] = $result[$key][$key2];
    		}
    		$result2[$key]['id'] = $id;
    		$id++;
    	}
    	
    	return $result2;
    }

    public function getProjectOverhead($showAll='',$where='',$sort='',$dir='')
    {
        if ($showAll == '')
            $where .= " AND stsclose=0";
        if ($sort && $dir)
        {
            $order = " ORDER BY $sort $dir";
        }

        $sql = "SELECT * FROM master_project WHERE type = 'O' $where $order";
        $fetch = $this->db->query($sql);
    	$result = $fetch->fetchAll();

        return $result;
    }

    public function getProjectNonOverhead($showAll='',$where='',$sort='',$dir='')
    {
        if ($showAll == '')
            $where .= " AND stsclose=0";
        if ($sort && $dir)
        {
            $order = " ORDER BY $sort $dir";
        }

        $sql = "SELECT * FROM master_project WHERE type = 'P' $where $order";
        $fetch = $this->db->query($sql);
        $result = $fetch->fetchAll();

        return $result;
    }
    public function getcustomer($prj_kode)
    {
        $query = "SELECT mp.cus_kode,cus_nama,alamat FROM master_project mp
                LEFT JOIN master_customer mc ON mp.cus_kode = mc.cus_kode where Prj_kode = '$prj_kode' ";
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }

}
?>
