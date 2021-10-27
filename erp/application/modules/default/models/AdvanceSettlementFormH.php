<?php
class Default_Models_AdvanceSettlementFormH extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_asfh';

    protected $_primary = 'trano';
    protected $_trano;
    protected $_prj_kode;
    protected $_prj_nama;
    protected $_work_id;
    protected $_workname;
    protected $_tgl;

    protected $db;
    protected $const;
    private $DEFAULT;
    private $FINANCE;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');

        $models = array(
            "AdvanceSettlementForm",
            "AdvanceSettlementFormCancel",
            "AdvanceSettlementFormD"
        );

        $this->DEFAULT = QDC_Model_Default::init($models);
        $models = array(
            "ArfAging"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function updateArfAging($trano='')
    {
        $data = $this->fetchRow("trano = '$trano'");
        if (!$data)
            return false;

        $arfNo = $data['arf_no'];
        $cekArfAging = $this->FINANCE->ArfAging->fetchAll("arf_no = '$arfNo'");
        if (!$cekArfAging)
            return false;

        foreach($cekArfAging as $k => $v)
        {
            $select = $this->db->select()
                ->from(array($this->DEFAULT->AdvanceSettlementForm->__name()),array("SUM(qty*harga) as total"))
                ->where("prj_kode = '{$v['prj_kode']}' AND sit_kode = '{$v['sit_kode']}' AND kode_brg = '{$v['kode_brg']}' AND workid = '{$v['workid']}' AND arf_no = '{$v['arf_no']}' AND trano = '$trano'");

            $settle = $this->db->fetchRow($select);

            $select = $this->db->select()
                ->from(array($this->DEFAULT->AdvanceSettlementFormCancel->__name()),array("SUM(qty*harga) as total"))
                ->where("prj_kode = '{$v['prj_kode']}' AND sit_kode = '{$v['sit_kode']}' AND kode_brg = '{$v['kode_brg']}' AND workid = '{$v['workid']}' AND arf_no = '{$v['arf_no']}' AND trano = '$trano'");

            $settleCancel = $this->db->fetchRow($select);

            $totalSettle = floatval($settle['total']);
            $totalSettleCancel = floatval($settleCancel['total']);

            $lastSettle = floatval($v['total_settle']);
            $lastSettleCancel = floatval($v['total_settle_cancel']);

            $this->FINANCE->ArfAging->update(array(
                "total_settle" => ($lastSettle + $totalSettle),
                "total_settle_cancel" => ($lastSettleCancel + $totalSettleCancel)
            ),"id = {$v['id']}");
        }
    }

    public function __name()
    {
        return $this->_name;
    }
}

?>