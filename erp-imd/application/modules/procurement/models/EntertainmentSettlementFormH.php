<?php
class Procurement_Models_EntertainmentSettlementFormH extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_esfh';

    protected $_primary = 'trano';
    protected $_trano;
    protected $_prj_kode;
    protected $_prj_nama;
    protected $_work_id;
    protected $_workname;
    protected $_tgl;
    
    protected $esfd, $esfc, $esf;
    
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

//        $models = array(
//            "EntertainmentSettlementForm",
//            "EntertainmentSettlementFormCancel",
//            "EntertainmentSettlementFormD"
//        );
//
//        $this->DEFAULT = QDC_Model_Default::init($models);
        $this->esf = new Procurement_Models_EntertainmentSettlementForm();
        $this->esfc = new Procurement_Models_EntertainmentSettlementFormCancel();
        $this->esfd = new Procurement_Models_EntertainmentSettlementFormD();
//        $models = array(
//            "ErfAging"
//        );
//        $this->FINANCE = QDC_Model_Finance::init($models);
    }

//    public function updateErfAging($trano='')
//    {
//        $data = $this->fetchRow("trano = '$trano'");
//        if (!$data)
//            return false;
//
//        $erfNo = $data['erf_no'];
//        $cekErfAging = $this->FINANCE->ErfAging->fetchAll("erf_no = '$erfNo'");
//        if (!$cekErfAging)
//            return false;
//
//        foreach($cekErfAging as $k => $v)
//        {
//            $select = $this->db->select()
//                ->from(array($this->esf->__name()),array("SUM(qty*harga) as total"))
//                ->where("prj_kode = '{$v['prj_kode']}' AND sit_kode = '{$v['sit_kode']}' AND kode_brg = '{$v['kode_brg']}' AND workid = '{$v['workid']}' AND erf_no = '{$v['erf_no']}' AND trano = '$trano'");
//
//            $settle = $this->db->fetchRow($select);
//
//            $select = $this->db->select()
//                ->from(array($this->esfc->__name()),array("SUM(qty*harga) as total"))
//                ->where("prj_kode = '{$v['prj_kode']}' AND sit_kode = '{$v['sit_kode']}' AND kode_brg = '{$v['kode_brg']}' AND workid = '{$v['workid']}' AND erf_no = '{$v['erf_no']}' AND trano = '$trano'");
//
//            $settleCancel = $this->db->fetchRow($select);
//
//            $totalSettle = floatval($settle['total']);
//            $totalSettleCancel = floatval($settleCancel['total']);
//
//            $lastSettle = floatval($v['total_settle']);
//            $lastSettleCancel = floatval($v['total_settle_cancel']);
//
//            $this->FINANCE->ErfAging->update(array(
//                "total_settle" => ($lastSettle + $totalSettle),
//                "total_settle_cancel" => ($lastSettleCancel + $totalSettleCancel)
//            ),"id = {$v['id']}");
//        }
//    }

    public function __name()
    {
        return $this->_name;
    }
}

?>