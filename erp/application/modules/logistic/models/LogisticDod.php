<?php

class Logistic_Models_LogisticDod extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_whod';
    protected $_primary = 'trano';
	protected $db;

    public $select;
    private $isSelect;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getPrimaryKey()
    {
        return $this->_primary;
    }
    public function __name()
    {
        return $this->_name;
    }

    public function getSelect()
    {
        $this->isSelect = true;
        return $this;
    }

    public function getDetail($trano='',$prjKode='',$sitKode='',$workid='',$kodeBrg='')
    {
        $select = $this->db->select()
            ->from(array($this->_name));

        if ($trano)
            $select->where("trano=?",$trano);
        if ($prjKode)
            $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
            $select->where("sit_kode=?",$sitKode);
        if ($workid)
            $select->where("workid=?",$workid);
        if ($kodeBrg)
            $select->where("kode_brg=?",$kodeBrg);


        $this->select = $select;
        if ($this->isSelect)
            return $this->select;
        else
            return $this->db->fetchAll($select);
    }
}
?>
