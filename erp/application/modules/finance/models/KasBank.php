<?php
class Finance_Models_KasBank extends Zend_Db_Table_Abstract
{

    private $FINANCE,$db;
    public function __construct()
    {
        $this->db = Zend_Registry::get("db");
        $models = array(
            "BankPaymentVoucher",
            "AccountingJurnalBank",
            "PaymentRPI",
            "PaymentARF",
            "PaymentReimbursH"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
    }

    public function fetchAll($month='',$coa_kode='')
    {
        $select = $this->db->select()
            ->from(array("a" => $this->FINANCE->AccountingJurnalBank->name()))
            ->joinLeft(
            array("b" => $this->FINANCE->BankPaymentVoucher->name()),
            "a.trano = b.trano",
            array("item_type")
        )
            ->where("a.tgl LIKE '%$month%'")
            ->group("id");

        if ($coa_kode)
            $select = $select->where("a.coa_kode = '$coa_kode'");

        $fetch = $this->db->query($select);
        $fetch = $fetch->fetchAll();
        $data = array();
        $data['data'] = array();
        if ($fetch)
        {
            foreach($fetch as $k => $v)
            {
                $type = $v['item_type'];
                $trano = $v['trano'];
                $fetch[$k]['debit'] = floatval($v['debit']);
                $fetch[$k]['credit'] = floatval($v['credit']);
                switch($type)
                {
                    case "RPI":
                        $cek = $this->FINANCE->PaymentRPI->fetchRow("voc_trano = '$trano'");
                        if ($cek)
                        {
                            $fetch[$k]['payment_trano'] = $cek['trano'];
                            $fetch[$k]['payment_tgl'] = $cek['tgl'];
                        }
                        break;
                    case "ARF":
                        $cek = $this->FINANCE->PaymentARF->fetchRow("voc_trano = '$trano'");
                        if ($cek)
                        {
                            $fetch[$k]['payment_trano'] = $cek['trano'];
                            $fetch[$k]['payment_tgl'] = $cek['tgl'];
                        }
                        break;
                    case "REM":
                        $cek = $this->FINANCE->PaymentReimbursH->fetchRow("voc_trano = '$trano'");
                        if ($cek)
                        {
                            $fetch[$k]['payment_trano'] = $cek['trano'];
                            $fetch[$k]['payment_tgl'] = $cek['tgl'];
                        }
                        break;
                }
            }
            $data['data'] = $fetch;
        }

        return $data;
    }
}
?>