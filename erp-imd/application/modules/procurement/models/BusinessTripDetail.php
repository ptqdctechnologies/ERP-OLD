<?php

class Procurement_Models_BusinessTripDetail extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_brfd';

    protected $_primary = 'trano';
    protected $db;

    private $select;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function getCost($params=array())
    {
        foreach($params as $k => $v)
        {
            $temp = $k;
            ${"$temp"} = $v;
        }

        $select = $this->db->select()
            ->from(array($this->_name),array(
                "prj_kode",
                "sit_kode",
                "workid",
                "kode_brg",
                "totalIDR" => new Zend_Db_Expr("(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END)"),
                "totalHargaUSD" => new Zend_Db_Expr("(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END)"),
            ));

        if ($prjKode)
            $select = $select->where("prj_kode = '$prjKode'");
        if ($sitKode)
            $select = $select->where("sit_kode = '$sitKode'");
        if ($workid)
            $select = $select->where("workid = '$workid'");
        if ($kodeBrg)
            $select = $select->where("kode_brg = '$kodeBrg'");
        if ($trano)
            $select = $select->where("trano = '$trano'");
        if ($exclude_trano)
            $select = $select->where("trano != '$exclude_trano'");

        $data = $this->db->fetchAll($select);
        if ($returnORM)
        {
            $this->select = $select;
            return $this;
        }

        return $data;
    }

    public function getCostSummary($params=array())
    {
        if (!$this->select)
            $this->select = $this->getCost($params);

        $select = $this->db->select()
            ->from(array("a" => $this->select),array(
                "totalIDR" => "COALESCE(SUM(a.totalIDR),0)",
                "totalUSD" => "COALESCE(SUM(a.totalHargaUSD),0)"
            ));

        $data = $this->db->fetchAll($select);
        if ($returnORM)
        {
            $this->select = select;
            return $this;
        }

        return $data;
    }
}