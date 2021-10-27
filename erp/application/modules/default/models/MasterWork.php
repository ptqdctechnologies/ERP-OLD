<?php

class Default_Models_MasterWork extends Zend_Db_Table_Abstract
{
    protected $_name = 'masterengineer_work';

    protected $_primary = 'workid';
    protected $_workid;
    protected $_workname;

    protected $db;
    protected $const;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    
	public function getWorkByBOQ3($prj_kode='',$sit_kode='')
    {
    	if ($sit_kode != '')
    		$query = " AND sit_kode = '$sit_kode'";
    		
		$sql = "
                       SELECT
                            a.kode_brg,
                            a.nama_brg,
                            a.workid,
                            a.workname
                        FROM
                            transengineer_boq3d a
                        WHERE
                            prj_kode = '$prj_kode'
							$query";
		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }
    
    public function getWorkPr($prj_kode='',$sit_kode='')
    {
	$sql = "SELECT
                    p.trano,
                    p.tgl,
                    p.prj_kode,
                    p.prj_nama,
                    p.sit_kode,
                    p.sit_nama,
                    p.workid,
                    p.workname
                FROM(
                       SELECT
                            a.trano,
                            a.tgl,
                            a.prj_kode,
                            a.prj_nama,
                            a.sit_kode,
                            a.sit_nama,
                            a.workid,
                            a.workname
                        FROM
                            transengineer_boq3d a
                        WHERE
                            prj_kode = '$prj_kode'
                        AND
                            sit_kode = '$sit_kode') p
                GROUP BY p.trano
                ORDER BY p.trano ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

    public function getWorkname($workid='')
    {
        if (!$workid)
            return false;

        $data = $this->fetchRow("workid = '$workid'");
        if ($data)
            return $data['workname'];

        return 'NOT FOUND';
    }

    public function isExist($workid='')
    {
        $item = $this->fetchRow("workid='$workid'");

        if (!$item)
            return false;

        return true;
    }

}?>