<?php

/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */
class Finance_Models_PaymentInvoice extends Zend_Db_Table_Abstract {

    protected $_name = 'finance_payment_invoice';
    protected $_primary = 'trano';
    protected $db;

    public function getPrimaryKey() {
        return $this->_primary;
    }

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getpaidlist($trano) {
        $query = "SELECT trano,tgl,uid,total,val_kode,statusppn FROM " . $this->_name . " WHERE inv_no = '$trano'";

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }

    public function getTotalPaymentInvoice($prjKode = '', $sitKode = '', $startDate = '', $endDate = '') {

        $where2 = '';

        if ($sitKode != '') {
            $where2 = " AND sit_kode='$sitKode'";
        }

        if ($startDate != '' && $endDate != '')
            $sqlDate = " AND tgl BETWEEN '$startDate' AND '$endDate' ";

        $query = "SELECT COALESCE(SUM(total*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE(SUM(total*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD, 
                   COALESCE(SUM(total*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR
                   FROM {$this->_name} WHERE prj_kode = '$prjKode' $where2  $sqlDate";
 
        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data;
    }

    public function getTotalPaymentInvoiceNoPPN($prjKode = '', $sitKode = '', $startDate = '', $endDate = '') {

        $where2 = '';

        if ($sitKode != '') {
            $where2 = " AND sit_kode='$sitKode'";
        }

        if ($startDate != '' && $endDate != '')
            $sqlDate = " AND tgl BETWEEN '$startDate' AND '$endDate' ";

        $query = "SELECT COALESCE(SUM((total/1.1)*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE(SUM((total/1.1)*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD, 
                   COALESCE(SUM((total/1.1)*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR
                   FROM {$this->_name} WHERE prj_kode = '$prjKode' $where2  $sqlDate";
 
        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data;
    }

    public function getTotalPaymentInvoicePerDate($prjKode = '', $sitKode = '', $startDate = '', $endDate = '') {

        $where2 = '';

        if ($sitKode != '') {
            $where2 = " AND sit_kode='$sitKode'";
        }

        if ($startDate != '' && $endDate != '')
            $sqlDate = " AND tgl BETWEEN '$startDate' AND '$endDate' ";

        $query = "SELECT 
                   tgl,
                   trano,
                   uid,
                   val_kode,
                   rateidr,
                   prj_kode,
                   prj_nama,
                   sit_kode,
                   sit_nama,
                   COALESCE((0.9*total*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE((0.9*total*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD, 
                   COALESCE((0.9*total*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR
                   FROM {$this->_name} WHERE prj_kode = '$prjKode' $where2  $sqlDate";

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }

    public function getTotalPaymentInvoicePerDateNoPPN($prjKode = '', $sitKode = '', $startDate = '', $endDate = '') {

        $where2 = '';

        if ($sitKode != '') {
            $where2 = " AND sit_kode='$sitKode'";
        }

        if ($startDate != '' && $endDate != '')
            $sqlDate = " AND tgl BETWEEN '$startDate' AND '$endDate' ";

        $query = "SELECT 
                   tgl,
                   trano,
                   uid,
                   val_kode,
                   rateidr,
                   prj_kode,
                   prj_nama,
                   sit_kode,
                   sit_nama,
                   COALESCE(((total/1.1)*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE(((total/1.1)*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD, 
                   COALESCE(((total/1.1)*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR
                   FROM {$this->_name} WHERE prj_kode = '$prjKode' $where2  $sqlDate";

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;
    }

    public function getpaiddetail($trano) {
        $invoiceD = new Finance_Models_InvoiceDetail();
        $subselect = $this->db->select()
                        ->from(array("a" => $this->_name), array(
                            "*",
                            "total as totals",
                            "sumpaidlist" => "",
                            "total_ppn" => "if(b.statusppn ='Y',round((b.total * 0.1),2), round(b.total))"
                        ))->joinLeft(array("b" => $invoiceD->name()), "a.inv_no = b.trano", array(
            "trano as trano_detail",
            "jumlah as total_withppn",
            "total",
            "rates" => "rateidr"
        ));

        if ($trano)
            $subselect->where("a.trano = '$trano'");

        $data['data'] = $this->db->fetchAll($subselect);
        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['total_deduction_after'] = 0;
            $data['data'][$k]['total_deduction_before'] = 0;
        }
        return $data;
    }

    public function name() {
        return $this->_name;
    }

}

?>