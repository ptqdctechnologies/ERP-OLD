<?php

class Default_Models_Cost extends Zend_Db_Table_Abstract {

    private $db;
    private $workidMsc;

    function __construct() {
        
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->workidMsc = array(
            1100, 2100, 3100, 4100, 5100, 6100, 7100
        );
        
    }
    
    public function isWorkidMsc($workid = '') {
        return in_array($workid, $this->workidMsc);
    }
    
    public function totalOCA($prj_kode='', $sit_kode='', $workid='',$kode_brg='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : '';
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        $sql = "SELECT  
                COALESCE(SUM(CASE val_kode WHEN 'IDR' THEN qty*harga ELSE 0 END),0) AS totalIDR, 
                COALESCE(SUM(CASE val_kode WHEN 'USD' THEN qty*harga ELSE 0 END),0) AS totalUSD,
                COALESCE(SUM(CASE val_kode WHEN 'USD' THEN qty*harga*rateidr ELSE qty*harga END),0) AS total
                FROM imderpdb.procurement_asfdd WHERE trano LIKE '%OCA%' AND prj_kode='$prj_kode' $s_sit $s_brg $s_work ;";
 
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();
        
        return $data;
    }
    
    public function getPO($trano,$workid,$kode_brg)
    {
        $sql = "SELECT  
                COALESCE(SUM(CASE val_kode WHEN 'IDR' THEN (CASE qtyspl*hargaspl WHEN 0 THEN qty*harga ELSE qtyspl*hargaspl END) ELSE 0 END),0) AS totalIDR, 
                COALESCE(SUM(CASE val_kode WHEN 'USD' THEN (CASE qtyspl*hargaspl WHEN 0 THEN qty*harga ELSE qtyspl*hargaspl END) ELSE 0 END),0) AS totalUSD,
                COALESCE(SUM(CASE val_kode WHEN 'USD' THEN qty*harga*rateidr ELSE qty*harga END),0) AS total
                FROM imderpdb.procurement_pod WHERE pr_no='$trano' AND workid='$workid' AND kode_brg='$kode_brg' AND approve !=300;";
     
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();
        
        return $data;
        
    }
    
    public function getDOR($trano,$workid,$kode_brg)
    {
        $sql = "SELECT COALESCE(SUM(qty),0) AS qty,COALESCE(SUM(qty*harga),0) AS total FROM imderpdb.procurement_pointd 
                WHERE pr_no='$trano' AND workid='$workid' AND kode_brg='$kode_brg' AND approve !=300;";
        
        $fetch = $this->db->query($sql);
        $data = $fetch->fetch();
        
        return $data;
        
    }

    public function totalRequests($prj_kode='', $sit_kode='', $workid='',$kode_brg='',$trano='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
    
        $s_trano = ($trano !=''|| $trano!=null)  ? " AND trano NOT IN ('$trano') " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : '';
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        // Buat Temporary Table RecordedCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS TotalRequest;
                CREATE TEMPORARY TABLE TotalRequest
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
        //INSERT ALL PR
        $sql1 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PR',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_prd
                WHERE approve !=300 AND prj_kode='$prj_kode' $s_sit $s_brg $s_work $s_trano ;";
        $this->db->query($sql1);
        
        
        // INSERT ALL ARF
        $sql2 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ARF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_arfd
                WHERE approve !=300 AND prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE '%ARF%';";
        
        $this->db->query($sql2);
        
        // INSERT ALL BRF
        $sql3 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM imderpdb.procurement_brfd
                WHERE approve !=300 AND prj_kode='$prj_kode' $s_sit $s_brg $s_work;";
              
        $this->db->query($sql3);
        
        // INSERT APPROVED CANCELED BSF
        $sql6 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'CANCELED BSF',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfddcancel
                WHERE prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND approve=400  AND trano LIKE '%BSF%';";

        $this->db->query($sql6);
        
        // INSERT APPROVED CANCELED ASF
        $sql7 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'CANCELED ASF',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_asfddcancel
                WHERE prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND approve=400  AND trano LIKE '%ASF%';";

        $this->db->query($sql7);
        
        // INSERT MATERIAL RETURN
        $sql8 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_whbringbackd
                WHERE approve =400 AND prj_kode='$prj_kode' $s_sit $s_brg $s_work ;";
        
        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO TotalRequest (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM procurement_whreturnd
                WHERE approve =400 AND prj_kode='$prj_kode' $s_sit $s_brg $s_work ;";
        $this->db->query($sql9);
        
        $sql12 = "SELECT COALESCE(SUM(total),0) AS total,COALESCE(SUM(amount),0) AS amount,
                    COALESCE(SUM(CASE val_kode WHEN 'IDR' THEN amount ELSE 0 END),0) AS totalIDR, 
                    COALESCE(SUM(CASE val_kode WHEN 'USD' THEN amount ELSE 0 END),0) AS totalUSD
                  FROM TotalRequest;";

        $fetch = $this->db->query($sql12);
        $data = $fetch->fetch();
        
        return $data;
    }
    
    public function paidCostPerDate($prj_kode='', $sit_kode='', $start_date='', $end_date='')
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
        
        $s_sit_rpi = ($sit_kode !=''|| $sit_kode!=null)  ? " AND d.sit_kode='$sit_kode' " : '';
        $s_date_rpi = (($start_date !=''|| $start_date!=null) && ($end_date !=''|| $end_date!=null)) ? " AND (d.tgl BETWEEN '$start_date' AND '$end_date') " : '';
        
        // INSERT ASF CLAIM
        $sql1 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF CLAIM',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%ASF%' ;";
  
        $this->db->query($sql1);
        
        // INSERT BSF CLAIM
        $sql2 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF CLAIM',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%BSF%' ;";
        
        $this->db->query($sql2);

        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM imderpdb.boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND approve !=300 ; ";

        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ; ";

        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT PAYMENT RPI(to site)
        $sql6 ="INSERT INTO PaidCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT d.prj_kode, d.prj_nama, d.sit_kode, d.sit_nama, d.tgl,'Payment RPI(to site)',d.trano, d.val_kode, d.rateidr,
                COALESCE(d.total_bayar,0),
                COALESCE(CASE d.val_kode WHEN 'IDR' THEN d.total_bayar ElSE d.total_bayar*d.rateidr END,0),
                d.uid
                FROM imderpdb.finance_payment_rpi d
                INNER JOIN imderpdb.procurement_rpih h ON (d.doc_trano = h.trano)
                WHERE d.stspayment ='Y' AND h.statusbrg='SITE' AND h.prj_kode='$prj_kode' $s_sit_rpi $s_date_rpi ;";
      
        $this->db->query($sql6);
        
        // INSERT DO
        $sql7 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'DO', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_whod
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ;";

        $this->db->query($sql7);
        
        // INSERT MATERIAL CANCEL
        $sql8 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO PaidCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql9);
        
        $sql10 = 'SELECT prj_kode,
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

        $fetch = $this->db->query($sql10);
        $data = $fetch->fetchAll();
        
        return $data;
        

    }
    
    public function recordedCostPerDate($prj_kode='', $sit_kode='', $start_date='', $end_date='')
    {

        // Buat Temporary Table RecordedCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS RecordedCost;
                CREATE TEMPORARY TABLE RecordedCost
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
        
        $s_sit_rpi = ($sit_kode !=''|| $sit_kode!=null)  ? " AND d.sit_kode='$sit_kode' " : '';
        $s_date_rpi = (($start_date !=''|| $start_date!=null) && ($end_date !=''|| $end_date!=null)) ? " AND (d.tgl BETWEEN '$start_date' AND '$end_date') " : '';
        
         // INSERT ASF CLAIM
        $sql1 ="INSERT INTO RecordedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF CLAIM',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%ASF%' ;";
 
        $this->db->query($sql1);
        
        // INSERT BSF CLAIM
        $sql2 ="INSERT INTO RecordedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF CLAIM',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%BSF%' ;";
        
        $this->db->query($sql2);

        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM imderpdb.boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND approve !=300 ; ";
  
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ; ";
      
        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT APPROVED RPI (to site)
        $sql7 ="INSERT INTO RecordedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT d.prj_kode, d.prj_nama, d.sit_kode, d.sit_nama, d.tgl,'RPI(to site)',d.trano, d.val_kode, d.rateidr,
                COALESCE(d.harga*d.qty,0),
                COALESCE(CASE d.val_kode WHEN 'IDR' THEN d.qty*d.harga ElSE d.qty*d.harga*d.rateidr END,0),
                d.petugas
                FROM imderpdb.procurement_rpid d
                WHERE  d.prj_kode='$prj_kode' $s_sit_rpi $s_date_rpi AND d.approve !=300 AND d.statusbrg='SITE' ; ";
                
        $this->db->query($sql7);
        
        // INSERT DO
        $sql5 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'DO', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_whod
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ;";

        $this->db->query($sql5);
        
        // INSERT MATERIAL CANCEL
        $sql8 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO RecordedCost(prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql9);
        
        // RPI CANCEL
        // KARENA RPI APABILA DI-CANCEL qty BERUBAH MENJADI NOL MAKA TIDAK PERLU DIQUERY 
        
                
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
                    author FROM RecordedCost GROUP BY trano,kategori,val_kode ORDER BY kategori,tgl ASC;';

        $fetch = $this->db->query($sql17);
        $data = $fetch->fetchAll();
        
        return $data;

    }
        
    public function committedCostPerDate($prj_kode='', $sit_kode='', $start_date='', $end_date='')
    {
        // Buat Temporary Table CommittedCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS CommitedCost;
                CREATE TEMPORARY TABLE CommitedCost
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
        
        $s_sit_dor = ($sit_kode !=''|| $sit_kode!=null)  ? " AND dor.sit_kode='$sit_kode' " : '';
        $s_date_dor = (($start_date !=''|| $start_date!=null) && ($end_date !=''|| $end_date!=null)) ? " AND (dor.tgl BETWEEN '$start_date' AND '$end_date') " : '';

                
        // INSERT APPROVED ARF
        $sql1 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ARF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_arfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND trano LIKE 'ARF%' AND approve!=300 ; ";
                
        $this->db->query($sql1);
        
        // INSERT APPROVED BRF
        $sql2 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM imderpdb.procurement_brfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND approve !=300 ; ";
  
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_date ; ";
      
        $this->db->query($sql4);

        
        // INSERT OCA
        $sql5 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT APPROVED PO
        $sql6 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PO',trano, val_kode, rateidr,
                CASE COALESCE(qtyspl*hargaspl,0) WHEN 0 THEN COALESCE(qty*harga,0) ELSE COALESCE(qtyspl*hargaspl,0) END,
                COALESCE(CASE val_kode WHEN 'IDR' THEN CASE COALESCE(qtyspl*hargaspl,0) WHEN 0 THEN COALESCE(qty*harga,0) ELSE COALESCE(qtyspl*hargaspl,0) END ElSE CASE COALESCE(qtyspl*hargaspl*rateidr,0) WHEN 0 THEN COALESCE(qty*harga*rateidr,0) ELSE COALESCE(qtyspl*hargaspl*rateidr,0) END END,0),
                petugas
                FROM imderpdb.procurement_pod
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND COALESCE(harga*qty,0) >0 AND approve!=300 ; ";
            
        $this->db->query($sql6);
        
        // PO CANCEL
        // KARENA PO APABILA DI-CANCEL qty BERUBAH MENJADI NOL MAKA TIDAK PERLU DIQUERY 
        
        // INSERT APPROVED DOR DARI PR YANG TIDAK DIJADIKAN PO
        $sql8=" INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author) 
                SELECT dor.prj_kode,dor.prj_nama,dor.sit_kode,dor.sit_nama,dor.tgl,'DOR',dor.trano,dor.val_kode,dor.rateidr,COALESCE(dor.qty*dor.harga,0),(CASE dor.val_kode WHEN 'IDR' THEN COALESCE(dor.qty*dor.harga,0) ElSE (COALESCE(dor.qty*dor.harga,0)*dor.rateidr) END) , dor.petugas
                FROM imderpdb.procurement_pointd dor 
                LEFT JOIN imderpdb.procurement_pod pod ON (pod.pr_no = dor.pr_no AND pod.kode_brg = dor.kode_brg)
                WHERE dor.prj_kode='$prj_kode' $s_sit_dor $s_date_dor AND (pod.trano IS NULL) AND dor.approve !=300 ";       

        $this->db->query($sql8);
        
        // INSERT MATERIAL CANCEL
        $sql9 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql9);

        
        // INSERT MATERIAL RETURN
        $sql10 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_date  AND approve!=300 ;";

        $this->db->query($sql10);
        
        // INSERT APPROVED ASF DUE TO COMPANY
        $sql11 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%ASF%' ;";
    
        $this->db->query($sql11);
        
         // INSERT APPROVED BSF DUE TO COMPANY
        $sql12 ="INSERT INTO CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_date AND qty*harga >0 AND approve !=300 AND trano LIKE '%BSF%' ;";
      
        $this->db->query($sql12);
               
        $sql13 = 'SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    SUM(amount) AS amount,
                    SUM(total) AS total,
                    author FROM CommitedCost GROUP BY trano,kategori,val_kode ORDER BY kategori,tgl ASC;';

        $fetch = $this->db->query($sql13);

        $data = $fetch->fetchAll();
        
        return $data; 
        
    }
    
    public function committedCostTotal($prj_kode='', $sit_kode='', $workid='',$kode_brg='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : '';
        
        $s_brg_dor = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND dor.kode_brg='$kode_brg' " : '';
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        $s_work_dor = ($workid !=''|| $workid!=null)  ? " AND dor.workid='$workid' " : '';
        
        $s_brg_pmeal = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : " AND kode_brg='xxx' ";
        
        $s_sit_dor = ($sit_kode !=''|| $sit_kode!=null)  ? " AND dor.sit_kode='$sit_kode' " : '';
        
        // Buat Temporary Table CommittedCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS imderpdb.CommitedCost;
                CREATE TEMPORARY TABLE imderpdb.CommitedCost
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
    
        // INSERT APPROVED ARF
        $sql1 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ARF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_arfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE 'ARF%' AND approve!=300 ;";
                
        $this->db->query($sql1);
        
        // INSERT APPROVED BRF
        $sql2 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM imderpdb.procurement_brfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";
            
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg_pmeal AND approve !=300 ;";
        
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work ;";
 
        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT APPROVED PO
        $sql6 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PO',trano, val_kode, rateidr,
                CASE COALESCE(qtyspl*hargaspl,0) WHEN 0 THEN COALESCE(qty*harga,0) ELSE COALESCE(qtyspl*hargaspl,0) END,
                COALESCE(CASE val_kode WHEN 'IDR' THEN CASE COALESCE(qtyspl*hargaspl,0) WHEN 0 THEN COALESCE(qty*harga,0) ELSE COALESCE(qtyspl*hargaspl,0) END ElSE CASE COALESCE(qtyspl*hargaspl*rateidr,0) WHEN 0 THEN COALESCE(qty*harga*rateidr,0) ELSE COALESCE(qtyspl*hargaspl*rateidr,0) END END,0),
                petugas
                FROM imderpdb.procurement_pod
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND COALESCE(harga*qty,0) >0 AND approve!=300 ; ";

        $this->db->query($sql6);
                  
        // PO CANCEL
        // KARENA PO APABILA DI-CANCEL qty BERUBAH MENJADI NOL MAKA TIDAK PERLU DIQUERY 
              
        // INSERT APPROVED DOR DARI PR YANG TIDAK DIJADIKAN PO
        $sql8=" INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author) 
                SELECT dor.prj_kode,dor.prj_nama,dor.sit_kode,dor.sit_nama,dor.tgl,'DOR',dor.trano,dor.val_kode,dor.rateidr,COALESCE(dor.qty*dor.harga,0),(CASE dor.val_kode WHEN 'IDR' THEN COALESCE(dor.qty*dor.harga,0) ElSE (COALESCE(dor.qty*dor.harga,0)*dor.rateidr) END) , dor.petugas
                FROM imderpdb.procurement_pointd dor 
                LEFT JOIN imderpdb.procurement_pod pod ON (pod.pr_no = dor.pr_no AND pod.kode_brg = dor.kode_brg)
                WHERE dor.prj_kode='$prj_kode' $s_sit_dor $s_brg_dor $s_work_dor AND (pod.trano IS NULL) AND dor.approve !=300; ";       

        $this->db->query($sql8);
        
        // INSERT MATERIAL CANCEL
        $sql9 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work  AND approve!=300 ;";

        $this->db->query($sql9);
        
        // INSERT MATERIAL RETURN
        $sql10 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work  AND approve!=300 ;";

        $this->db->query($sql10);
        
        // INSERT APPROVED ASF DUE TO COMPANY
        $sql11 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND approve !=300 AND trano LIKE '%ASF%' ;";

        $this->db->query($sql11);
        
         // INSERT APPROVED BSF DUE TO COMPANY
        $sql12 ="INSERT INTO imderpdb.CommitedCost (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND approve !=300 AND trano LIKE '%BSF%' ;";

        $this->db->query($sql12);
        
        $sql13 = 'SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    SUM(amount) AS amount,
                    SUM(total) AS total,
                    author FROM imderpdb.CommitedCost;';

        $fetch = $this->db->query($sql13);
        $data = $fetch->fetch();
        
        return $data; 
        
    }
    
    public function totalRequestsV2($prj_kode='', $sit_kode='', $workid='',$kode_brg='',$trano='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : "";
        
        $s_brg_pmeal = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : " AND kode_brg='xxx' ";
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        $s_trano = ($trano !=''|| $trano!=null)  ? " WHERE trano !='$trano' " : '';
        
        // Buat Temporary Table totalRequests
        $sql =" DROP TEMPORARY TABLE IF EXISTS totalRequests;
                CREATE TEMPORARY TABLE totalRequests
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
         
        // INSERT APPROVED ARF
        $sql1 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ARF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_arfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE 'ARF%' AND approve!=300 ; ";
                
        $this->db->query($sql1);
        
        // INSERT APPROVED BRF
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM imderpdb.procurement_brfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT APPROVED PR
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PR',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_prd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM imderpdb.boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit  $s_brg_pmeal AND approve !=300 ; ";
  
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work ; ";
      
        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT MATERIAL CANCEL
        $sql8 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql9);
        
        // INSERT APPROVED ASF DUE TO COMPANY
        $sql10 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%ASF%' AND approve !=300;";
    
        $this->db->query($sql10);
        
         // INSERT APPROVED BSF DUE TO COMPANY
        $sql11 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%BSF%' AND approve !=300  ;";
      
        $this->db->query($sql11);
        
        $sql14 = "SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    COALESCE(SUM(amount),0) AS amount,
                    COALESCE(SUM(total),0) AS total,
                    author FROM totalRequests $s_trano;";

        $fetch = $this->db->query($sql14);
        $data = $fetch->fetch();
        
        return $data;
            
    }

    public function totalRequestsdetail($prj_kode='', $sit_kode='', $workid='',$kode_brg='',$trano='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : "";
        
        $s_brg_pmeal = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : " AND kode_brg='xxx' ";
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        $s_trano = ($trano !=''|| $trano!=null)  ? " WHERE trano !='$trano' " : '';
        
        // Buat Temporary Table totalRequests
        $sql =" DROP TEMPORARY TABLE IF EXISTS totalRequests;
                CREATE TEMPORARY TABLE totalRequests
                (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    prj_kode varchar(255) DEFAULT '',
                    prj_nama varchar(255) DEFAULT '',
                    sit_kode varchar(255) DEFAULT '',
                    sit_nama varchar(255) DEFAULT '',
                    tgl timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    kategori varchar(20) DEFAULT '',
                    trano char(20) DEFAULT '',
                    qty varchar(20) DEFAULT '0',
                    harga decimal (22,4) DEFAULT '0',
                    val_kode varchar(6) DEFAULT '',
                    rateidr decimal(12,2) DEFAULT '0',
                    amount decimal(22,4) DEFAULT '0',
                    total decimal(22,4) DEFAULT '0',
                    author varchar(30) DEFAULT '',
                    PRIMARY KEY (id)
                );";
        
        $this->db->query($sql);
         
        // INSERT APPROVED ARF
        $sql1 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ARF',trano, qty, harga, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_arfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE 'ARF%' AND approve!=300 ; ";
                
        $this->db->query($sql1);
        
        // INSERT APPROVED BRF
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, qty, harga, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM imderpdb.procurement_brfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT APPROVED PR
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PR',trano, qty, harga, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_prd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran,qty, harga_borong, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM imderpdb.boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit  $s_brg_pmeal AND approve !=300 ; ";
  
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, qty, harga, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work ; ";
      
        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, qty, harga,val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM imderpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT MATERIAL CANCEL
        $sql8 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano,  qty, harga,val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, qty, harga, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, qty, harga, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql9);
        
        // INSERT APPROVED ASF DUE TO COMPANY
        $sql10 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF Due to Company',trano, qty, harga, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%ASF%' AND approve !=300;";
    
        $this->db->query($sql10);
        
         // INSERT APPROVED BSF DUE TO COMPANY
        $sql11 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano,  qty, harga, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF Due to Company',trano,qty, harga,  val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM imderpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%BSF%' AND approve !=300  ;";
      
        $this->db->query($sql11);
        
        $sql12 = "SELECT SUM(qty) as qty, avg(harga) as harga, COALESCE(SUM(total),0) AS total,COALESCE(SUM(amount),0) AS amount,val_kode as val_kode_spend,
                    COALESCE(SUM(CASE val_kode WHEN 'IDR' THEN amount ELSE 0 END),0) AS totalIDR,
                    COALESCE(SUM(CASE val_kode WHEN 'USD' THEN amount ELSE 0 END),0) AS totalUSD
                  FROM totalRequests;";

        $fetch = $this->db->query($sql12);
        $data = $fetch->fetch();
        
        return $data;
    }
}

