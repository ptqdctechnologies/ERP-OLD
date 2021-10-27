<?php

Class QDC_Inventory_Stock {

    public $DEFAULT;
    public $FINANCE;
    public $LOGISTIC;
    private $db;

    public function __construct($params = '') {

        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        $models = array(
            "MasterPeriode",
            "AccountingSaldoStock"
        );
        $this->FINANCE = QDC_Model_Finance::init($models);
        $models = array(
            "LogisticInputSupplier",
            "LogisticInputSupplierH",
            "LogisticMaterialReturn",
            "LogisticMaterialReturnH",
            "LogisticMaterialCancel",
            "LogisticMaterialCancelH",
            "LogisticDod",
            "LogisticDoh",
            "LogisticDord",
            "LogisticDorh"
        );
        $this->LOGISTIC = QDC_Model_Logistic::init($models);
        $models = array(
            "MasterBarang"
        );
        $this->DEFAULT = QDC_Model_Default::init($models);
        $this->db = Zend_Registry::get("db");
    }

    public static function getConfig($models = array()) {
        
    }

    public static function factory($params = array()) {
        return new self($params);
    }

    public function getStock($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        //----------------------- temporary table first!! --------------------- //
        //              kolek al rekod,den put in to your temporari tabel       //

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS detailstock;
            CREATE TEMPORARY TABLE detailstock( 
            `id` INT NOT NULL AUTO_INCREMENT ,
            `trano` VARCHAR(20) NULL,
            `kode_brg` VARCHAR(45) NULL ,
            `nama_brg` VARCHAR(75) NULL ,
            `gdg_kode` VARCHAR(45) NULL ,            
            `masuk` DECIMAL(22,2) DEFAULT '0.00' ,
            `keluar` DECIMAL(22,2) DEFAULT '0.00' ,
            `saldo` DECIMAL(22,2) DEFAULT '0.00',
            `tgl` DATETIME NULL , 
            `urut` VARCHAR(1) NULL,
            `harga` DECIMAL(22,2) DEFAULT '0.00',
            
            PRIMARY KEY (`id`) ,
            UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            
            ALTER TABLE `detailstock`
                ADD INDEX `idx1` (`kode_brg` ASC),
                ADD INDEX `idx2` (`trano` ASC),
                ADD INDEX `idx3` (`gdg_kode` ASC);";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


//        if ($periode) {
//            $periode = QDC_Finance_Periode::factory()->getPeriode($periode);
//            $tglAwal = $periode['tgl_awal'] . " 00:00:00";
//            $tglAkhir = $periode['tgl_akhir'] . " 23:59:59";
//        }
        //get data from saldo stock

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingSaldoStock->__name()), array(
                    "kode_brg",
                    "nama_brg",
                    "qty",
                    "periode",
                    "tahun",
                    "hargaavg",
                    "gdg_kode",
                    "tgl" => "IF(tgl IS NULL, STR_TO_DATE(CONCAT(tahun,'-',periode,'-01'),'%Y-%m-%d'),tgl)"
                ))
                ->order(array("tgl DESC"));

        if ($kode_brg)
            $select = $select->where("kode_brg = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        if ($periode) {
//            $s = new DateTime(date("Y-m-d", strtotime($periode)));
//            $s->sub(new DateInterval('P1D'));
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $bulan = $periodenew['bulan'];
            $tahun = $periodenew['tahun'];
//            $start = $s->format("Y-m-d");
//            $select = $select->where("(" . $this->db->quoteInto(" periode = ?", date("m", strtotime($periode)) - 1) . " AND " . $this->db->quoteInto("tahun = ?", date("Y", strtotime($periode))) . ")");
            if ($periode) {
                if ($bulan != '') {
                    if ($bulan == '01') {
                        $bulan = '12';
                        $tahun = (string) ($tahun - 1);
                        $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                    } else {
                        $tahun = (string) ($tahun);

                        if ($bulan <= 10)
                            $bulan = "0" . (string) ($bulan - 1);
                        else
                            $bulan = (string) ($bulan - 1);

                        $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                    }
                }
            }
//            $select = $select->where("(CAST(periode AS DECIMAL) <= " . intval($bulan) . " AND " . "CAST(tahun AS DECIMAL) <= " . intval($tahun) . ")");
        }

        $select2 = $this->db->select()
                ->from(array($this->DEFAULT->MasterBarang->__name()));
        if ($kode_brg)
            $select2 = $select2->where("kode_brg = '$kode_brg'" );
        if ($nama_brg)
            $select2 = $select2->where("nama_brg LIKE ?", "%{$nama_brg}%");

        $select = $this->db->select()
                ->from(
                        array("a" => $select2), array(
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "a.nama_brg",
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
//                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    "qty" => "COALESCE(sum(b.qty),0)",
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("0"),
                    "harga" => "Coalesce(sum(b.qty*b.hargaavg),0)"
                        )
                )
                ->joinLeft(array("b" => $select), 'a.kode_brg=b.kode_brg', array())
                ->group(array("kode_brg"));

        $select = $select->where("stspmeal='N'");

        $selectSql = (string) $select;

        $sql = "INSERT INTO detailstock $selectSql";
        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //get data from isupp        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticInputSupplier->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"),
                    "harga" => "Coalesce(sum(qty*harga),0)"
                )
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "
            INSERT INTO detailstock $selectSql
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from ican        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticMaterialCancel->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"), "harga" => "Coalesce(sum(qty*harga),0)"
                )
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from ilov        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticMaterialReturn->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"), "harga" => "Coalesce(sum(qty*harga),0)")
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from do        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    new Zend_Db_Expr("0"),
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("2"), "harga" => "Coalesce(sum(qty*harga),0)")
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");
        //note cari do untuk semua gudang ada di DOH
        //tambahkan Site pada kode pencarian gudang
        //

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.trano",
                    "a.kode_brg",
                    "a.nama_brg",
                    "b.gdg_kode_to",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "a.tgl",
                    new Zend_Db_Expr("2"), new Zend_Db_Expr("0"))
                )->join(array("b" => $this->LOGISTIC->LogisticDoh->__name()), "a.trano=b.trano", array())
                ->group(array("a.trano"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("b.gdg_kode_to=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }

        ////note cari do untuk semua gudang ada di DOH
        //tambahkan Site pada kode pencarian gudang
        //

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
//
        //collect all
        $select = $this->db->select()
                ->from(array("detailstock"), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->order(array("nama_brg","kode_brg", "tgl asc", "urut asc"))
                ->limit($limit, $offset);



        $data = $this->db->fetchAll($select);
        foreach ($data as $k => $v) {
            if ($v['trano'] == '' && $v['urut'] != 0)
                unset($data[$k]);
        }

        
        $qtymasuk = $qtykeluar = $saldo = $totalhargaMasuk = $totalhargaKeluar = 0;
        
        foreach ($data as $k => $v) {

            if ($v['masuk'] != '0' || $v['saldo'] != '0') {
                $qtymasuk += $v['masuk'];
                $totalhargaMasuk += $v['harga'];
            }
            if ($v['keluar'] != '0') {
                $qtykeluar += $v['keluar'];
                $totalhargaKeluar += $v['harga'];
            }

            $saldo += $v['saldo'];
        }

        $totalQty = ($saldo + $qtymasuk) - $qtykeluar;
        $totalharga = $totalhargaMasuk - $totalhargaKeluar;

        $finalAvgPrice = $totalharga / $totalQty;
        
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        return array(
            "data" => $data,
            "count" => $count,
            "hargaavg" =>$finalAvgPrice
        );
    }

    public function createTableActualStock() {
        $query = "DROP TEMPORARY TABLE IF EXISTS actual_stock;";
        $fetch = $this->db->query($query);

        $query2 = "CREATE TEMPORARY TABLE actual_stock
                  ( `id` INT NOT NULL AUTO_INCREMENT ,
                    `kode_brg` VARCHAR(45) NULL ,
                    `nama_brg` VARCHAR(45) NULL ,
                    `gdg_kode` VARCHAR(45) NULL ,
                    `actual` DECIMAL(22,2) DEFAULT '0.00' ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC));

            ALTER TABLE `actual_stock`
            ADD INDEX `idx1` (`kode_brg` ASC),
            ADD INDEX `idx2` (`gdg_kode` ASC);
               ";
        $fetch = $this->db->query($query2);
    }

    public function createTableAllStock() {
        $query = "DROP TEMPORARY TABLE IF EXISTS all_stock;";
        $fetch = $this->db->query($query);

        $query2 = "CREATE TEMPORARY TABLE all_stock
                  ( `id` INT NOT NULL AUTO_INCREMENT ,
                    `kode_brg` VARCHAR(45) NULL ,
                    `sat_kode` VARCHAR(45) NULL ,
                    `nama_brg` VARCHAR(45) NULL ,
                    `gdg_kode` VARCHAR(45) NULL ,
                    `masuk` DECIMAL(22,2) DEFAULT '0.00' ,
                    `keluar` DECIMAL(22,2) DEFAULT '0.00' ,
                    `saldo` DECIMAL(22,2) DEFAULT '0.00' ,
                    `is_stock` VARCHAR(45) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC));

            ALTER TABLE `all_stock`
            ADD INDEX `idx1` (`kode_brg` ASC),
            ADD INDEX `idx2` (`gdg_kode` ASC);
               ";
        $fetch = $this->db->query($query2);
    }

    public function getStockAll($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if ($show_all == '')
            $show_all = false;


        //Create empty Temporary table for All Stock
        $this->createTableAllStock();


        //Ambil data master_barang-----------------------------------------------------------------------

        $condition = "stspmeal ='N'";
        if ($kode_brg)
            $condition.=" AND kode_brg = '$kode_brg' ";
        if ($nama_brg)
            $condition.=" AND nama_brg LIKE '%$nama_brg%' ";

        $sql = "INSERT INTO all_stock SELECT NULL,kode_brg,sat_kode,nama_brg,NULL,NULL,0,0,0 FROM master_barang_project_2009 where $condition ORDER BY kode_brg;";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Ambil data iSupp---------------------------------------------------------------------
        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticInputSupplier->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "a.nama_brg",
                    "a.gdg_kode",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0")
                        )
                )->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->group(array("a.kode_brg", "a.gdg_kode"))
                ->order(array("a.kode_brg"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("a.nama_brg LIKE ?", "%{$nama_brg}%");


        if ($gdg_kode)
            $select = $select->where("a.gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("a.tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("a.tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "INSERT INTO all_stock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Ambil data i-Can
        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticMaterialCancel->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "a.nama_brg",
                    "a.gdg_kode",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0")
                        )
                )->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->group(array("a.kode_brg", "a.gdg_kode"))
                ->order(array("a.kode_brg"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("a.nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("a.gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("a.tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("a.tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "INSERT INTO all_stock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //Ambil data i-Lov
        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticMaterialReturn->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "a.nama_brg",
                    "a.gdg_kode",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0")
                        )
                )->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->group(array("a.kode_brg", "a.gdg_kode"))
                ->order(array("a.kode_brg"));
        if ($kode_brg)
            $select = $select->where("a.kode_brg  = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("a.nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("a.gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("a.tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("a.tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");


        $selectSql = (string) $select;
        $sql = "INSERT INTO all_stock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Ambil data DO
        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "a.nama_brg",
                    "a.gdg_kode",
                    new Zend_Db_Expr("0"),
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0")
                        )
                )->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->group(array("a.kode_brg", "a.gdg_kode"))
                ->order(array("a.kode_brg"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg = '$kode_brg'");
        if ($nama_brg)
            $select = $select->where("a.nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("a.gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("a.tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("a.tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");
        //
        $selectSql = (string) $select;
        $sql = "INSERT INTO all_stock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //Ambil data DO
        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "a.nama_brg",
                    "c.gdg_kode_to",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0")
                        )
                )->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->join(array("c" => $this->LOGISTIC->LogisticDoh->__name()), "a.trano=c.trano", array())
                ->group(array("a.kode_brg", "a.gdg_kode", "a.trano"))
                ->order(array("a.kode_brg"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("a.nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("a.gdg_kode=?", $gdg_kode);
        if ($periode) {
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";
            $tglAkhir = $periodenew['tgl_akhir'] . " 23:59:59";
            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
        }
//        if ($periode)
//            $select = $select->where("(" . $this->db->quoteInto("a.tgl >= ?", date("Y-m-01", strtotime($periode))) . " AND " . $this->db->quoteInto("a.tgl <= ?", date("Y-m-d", strtotime($periode))) . ")");
//        $selectSql = (string) $select;
////        var_dump($selectSql);die;
//        $sql = "INSERT INTO all_stock $selectSql ";
//
//        $fetch = $this->db->prepare($sql);
//        $fetch->execute();
//        
        //ambil saldo stock

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingSaldoStock->__name()), array(
                    "kode_brg",
                    "nama_brg",
                    "qty",
                    "periode",
                    "tahun",
                    "hargaavg",
                    "gdg_kode",
                    "tgl" => "IF(tgl IS NULL, STR_TO_DATE(CONCAT(tahun,'-',periode,'-01'),'%Y-%m-%d'),tgl)"
                ))
                ->order(array("tgl DESC"));

        if ($kode_brg)
            $select = $select->where("kode_brg = '$kode_brg'");
        if ($nama_brg)
            $select = $select->where("nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

//         if ($periode) {
//            $periode = QDC_Finance_Periode::factory()->getPeriode($periode);
//            $tglAwal = $periode['tgl_awal'] . " 00:00:00";
//            $tglAkhir = $periode['tgl_akhir'] . " 23:59:59";
//            $select = $select->where("a.tgl between '$tglAwal' and '$tglAkhir'");
//        }
        if ($periode) {
//            $s = new DateTime(date("Y-m-d", strtotime($periode)));
//            $s->sub(new DateInterval('P1D'));
            $periodenew = QDC_Finance_Periode::factory()->getPeriode($periode);
            $bulan = $periodenew['bulan'];
            $tahun = $periodenew['tahun'];
            if ($periode) {
                if ($bulan != '') {
                    if ($bulan == '01') {
                        $bulan = '12';
                        $tahun = (string) ($tahun - 1);
                        $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                    } else {
                        $tahun = (string) ($tahun);

                        if ($bulan <= 10)
                            $bulan = "0" . (string) ($bulan - 1);
                        else
                            $bulan = (string) ($bulan - 1);

                        $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                    }
                }
            }
//            $start = $s->format("Y-m-d");
//            $select = $select->where("(" . $this->db->quoteInto(" periode = ?", date("m", strtotime($periode)) - 1) . " AND " . $this->db->quoteInto("tahun = ?", date("Y", strtotime($periode))) . ")");
//            $select = $select->where("(CAST(periode AS DECIMAL) <= " . intval($bulan) . " AND " . "CAST(tahun AS DECIMAL) <= " . intval($tahun) . ")");
        }

        $select = $this->db->select()
                ->from(
                        array("a" => $select), array(
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "b.sat_kode",
                    "nama_brg",
                    "gdg_kode",
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "qty" => "COALESCE(sum(a.qty),0)",
                    new Zend_Db_Expr("1")
                        )
                )
                ->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->group(array("a.kode_brg", "a.gdg_kode"));

        $select = $select->where("stspmeal='N'");

        $selectSql = (string) $select;

        $sql = "
            INSERT INTO all_stock
            $selectSql
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        $select = $this->db->select()
                ->from(array("a" => "all_stock"), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *"),
                    "a.id",
                    "a.kode_brg",
                    "a.nama_brg",
                    "a.sat_kode",
                    "a.gdg_kode",
                    "SUM(COALESCE(a.saldo,0))saldo",
                    "SUM(COALESCE(a.masuk,0)) masuk",
                    "SUM(COALESCE(a.keluar,0)) keluar",
                    "((SUM(COALESCE(a.saldo,0))+SUM(COALESCE(a.masuk,0)))-SUM(COALESCE(a.keluar,0)))AS balance",
                    new Zend_Db_Expr("0")
                ))->join(array("b" => $this->DEFAULT->MasterBarang->__name()), "a.kode_brg=b.kode_brg", array())
                ->where("stspmeal = 'N'")
                ->group(array("a.kode_brg", "a.gdg_kode"))
                ->order(array("a.nama_brg","a.kode_brg"))
                ->having("balance != 0")
                ->limit($limit, $offset);

        if ($show_all == true)
            $select->reset(Zend_Db_Select::HAVING);

        $data = $this->db->fetchAll($select);
//        foreach ($data as $k => $v) {
//            if ($v['is_stock'] == '1')
////                unset($data[$k]);
//        }

        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        return array(
            "data" => $data,
            "count" => $count
        );
//        //solusi sementara untuk menampilkan data brdasarkan gudang
//        
//        foreach ($data as $k => $v) {
//            $kode = $v['kode_brg'];
//            foreach ($v as $k2 => $v2){
//              $temp[$kode][$k2] = $v2;
//              if ($k2 == 'gdg_kode')
//              {
//                  $temp[$kode]['total_balance'][] = array(
//                        "gdg_kode" => $v['gdg_kode'],
//                        "balance" => $v['balance']
//                    );
//              }
//              
//          }
//            
//        }
//
//
////        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
//        $count = count($temp);
//        return array(
//            "data" => $temp,
//            "count" => $count
//        );
    }

    public function compareActualStock($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if (!$actual_stock || count($actual_stock) == 0)
            return false;

        $this->createTableActualStock();

        foreach ($actual_stock as $k => $v) {
            $sql = "INSERT INTO actual_stock(kode_brg,nama_brg,gdg_kode,actual) VALUES (" .
                    "'" . $v['kode_brg'] . "'," .
                    "'" . $v['nama_brg'] . "'," .
                    "'" . $v['gdg_kode'] . "'," .
                    $v['actual'] .
                    ")";
            $this->db->query($sql);
        }

        $this->getStockAll($params);

        $select = $this->db->select()
                ->from(array("all_stock"), array(
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "SUM(COALESCE(saldo,0))saldo",
                    "SUM(COALESCE(masuk,0)) masuk",
                    "SUM(COALESCE(keluar,0)) keluar",
                    "((SUM(COALESCE(saldo,0))+SUM(COALESCE(masuk,0)))-SUM(COALESCE(keluar,0)))AS balance"
                ))
                ->group(array("kode_brg", "gdg_kode"))
                ->having("balance != 0")
                ->order(array("kode_brg"));

        $sql = $this->db->select()
                ->from(array("a" => "actual_stock"))
                ->joinLeft(array("b" => $select), "a.kode_brg=b.kode_brg", array(
            "saldo",
            "masuk",
            "keluar",
            "balance",
        ));

        $data = $this->db->fetchAll($sql);

        return $data;
    }

    public function getStockForDo($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
}

        //----------------------- temporary table first!! --------------------- //
        //              kolek al rekod,den put in to your temporari tabel       //

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS detailstock;
            CREATE TEMPORARY TABLE detailstock( 
            `id` INT NOT NULL AUTO_INCREMENT ,
            `trano` VARCHAR(20) NULL,
            `kode_brg` VARCHAR(45) NULL ,
            `nama_brg` VARCHAR(75) NULL ,
            `gdg_kode` VARCHAR(45) NULL ,            
            `masuk` DECIMAL(22,2) DEFAULT '0.00' ,
            `keluar` DECIMAL(22,2) DEFAULT '0.00' ,
            `saldo` DECIMAL(22,2) DEFAULT '0.00',
            `tgl` DATETIME NULL , 
            `urut` VARCHAR(1) NULL,
            
            PRIMARY KEY (`id`) ,
            UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            
            ALTER TABLE `detailstock`
                ADD INDEX `idx1` (`kode_brg` ASC),
                ADD INDEX `idx2` (`trano` ASC),
                ADD INDEX `idx3` (`gdg_kode` ASC);";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        $periodenew = QDC_Finance_Periode::factory()->getCurrentPeriode();
        $bulan = $periodenew['bulan'];
        $tahun = $periodenew['tahun'];
        $tglAwal = $periodenew['tgl_awal'] . " 00:00:00";

        //get data from saldo stock

        $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingSaldoStock->__name()), array(
                    "kode_brg",
                    "nama_brg",
                    "qty",
                    "periode",
                    "tahun",
                    "hargaavg",
                    "gdg_kode",
                    "tgl" => "IF(tgl IS NULL, STR_TO_DATE(CONCAT(tahun,'-',periode,'-01'),'%Y-%m-%d'),tgl)"
                ))
                ->order(array("tgl DESC"));

        if ($kode_brg)
            $select = $select->where("kode_brg = '$kode_brg' ");
        if ($nama_brg)
            $select = $select->where("nama_brg LIKE ?", "%{$nama_brg}%");
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);




        if ($periodenew) {
            if ($bulan != '') {
                if ($bulan == '01') {
                    $bulan = '12';
                    $tahun = (string) ($tahun - 1);
                    $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                } else {
                    $tahun = (string) ($tahun);

                    if ($bulan <= 10)
                        $bulan = "0" . (string) ($bulan - 1);
                    else
                        $bulan = (string) ($bulan - 1);

                    $select = $select->where("periode = '$bulan' AND tahun = '$tahun'");
                }
            }
        }

        $select2 = $this->db->select()
                ->from(array($this->DEFAULT->MasterBarang->__name()));
        if ($kode_brg)
            $select2 = $select2->where("kode_brg = '$kode_brg'");
        if ($nama_brg)
            $select2 = $select2->where("nama_brg LIKE ?", "%{$nama_brg}%");

        $select = $this->db->select()
                ->from(
                        array("a" => $select2), array(
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("NULL"),
                    "a.kode_brg",
                    "a.nama_brg",
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "qty" => "COALESCE(sum(b.qty),0)",
                    new Zend_Db_Expr("NULL"),
                    new Zend_Db_Expr("0")
                        )
                )
                ->joinLeft(array("b" => $select), 'a.kode_brg=b.kode_brg', array())
                ->group(array("kode_brg"));

        $select = $select->where("stspmeal='N'");

        $selectSql = (string) $select;

        $sql = "INSERT INTO detailstock $selectSql";
        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        

        //get data from isupp        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticInputSupplier->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"))
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        $select = $select->where("tgl between '$tglAwal' and '$tgl_current'");


        $selectSql = (string) $select;
        $sql = "
            INSERT INTO detailstock $selectSql
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from ican        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticMaterialCancel->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"))
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        $select = $select->where("tgl between '$tglAwal' and '$tgl_current'");

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from ilov        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticMaterialReturn->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("1"))
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        $select = $select->where("tgl between '$tglAwal' and '$tgl_current'");

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        //get data from do        
        $select = $this->db->select()
                ->from(
                        array($this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "trano",
                    "kode_brg",
                    "nama_brg",
                    "gdg_kode",
                    new Zend_Db_Expr("0"),
                    "qty" => new Zend_Db_Expr("SUM(qty)"),
                    new Zend_Db_Expr("0"),
                    "tgl",
                    new Zend_Db_Expr("2"))
                )
                ->group(array("trano"));

        if ($kode_brg)
            $select = $select->where("kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("gdg_kode=?", $gdg_kode);

        $select = $select->where("tgl between '$tglAwal' and '$tgl_current'");
        //note cari do untuk semua gudang ada di DOH
        //tambahkan Site pada kode pencarian gudang
        //

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


        $select = $this->db->select()
                ->from(
                        array("a" => $this->LOGISTIC->LogisticDod->__name()), array(
                    new Zend_Db_Expr("NULL"),
                    "a.trano",
                    "a.kode_brg",
                    "a.nama_brg",
                    "b.gdg_kode_to",
                    "a.qty" => new Zend_Db_Expr("SUM(a.qty)"),
                    new Zend_Db_Expr("0"),
                    new Zend_Db_Expr("0"),
                    "a.tgl",
                    new Zend_Db_Expr("2"))
                )->join(array("b" => $this->LOGISTIC->LogisticDoh->__name()), "a.trano=b.trano", array())
                ->group(array("a.trano"));

        if ($kode_brg)
            $select = $select->where("a.kode_brg=?", $kode_brg);
        if ($gdg_kode)
            $select = $select->where("b.gdg_kode_to=?", $gdg_kode);

        $select = $select->where("a.tgl between '$tglAwal' and '$tgl_current'");

        ////note cari do untuk semua gudang ada di DOH
        //tambahkan Site pada kode pencarian gudang
        //

        $selectSql = (string) $select;
        $sql = "INSERT INTO detailstock $selectSql ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
//
        //collect all
        $select = $this->db->select()
                ->from(array("detailstock"), array(
                    new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
                ))
                ->order(array("nama_brg", "kode_brg", "tgl asc", "urut asc"))
                ->limit($limit, $offset);



        $data = $this->db->fetchAll($select);
        
        
        $saldo = $data['0']['saldo'];
        $balance = 0;
        foreach ($data as $k => $v) {

            if ($v['saldo'] == '0') {

                if ($v['masuk'] !== '0') {
                    $balance = $saldo + $v['masuk'];
                    $saldo = $balance;
                }

                if ($v['keluar'] !== '0') {
                    $balance = $saldo - $v['keluar'];
                    $saldo = $balance;
                }
            }
        }
       
//        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");
        return $saldo;
    }

}

?>