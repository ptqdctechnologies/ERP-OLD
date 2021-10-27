<?php

Class QDC_Inventory_Periode {

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
            "LogisticDorh",
            "MasterGudang"
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

    /**
     * @static
     * @param $params
     * @return QDC_Finance_Periode
     *
     * Method factory dipanggil apabila QDC_FINANCE_PERIODE di inisialisasi secara statik
     */
    public static function factory($params = array()) {
        return new self($params);
    }

    /**
     * @param string $perKode
     *
     * Fungsi untuk mendapatkan daftar barang saat ini, hanya khusus Warehouse to Site
     * Current QTY In = (iSupp + iCan + iLov)
     * Current QTY Out = DO
     */
    public function getCurrentItems($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        $where = '';
        //Ambil periode accounting yg sedang aktif
        if ($perKode != '') {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);

            $tglAwal = $periode['tgl_awal'];
            $tglAkhir = $periode['tgl_akhir'];

            $where = " WHERE tgl BETWEEN '$tglAwal' AND '$tglAkhir'";

            $bulan = $periode['bulan'];
            $tahun = $periode['tahun'];
        }

        //Jika ada filter bedasarkan tgl
        if ($start_date != '') {
            $tglAwal = date("Y-m-d", strtotime($start_date));
            if ($end_date != '') {
                $tglAkhir = date("Y-m-d", strtotime($end_date));
                $where = " WHERE tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
            } else {
                $where = " WHERE tgl = '$tglAwal'";
            }
        }

        if ($gdg_kode != '') {
            if ($where)
                $where .= " AND gdg_kode = '$gdg_kode'";
            else
                $where = " WHERE gdg_kode = '$gdg_kode'";
        }

        $sql = "DROP TEMPORARY TABLE IF EXISTS saldo_stock;
            CREATE TEMPORARY TABLE saldo_stock(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00',
              `tgl` DATETIME NULL,
              `val_kode` VARCHAR(4) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );

            ALTER TABLE `saldo_stock`
            ADD INDEX `idx1` (`kode_brg` ASC),
            ADD INDEX `idx2` (`gdg_kode` ASC);";

        $this->db->query($sql);

        $where_saldo = '';

        if ($gdg_kode != '') {
            $where_saldo = " where gdg_kode = '$gdg_kode' ";
        }
        if ($periode) {
            if ($bulan != '') {
                if ($bulan == '01') {
                    $bulan = '12';
                    $tahun = (string) ($tahun - 1);
                    if ($where_saldo)
                        $where_saldo .= " AND periode = '$bulan' ";
                    else
                        $where_saldo = " Where periode = '$bulan' ";

                    $where_saldo .= " AND tahun = '$tahun'";
                } else {
                    $tahun = (string) ($tahun);

                    if ($bulan <= 10)
                        $bulan = "0" . (string) ($bulan - 1);
                    else
                        $bulan = (string) ($bulan - 1);

                    if ($where_saldo)
                        $where_saldo .= " AND periode = '$bulan' ";
                    else
                        $where_saldo = " Where periode = '$bulan' ";

                    $where_saldo .= " AND tahun = '$tahun'";
                }
            }
        }
        
        if($where_saldo)
            $whereTemporary = " AND sts_temporary = 0 ";
        else
            $whereTemporary = " Where sts_temporary = 0 ";

        $sql = "
            INSERT INTO saldo_stock
            SELECT
                  NULL,
                  kode_brg,
                  gdg_kode,
                  nama_brg,
                  SUM(qty) AS qty,
                  SUM(qty_hitung) AS qty_hitung,
                  SUM(harga) AS harga,
                  tgl,
                  val_kode
             FROM (
              SELECT
                  kode_brg,
                  coalesce(a.gdg_kode,'1') AS gdg_kode,
                  nama_brg,
                  qty,
                  qty AS qty_hitung,
                  (qty*hargaavg) AS harga,
                  COALESCE(tgl,concat(`tahun`,'-',`periode`,'-','01')) as tgl,
                  val_kode
                FROM {$this->FINANCE->AccountingSaldoStock->__name()} a
                left join {$this->LOGISTIC->MasterGudang->__name()} b
                    on a.gdg_kode = b.gdg_kode
                $where_saldo $whereTemporary
            ) a
            GROUP BY kode_brg,gdg_kode order by kode_brg, gdg_kode, tgl desc;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

//                    $sql = "select * from saldo_stock";
//        $invent = $this->db->query($sql);
//
//        $data = $invent->fetchAll();
//        var_dump($data);die;
//            $select = $this->db->select()
//                    ->from(array($this->FINANCE->AccountingSaldoStock->__name()), array(
//                new Zend_Db_Expr("NULL"),
//                "kode_brg",
//                "gdg_kode",
//                "nama_brg",
//                "SUM(qty) AS qty",
//                "SUM(qty_hitung) AS qty_hitung",
//                "SUM(hargaavg) AS harga",
//                "tgl" => "COALESCE(tgl,concat(`tahun`,'-',`periode`,'-','01'))",
//                "val_kode"
//            ));
//        
//
//        $select = $select->order(array("kode_brg", "gdg_kode", "tgl desc"))
//                ->group(array("kode_brg", "gdg_kode"));
//
//        $sql = "INSERT INTO saldo_stock $select";
//        $this->db->query($sql);
//        $sql = "select * from saldo_stock";
//        $invent = $this->db->query($sql);
//
//        $data = $invent->fetchAll();
//        var_dump($data);die;
        //Create temporary table...
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS isupp;
            CREATE TEMPORARY TABLE isupp(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              `sat_kode` VARCHAR(35) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS ican;
            CREATE TEMPORARY TABLE ican(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              `sat_kode` VARCHAR(35) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS ilov;
            CREATE TEMPORARY TABLE ilov(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              `sat_kode` VARCHAR(35) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS inventory_current_in;
            CREATE TEMPORARY TABLE inventory_current_in(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              `sat_kode` VARCHAR(35) NULL ,
              `tipe` VARCHAR(35) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );

            ALTER TABLE `inventory_current_in`
            ADD INDEX `idx1` (`kode_brg` ASC) ;


            DROP TEMPORARY TABLE IF EXISTS inventory_current_out;
            CREATE TEMPORARY TABLE inventory_current_out(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `gdg_kode` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `qty_hitung` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              `sat_kode` VARCHAR(35) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            ALTER TABLE `inventory_current_out`
            ADD INDEX `idx1` (`kode_brg` ASC) ;
        ";
        $fetch = $this->db->query($sql);


//-------------------------- Saldo in section ---------------------------//
        //Ambil data iSupp
        $sql = "
            INSERT INTO isupp
            SELECT
                  NULL,
                  kode_brg,
                  gdg_kode,
                  nama_brg,
                  SUM(qty) AS qty,
                  SUM(qty_hitung) AS qty_hitung,
                  SUM(harga) AS harga,
                  val_kode,
                  sat_kode
             FROM (
              SELECT
                  kode_brg,
                  coalesce(gdg_kode,'1') AS gdg_kode,
                  nama_brg,
                  IF(from_do = 1,0,qty) AS qty,
                  IF(from_do = 1,0,qty) AS qty_hitung,
                  IF(from_do = 1,0,(qty*harga)) AS harga,
                  val_kode,
                  sat_kode
                FROM {$this->LOGISTIC->LogisticInputSupplier->__name()}
                $where 
            ) a
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();



        //Ambil data iLov
        $sql = "
            INSERT INTO ilov
            SELECT * FROM (
                SELECT NULL,kode_brg,
                coalesce(gdg_kode,'1') AS gdg_kode,
                nama_brg,
                SUM(qty) AS qty,
                SUM(qty) AS qty_hitung,
                SUM(qty*harga) AS harga, val_kode, sat_kode
                FROM {$this->LOGISTIC->LogisticMaterialReturn->__name()}
              $where 
            ) a
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        //Ambil data iCan
        $sql = "
            INSERT INTO ican
            SELECT * FROM (
                SELECT NULL,kode_brg,
                coalesce(gdg_kode,'1') AS gdg_kode,
                nama_brg,
                SUM(qty) AS qty,
                SUM(qty) AS qty_hitung,
                SUM(qty*harga) AS harga, val_kode, sat_kode
                FROM {$this->LOGISTIC->LogisticMaterialCancel->__name()}
                $where 
            ) a
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Masukkan iSupp, iLov & iCan ke tabel baru...
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,
            gdg_kode,
            nama_brg,qty,qty_hitung,harga, val_kode, sat_kode, 'isupp' FROM isupp
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,gdg_kode,nama_brg,qty,qty_hitung,harga, val_kode, sat_kode, 'isupp' FROM ican
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,gdg_kode,nama_brg,qty,qty_hitung,harga, val_kode, sat_kode, 'isupp' FROM ilov
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,gdg_kode,nama_brg,qty,qty_hitung,harga, val_kode, '', 'saldo' FROM saldo_stock
            GROUP BY kode_brg,gdg_kode;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        if ($perKode != '') {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);

            $tglAwal = $periode['tgl_awal'];
            $tglAkhir = $periode['tgl_akhir'];

            $where2 = " AND b.tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,a.kode_brg,a.dest_kode,a.nama_brg,SUM(a.qty) AS qty,SUM(if(a.dest_kode='S',0,a.qty)) AS qty_hitung, 
            SUM(a.harga) as harga, a.sat_kode,a.val_kode,''
            FROM (
              SELECT b.kode_brg,
              coalesce(c.gdg_kode_to,'1') AS dest_kode,
              b.nama_brg,b.qty,if(c.gdg_kode_to='S',0,(b.qty*b.harga)) as harga,b.sat_kode,b.val_kode
              FROM {$this->LOGISTIC->LogisticDod->__name()} b
              LEFT JOIN {$this->LOGISTIC->LogisticDoh->__name()} c
              ON b.trano = c.trano
              WHERE b.trano is NOT NULL $where2 
              GROUP BY b.trano,c.trano,b.kode_brg,c.gdg_kode_to
            ) a
            GROUP BY a.kode_brg,a.dest_kode
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


//-------------------------- end of section ---------------------------//

        
//-------------------------- Saldo out section ---------------------------//

        if ($perKode != '') {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);

            $tglAwal = $periode['tgl_awal'];
            $tglAkhir = $periode['tgl_akhir'];

            $where2 = " AND b.tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        $sql = "
            INSERT INTO inventory_current_out
            SELECT NULL,a.kode_brg,a.from_kode,a.nama_brg,SUM(a.qty) AS qty,SUM(a.qty) AS qty_hitung,SUM(a.harga) as harga, a.sat_kode, a.val_kode 
            FROM (
              SELECT b.kode_brg,
              coalesce(c.gdg_kode_from,'1') AS from_kode,
              b.nama_brg,b.qty, (b.qty*b.harga) as harga ,b.sat_kode,b.val_kode
              FROM {$this->LOGISTIC->LogisticDod->__name()} b
              LEFT JOIN {$this->LOGISTIC->LogisticDoh->__name()} c
              ON b.trano = c.trano
              WHERE b.trano is NOT NULL $where2 
              GROUP BY b.trano,c.trano,b.kode_brg,c.gdg_kode_from
            ) a
            GROUP BY a.kode_brg,a.from_kode
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();


//-------------------------- end of section ---------------------------//


        if ($offset != '' || $limit != '') {
            $limits = " LIMIT $offset,$limit";
        }

//------------------ collect all transaction into temporary table -----//        

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS tmp_in;
            CREATE TEMPORARY TABLE tmp_in
            SELECT  kode_brg,nama_brg,COALESCE(SUM(qty),0) AS qty,COALESCE(SUM(qty_hitung),0) AS qty_hitung,COALESCE(SUM(harga),0) AS harga, val_kode, sat_kode, gdg_kode FROM inventory_current_in
            GROUP BY kode_brg,gdg_kode;
        ";
        $fetch = $this->db->query($sql);

        //           

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS tmp_out;
            CREATE TEMPORARY TABLE tmp_out
            SELECT  kode_brg,nama_brg,COALESCE(SUM(qty),0) AS qty,COALESCE(SUM(qty_hitung),0) AS qty_hitung,COALESCE(SUM(harga),0) AS harga, val_kode, sat_kode, gdg_kode FROM inventory_current_out
            GROUP BY kode_brg,gdg_kode
        ";
        $fetch = $this->db->query($sql);

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS temp_hargaavgin;
            CREATE TEMPORARY TABLE temp_hargaavgin
            SELECT kode_brg,sum(harga)hargaavg,sum(qty_hitung)qtyavg from tmp_in GROUP BY kode_brg;
        ";
        $fetch = $this->db->query($sql);


        $sql = "
            DROP TEMPORARY TABLE IF EXISTS temp_hargaavgout;
            CREATE TEMPORARY TABLE temp_hargaavgout
            SELECT kode_brg,sum(harga)hargaavg,sum(qty_hitung)qtyavg from tmp_out GROUP BY kode_brg;
        ";
        $fetch = $this->db->query($sql);


        $sql = "
            DROP TEMPORARY TABLE IF EXISTS tmp_gabung_avg;
            CREATE TEMPORARY TABLE tmp_gabung_avg
            SELECT COALESCE(a.kode_brg,b.kode_brg)kode_brg,(COALESCE(a.hargaavg,0) - COALESCE(b.hargaavg,0)) / (COALESCE(a.qtyavg,0) - COALESCE(b.qtyavg,0)) as hargaavg
            FROM temp_hargaavgin a
                LEFT JOIN temp_hargaavgout b
                ON a.kode_brg = b.kode_brg";
        $fetch = $this->db->query($sql);


        $sql = "
            DROP TEMPORARY TABLE IF EXISTS tmp_gabung;
            CREATE TEMPORARY TABLE tmp_gabung
            SELECT a.kode_brg,
                  a.qty AS qtyIn,
                  COALESCE(b.qty,0) AS qtyOut,
                  (COALESCE(a.qty,0)-COALESCE(b.qty,0)) AS qty,
                  (COALESCE(a.harga,0) - COALESCE(b.harga,0)) AS harga,
                  c.hargaavg AS hargaavg,
                  a.val_kode,
                  a.nama_brg,
                  a.sat_kode,
                  a.gdg_kode
                FROM tmp_in a
                LEFT JOIN tmp_out b
                ON a.kode_brg = b.kode_brg AND a.gdg_kode = b.gdg_kode
                left join tmp_gabung_avg c
                on a.kode_brg = c.kode_brg;            
        ";
        $fetch = $this->db->query($sql);


        if ($query != '') {
            $where = "WHERE $query";
        } else {
            $where = '';
        }

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS z.* FROM tmp_gabung z 
                where gdg_kode IN (SELECT gdg_kode FROM master_gudang) and kode_brg is not null
            $limits;
        ";

        $invent = $this->db->query($sql);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $data = $invent->fetchAll();

        return array("data" => $data, "count" => $count);
    }

    public function closing($perkode = '', $gdg_kode = '') {
        if (!$perkode)
            return false;

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);
        $data = $this->getCurrentItems(array(
            "perKode" => $perkode,
            "gdg_kode" => $gdg_kode
        ));

        if (count($data['data']) == 0)
            return false;

        $data = $this->convertDataStock($data['data']);

        $prevPeriode = QDC_Finance_Periode::factory()->getPreviousPeriode($periode);

        if ($prevPeriode) {
            $dataPrev = $this->getPrevItems(array(
                "perKode" => $prevPeriode['perkode'],
                "gdg_kode" => $gdg_kode
            ));
            if ($dataPrev)
                $newData = $this->mergeDataStock($dataPrev, $data);
            else
                $newData = $data;
        }

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
                    "valuta" => "USD"
                ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        foreach ($newData as $k => $v) {
            $arrayInsert = array(
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "qty" => $v['qty'],
                "hargaavg" => $v['hargaavg'],
                "total_harga" => $v['harga'],
                "periode" => $periode['bulan'],
                "tahun" => $periode['tahun'],
                "stsslowmoving" => $v['stsslowmoving'],
                "val_kode" => $v['val_kode'],
                "gdg_kode" => $gdg_kode,
                "rateidr" => $currentRateidr
            );
            //Insert ke saldo stock..
            $this->FINANCE->AccountingSaldoStock->insert($arrayInsert);

            //Update master barang...
            if ($v['hargaavg'] > 0 || $v['hargaavg'] != '') {
                $hargaAVG = $this->FINANCE->AccountingSaldoStock->getHargaAvg($perkode, $v['kode_brg']);
                if ($hargaAVG !== false)
                    $this->DEFAULT->MasterBarang->update(array("hargaavg" => $hargaAVG), "kode_brg = '{$v['kode_brg']}'");
            }
        }

//        $arrayInsert = array(
//            "stscloseinventory" => 1,
//            "tglcloseinventory" => new Zend_Db_Expr("NOW()"),
//            "uidcloseinventory" => QDC_User_Session::factory()->getCurrentUID()
//        );
//        $this->FINANCE->MasterPeriode->update($arrayInsert, "perkode = '{$periode['perkode']}'");

        return true;
    }

    private function convertDataStock($data = array()) {
        $returnData = array();
        foreach ($data as $k => $v) {
            if ($v['kode_brg'] == '')
                continue;
            $kodeBrg = $v['kode_brg'];
            $returnData[$kodeBrg] = $v;
        }

        return $returnData;
    }

    private function mergeDataStock($oldData = array(), $newData = array()) {
        $newDataStock = array();
        foreach ($oldData as $k => $v) {
            $kodeBrg = $v['kode_brg'];
            $namaBrg = $this->DEFAULT->MasterBarang->getName($kodeBrg);
            if ($newData[$k] != '') {

                if ($v['qtyIn'] > 0 || $newData[$k]['qty'] > 0) {
                    $hargaAVG = (($v['hargaavg'] * $v['qty']) + $newData[$k]['harga']) / ($newData[$k]['qtyIn'] + $v['qty']);
                }

                $newDataStock[$kodeBrg] = array(
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $namaBrg,
                    "qty" => floatval($oldData[$k]['qty']) + floatval($newData[$k]['qty']),
                    "hargaavg" => $hargaAVG,
                    "val_kode" => $v['val_kode']
                );
                unset($newData[$k]);
            } else {
                $newDataStock[$kodeBrg] = array(
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $namaBrg,
                    "qty" => floatval($v['qty']),
                    "hargaavg" => floatval($v['hargaavg']),
                    "stsslowmoving" => 1,
                    "val_kode" => $v['val_kode']
                );
            }
        }

        if (count($newData) > 0) {
            foreach ($newData as $k => $v) {
                $kodeBrg = $v['kode_brg'];
                $namaBrg = $this->DEFAULT->MasterBarang->getName($kodeBrg);
                $newDataStock[$kodeBrg] = array(
                    "kode_brg" => $v['kode_brg'],
                    "nama_brg" => $namaBrg,
                    "qty" => floatval($v['qty']),
                    "hargaavg" => floatval($v['hargaavg']),
                    "val_kode" => $v['val_kode']
                );
            }
        }

        return $newDataStock;
    }

    /**
     * @param array $params
     *
     * Fungsi untuk mendapatkan daftar barang dari periode sebelumnya
     */
    public function getPrevItems($params = array()) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }
        $where = '';
        //Ambil periode accounting yg sedang aktif
        if ($perKode == '') {
            $periode = QDC_Finance_Periode::factory()->getPreviousPeriode();
        } else {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);
        }

        if (!$periode) {
            return false;
        } else {
            $tahun = $periode['tahun'];
            $bulan = $periode['bulan'];

            $select = $this->db->select()
                    ->from(array($this->FINANCE->AccountingSaldoStock->__name()))
                    ->where("periode=?", $bulan)
                    ->where("tahun=?", $tahun);

            if ($gdg_kode)
                $select = $select->where("gdg_kode=?", $gdg_kode);

            $dataPrev = $this->db->fetchAll($select);

            $dataPrev = $this->convertDataStock($dataPrev);

            return $dataPrev;
        }
    }

    public function getItemsDetail($params) {
        if ($params != '')
            foreach ($params as $k => $v) {
                $temp = $k;
                ${"$temp"} = $v;
            }

        if ($kode_brg == '')
            return false;

        $where = '';
        //Ambil periode accounting yg sedang aktif
        if ($perKode != '') {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);

            $tglAwal = $periode['tgl_awal'];
            $tglAkhir = $periode['tgl_akhir'];

            $where = " WHERE tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        //Jika ada filter bedasarkan tgl
        if ($start_date != '') {
            $tglAwal = date("Y-m-d", strtotime($start_date));
            if ($end_date != '') {
                $tglAkhir = date("Y-m-d", strtotime($end_date));
                $where = " WHERE tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
            } else {
                $where = " WHERE tgl = '$tglAwal'";
            }
        }

        if ($where)
            $where .= " AND kode_brg = '$kode_brg'";
        else
            $where = "WHERE kode_brg = '$kode_brg'";

        $data = array();

        //Ambil data iSupp
        $sql = "
            SELECT trano,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga,tgl, sat_kode, prj_kode, sit_kode
            FROM {$this->LOGISTIC->LogisticInputSupplier->__name()}
            $where
            GROUP BY trano,kode_brg;
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();
        $i = 0;
        foreach ($fetch as $k => $v) {
            $v['type'] = 'WAREHOUSE IN';
            $v['sub_type'] = 'MATERIAL FROM SUPPLIER';
            $v['trans'] = 'ISUPP';
            $v['id'] = $i;
            $data[] = $v;
            $i++;
        }

        $sql = "
            SELECT trano,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga,tgl, sat_kode, prj_kode, sit_kode
            FROM {$this->LOGISTIC->LogisticMaterialReturn->__name()}
            $where
            GROUP BY trano,kode_brg;
        ";

        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        foreach ($fetch as $k => $v) {
            $v['type'] = 'WAREHOUSE IN';
            $v['sub_type'] = 'MATERIAL RETURN';
            $v['trans'] = 'ILOV';
            $data[] = $v;
            $v['id'] = $i;
            $i++;
        }
        //Ambil data iCan
        $sql = "
            SELECT trano,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga,tgl, sat_kode, prj_kode, sit_kode
            FROM {$this->LOGISTIC->LogisticMaterialCancel->__name()}
            $where
            GROUP BY trano,kode_brg;
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        foreach ($fetch as $k => $v) {
            $v['type'] = 'WAREHOUSE IN';
            $v['sub_type'] = 'MATERIAL CANCEL';
            $v['trans'] = 'ILOV';
            $data[] = $v;
            $v['id'] = $i;
            $i++;
        }

        if ($where) {
            if ($tglAwal) {
                if ($tglAkhir != '') {
                    $where2 = " AND b.tgl BETWEEN '$tglAwal' AND '$tglAkhir' AND b.kode_brg = '$kode_brg'";
                } else {
                    $where2 = " AND b.tgl = '$tglAwal' AND b.kode_brg = '$kode_brg'";
                }
            } else {
                $where2 = " AND b.kode_brg = '$kode_brg'";
            }
        }
        $sql = "
            SELECT trano,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga,tgl, sat_kode, prj_kode, sit_kode
            FROM (
              SELECT b.trano,b.kode_brg,b.nama_brg,b.qty,b.harga,b.tgl, b.sat_kode, b.prj_kode, b.sit_kode
              FROM {$this->LOGISTIC->LogisticDorh->__name()} a
              LEFT JOIN {$this->LOGISTIC->LogisticDod->__name()} b
              ON a.trano = b.mdi_no
              WHERE a.deliver_to = 'wh-site' AND b.trano is NOT NULL $where2
              GROUP BY a.trano,b.trano,b.kode_brg
            ) a
            GROUP BY trano,kode_brg
        ";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetchAll();

        foreach ($fetch as $k => $v) {
            $v['type'] = 'WAREHOUSE OUT';
            $v['sub_type'] = 'DELIVERY ORDER TO SITE';
            $v['trans'] = 'DO';
            $data[] = $v;
            $v['id'] = $i;
            $i++;
        }

        return $data;
    }

    public function closingByOne() {
        $data = $this->getCurrentItems();

        if (count($data['data']) == 0)
            return false;

        $data = $this->convertDataStock($data['data']);

        return $data;
    }

    public function populateExisting() {


        //isupp



        $sql = "SELECT * FROM saldo_stock";
        $data = $this->db->query($sql)->fetchAll();

//        var_dump($data);
    }

}

?>