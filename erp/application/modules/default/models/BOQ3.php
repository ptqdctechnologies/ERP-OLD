<?php

class Default_Models_BOQ3 extends Zend_Db_Table_Abstract {

    private $db;
    private $workidMsc;

    function __construct() {
        $this->db = Zend_Registry::get('db');
        $this->workidMsc = array(
            1100, 2100, 3100, 4100, 5100, 6100, 7100
        );
    }

    public function isWorkidMsc($workid = '') {
        return in_array($workid, $this->workidMsc);
    }

    public function getBoq3ori($prjKode = '', $sitKode = '') {
    // Buat Temporary Table Original
        $s_sit = ($sitKode !=''|| $sitKode!=null)  ? " AND sit_kode='$sitKode' " : '';
        
           $sql = "DROP TEMPORARY TABLE IF EXISTS Original;
              CREATE TEMPORARY TABLE Original    
              SELECT a.trano, a.prj_kode, a.sit_kode, a.qty, a.harga, a.total, a.val_kode, b.nama_brg, b.kode_brg, a.workid, a.workname, a.cfs_kode, a.cfs_nama                
              FROM transengineer_boq3d a
              INNER JOIN master_barang_project_2009 b ON (b.kode_brg=a.kode_brg)
              WHERE a.trano !='\"\"' AND a.prj_kode = '$prjKode' $s_sit 
              ";
        $this->db->query($sql);
        
            $sql1 = "SELECT * FROM Original";
        
        
        $fetch = $this->db->query($sql1);
        $data = $fetch->fetchAll();
        return $data;
    }
    
    
    
    
    public function getAfe($prjKode = '', $sitKode = '', $workId = '', $kodeBrg = '') {
         // Buat Temporary Table ActualCost
        $sql ="DROP TEMPORARY TABLE IF EXISTS ActualCost;
                CREATE TEMPORARY TABLE ActualCost
                (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    prj_kode varchar(255) DEFAULT '',
                    sit_kode varchar(255) DEFAULT '',
                    val_kode varchar(6) DEFAULT '',
                    qty decimal(12,4) DEFAULT '0.0000',
                    harga decimal(15,4) DEFAULT '0.0000',
                    total decimal(15,4) DEFAULT '0.0000',
                    workid char(255) DEFAULT '',
                    workname char(255) DEFAULT '',
                    cfs_kode char(5) DEFAULT '',
                    cfs_nama char(255) DEFAULT '',
                    kode_brg char(50) DEFAULT '',
                    nama_brg char(255) DEFAULT '',
                    PRIMARY KEY (id)
                );";
        
        $this->db->query($sql);
       
        $s_sit = ($sitKode !=''|| $sitKode!=null)  ? " AND a.sit_kode='$sitKode' " : '';
        
        //AFE
        $sql1 = "INSERT INTO ActualCost (prj_kode, sit_kode, qty, harga, total, val_kode, id, workid, kode_brg, workname, nama_brg, cfs_kode, cfs_nama)
                SELECT a.prj_kode, a.sit_kode, a.qty, a.harga, a.total, a.val_kode, a.id, a.workid, a.kode_brg, a.workname, b.nama_brg, a.cfs_kode, a.cfs_nama
                FROM transengineer_boq3d a
                INNER JOIN master_barang_project_2009 b ON (b.kode_brg=a.kode_brg)
                WHERE
                   a. prj_kode = '$prjKode'
                    $s_sit
                ORDER BY id DESC
        ";
        $this->db->query($sql1);
        
        $sql2 = "SELECT prj_kode, sit_kode, qty, harga, total, val_kode, id, workid, kode_brg, workname, nama_brg, cfs_kode, cfs_nama
                FROM ActualCost
                GROUP BY prj_kode, sit_kode, workid, kode_brg
                    
        ";
        
        
         $fetch = $this->db->query($sql2);
         $data = $fetch->fetchAll();
         return $data;
       
    }
    
    public function getAfed($prjKode = '', $sitKode = '', $valKode = '') {
         // Buat Temporary Table ActualCost
        if($sitKode !="" || $sitKode != null){$sitKode= " AND sit_kode='$sitKode' " ;}
        if($valKode !='' || $valKode != null){$valKode= " WHERE val_kode='$valKode' " ;}
        
        //AFE
        $sql = "DROP TEMPORARY TABLE IF EXISTS erpd.actualcost;
                CREATE TEMPORARY TABLE erpdb.actualcost
                SELECT id, tgl, prj_kode, prj_nama, sit_kode, sit_nama, cfs_kode, cfs_nama, kode_brg, nama_brg, workid, workname, qty ,harga, val_kode
                FROM erpdb.transengineer_boq3d 
                WHERE
                   prj_kode = '$prjKode'
                    $sitKode
                ORDER BY id DESC
        ";
        $this->db->query($sql);
        
        $sql1 = "DROP TEMPORARY TABLE IF EXISTS erpdb.group_actualcost;
                 CREATE TEMPORARY TABLE erpdb.group_actualcost
                SELECT id, tgl, prj_kode, prj_nama, sit_kode, sit_nama, cfs_kode, cfs_nama, kode_brg, nama_brg, workid, workname, qty ,harga, val_kode
                FROM  erpdb.actualcost
                 GROUP BY prj_kode, sit_kode, workid, kode_brg
         ";
        
        $this->db->query($sql1);
        
        $sql2 = "SELECT id, tgl, prj_kode, prj_nama, sit_kode, sit_nama, cfs_kode, cfs_nama, kode_brg, nama_brg, workid, workname, qty ,harga, val_kode,
                    (qty*harga) AS total
                FROM erpdb.group_actualcost 
                    
        ";
        
        
         $fetch = $this->db->query($sql2);
         $data = $fetch->fetchAll();
         return $data;
         
         
       
    }
    
     public function getActual($prjKode = '', $sitKode = '',$valKode='',$workId='',$kodeBrg='') {

        if($sitKode !="" || $sitKode != null){$sitKode= " AND sit_kode='$sitKode' " ;}
        if($valKode !="" || $valKode != null){$valKode= " AND val_kode='$valKode' " ;}
        if($workId !="" || $workId != null){$workId= " AND workid='$workId' " ;}
        if($kodeBrg !="" || $kodeBrg != null){$kodeBrg= " AND kode_brg='$kodeBrg' " ;}
        
        //tarik BOQ3 berdasarkan id terbaru
        $sql = "DROP TEMPORARY TABLE IF EXISTS erpdb.updated_boq3;
                CREATE TEMPORARY TABLE erpdb.updated_boq3
                SELECT id, tgl,prj_kode,prj_nama,sit_kode,sit_nama,cfs_kode,cfs_nama,kode_brg,nama_brg,workid,workname,qty,harga,val_kode,rateidr
                FROM erpdb.transengineer_boq3d WHERE prj_kode='$prjKode' $sitKode  ORDER BY id DESC;";
        
        $this->db->query($sql);

        $sql2 = "DROP TEMPORARY TABLE IF EXISTS erpdb.group_updated_boq3;
                 CREATE TEMPORARY TABLE erpdb.group_updated_boq3
                 SELECT id, tgl,prj_kode,prj_nama,sit_kode,sit_nama,cfs_kode,cfs_nama,kode_brg,nama_brg,workid,workname,qty,harga,val_kode,rateidr
                 FROM  erpdb.updated_boq3 GROUP BY kode_brg,workid;";
        
        $this->db->query($sql2);

        $sql3 = "SELECT 
                    id, tgl,prj_kode,prj_nama,sit_kode,sit_nama,cfs_kode,cfs_nama,kode_brg,nama_brg,workid,workname,val_kode,rateidr,qty,harga,
                    FROM erpdb.group_updated_boq3 WHERE prj_kode='$prjKode' $valKode $workId $kodeBrg ;";

        $fetch = $this->db->query($sql3);
        $data = $fetch->fetchAll();

        return $data;
    }
    
    public function totalRequestsV2($prj_kode='', $sit_kode='', $workid='',$kode_brg='')
    {
        $s_sit = ($sit_kode !=''|| $sit_kode!=null)  ? " AND sit_kode='$sit_kode' " : '';
        
        $s_brg = (($kode_brg !=''|| $kode_brg!=null) && !$this->isWorkidMsc($workid) )  ? " AND kode_brg='$kode_brg' " : '';
        
        $s_work = ($workid !=''|| $workid!=null)  ? " AND workid='$workid' " : '';
        
        // Buat Temporary Table CommittedCost
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
                FROM erpdb.procurement_arfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE 'ARF%' AND approve!=300 ; ";
                
        $this->db->query($sql1);
        
        // INSERT APPROVED BRF
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BRF',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                uid
                FROM erpdb.procurement_brfd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT APPROVED PR
        $sql2 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'PR',trano, val_kode, rateidr,
                COALESCE(harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga ElSE qty*harga*rateidr END,0),
                petugas
                FROM erpdb.procurement_prd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ; ";
                
        $this->db->query($sql2);
        
        // INSERT PIECEMEAL
        $sql3 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Piecemeal',notran, val_kode, rateidr,
                COALESCE(harga_borong*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN qty*harga_borong ElSE qty*harga_borong*rateidr END,0),
                ''
                FROM erpdb.boq_dboqpasang
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg AND approve !=300 ; ";
  
        $this->db->query($sql3);
        
        // INSERT Salary, Jamsostek, Personal Income Tax (VERSI SEBELUM OCA)
        $sql4 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM erpdb.procurement_sald 
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg ; ";
      
        $this->db->query($sql4);
        
        // INSERT OCA
        $sql5 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl, 'OCA', trano, val_kode, rateidr, COALESCE(qty*harga,0), COALESCE(CASE val_kode WHEN 'IDR' THEN (qty*harga) ElSE qty*harga*rateidr END,0), petugas
                FROM erpdb.procurement_asfdd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND trano LIKE '%OCA%' AND approve !=300 ;";

        $this->db->query($sql5);
        
        // INSERT MATERIAL CANCEL
        $sql8 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Cancel',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM erpdb.procurement_whbringbackd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql8);
        
        // INSERT MATERIAL RETURN
        $sql9 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'Material Return',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM erpdb.procurement_whreturnd
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND approve!=300 ;";

        $this->db->query($sql9);
        
        // INSERT APPROVED ASF DUE TO COMPANY
        $sql10 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'ASF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM erpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%ASF%' AND approve !=300;";
    
        $this->db->query($sql10);
        
         // INSERT APPROVED BSF DUE TO COMPANY
        $sql11 ="INSERT INTO totalRequests (prj_kode, prj_nama, sit_kode, sit_nama, tgl,kategori,trano, val_kode, rateidr,amount,total,author)
                SELECT prj_kode, prj_nama, sit_kode, sit_nama, tgl,'BSF Due to Company',trano, val_kode, rateidr,
                COALESCE(-1*harga*qty,0),
                COALESCE(CASE val_kode WHEN 'IDR' THEN -1*qty*harga ElSE -1*qty*harga*rateidr END,0),
                petugas
                FROM erpdb.procurement_asfddcancel
                WHERE  prj_kode='$prj_kode' $s_sit $s_brg $s_work AND qty*harga >0 AND trano LIKE '%BSF%' AND approve !=300  ;";
      
        $this->db->query($sql11);
        
        $sql14 = 'SELECT prj_kode,
                    prj_nama,
                    sit_kode,
                    sit_nama,
                    tgl,
                    kategori,
                    trano,
                    val_kode,
                    COALESCE(SUM(amount),0) AS amount,
                    COALESCE(SUM(total),0) AS total,
                    author FROM totalRequests;';

        $fetch = $this->db->query($sql14);
        $data = $fetch->fetch();
        
        return $data;
            
    }
    

}

