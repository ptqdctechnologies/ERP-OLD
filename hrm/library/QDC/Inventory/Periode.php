<?php

Class QDC_Inventory_Periode
{
    public $DEFAULT;
    public $FINANCE;
    public $LOGISTIC;
    private $db;

    public function __construct($params = '')
    {

        if ($params != '')
            foreach($params as $k => $v)
            {
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

    public static function getConfig($models=array())
    {

    }

    /**
     * @static
     * @param $params
     * @return QDC_Finance_Periode
     *
     * Method factory dipanggil apabila QDC_FINANCE_PERIODE di inisialisasi secara statik
     */
    public static function factory($params=array())
    {
        return new self($params);
    }

    /**
     * @param string $perKode
     *
     * Fungsi untuk mendapatkan daftar barang saat ini, hanya khusus Warehouse to Site
     * Current QTY In = (iSupp + iCan + iLov)
     * Current QTY Out = DO
     */
    public function getCurrentItems($params = array())
    {
        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                ${"$temp"} = $v;
            }
        $where = '';
        //Ambil periode accounting yg sedang aktif
        if ($perKode != '')
        {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);

            $tglAwal = $periode['tgl_awal'];
            $tglAkhir = $periode['tgl_akhir'];

            $where = " WHERE tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }

        //Create temporary table...
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS isupp;
            CREATE TEMPORARY TABLE isupp(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS ican;
            CREATE TEMPORARY TABLE ican(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS ilov;
            CREATE TEMPORARY TABLE ilov(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS inventory_current_in;
            CREATE TEMPORARY TABLE inventory_current_in(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              `harga` DECIMAL(22,2) DEFAULT '0.00' ,
              `val_kode` VARCHAR(4) NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
            DROP TEMPORARY TABLE IF EXISTS inventory_current_out;
            CREATE TEMPORARY TABLE inventory_current_out(
              `id` INT NOT NULL AUTO_INCREMENT ,
              `kode_brg` VARCHAR(45) NULL ,
              `nama_brg` VARCHAR(255) NULL ,
              `qty` DECIMAL(22,4) DEFAULT '0.00' ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `id_UNIQUE` (`id` ASC)
            );
        ";
        $fetch = $this->db->query($sql);

        //Ambil data iSupp
        $sql = "
            INSERT INTO isupp
            SELECT NULL,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga, val_kode
            FROM {$this->LOGISTIC->LogisticInputSupplier->__name()}
            $where
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        //Ambil data iLov
        $sql = "
            INSERT INTO ilov
            SELECT NULL,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga, val_kode
            FROM {$this->LOGISTIC->LogisticMaterialReturn->__name()}
            $where
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        //Ambil data iCan
        $sql = "
            INSERT INTO ican
            SELECT NULL,kode_brg,nama_brg,SUM(qty) AS qty,SUM(qty*harga) AS harga, val_kode
            FROM {$this->LOGISTIC->LogisticMaterialCancel->__name()}
            $where
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Masukkan iSupp, iLov & iCan ke tabel baru...
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,nama_brg,qty,harga, val_kode FROM isupp
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,nama_brg,qty,harga, val_kode FROM ican
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();
        $sql = "
            INSERT INTO inventory_current_in
            SELECT NULL,kode_brg,nama_brg,qty,harga, val_kode FROM ilov
            GROUP BY kode_brg;
        ";

        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        //Masukkan DO ke tabel...
        if ($where)
        {
            $where2 = " AND b.tgl BETWEEN '$tglAwal' AND '$tglAkhir'";
        }
        $sql = "
            INSERT INTO inventory_current_out
            SELECT NULL,a.kode_brg,a.nama_brg,SUM(a.qty) AS qty
            FROM (
              SELECT b.kode_brg,b.nama_brg,b.qty
              FROM {$this->LOGISTIC->LogisticDorh->__name()} a
              LEFT JOIN {$this->LOGISTIC->LogisticDod->__name()} b
              ON a.trano = b.mdi_no
              WHERE a.deliver_to = 'wh-site' AND b.trano is NOT NULL $where2
              GROUP BY a.trano,b.trano,b.kode_brg
            ) a
            GROUP BY a.kode_brg
        ";
        $fetch = $this->db->prepare($sql);
        $fetch->execute();

        if ($offset != '' || $limit != '')
        {
            $limits = " LIMIT $offset,$limit";
        }

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS z.*,y.nama_brg FROM (
                SELECT a.kode_brg,
                  a.qty AS qtyIn,
                  COALESCE(b.qty,0) AS qtyOut,
                  (COALESCE(a.qty,0)-COALESCE(b.qty,0)) AS qty,
                  COALESCE(a.harga,0) AS harga,
                  (IF(COALESCE(a.qty,0) > 0,(COALESCE(a.harga,0) / a.qty ), 0)) AS hargaavg,
                  a.val_kode
                FROM (
                  SELECT  kode_brg,nama_brg,COALESCE(SUM(qty),0) AS qty,COALESCE(SUM(harga),0) AS harga, val_kode FROM inventory_current_in
                  GROUP BY kode_brg
                ) a
                LEFT JOIN (
                  SELECT  kode_brg,nama_brg,COALESCE(SUM(qty),0) AS qty FROM inventory_current_out
                  GROUP BY kode_brg
                ) b
                ON a.kode_brg = b.kode_brg
            ) z LEFT JOIN {$this->DEFAULT->MasterBarang->__name()} y
            ON z.kode_brg = y.kode_brg
            $limits;
        ";

        $invent = $this->db->Query($sql);
        $count = $this->db->fetchOne("SELECT FOUND_ROWS()");

        $data = $invent->fetchAll();

        return array("data" => $data, "count" => $count);
    }

    public function closing($perkode='')
    {
        if (!$perkode)
            return false;

        $periode = QDC_Finance_Periode::factory()->getPeriode($perkode);
        $data = $this->getCurrentItems(array("perKode" => $perkode));

        if (count($data['data']) == 0)
            return false;

        $data = $this->convertDataStock($data['data']);

        $prevPeriode = QDC_Finance_Periode::factory()->getPreviousPeriode($periode);

        if ($prevPeriode)
        {
            $dataPrev = $this->getPrevItems(array(
                "perKode" => $prevPeriode['perkode']
            ));
            if ($dataPrev)
                $newData = $this->mergeDataStock($dataPrev,$data);
            else
                $newData = $data;
        }

        $currentRateidr = QDC_Common_ExchangeRate::factory(array(
            "valuta" => "USD"
        ))->getExchangeRate();
        $currentRateidr = $currentRateidr['rateidr'];

        foreach($newData as $k => $v)
        {
            $arrayInsert = array(
                "kode_brg" => $v['kode_brg'],
                "nama_brg" => $v['nama_brg'],
                "qty" => $v['qty'],
                "hargaavg" => $v['hargaavg'],
                "periode" => $periode['bulan'],
                "tahun" => $periode['tahun'],
                "stsslowmoving" => $v['stsslowmoving'],
                "val_kode" => $v['val_kode'],
                "rateidr" => $currentRateidr
            );
            //Insert ke saldo stock..
            $this->FINANCE->AccountingSaldoStock->insert($arrayInsert);

            //Update master barang...
            if ($v['hargaavg'] > 0 || $v['hargaavg'] != '')
                $this->DEFAULT->MasterBarang->update(array("hargaavg" => $v['hargaavg']),"kode_brg = '{$v['kode_brg']}'");
        }

        $arrayInsert = array(
            "stscloseinventory" => 1,
            "tglcloseinventory" => new Zend_Db_Expr("NOW()"),
            "uidcloseinventory" => QDC_User_Session::factory()->getCurrentUID()
        );
        $this->FINANCE->MasterPeriode->update($arrayInsert, "perkode = '{$periode['perkode']}'");

        return true;
    }

    private function convertDataStock($data=array())
    {
        $returnData = array();
        foreach ($data as $k => $v)
        {
            if ($v['kode_brg'] == '')
                continue;
            $kodeBrg = $v['kode_brg'];
            $returnData[$kodeBrg] = $v;
        }

        return $returnData;
    }

    private function mergeDataStock($oldData=array(),$newData=array())
    {
        $newDataStock = array();
        foreach($oldData as $k => $v)
        {
            $kodeBrg = $v['kode_brg'];
            $namaBrg = $this->DEFAULT->MasterBarang->getName($kodeBrg);
            if ($newData[$k] != '')
            {

                if ($v['qtyIn'] > 0 || $newData[$k]['qty'] > 0)
                {
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
            }
            else
            {
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

        if (count($newData) > 0)
        {
            foreach($newData as $k => $v)
            {
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
    public function getPrevItems($params = array())
    {
        if ($params != '')
            foreach($params as $k => $v)
            {
                $temp = $k;
                ${"$temp"} = $v;
            }
        $where = '';
        //Ambil periode accounting yg sedang aktif
        if ($perKode == '')
        {
            $periode = QDC_Finance_Periode::factory()->getPreviousPeriode();
        }
        else
        {
            $periode = QDC_Finance_Periode::factory()->getPeriode($perKode);
        }

        if (!$periode)
        {
            return false;
        }
        else
        {
            $tahun = $periode['tahun'];
            $bulan = $periode['bulan'];

            $select = $this->db->select()
                ->from(array($this->FINANCE->AccountingSaldoStock->__name()))
                ->where("periode = '$bulan' AND tahun = '$tahun'");
            $dataPrev = $this->db->fetchAll($select);

            $dataPrev = $this->convertDataStock($dataPrev);

            return $dataPrev;
        }
    }

//    public function cekValuta($where)
//    {
//        if (!$where)
//            $where = "val_kode = '\"\"' OR val_kode = '' OR val_kode IS NULL";
//        else
//            $where .= " AND (val_kode = '\"\"' OR val_kode = '' OR val_kode IS NULL)";
//        $sql = "
//                SELECT kode_brg, nama_brg, val_kode
//                FROM {$this->LOGISTIC->LogisticInputSupplier->__name()}
//                $where
//                GROUP BY kode_brg, val_kode
//        ";
//
//
//    }
}
?>