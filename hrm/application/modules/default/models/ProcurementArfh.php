<?php
class Default_Models_ProcurementArfh extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_arfh';

    protected $_primary = 'trano';
    protected $_trano;
    protected $_prj_kode;
    protected $_prj_nama;
    protected $_work_id;
    protected $_workname;
    protected $_tgl;

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

    public function getPoPpn($tgl1='',$tgl2='')
    {
	$sql = "SELECT
                        trano,
		        DATE_FORMAT(tgl,'%m/%d/%Y') as tgl_trans,
			prj_kode,
			prj_nama,
		 FROM
			procurement_arfh
		 WHERE
		 	tgl
		 BETWEEN
			'$tgl1'
		 AND
			'$tgl2' ";

		$fetch = $this->db->query($sql);
		$result = $fetch->fetchAll();

		return $result;
    }

}

?>
