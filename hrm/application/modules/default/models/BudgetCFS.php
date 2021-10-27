<?php
class Default_Models_BudgetCFS extends Zend_Db_Table_Abstract
{

    private $db;
    private $project;
    private $memcache;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');

    }

    public function getAsfd($action='summary',$prjKode='',$sitKode='',$status='')
    {
        if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	
        if($action == 'summary')
         {
             $sql = "
                    DROP TEMPORARY TABLE IF EXISTS asfd;
                    CREATE TEMPORARY TABLE asfd
                    SELECT
                        trano,
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_asfdd
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode
                    GROUP BY trano";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM asfd";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;


            $sql = "
                    DROP TEMPORARY TABLE IF EXISTS asfdWork;
                    CREATE TEMPORARY TABLE asfdWork
                    SELECT
                        a.*
                    FROM asfd a LEFT JOIN `asf_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NOT NULL
                        AND b.final = 1;";
            $this->db->query($sql);

            $sql = "
                    INSERT INTO asfdWork
                    SELECT
                        a.*
                    FROM asfd a LEFT JOIN `asf_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NULL;";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM asfdWork";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;

            $sql = "
                    SELECT
                        SUM(a.totalIDR) + SUM(a.totalUSD) as totalASF,
                        SUM(a.totalIDR) as totalIDR,
                        SUM(a.totalUSD) as totalUSD,
                        SUM(a.totalIDR) as totalHargaIDR,
                        SUM(a.totalHargaUSD) as totalHargaUSD
                    FROM asfdWork a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_asfd
                    WHERE
                            prj_kode = '$prjKode'
                        $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getAsfdcancel($action='summary',$prjKode='',$sitKode='',$status='')
    {
        if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";

        if($action == 'summary')
         {
             $sql = "
                    DROP TEMPORARY TABLE IF EXISTS asfdc;
                    CREATE TEMPORARY TABLE asfdc
                    SELECT
                        trano,
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_asfddcancel
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode
                    GROUP BY trano";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM asfdc";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;


            $sql = "
                    DROP TEMPORARY TABLE IF EXISTS asfdcWork;
                    CREATE TEMPORARY TABLE asfdcWork
                    SELECT
                        a.*
                    FROM asfdc a LEFT JOIN `asf_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NOT NULL
                        AND b.final = 1;";
            $this->db->query($sql);

            $sql = "
                    INSERT INTO asfdcWork
                    SELECT
                        a.*
                    FROM asfdc a LEFT JOIN `asf_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NULL;";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM asfdcWork";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;

            $sql = "
                    SELECT
                        SUM(a.totalIDR) + SUM(a.totalUSD) as totalRPI,
                        SUM(a.totalIDR) as totalIDR,
                        SUM(a.totalUSD) as totalUSD,
                        SUM(a.totalIDR) as totalHargaIDR,
                        SUM(a.totalHargaUSD) as totalHargaUSD
                    FROM asfdcWork a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_asfddcancel
                    WHERE
                            prj_kode = '$prjKode'
                        $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }
    public function getRpid($action='summary',$prjKode='',$sitKode='',$status='')
    {
        if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	if ($status != '')
    	{
    		if ($status == 'service-materialsite')
    		{
    			$SQLsite = " AND (typepo = 'service' OR (typepo='material' AND statussite = 'Y')) ";
    		}
    		elseif ($status == 'service')
    		{
    			$SQLsite = " AND (typepo = 'service')";
    		}
    		elseif ($status == 'materialsite')
    		{
    			$SQLsite = " AND (typepo='material' AND statussite = 'Y') ";
    		}
    	}
        if($action == 'summary')
         {
             $sql = "
                    DROP TEMPORARY TABLE IF EXISTS rpid;
                    CREATE TEMPORARY TABLE rpid
                    SELECT
                        trano,
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_rpid
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode
                        $SQLsite
                    GROUP BY trano";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM rpid";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;

            
            $sql = "
                    DROP TEMPORARY TABLE IF EXISTS rpidWork;
                    CREATE TEMPORARY TABLE rpidWork
                    SELECT
                        a.*
                    FROM rpid a LEFT JOIN `rpi_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NOT NULL
                        AND b.final = 1;";
            $this->db->query($sql);

            $sql = "
                    INSERT INTO rpidWork
                    SELECT
                        a.*
                    FROM rpid a LEFT JOIN `rpi_workflow` b
                    ON b.item_id = a.trano
                    WHERE
                        b.item_id IS NULL;";
            $this->db->query($sql);

            $sql = "SELECT COALESCE(COUNT(*),0) AS numRow FROM rpidWork";
            $fetch = $this->db->query($sql);
            $count = $fetch->fetch();
            if ($count['numRow'] == 0)
                return 0;

            $sql = "
                    SELECT
                        SUM(a.totalIDR) + SUM(a.totalUSD) as totalRPI,
                        SUM(a.totalIDR) as totalIDR,
                        SUM(a.totalUSD) as totalUSD,
                        SUM(a.totalIDR) as totalHargaIDR,
                        SUM(a.totalHargaUSD) as totalHargaUSD
                    FROM rpidWork a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_rpid
                    WHERE
                            prj_kode = '$prjKode'
                        $sitKode
                        $SQLsite";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getPod($action='summary',$prjKode='',$sitKode='',$statussite='', $cfsKode='')
    {
        if ($cfsKode != '')
        {
            if ($cfsKode != 'Invalid')
            {
                $cfsKode = " AND cfs_kode = '$cfsKode'";
            }
            else
            {
                $cfsKode = " AND cfs_kode IN (null,'','\"\"','x','X')";
            }
        }

        if ($sitKode != '')
            $sitKode = " AND sit_kode = '$sitKode'";
    	if ($statussite != '')
    	{
    		$sqlSite = " AND statussite = '$statussite' ";
    	}
        if ($action == 'summary')
        {


            $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalPOD,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_pod
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                            $sqlSite
                            $cfsKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
             $sql = "SELECT *
                     FROM procurement_pod
                     WHERE
                            prj_kode = '$prjKode'
                        $sitKode
                        $sqlSite";
             $fetch = $this->db->query($sql);
             $gTotal = $fetch->fetchAll();
             return $gTotal;
        }
    }

    public function getRpidWork($startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (date BETWEEN '$startDate' AND '$endDate')";
        }
        $sql = "
                    DROP TEMPORARY TABLE IF EXISTS `rpi_workflow`;
                    CREATE TEMPORARY TABLE `rpi_workflow`
                    SELECT
                        item_id,final
                    FROM workflow_trans
                    WHERE
                        item_type LIKE '%RPI%'
                        $sqlDate
                    GROUP BY item_id;";
        $this->db->query($sql);
    }

    public function getAsfdWork($startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (date BETWEEN '$startDate' AND '$endDate')";
        }
        $sql = "
                    DROP TEMPORARY TABLE IF EXISTS `asf_workflow`;
                    CREATE TEMPORARY TABLE `asf_workflow`
                    SELECT
                        item_id,final
                    FROM workflow_trans
                    WHERE
                        item_type LIKE '%ASF%'
                        $sqlDate
                    GROUP BY item_id;";
        $this->db->query($sql);
    }

    public function getBudgetProject($all=false,$prjKode='',$sitKode='',$detail=false,$offset=0,$allSite=false,$startDate='',$endDate='')
    {
    	if (!$allSite)
    		$limit = " LIMIT $offset,20";
        if ($all)
        {
         $sql = "SELECT
                    p.prj_kode,
                    p.prj_nama,
                    p.tglaw,
                    s.sit_kode,
                    s.sit_nama,
                    s.stsoverhead
                FROM master_project p
                LEFT JOIN master_site s
                ON (p.prj_kode = s.prj_kode)
                ORDER BY s.sit_kode ASC $limit;";
        $fetch = $this->db->query($sql);
        $master = $fetch->fetchAll();
        }
        else
        {
            if (isset($sitKode) && $sitKode != '')
                $addSql = " AND s.sit_kode='$sitKode'";
            $sql = "SELECT
                    p.prj_kode,
                    p.prj_nama,
                    p.tglaw,
                    s.sit_kode,
                    s.sit_nama,
                    s.stsoverhead
                FROM master_project p
                LEFT JOIN master_site s
                ON (p.prj_kode = s.prj_kode)
                WHERE p.prj_kode='$prjKode'" . $addSql .
                " ORDER BY s.sit_kode ASC $limit";
            $fetch = $this->db->query($sql);
            $master = $fetch->fetchAll();
        }
        $result = array();
        for ($i=0;$i<count($master);$i++)
        {
            $prjKode = $master[$i]['prj_kode'];
            $sitKode = $master[$i]['sit_kode'];
			$prjNama = $master[$i]['prj_nama'];
            $tglAw	= $master[$i]['tglaw'];

            $tglCFS = $this->getSiteCFSDate($prjKode,$sitKode);

			$result[$i]['prj_nama'] = $prjNama;
			$result[$i]['tglaw']= $tglAw;
			$result[$i]['tglcfs']= $tglCFS;

            $result[$i]['prj_kode'] = $prjKode;
            $result[$i]['sit_kode'] = $sitKode;
            $result[$i]['sit_nama'] = $master[$i]['sit_nama'];
            $result[$i]['stsoverhead'] = $master[$i]['stsoverhead'];

            //Start for BoQ3
            //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
			$boq3_current =  $this->getBoq3('summary-current', $prjKode, $sitKode,$startDate,$endDate);
            $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
			$result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
            $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
            $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
            $result[$i]['boq3_current1'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_current2'] = $boq3_current['totalHargaUSD'];
         	$boq3 = $this->getBoq3('summary-ori', $prjKode, $sitKode,$startDate,$endDate);
            $result[$i]['boq3_ori'] = $boq3['grandTotal'];
            $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
            $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
            $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
            $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];


            //Start for BoQ2
            //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
            $boq2 = $this->getBoq2('summary-ori', $prjKode, $sitKode,$startDate,$endDate);
            $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
            $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
            $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
            $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
            $boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode,$startDate,$endDate);
            $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
            $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
            $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
            $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];
        }

        return $result;
    }

    public function getBoq3($action='summary',$prjKode='',$sitKode='',$startDate='',$endDate='')
    {

        $sql = "SELECT COALESCE(NUll,rateidr) as rateidr FROM transengineer_boq3h WHERE prj_kode = '$prjKode' LIMIT 1;";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();
        if ($fetch)
            $rateUSD = $fetch['rateidr'];
        else
            $rateUSD = 0;

        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl BETWEEN '$startDate' AND '$endDate')";
        }

        $sql = "

            DROP TEMPORARY TABLE IF EXISTS boq3_ori_sort;
            CREATE TEMPORARY TABLE boq3_ori_sort
                SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
                    a.workid,
                    (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                    (IF(a.cfs_kode IN (null,'','x','X','\"\"'),'Invalid',a.cfs_kode)) AS cfs_kode,
                    a.sit_kode,
                    a.cfs_nama,
                    a.val_kode,
                    a.qty,
                    a.rateidr,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga) ElSE 0.00 END) AS hargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga) ElSE 0.00 END) AS hargaUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga * a.qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga * a.qty * $rateUSD) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga * a.qty) ElSE 0.00 END) AS totalHargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga * a.qty) ElSE 0.00 END) AS totalHargaUSD
                FROM transengineer_boq3d a WHERE
                    a.prj_kode = '$prjKode'
                and
                    a.sit_kode  = '$sitKode'
                and
                    a.rev = 'N'
                    $sqlDate
                ORDER BY a.tgl DESC;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_ori ;
            CREATE TEMPORARY TABLE boq3_ori
                SELECT * FROM boq3_ori_sort a GROUP BY a.workid,a.kode_brg;
        ";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_koreksi_sort ;
            CREATE TEMPORARY TABLE boq3_koreksi_sort
                    SELECT b.trano,
                    (IF(b.workid IN (1100,2100,3100,4100,5100),'XX',b.kode_brg)) AS kode_brg,
                    (IF(b.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
                    b.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                    (IF(b.cfs_kode IN (null,'','x','X','\"\"'),'Invalid',b.cfs_kode)) AS cfs_kode,
                    b.sit_kode,
                    b.cfs_nama,
			        b.val_kode,
                    b.qty,
                    rateidr,
			        b.urut,
                    (CASE b.val_kode WHEN 'IDR' THEN (b.harga) ElSE 0.00 END) AS hargaIDR,
                    (CASE b.val_kode WHEN 'USD' THEN (b.harga) ElSE 0.00 END) AS hargaUSD,
                    (CASE b.val_kode WHEN 'IDR' THEN (b.harga * b.qty) ElSE 0.00 END) AS totalIDR,
                    (CASE b.val_kode WHEN 'USD' THEN (b.harga * b.qty * $rateUSD) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'IDR' THEN (harga * qty) ElSE 0.00 END) AS totalHargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (harga * qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM transengineer_kboq3d b WHERE
                        b.prj_kode = '$prjKode'
                    and
                        b.sit_kode  = '$sitKode'
                        $sqlDate
                    ORDER BY tgl DESC,b.trano DESC,b.urut DESC;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_koreksi ;
            CREATE TEMPORARY TABLE boq3_koreksi
                SELECT * FROM boq3_koreksi_sort a GROUP BY a.workid,a.kode_brg;
        ";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_revisi;
            CREATE TEMPORARY TABLE boq3_revisi
                SELECT
                    a.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                    a.cfs_kode,
		    	    a.cfs_nama,
			        a.kode_brg,
                    a.nama_brg,
                    (IF(b.qty IS NOT  NULL,b.qty,a.qty))as qty,
			        a.val_kode,
                    (IF(b.hargaIDR IS NOT NULL,IF(b.hargaIDR != 0.00,b.hargaIDR,0.00),a.hargaIDR)) AS hargaIDR,
                    (IF(b.hargaUSD IS NOT NULL,IF(b.hargaUSD != 0.00,b.hargaUSD,0.00),a.hargaUSD)) AS hargaUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)) * (IF(b.rateidr IS NOT  NULL, $rateUSD, $rateUSD)))as totalUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalHargaIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)))as totalHargaUSD
                FROM
                    boq3_ori a
                LEFT JOIN
                    boq3_koreksi b
                ON
                    (a.workid = b.workid AND b.kode_brg = a.kode_brg);";
        $this->db->query($sql);
        $sql = "
            INSERT INTO boq3_revisi
                SELECT
                    b.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                    b.cfs_kode,
		    	    b.cfs_nama,
			        b.kode_brg,
                    b.nama_brg,
                    b.qty,
			        b.val_kode,
                    (IF(b.hargaIDR != 0.00,b.hargaIDR,0.00)) AS hargaIDR,
                    (IF(b.hargaUSD != 0.00,b.hargaUSD,0.00)) AS hargaUSD,
                    (CASE b.val_kode WHEN 'IDR' THEN (b.qty * b.hargaIDR) ELSE 0.00 END)as totalIDR,
                    (CASE b.val_kode WHEN 'USD' THEN (b.qty * b.hargaUSD * $rateUSD) ELSE 0.00 END)as totalUSD,
                    (CASE b.val_kode WHEN 'IDR' THEN (b.qty * b.hargaIDR) ELSE 0.00 END)as totalHargaIDR,
                    (CASE b.val_kode WHEN 'USD' THEN (b.qty * b.hargaUSD) ELSE 0.00 END)as totalHargaUSD
                FROM
                     boq3_ori a
                RIGHT JOIN
                    boq3_koreksi b
                ON
                    (a.workid = b.workid AND b.kode_brg = a.kode_brg)
                WHERE
                    a.qty IS NULL;

        ";
        $this->db->query($sql);
            switch ($action)
            {
                case 'summary-current':
					$sql = "
					    SELECT
                            SUM(totalIDR)+ SUM(totalUSD)as grandTotal,
                            COALESCE(SUM(totalIDR),0) as totalIDR,
                            COALESCE(SUM(totalUSD),0) as totalUSD,
                            COALESCE(SUM(totalHargaIDR),0) as totalHargaIDR,
                            COALESCE(SUM(totalHargaUSD),0) as totalHargaUSD
                        FROM boq3_revisi;";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetch();
                    return $gTotal;
                break;
                case 'summary-ori':
                    $sql = "
                        SELECT
                            SUM(totalIDR)+ SUM(totalUSD)as grandTotal,
                            COALESCE(SUM(totalIDR),0) as totalIDR,
                            COALESCE(SUM(totalUSD),0) as totalUSD,
                            COALESCE(SUM(totalHargaIDR),0) as totalHargaIDR,
                            COALESCE(SUM(totalHargaUSD),0) as totalHargaUSD
                        FROM boq3_ori;
                    ";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetch();
                    return $gTotal;
                break;
                case 'all-ori':
                    $sql = "
                        SELECT
 	                        *
                        FROM boq3_ori ORDER BY workid ASC, kode_brg ASC;
                    ";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetchAll();
                    return $gTotal;
                break;
                case 'all-current':
                    $sql = "
                        SELECT
 	                        *
                        FROM boq3_revisi ORDER BY workid ASC, kode_brg ASC;
                    ";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetchAll();
                    return $gTotal;
                break;
                case 'all-current-by-workid':

                    $query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','$action')");
					$query->execute();
					$workid = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
					$query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','all-current')");
					$query->execute();
                    $data = $query->fetchAll();
					$query->closeCursor();
					for($j=0;$j<count($workid);$j++)
                    {
                    	$workid_cari = $workid[$j]['workid'];
                    	$workid_result = $this->project->getWorkDetail($workid_cari);
                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['workid'] == $workid_cari)
                    		{
                    			$hasil[$workid_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                case 'all-ori-by-workid':
                     $query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','$action')");
					$query->execute();
					$workid = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
					$query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','all-ori')");
					$query->execute();
                    $data = $query->fetchAll();
					$query->closeCursor();
					for($j=0;$j<count($workid);$j++)
                    {
                    	$workid_cari = $workid[$j]['workid'];
                    	$workid_result = $this->project->getWorkDetail($workid_cari);
                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['workid'] == $workid_cari)
                    		{
                    			$hasil[$workid_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                case 'all-current-by-workid':
                	break;
                case 'all-pmeal':
					$query=$this->db->prepare("call procurement_piecemeal('$prjKode','$sitKode')");
					$query->execute();
					$gTotal = $query->fetchAll();
					$query->closeCursor();
                    return $gTotal;
                break;
                case 'all-current-by-workid':

                    $query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$workid = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-current')");
					$query->execute();
                    $data = $query->fetchAll();
					$query->closeCursor();
					for($j=0;$j<count($workid);$j++)
                    {
                    	$workid_cari = $workid[$j]['workid'];
                    	$workid_result = $this->project->getWorkDetail($workid_cari);
                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['workid'] == $workid_cari)
                    		{
                    			$hasil[$workid_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                case 'all-ori-by-workid':
                     $query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$workid = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-ori')");
					$query->execute();
                    $data = $query->fetchAll();
					$query->closeCursor();
					for($j=0;$j<count($workid);$j++)
                    {
                    	$workid_cari = $workid[$j]['workid'];
                    	$workid_result = $this->project->getWorkDetail($workid_cari);
                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['workid'] == $workid_cari)
                    		{
                    			$hasil[$workid_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                case 'all-current-by-workid':
                	break;
                case 'boq3-gabung':
                    $query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-current-by-cfskode')");
					$query->execute();
					$cfskode = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
                	$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-ori-current-gabung')");
                	$query->execute();
					$data = $query->fetchAll();
					$query->closeCursor();

                    for($j=0;$j<count($cfskode);$j++)
                    {
                        $cfskode_cari = $cfskode[$j]['cfs_kode'];
                    	$cfskode_result = $this->project->getWorkDetail($cfskode_cari);
                    	$hasil[$cfskode_cari]['cfs_nama'] = $cfskode_result['cfs_nama'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['cfs_kode'] == $cfskode_cari)
                    		{
                    			$hasil[$cfskode_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                case 'summary-by-cfskode':
                    $query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-ori-by-sitkode')");
					$query->execute();
					$cfskode = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
                	$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-ori-current-gabung')");
                	$query->execute();
					$data = $query->fetchAll();
                    $query->closeCursor();
                    $cari = array();
                    $prj = $prjKode;
                    $sit = $sitKode;
                    foreach ($data as $index => $arrays2)
                    {
                            $cfs = $arrays2['cfs_kode'];
                            $totalIDR = $arrays2['totalIDR'];
                            if ($cfs == '' || $cfs == '""')
                                $cfs = 'x';

                            $cari[$sit][$cfs] = $cfs;
                    }

                    for($j=0;$j<count($cfskode);$j++)
                    {
                        $cfskode_cari = $cfskode[$j]['cfs_kode'];
                    	$cfskode_result = $this->project->getWorkDetail($cfskode_cari);
                    	$hasil[$cfskode_cari]['cfs_nama'] = $cfskode_result['cfs_nama'];
						$indeks = 0;
                    	foreach ($data as $key => $key2)
                    	{
                    		if ($data[$key]['cfs_kode'] == $cfskode_cari)
                    		{
                    			$hasil[$cfskode_cari][$indeks] = $data[$key];
                    			$indeks++;
                    		}
                    	}
                    }
                    return $hasil;
                break;
                default:
                    return '';
                break;
            }
    }

    public function getBoq2($action='summary',$prjKode='',$sitKode='',$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl BETWEEN '$startDate' AND '$endDate')";
        }

        if ($action == 'summary-ori' || $action == 'summary-current')
        {
            $sql = "
                DROP TEMPORARY TABLE IF EXISTS boq2_budget;
                CREATE TEMPORARY TABLE boq2_budget
                SELECT 
            		(a.totalOriginIDR) AS totalOriginIDR,
            		(a.totalOriginIDR) AS totalOriginHargaIDR,
            		(a.totalOriginHargaUSD) AS totalOriginHargaUSD,
            		(a.totalCurrentIDR + a.totalOriginIDR) AS totalCurrentIDR,
            		(a.totalOriginIDR + a.totalCurrentIDR) AS totalCurrentHargaIDR,
            		(a.totalOriginHargaUSD + a.totalCurrentHargaUSD) AS totalCurrentHargaUSD
                FROM
                (
                    SELECT
                        total AS totalOriginIDR,
                        totalusd AS totalOriginHargaUSD,
                        COALESCE(
                        (
                            SELECT
                                SUM(totaltambah)
                            FROM
                                transengineer_kboq2h
                            WHERE
                                prj_kode = a.prj_kode AND sit_kode=a.sit_kode
                                $sqlDate
                        ),0) AS totalCurrentIDR,
                        COALESCE(
                        (
                            SELECT
                                SUM(totaltambahusd)*rateidr
                            FROM
                                transengineer_kboq2h
                            WHERE
                                prj_kode = a.prj_kode AND sit_kode=a.sit_kode
                                $sqlDate
                        ),0) AS totalCurrentUSD,
                        COALESCE(
                        (
                            SELECT
                                SUM(totaltambahusd)
                            FROM
                                transengineer_kboq2h
                            WHERE
                                prj_kode = a.prj_kode AND sit_kode=a.sit_kode
                                $sqlDate
                        ),0) AS totalCurrentHargaUSD
                    FROM
                        transengineer_boq2h a
                    WHERE
                        prj_kode = '$prjKode' AND sit_kode='$sitKode'
                        $sqlDate
                    ORDER BY
                        tgl DESC, trano DESC, sit_kode ASC
                    LIMIT 1
                ) a;";

            $this->db->query($sql);
            $sql = "
                SELECT
                *
                FROM boq2_budget";
            
            $fetch = $this->db->query($sql);
			$gTotal = $fetch->fetch();

            return $gTotal;

        }
        else
        {
            $sql = "CREATE TEMPORARY TABLE boq2_ori
                    SELECT *
                    FROM
                     transengineer_boq2h a
                    WHERE
                      prj_kode = '$prjKode' AND sit_kode='$sitKode'
                    ORDER BY
                      sit_kode ASC";
            $fetch = $this->db->query($sql);
            $sql = "CREATE TEMPORARY TABLE boq2_koreksi
                    SELECT *
                    FROM
                     transengineer_kboq2h a
                    WHERE
                      prj_kode = '$prjKode' AND sit_kode='$sitKode'
                    ORDER BY
                      sit_kode ASC";
            $fetch = $this->db->query($sql);
            $sql = "CREATE TEMPORARY TABLE boq2_current
                    SELECT *
                    FROM boq2_ori";
            $fetch = $this->db->query($sql);
            $sql = "INSERT INTO boq2_current
                    SELECT *
                    FROM boq2_koreksi";
            $fetch = $this->db->query($sql);
            switch($action)
            {
                case 'all-ori':
                    $sql = "SELECT *
                            FROM boq2_ori
                            ORDER BY sit_kode ASC";
                    $fetch = $this->db->query($sql);
                    $result = $fetch->fetchAll();
                    return $result;
                break;
                case 'all-current' :
                    $sql = "SELECT *
                            FROM boq2_current
                            ORDER BY sit_kode ASC";
                    $fetch = $this->db->query($sql);
                    $result = $fetch->fetchAll();
                    return $result;
                break;
            }
        }
    }

    public function getArfd($action='summary',$prjKode='',$sitKode='', $cfsKode='',$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl BETWEEN '$startDate' AND '$endDate')";
        }

        if ($cfsKode != '')
        {
            if ($cfsKode != 'Invalid')
            {
                $cfsKode = " AND cfs_kode = '$cfsKode'";
            }
            else
            {
                $cfsKode = " AND cfs_kode IN (null,'','\"\"','x','X')";
            }
        }

    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
        if ($action == 'summary')
        {
            $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalARF,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_arfd
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                            $cfsKode
                            $sqlDate
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
            $sql = "SELECT *
                    FROM procurement_arfd
                    WHERE
                            prj_kode = '$prjKode'
                    $sitKode
                    $sqlDate";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getAsfdd($action='summary',$prjKode='',$sitKode='',$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl BETWEEN '$startDate' AND '$endDate')";
        }
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
        if ($action == 'summary')
        {
            $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalASFDD,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_asfdd
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                            $sqlDate
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
            $sql = "SELECT *
                    FROM procurement_asfdd
                    WHERE
                            prj_kode = '$prjKode'
                    $sitKode
                    $sqlDate";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
    }

    public function getAsfddCancel($action='summary',$prjKode='',$sitKode='',$startDate='',$endDate='')
    {
        //Date range
        if ($startDate != '' && $endDate != '')
        {
            $sqlDate = " AND (tgl BETWEEN '$startDate' AND '$endDate')";
        }
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalAsfddCancel,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_asfddcancel
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                            $sqlDate
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_asfddcancel
                    WHERE
                            prj_kode = '$prjKode'
                        AND sit_kode = '$sitKode'
                        $sqlDate";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getSiteCFSDate($prjKode='',$sitKode='')
    {
        $sql = "
            SELECT
                MIN(tgl) as tgl
            FROM
                transengineer_boq3h
            WHERE
                prj_kode = '$prjKode'
            AND
                sit_kode = '$sitKode'";

        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetch();

        if ($hasil['tgl'] != '')
            return date("d M Y",strtotime($hasil['tgl']));
        else
            return '';
        
    }

    public function getPiecemeal($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
        if ($action == 'summary')
        {
            $sql = "SELECT
                        SUM(harga_borong * qty) AS totalPieceMeal
                    FROM boq_dboqpasang
                    WHERE
                            prj_kode = '$prjKode'
                    $sitKode;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal['totalPieceMeal'];
        }
        else
        {
             $sql = "SELECT
                        *
                    FROM boq_dboqpasang
                    WHERE
                            prj_kode = '$prjKode'
                    $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getLeftOver($action='summary',$prjKode='',$sitKode='')
    {
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalLeftOver,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_whbringbackd
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_whbringbackd
                    WHERE
                            prj_kode = '$prjKode'
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getCancel($action='summary',$prjKode='',$sitKode='')
    {
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalCancel,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_whreturnd
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_whreturnd
                    WHERE
                            prj_kode = '$prjKode'
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getInvoice($action='summary',$prjKode='',$sitKode='')
    {
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalHargaIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (total) ElSE 0.00 END) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (total) ElSE 0.00 END) AS totalHargaUSD
                        FROM finance_invoiced
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM finance_invoiced
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getPaymentInvoice($action='summary',$prjKode='',$sitKode='')
    {
    	if ($sitKode != '')
    		$sitKode = " AND sit_kode = '$sitKode'";
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalHargaIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (total) ElSE 0.00 END) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (total) ElSE 0.00 END) AS totalHargaUSD
                        FROM finance_payment_invoice
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM finance_payment_invoice
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }
}