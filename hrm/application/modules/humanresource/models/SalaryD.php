<?php
class HumanResource_Models_SalaryD extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_sald';
    private $db;
     protected $_primary = 'trano';

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function getSalarySummaryForCFS($prjKode='')
    {
        $gaji = $this->fetchAll("prj_kode='$prjKode'");
        if ($gaji)
        {
            $gaji = $gaji->toArray();
            $result = array();
            foreach($gaji as $k => $v)
            {
                $sitKode = $v['sit_kode'];
                $result[$sitKode] += floatval($v['harga']);
            }

            return $result;
        }
        else
            return '';
    }
}
?>