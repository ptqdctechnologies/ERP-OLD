<?php

class Finance_Models_PaymentRPI extends Zend_Db_Table_Abstract {

    protected $_name = 'finance_payment_rpi';
    protected $db;
    protected $const;

    public function __construct() {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getName() {
        return $this->_name;
    }

    public function __name() {
        return $this->_name;
    }

    public function updateRatePayment($params) {

        $trano = $params['trano'];
        $rate = $params['rate'];
        $queryHeader = "update finance_payment_rpi set rateidr = '$rate' where trano = '$trano'";
        $queryDetail = "update finance_payment_rpid set rateidr = '$rate' where trano = '$trano'";

        $actionH = $this->db->query($queryHeader);
        $actionD = $this->db->query($queryDetail);


        try {
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

     public function getTotalPaymentInvoice ($prj_kode,$sit_kode, $startDate='', $endDate='')
    {
        $sqlDate = '';

        if($startDate !='' && $endDate !=''){$sqlDate=" AND (date(tgl) BETWEEN '$startDate' AND '$endDate') ";}

        if($sit_kode !='' && $sit_kode !=''){$sit_kode =" AND sit_kode='$sit_kode' ";}

        $query = " SELECT COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                   COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD,
                   COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR    
                   FROM {$this->_name} WHERE stspayment ='Y' AND prj_kode = '$prj_kode' $sit_kode $sqlDate ";

        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data;

    }

    public function getTotalPaymentInvoicePerItem($prj_kode='',$sit_kode='', $startDate='', $endDate='')
    {
        $sqlDate = '';

        if($startDate !='' && $endDate !=''){$sqlDate=" AND ( date(tgl) BETWEEN '$startDate' AND '$endDate') ";}

        if($sit_kode !='' && $sit_kode !=''){$sit_kode =" AND sit_kode='$sit_kode' ";}

        $query = " SELECT 
                    tgl,
                    trano,
                    uid,
                    val_kode,
                    rateidr,
                    prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'USD' THEN rateidr ELSE 1 END)),0) AS total,
                    COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'USD' THEN 1 ELSE 0 END)),0) AS totalUSD,
                    COALESCE(SUM(total_bayar*(CASE val_kode WHEN 'IDR' THEN 1 ELSE 0 END)),0) AS totalIDR    
                    FROM {$this->_name} WHERE stspayment ='Y' AND prj_kode = '$prj_kode' $sit_kode $sqlDate GROUP BY trano,val_kode";

        $fetch = $this->db->query($query);
        $data = $fetch->fetchAll();
        return $data;

    }
    
    public function getTotalPaidCostPerItem($prj_kode='', $sit_kode='', $start_date='', $end_date='')
    {
        // Buat Temporary Table PaidCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS PaidCost;
                CREATE TEMPORARY TABLE PaidCost
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
        

        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        $s_date = (($start_date !=''|| $start_date!=null) && ($end_date !=''|| $end_date!=null)) ? " AND (tgl BETWEEN '$start_date' AND '$end_date') " : '';
        
        // INSERT PAYMENT RPI
        $sql1 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Payment RPI',trano, val_kode, rateidr,
                COALESCE(total_bayar,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN total_bayar ElSE total_bayar*rateidr END,0),
                uid
                FROM finance_payment_rpid WHERE stspayment ='Y' AND prj_kode='$prj_kode' $s_sit $s_date;";
 
        $this->db->query($sql1);
        
        
        // INSERT Salary
        $sql2 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'Salary', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND nama_brg='Salaries'; ";
              
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql5 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND  stspmeal='Y'; ";
         
        $this->db->query($sql5);

        
        // INSERT APPROVED ASF
        $sql6 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve=400 AND trano LIKE '%ASF%';";
 
        $this->db->query($sql6);
        
        // INSERT APPROVED BSF
        $sql7 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve=400 AND trano LIKE '%BSF%';";
 
        $this->db->query($sql7);
        
        // INSERT OCA
        $sql8 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'OCA',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve=400 AND trano LIKE '%OCA%';";
 
        $this->db->query($sql8);
        
         // INSERT APPROVED CANCELED ASF
        $sql9 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'CANCELED ASF',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve=400 AND trano LIKE '%ASF%';";

        $this->db->query($sql9);
        
         // INSERT APPROVED CANCELED BSF
        $sql10 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'CANCELED BSF',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve=400 AND trano LIKE '%BSF%';";

        $this->db->query($sql10);
        
        // INSERT MATERIAL RETURN
        $sql11 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ;";

        $this->db->query($sql11);
        
        // INSERT MATERIAL RETURN
        $sql12 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ;";

        $this->db->query($sql12);
        
        // Temporary Table Warehouse
        $sql13 = "  DROP TEMPORARY TABLE IF EXISTS whod;
                    CREATE TEMPORARY TABLE whod
                    SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,trano, val_kode, rateidr, kode_brg,qty,harga,
                    petugas 
                    FROM procurement_whod WHERE prj_kode='$prj_kode' $s_sit $s_date ;";
      
        $this->db->query($sql13);
        
        // Temporary Table PO
        $sql14 = "  DROP TEMPORARY TABLE IF EXISTS pod;
                    CREATE TEMPORARY TABLE pod
                    SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,trano, val_kode, rateidr, kode_brg,qty,harga, 
                    petugas 
                    FROM procurement_pod WHERE prj_kode='$prj_kode' $s_sit $s_date AND approve=400 ;";

        $this->db->query($sql14);
        
        // DO-PO
        $sql15 ="   DROP TEMPORARY TABLE IF EXISTS podo;
                    CREATE TEMPORARY TABLE podo
                    SELECT w.prj_kode, w.prj_nama, w.sit_kode, w.sit_nama, w.tgl,w.trano, w.val_kode, w.rateidr, w.kode_brg,w.qty,w.harga, 
                    w.petugas 
                    FROM whod w
                    INNER JOIN pod p ON (p.kode_brg = w.kode_brg)
                    WHERE  (w.qty - p.qty) > 0 ;";

        $this->db->query($sql15);
        
        // DO-PO
        $sql16 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                 SELECT w.prj_kode, w.prj_nama, w.sit_kode, w.sit_nama, w.tgl,'DO-PO',w.trano, w.val_kode, w.rateidr, 
                    COALESCE(SUM(w.harga*w.qty),0) AS amount,
                    COALESCE(CASE val_kode WHEN 'IDR' THEN SUM(w.qty*w.harga) ElSE SUM(w.qty*w.harga*w.rateidr) END,0) AS total, 
                    w.petugas 
                FROM podo w
                GROUP BY w.kode_brg;";

        $this->db->query($sql16);
        
                $sql17 = 'SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    SUM(amount) AS amount,
                    SUM(total) AS total,
                    author FROM PaidCost GROUP BY trano,kategori,val_kode ORDER BY kategori,tgl ASC;';

        $fetch = $this->db->query($sql17);
        $data = $fetch->fetchAll();
        
        return $data;
        

    }

}

?>