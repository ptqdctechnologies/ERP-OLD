<?php

class Default_Models_CronBudget extends Zend_Db_Table_Abstract
{
    protected $_name = 'cron_budget';
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
    }
    
    public function updateKodeBrgMisc()
    {
        $sql = "UPDATE erpdb.transengineer_boq3d SET kode_brg='XX' WHERE workid IN ('1100','2100','3100','4100','5100','6100')";
        $this->db->query($sql);
        
    }
    
    public function updatePeaceMealMisc()
    {
        $sql = "UPDATE erpdb.transengineer_boq3d SET stspmeal='N' WHERE workid IN ('1100','2100','3100','4100','5100','6100')";
        $this->db->query($sql);
        
    }
    
    public function setBoq3IDRrate()
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS IDRrate;
                CREATE TEMPORARY TABLE IDRrate
                SELECT date(tgl) AS tgl, rateidr FROM (SELECT date(tgl) AS tgl, rateidr FROM finance_exchange_rate  WHERE rateidr > 2000 ORDER BY date(tgl) DESC) a GROUP BY date(a.tgl);";
        $this->db->query($sql);
        
        $sql2 = "UPDATE erpdb.transengineer_boq3d d
                 INNER JOIN IDRrate h ON (h.tgl = d.tgl)
                 SET d.rateidr = h.rateidr
                 WHERE d.rateidr=0;";
        $this->db->query($sql2);
    }
    
    public function setBoq2IDRrate()
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS IDRrate;
                CREATE TEMPORARY TABLE IDRrate
                SELECT date(tgl) AS tgl, rateidr FROM (SELECT date(tgl) AS tgl, rateidr FROM finance_exchange_rate  WHERE rateidr > 2000 ORDER BY date(tgl) DESC) a GROUP BY date(a.tgl);";
        $this->db->query($sql);
        
        $sql2 = "UPDATE transengineer_boq2h d
                 INNER JOIN IDRrate h ON (h.tgl = d.tgl)
                 SET d.rateidr = h.rateidr
                 WHERE d.rateidr=0;";
        $this->db->query($sql2);
    }
    
    public function setIDRforBRFP()
    {
        $sql = "UPDATE erpdb.procurement_arfd SET val_kode='IDR' WHERE trano LIKE '%BRFP%';";
        $this->db->query($sql);
        
        $sql2 = "UPDATE erpdb.procurement_arfh SET val_kode='IDR' WHERE trano LIKE '%BRFP%';";
        $this->db->query($sql2);
    }
    
    public function setKBoq2IDRrate()
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS IDRrate;
                CREATE TEMPORARY TABLE IDRrate
                SELECT date(tgl) AS tgl, rateidr FROM (SELECT date(tgl) AS tgl, rateidr FROM finance_exchange_rate  WHERE rateidr > 2000 ORDER BY date(tgl) DESC) a GROUP BY date(a.tgl);";
        $this->db->query($sql);
        
        $sql2 = "UPDATE erpdb.transengineer_kboq2h d
                 INNER JOIN IDRrate h ON (h.tgl = d.tgl)
                 SET d.rateidr = h.rateidr
                 WHERE d.rateidr=0;";
        $this->db->query($sql2);
    }
    
    public function setHargDO()
    {
        $sql = 'UPDATE erpdb.procurement_whod do 
                INNER JOIN erpdb.procurement_pointd dor ON (dor.trano = do.mdi_no AND dor.kode_brg = do.kode_brg)
                SET do.harga = dor.harga';
        $this->db->query($sql);
    }
    
    public function setSupplierRPI()
    {
        $sql = 'UPDATE erpdb.procurement_rpid rpi 
                INNER JOIN erpdb.master_suplier ms ON (rpi.sup_kode=ms.sup_kode) 
                SET rpi.sup_nama = ms.sup_nama 
                WHERE rpi.sup_nama=\'""\';';
         $this->db->query($sql);
    }
    
    public function setKodeBrgBrf()
    {
        $sql = "UPDATE erpdb.procurement_brfd SET kode_brg='820005-0000', nama_brg='Travel & Fares/Business Trip'
                WHERE kode_brg='' OR kode_brg IS NULL";
        $this->db->query($sql);
    }
    
    public function setApproveColumn()
    {
        // RPI
        $sql = "DROP TEMPORARY TABLE IF EXISTS wf;
                CREATE TEMPORARY TABLE wf
                SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%RPI%' ORDER BY date DESC) h
                GROUP BY item_id ;";
        $this->db->query($sql);
        
        $sql1 = "UPDATE erpdb.procurement_rpid d
                INNER JOIN wf h ON (h.item_id = d.trano)
                SET d.approve = h.approve;";
        $this->db->query($sql1);
        
        //ASF, BSF OCA
        $sql2 = "DROP TEMPORARY TABLE IF EXISTS wf;
                 CREATE TEMPORARY TABLE wf
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE (item_type LIKE '%ASF%' OR item_type LIKE '%BSF%' OR item_type LIKE '%OCA%') ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql2);
        
        $sql3 = "UPDATE erpdb.procurement_asfdd d
                 INNER JOIN wf h ON (h.item_id = d.trano)
                 SET d.approve = h.approve;";
        $this->db->query($sql3);
        
        // CANCELED ASF & BSF
        $sql4 = "UPDATE erpdb.procurement_asfddcancel d
                 INNER JOIN wf h ON (h.item_id = d.trano)
                 SET d.approve = h.approve;";
        $this->db->query($sql4);
        
        $sql5 = "DROP TEMPORARY TABLE IF EXISTS wf;
                 CREATE TEMPORARY TABLE wf
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%PO%'  ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql5);
        
        //PO
        $sql6 = "UPDATE erpdb.procurement_pod d
                 INNER JOIN wf h ON (h.item_id = d.trano)
                 SET d.approve = h.approve;";
        $this->db->query($sql6);

        $sql7 = "DROP TEMPORARY TABLE IF EXISTS wf;
                 CREATE TEMPORARY TABLE wf
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%ARF%' ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql7);
        
        //ARF
        $sql8 = "UPDATE erpdb.procurement_arfd d
                 INNER JOIN wf h ON (h.item_id = d.trano)
                 SET d.approve = h.approve;";
        $this->db->query($sql8);
        
        //BRF
        $sql9 = "DROP TEMPORARY TABLE IF EXISTS wf;
                 CREATE TEMPORARY TABLE wf
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%BRF%'  ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql9);
        
        $sql10 = "UPDATE erpdb.procurement_brfd d
                    INNER JOIN wf h ON (h.item_id = d.trano)
                    SET d.approve = h.approve;";
        $this->db->query($sql10);

        //PR
        $sql11 = "DROP TEMPORARY TABLE IF EXISTS wf;
                 CREATE TEMPORARY TABLE wf
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%PR%' ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql11);
        
        $sql12 = "UPDATE erpdb.procurement_prd d
                    INNER JOIN wf h ON (h.item_id = d.trano)
                    SET d.approve = h.approve;";
        $this->db->query($sql12);
        
        //Piecemeal
        $sql11 = "DROP TEMPORARY TABLE IF EXISTS pmeal;
                 CREATE TEMPORARY TABLE pmeal
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%PBOQ3%'  ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql13);
        
        $sql12 = "UPDATE erpdb.boq_dboqpasang d
                    INNER JOIN pmeal h ON (h.item_id = d.notran)
                    SET d.approve = h.approve;";
        $this->db->query($sql14);
        
        //Material Return
        $sql11 = "DROP TEMPORARY TABLE IF EXISTS mreturn;
                 CREATE TEMPORARY TABLE mreturn
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%iLOV%'  ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql15);
        
        $sql12 = "UPDATE erpdb.procurement_whreturnd d
                    INNER JOIN mreturn h ON (h.item_id = d.trano)
                    SET d.approve = h.approve;";
        $this->db->query($sql16);
        
        //DOR
        $sql11 = "DROP TEMPORARY TABLE IF EXISTS dor;
                 CREATE TEMPORARY TABLE dor
                 SELECT * FROM (SELECT item_id,item_type,approve FROM erpdb.workflow_trans WHERE item_type LIKE '%DOR%'  ORDER BY date DESC) h
                 GROUP BY item_id ;";
        $this->db->query($sql17);
        
        $sql12 = "UPDATE erpdb.procurement_pointd d
                    INNER JOIN dor h ON (h.item_id = d.trano)
                    SET d.approve = h.approve;";
        $this->db->query($sql18);

    }

    public function save($data,$prjKode,$sitKode)
    {
        $this->purge($prjKode,$sitKode);
        $select = $this->db->select()
            ->from(array($this->_name));
        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
        {
            $select = $select->where("sit_kode=?",$sitKode);
            $key = "prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        }
        else
        {
            $select = $select->where("sit_kode = ''");
            $key = "prj_kode = '$prjKode' AND (sit_kode = ''  OR sit_kode IS NULL)";
        }
        $cek = $this->db->fetchRow($select);
        if (is_array($data))
        {
            $data = Zend_Json::encode($data);
        }
        if ($cek)
        {
            $update = array(
                "data" => $data,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->update($update,$key);
        }
        else
        {
            $insert = array(
                "data" => $data,
                "prj_kode" => $prjKode,
                "sit_kode" => $sitKode,
                "tgl" => date("Y-m-d H:i:s")
            );
            $this->insert($insert);
        }
    }

    public function load($prjKode,$sitKode)
    {
        $select = $this->db->select()
            ->from(array($this->_name));
        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
        {
            $select = $select->where("sit_kode=?",$sitKode);
        }
        else
        {
            $select = $select->where("sit_kode = '' OR sit_kode IS NULL");
        }
        $cek = $this->db->fetchRow($select);
        if ($cek)
        {
            $cek = $cek['data'];
            if ($cek != '')
            {
                $tmp = Zend_Json::decode($cek);
                if ($tmp != '')
                    $cek = $tmp;
            }
            return $cek;
        }
        else
        {
            return false;
        }
    }

    public function test($prjKode,$sitKode)
    {
        $select = $this->db->select()
            ->from(array($this->_name));
        if ($prjKode)
            $select = $select->where("prj_kode=?",$prjKode);
        if ($sitKode)
        {
            $select = $select->where("sit_kode=?",$sitKode);
        }
        else
        {
            $select = $select->where("sit_kode = '' OR sit_kode IS NULL");
        }
        $cek = $this->db->fetchRow($select);
        if ($cek)
            return true;
        else
            return false;
    }

    public function purgeAll()
    {
        $sql = "DELETE FROM {$this->_name}";
        $this->db->query($sql);
    }

    public function cleanAll()
    {
        $sql = "TRUNCATE TABLE {$this->_name}";
        $this->db->query($sql);
    }

    public function purge($prjKode='',$sitKode='')
    {
        $sql = "DELETE FROM {$this->_name} WHERE ";
        if ($prjKode)
            $sql .= "prj_kode = '$prjKode'";
        if ($sitKode)
            $sql .= " AND sit_kode = '$sitKode'";
        else
            $sql .= " AND (sit_kode = '' OR sit_kode IS NULL)";
        $this->db->query($sql);
    }

    public function getProjectList($prjKode='',$limit=100,$offset=0)
    {
        $select = $this->db->select()
                ->from(array('cr' => $this->_name),
                    array(
                        'prj_kode AS Prj_Kode'
                        ))
                ->joinLeft(array('prj' => 'master_project'),
                    'cr.prj_kode = prj.Prj_Kode',
                         array(
                             'prj.Prj_Nama'
                             ))
                ->group('cr.prj_kode')
                ->limit($limit,$offset)
                ->order('cr.prj_kode DESC');

        if($prjKode){
            $select = $select
                    ->where('cr.prj_kode LIKE ?',"%$prjKode%");
        }

        $result = $this->db->fetchAll($select);

        return $result;
    }

    public function __name()
    {
        return $this->_name;
    }
}