<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/1/11
 * Time: 11:05 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_InvoiceDetail extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_invoiced';

//    protected $_primary = 'trano';
    protected $db;

//    public function getPrimaryKey ()
//    {
//        return $this->_primary;
//    }


    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name() {
        return $this->_name;
    }

    public function getitemlist ($trano)
    {
        $query = "SELECT nama_brg,val_kode,total FROM " . $this->_name . " WHERE trano = '$trano'";;

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll ();
        return $data;

    }
    
     public function getTotalInvoice ($prj_kode,$sit_kode, $startDate='', $endDate='')
    {
        $sqlDate='';
        
        if($startDate !='' && $endDate !=''){$sqlDate=" AND (tgl BETWEEN '$startDate' AND '$endDate') ";}
        
        if($sit_kode !='' && $sit_kode !=''){$sit_kode =" AND sit_kode='$sit_kode' ";}
        
        $query = " SELECT COALESCE(SUM(harga*qty*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE(SUM(harga*qty*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD,
                   COALESCE(SUM(harga*qty*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR    
                   FROM {$this->_name} WHERE prj_kode = '$prj_kode' $sit_kode $sqlDate AND trano NOT IN (SELECT invoice_no FROM erpdb.finance_invoice_ci WHERE prj_kode = '$prj_kode' $sit_kode $sqlDate)";
          
        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data;

    }
    
     public function getTotalInvoicePerItem($prj_kode='',$sit_kode='', $startDate='', $endDate='')
    {
        $sqlDate='';$sqlDate2='';
        
        if($startDate !='' && $endDate !=''){$sqlDate=" AND (tgl BETWEEN '$startDate' AND '$endDate') ";}
        
        if($startDate !='' && $endDate !=''){$sqlDate2=" AND (cancel_date BETWEEN '$startDate' AND '$endDate') ";}

        if($sit_kode !='' && $sit_kode !=''){$sit_kode =" AND sit_kode='$sit_kode' ";}
        
        // Buat Temporary Table InvoiceTemp
        $sql ="DROP TEMPORARY TABLE IF EXISTS InvoiceTemp;
                CREATE TEMPORARY TABLE InvoiceTemp
                (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    prj_kode varchar(255) DEFAULT '',
                    prj_nama varchar(255) DEFAULT '',
                    sit_kode varchar(255) DEFAULT '',
                    sit_nama varchar(255) DEFAULT '',
                    tgl timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    kategori varchar(20) DEFAULT '',
                    trano char(20) DEFAULT '',
                    val_kode varchar(6) DEFAULT '',
                    rateidr decimal(12,2) DEFAULT '0',
                    amount decimal(22,4) DEFAULT '0',
                    total decimal(22,4) DEFAULT '0',
                    author varchar(30) DEFAULT '',
                    PRIMARY KEY (id)
                );";
        
        $this->db->query($sql);
        
        // INSERT INVOICE
        $sql2 ="INSERT INTO InvoiceTemp (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'Invoice', trano, val_kode, rateidr, COALESCE(harga*qty,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE harga*qty*rateidr END,0), uid
                FROM {$this->_name} 
                WHERE prj_kode = '$prj_kode' $sit_kode $sqlDate ; ";
     
        $this->db->query($sql2);
        
        // INSERT CANCELED INVOICE
        $sql3 ="INSERT INTO InvoiceTemp (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, cancel_date, 'Canceled Invoice', trano, val_kode, rateidr, COALESCE(-1*total_cancel,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (-1*total_cancel) ElSE -1*total_cancel*rateidr END,0), uid
                FROM erpdb.finance_invoice_ci
                WHERE prj_kode = '$prj_kode' $sit_kode $sqlDate2 ;";
              
        $this->db->query($sql3);
        
        $query = " SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    SUM(amount) AS amount,
                    SUM(total) AS total,
                    author FROM InvoiceTemp GROUP BY trano,val_kode ORDER BY kategori DESC;";
        
        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;

    }
    
     public function getMaxDateInvoice ($prj_kode='',$sit_kode='')
    {

        $sitKode = ($sit_kode !='' && $sit_kode !=null) ? " AND sit_kode='$sit_kode' ":'';
        
        $query = " SELECT MAX(tgl) max_date
                   FROM {$this->_name} WHERE prj_kode = '$prj_kode' $sitKode ";
          
        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data;

    }

    public function name()
    {
        return $this->_name;
    }
}
?>