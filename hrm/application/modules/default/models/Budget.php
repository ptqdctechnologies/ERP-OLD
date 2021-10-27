<?php
class Default_Models_Budget extends Zend_Db_Table_Abstract
{

    private $db;
    private $project;
    private $memcache;
    private $log;
    private $session;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
        $this->log = new Admin_Model_Logtransaction();
        $this->session = new Zend_Session_Namespace('login');

    }    

    public function getBoq3ByOne($prjKode='',$sitKode='',$workid='',$kodeBrg='')
    {

    	if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100 && $workid != 6100)
    	{
    		$query = " and
                   		a.kode_brg = '$kodeBrg'";
    		$query2 = " and
                   		b.kode_brg = '$kodeBrg'";
    	}
    	$sql = "SELECT rateidr FROM transengineer_boq3h WHERE prj_kode = '$prjKode' AND sit_kode = '$sitKode';";
    	$fetch = $this->db->query($sql);
    	$rateUSD = $fetch->fetch();

    	$rateUSD = $rateUSD['rateidr'];

    	if ($rateUSD == '')
    		$rateUSD = 0;
    	else
    		$rateUSD = abs($rateUSD);
    	$sql = "DROP TEMPORARY TABLE IF EXISTS boq3_ori;
                CREATE TEMPORARY TABLE boq3_ori
                    SELECT * FROM (SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
                    a.workid,
					(SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                    a.val_kode,
                    a.qty,
                    a.rateidr,
                    a.cfs_kode,
                    a.cfs_nama,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga) ElSE 0.00 END) AS hargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga) ElSE 0.00 END) AS hargaUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga*a.qty*$rateUSD) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalHargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM transengineer_boq3d a
                    WHERE
                        a.prj_kode = '$prjKode'
                    and
                        a.sit_kode = '$sitKode'
                    and
                    	a.workid = '$workid'
                    and
                        a.rev = 'N' $query
                    ORDER BY a.tgl DESC) b GROUP BY b.workid,b.kode_brg;";
    	$this->db->query($sql);
		$sql = 	"DROP TEMPORARY TABLE IF EXISTS boq3_koreksi ;
                 CREATE TEMPORARY TABLE boq3_koreksi SELECT * FROM (
                        SELECT b.trano,
                        (IF(b.workid IN (1100,2100,3100,4100,5100,6100),'XX',b.kode_brg)) AS kode_brg,
                    	(IF(b.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
                    	b.workid,
						(SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                        b.val_kode,
                        b.qty,
                        rateidr,
						b.urut,
						b.cfs_kode,
						b.cfs_nama,
                        (CASE b.val_kode WHEN 'IDR' THEN (b.harga) ElSE 0.00 END) AS hargaIDR,
                        (CASE b.val_kode WHEN 'USD' THEN (b.harga) ElSE 0.00 END) AS hargaUSD,
                        (CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END) AS totalIDR,
                        (CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty*$rateUSD) ElSE 0.00 END) AS totalUSD,
	                    (CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalHargaIDR,
	                    (CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM transengineer_kboq3d b WHERE
                            b.prj_kode = '$prjKode'
                        and
                            b.sit_kode = '$sitKode'
                        and
                        	b.workid = '$workid' $query2
                        ORDER BY tgl DESC,b.trano DESC,b.urut DESC
                    ) a GROUP BY a.workid,a.kode_brg;";
		$this->db->query($sql);
		$sql = "DROP TEMPORARY TABLE IF EXISTS boq3_revisi;
                CREATE TEMPORARY TABLE boq3_revisi
                    SELECT
                      a.workid,
					 (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                      a.kode_brg,
                      a.nama_brg,
                      (IF(b.qty IS NOT  NULL,b.qty,a.qty))as qty,
			        (IF(b.val_kode != a.val_kode,b.val_kode,a.val_kode)) as val_kode,
					a.cfs_kode,
					a.cfs_nama,
                    (IF(b.hargaIDR IS NOT NULL,IF(b.hargaIDR != 0.00,b.hargaIDR,0.00),a.hargaIDR)) AS hargaIDR,
                    (IF(b.hargaUSD IS NOT NULL,IF(b.hargaUSD != 0.00,b.hargaUSD,0.00),a.hargaUSD)) AS hargaUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)) * (IF(b.rateidr IS NOT  NULL,$rateUSD,$rateUSD)))as totalUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalHargaIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)))as totalHargaUSD
                    FROM
                      boq3_ori a
                    LEFT JOIN
                      boq3_koreksi b
                    ON
                      (a.workid = b.workid AND b.kode_brg = a.kode_brg);";
		$this->db->query($sql);
		$sql = "INSERT INTO boq3_revisi
                    SELECT
                      b.workid,
					  (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                      b.kode_brg,
                      b.nama_brg,
                      b.qty,
					  b.val_kode,
					  b.cfs_kode,
					  b.cfs_nama,
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
                      a.qty IS NULL;";
    	$fetch  = $this->db->query($sql);
        $sql = "SELECT * FROM boq3_revisi;";
    	$fetch  = $this->db->query($sql);
    	$gTotal = $fetch->fetch();
    	return $gTotal;

    }

    public function getBoq3PmealByOne($prjKode='',$sitKode='',$kodeBrg='')
    {
    	$sql = "SELECT rateidr FROM transengineer_boq3h WHERE prj_kode = '$prjKode' AND sit_kode = '$sitKode';";
    	$fetch = $this->db->query($sql);
    	$rateUSD = $fetch->fetch();

    	$rateUSD = $rateUSD['rateidr'];

    	if ($rateUSD == '')
    		$rateUSD = 0;
    	else
    		$rateUSD = abs($rateUSD);
    	$sql = "CREATE TEMPORARY TABLE boq3_ori
                    SELECT * FROM (
                    SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
                    a.workid,
					(SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                    a.val_kode,
                    a.stspmeal,
                    a.qty,
                    a.rateidr,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga) ElSE 0.00 END) AS hargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga) ElSE 0.00 END) AS hargaUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga*a.qty*$rateUSD) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'IDR' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalHargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (a.harga*a.qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM transengineer_boq3d a
                    WHERE
                        a.prj_kode = '$prjKode'
                    and
                        a.sit_kode = '$sitKode'
                    and
                    	a.kode_brg = '$kodeBrg'
                    and
                    	a.stspmeal = 'Y'
                    and
                        a.rev = 'N'
                    ORDER BY a.tgl DESC ) b GROUP BY b.kode_brg";
    	$this->db->query($sql);
		$sql = 	"CREATE TEMPORARY TABLE boq3_koreksi SELECT * FROM (
                        SELECT b.trano,
                        (IF(b.workid IN (1100,2100,3100,4100,5100,6100),'XX',b.kode_brg)) AS kode_brg,
                    	(IF(b.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
                    	b.workid,
						(SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                        b.val_kode,
                        b.stspmeal,
                        b.qty,
                        b.rateidr,
						b.urut,
                        (CASE b.val_kode WHEN 'IDR' THEN (b.harga) ElSE 0.00 END) AS hargaIDR,
                        (CASE b.val_kode WHEN 'USD' THEN (b.harga) ElSE 0.00 END) AS hargaUSD,
                        (CASE b.val_kode WHEN 'IDR' THEN (b.harga*b.qty) ElSE 0.00 END) AS totalIDR,
                        (CASE b.val_kode WHEN 'USD' THEN (b.harga*b.qty*$rateUSD) ElSE 0.00 END) AS totalUSD,
	                    (CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalHargaIDR,
	                    (CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM transengineer_kboq3d b WHERE
                            b.prj_kode = '$prjKode'
                        and
                            b.sit_kode = '$sitKode'
                        and
                        	b.kode_brg = '$kodeBrg'
                        and
                    	    b.stspmeal = 'Y'
                        ORDER BY tgl DESC,b.trano DESC,b.urut DESC
                    ) a GROUP BY a.kode_brg;";
		$this->db->query($sql);
		$sql = "CREATE TEMPORARY TABLE boq3_revisi
                    SELECT
                      a.workid,
					 (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                      a.kode_brg,
                      a.nama_brg,
                      (IF(b.qty IS NOT  NULL,b.qty,a.qty))as qty,
					a.val_kode,
					a.stspmeal,
			        a.trano,
                    (IF(b.hargaIDR IS NOT NULL,IF(b.hargaIDR != 0.00,b.hargaIDR,0.00),a.hargaIDR)) AS hargaIDR,
                    (IF(b.hargaUSD IS NOT NULL,IF(b.hargaUSD != 0.00,b.hargaUSD,0.00),a.hargaUSD)) AS hargaUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)) * (IF(b.rateidr IS NOT  NULL,$rateUSD,$rateUSD)))as totalUSD,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaIDR IS NOT  NULL,b.hargaIDR,a.hargaIDR)))as totalHargaIDR,
                    ((IF(b.qty IS NOT  NULL,b.qty,a.qty)) * (IF(b.hargaUSD IS NOT  NULL,b.hargaUSD,a.hargaUSD)))as totalHargaUSD
                    FROM
                      boq3_ori a
                    LEFT JOIN
                      boq3_koreksi b
                    ON
                      (b.kode_brg = a.kode_brg);";
		$this->db->query($sql);
		$sql = "INSERT INTO boq3_revisi
                    SELECT
                      b.workid,
					  (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                      b.kode_brg,
                      b.nama_brg,
                      b.qty,
					  b.val_kode,
					  b.stspmeal,
                      b.trano,
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
                      (b.kode_brg = a.kode_brg)
                    WHERE
                      a.qty IS NULL;";
    	$fetch  = $this->db->query($sql);
        $sql = "SELECT * FROM boq3_revisi;";
    	$fetch  = $this->db->query($sql);
    	$gTotal = $fetch->fetch();
    	return $gTotal;

    }

    public function getBudgetOverhead($prjKode='',$sitKode='',$budgetid='')
    {
        if ($budgetid != '')
            $where = " AND budgetid = '$budgetid'";
       $sql = "SELECT
                    trano,
                    urut,
                    budgetid,
					budgetname,
                    val_kode,

                    coa_kode,
                    coa_nama,

                    (CASE val_kode WHEN 'IDR' THEN (total) ElSE 0.00 END) AS totalIDR,
                    (CASE val_kode WHEN 'USD' THEN (total*rateidr) ElSE 0.00 END) AS totalUSD,
                    (CASE val_kode WHEN 'IDR' THEN (total) ElSE 0.00 END) AS totalHargaIDR,
                    (CASE val_kode WHEN 'USD' THEN (total) ElSE 0.00 END) AS totalHargaUSD

                    FROM transengineer_boq3dnonproject
                    WHERE
                        prj_kode = '$prjKode'
                    and
                        sit_kode = '$sitKode'
                    $where
                   " ;

        $fetch  = $this->db->query($sql);
    	$gTotal = $fetch->fetchAll();
    	return $gTotal;
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
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
                    a.workid,
                    (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
                    (IF(a.cfs_kode IN (null,'','x','X','\"\"'),'Invalid',a.cfs_kode)) AS cfs_kode,
                    a.sit_kode,
                    a.cfs_nama,
                    a.val_kode,
                    a.qty,
                    a.rateidr,
                    a.tgl,
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
                    (IF(b.workid IN (1100,2100,3100,4100,5100,6100),'XX',b.kode_brg)) AS kode_brg,
                    (IF(b.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
                    b.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
                    (IF(b.cfs_kode IN (null,'','x','X','\"\"'),'Invalid',b.cfs_kode)) AS cfs_kode,
                    b.sit_kode,
                    b.cfs_nama,
			        b.val_kode,
                    b.qty,
                    b.rateidr,
			        b.urut,
                    b.tgl,
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
			        (IF(b.val_kode != a.val_kode,b.val_kode,a.val_kode)) as val_kode,
                    a.tgl,
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
                    b.tgl,
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
//                	$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-current-by-workid')");
//					$query->execute();
//					$workid = $query->fetchAll();
//					$query->closeCursor();
                    $query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-current-by-cfskode')");
					$query->execute();
					$cfskode = $query->fetchAll();
					$query->closeCursor();
					$hasil = array();
                	$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','all-ori-current-gabung')");
                	$query->execute();
					$data = $query->fetchAll();
					$query->closeCursor();
//                    for($j=0;$j<count($workid);$j++)

                    for($j=0;$j<count($cfskode);$j++)
                    {
//                    	$workid_cari = $workid[$j]['workid'];
//                    	$workid_result = $this->project->getWorkDetail($workid_cari);
//                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
//						$indeks = 0;
//                    	foreach ($data as $key => $key2)
//                    	{
//                    		if ($data[$key]['workid'] == $workid_cari)
//                    		{
//                    			$hasil[$workid_cari][$indeks] = $data[$key];
//                    			$indeks++;
//                    		}
//                    	}
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
//                    for($j=0;$j<count($workid);$j++)
//var_dump("call procurement_boq3revisi('$prjKode','$sitKode','all-ori-current-gabung')");die;
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
//                            foreach ($cari[$sit] as $index2 => $arrays)
//                            {
//
//                            }
                    }
//                    echo '<pre>';
//                    var_dump($this->super_unique($cari));echo '</pre>';die;

                    for($j=0;$j<count($cfskode);$j++)
                    {
//                    	$workid_cari = $workid[$j]['workid'];
//                    	$workid_result = $this->project->getWorkDetail($workid_cari);
//                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
//						$indeks = 0;
//                    	foreach ($data as $key => $key2)
//                    	{
//                    		if ($data[$key]['workid'] == $workid_cari)
//                    		{
//                    			$hasil[$workid_cari][$indeks] = $data[$key];
//                    			$indeks++;
//                    		}
//                    	}
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

    public function getBoq2($action='summary',$prjKode='',$sitKode='')
    {
        if ($action == 'summary-ori' || $action == 'summary-current')
        {

            $query=$this->db->prepare("call procurement_boq2('$prjKode','$sitKode','summary-ori')");
			$query->execute();
			$gTotal = $query->fetch();
			$query->closeCursor();
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

    public function getPodByOne($action='summary',$prjKode='',$sitKode='',$supKode='',$trano='')
    {
        if ($prjKode != '' & $sitKode != '')
            $where = " prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        if ($supKode != '')
            $where = " sup_kode = '$supKode'";
        if ($trano != '')
            $where = " trano = '$trano'";
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
                            $where
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
                        $where;";
             $fetch = $this->db->query($sql);
             $gTotal = $fetch->fetchAll();
             return $gTotal;
        }
    }

    public function getArfd($action='summary',$prjKode='',$sitKode='', $cfsKode='')
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
                    $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getAsfdd($action='summary',$prjKode='',$sitKode='')
    {
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
                    $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
    }

    public function getMdod($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
            $sitKode = " AND sit_kode = '$sitKode'";
        if ($action == 'summary')
        {
//            $sql = "SELECT (a.totalIDR+a.totalUSD) as totalMDOD
//                    FROM (
//                    SELECT
//                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*CAST(qty AS SIGNED)) ElSE 0.00 END) AS totalIDR,
//                        SUM(CASE val_kode WHEN 'USD' THEN (harga*CAST(qty AS SIGNED)*rateidr) ElSE 0.00 END) AS totalUSD
//                    FROM procurement_mdod
//                    WHERE
//                            prj_kode = '$prjKode'
//                        AND sit_kode = '$sitKode'
//                        AND asalbarang='WH'
//                    ) a;";
            $sql = "SELECT
            			a.totalIDR,
            			a.totalUSD,
            			(a.totalIDR + a.totalUSD) as totalMDOD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
            		FROM
            		(
            		SELECT
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_mdod
                    WHERE
                            prj_kode = '$prjKode'
                        $sitKode
                        AND asalbarang='WH') a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
            $sql = "SELECT *
                    FROM procurement_mdod
                    WHERE
                            prj_kode = '$prjKode'
                    $sitKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
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
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalRPI,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_rpid
                        WHERE
                            prj_kode = '$prjKode'
                            $sitKode
                            $SQLsite
                        ) a ;";
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

    public function getRpidByOne($action='summary',$prjKode='',$sitKode='',$supKode='',$trano='',$pono='',$status='')
    {
       if ($prjKode != '' & $sitKode != '')
            $where = " prj_kode = '$prjKode' AND sit_kode = '$sitKode'";
        if ($supKode != '')
            $where = " sup_kode = '$supKode'";
        if ($trano != '')
            $where = " trano = '$trano'";
        if ($pono != '')
            $where = " po_no = '$pono'";
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
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalRPI,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_rpid
                        WHERE
                            $where
                            $SQLsite
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_rpid
                    WHERE
                        $where
                        $SQLsite";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getBudgetProject($all=false,$prjKode='',$sitKode='',$detail=false,$offset=0,$allSite=false)
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

			$result[$i]['prj_nama'] = $prjNama;
			$result[$i]['tglaw']= $tglAw;

            //Start for BoQ3
            //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
			$boq3_current =  $this->getBoq3('summary-current', $prjKode, $sitKode);
            $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
			$result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
            $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
            $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
            $result[$i]['boq3_current1'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_current2'] = $boq3_current['totalHargaUSD'];
         	$boq3 = $this->getBoq3('summary-ori', $prjKode, $sitKode);
            $result[$i]['boq3_ori'] = $boq3['grandTotal'];
            $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
            $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
            $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
            $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];


            //Start for BoQ2
            //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
            $boq2 = $this->getBoq2('summary-ori', $prjKode, $sitKode);
            $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
            $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
            $result[$i]['boq2_oriUSD'] = $boq2['totalOriginUSD'];
            $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
            $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
            $boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode);
            $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
            $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
            $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
            $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
            $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];

            //Start for MIP
            //Formula : MIP = (total PO + total ARF)
//            $POD = $this->getPod('summary', $prjKode, $sitKode);
//            $totalPO =  $POD['totalPOD'];
//            $totalPOIDR = $POD['totalIDR'];
//            $totalPOUSD = $POD['totalUSD'];
//            $totalPOHargaIDR = $POD['totalHargaIDR'];
//            $totalPOHargaUSD = $POD['totalHargaUSD'];
//            $ARF = $this->getArfd('summary', $prjKode, $sitKode);
//            $totalARF = $ARF['totalARF'];
//            $totalARFIDR = $ARF['totalIDR'];
//            $totalARFUSD = $ARF['totalUSD'];
//            $totalARFHargaIDR = $ARF['totalHargaIDR'];
//            $totalARFHargaUSD = $ARF['totalHargaUSD'];
//            $totalMIP = $totalARF + $totalPO;
//            $result[$i]['mip_current'] = $totalMIP;
//            $result[$i]['mip_currentIDR'] = $totalPOIDR + $totalARFIDR;
//            $result[$i]['mip_currentUSD'] = $totalPOUSD + $totalARFUSD;
//            $result[$i]['mip_currentHargaIDR'] = $totalPOHargaIDR + $totalARFHargaIDR;
//            $result[$i]['mip_currentHargaUSD'] = $totalPOHargaUSD + $totalARFHargaUSD;

            //Start for Boq4
            //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)

            $ASF = $this->getAsfdd('summary', $prjKode, $sitKode);
            $totalASF = $ASF['totalASFDD'];
            $totalASFIDR = $ASF['totalIDR'];
            $totalASFUSD = $ASF['totalUSD'];
            $totalASFHargaIDR = $ASF['totalHargaIDR'];
            $totalASFHargaUSD = $ASF['totalHargaUSD'];

//            $ASFCancel = $this->getAsfddCancel('summary', $prjKode, $sitKode);
//            $totalASFCancel = $ASFCancel['totalAsfddCancel'];
//            $totalASFCancelIDR = $ASFCancel['totalIDR'];
//            $totalASFCancelUSD = $ASFCancel['totalUSD'];
//            $totalASFCancelHargaIDR = $ASFCancel['totalHargaIDR'];
//            $totalASFCancelHargaUSD = $ASFCancel['totalHargaUSD'];

            $DO = $this->getDo('summary',$prjKode,$sitKode);
            $totalDO = $DO['totalDO'];
            $totalDOIDR = $DO['totalIDR'];
            $totalDOUSD = $DO['totalUSD'];
            $totalDOHargaIDR = $DO['totalHargaIDR'];
            $totalDOHargaUSD = $DO['totalHargaUSD'];
            
//            $MDO =  $this->getMdod('summary', $prjKode, $sitKode);
//            $totalMDO = $MDO['totalMDOD'];
//            $totalMDOIDR = $MDO['totalIDR'];
//            $totalMDOUSD = $MDO['totalUSD'];
//            $totalMDOHargaIDR = $MDO['totalHargaIDR'];
//            $totalMDOHargaUSD = $MDO['totalHargaUSD'];
            $pieceMeal = $this->getPiecemeal('summary', $prjKode, $sitKode);
            $totalPieceMeal = $pieceMeal;
//            $RPI = $this->getRpid('summary', $prjKode, $sitKode,'service');
            $RPI = $this->getRpid('summary', $prjKode, $sitKode,'');
            $totalRPI = $RPI['totalRPI'];
            $totalRPIIDR = $RPI['totalIDR'];
            $totalRPIUSD = $RPI['totalUSD'];
            $totalRPIHargaIDR = $RPI['totalHargaIDR'];
            $totalRPIHargaUSD = $RPI['totalHargaUSD'];

//            $PODsite = $this->getPod('summary', $prjKode, $sitKode,'Y'); //statussite = Y
//	        $totalPOsite =  $PODsite['totalPOD'];
//	        $totalPOIDRsite = $PODsite['totalIDR'];
//	        $totalPOUSDsite = $PODsite['totalUSD'];
//	        $totalPOHargaIDRsite = $PODsite['totalHargaIDR'];
//	        $totalPOHargaUSDsite = $PODsite['totalHargaUSD'];

	        $LeftOver = $this->getLeftOver('summary', $prjKode, $sitKode);
	        $totalLeftOver = $LeftOver['totalLeftOver'];
	        $totalLeftOverIDR = $LeftOver['totalIDR'];
	        $totalLeftOverUSD = $LeftOver['totalUSD'];
	        $totalLeftOverHargaIDR = $LeftOver['totalHargaIDR'];
	        $totalLeftOverHargaUSD = $LeftOver['totalHargaUSD'];

            $Cancel = $this->getCancel('summary', $prjKode, $sitKode);
	        $totalCancel = $Cancel['totalCancel'];
	        $totalCancelIDR = $Cancel['totalIDR'];
	        $totalCancelUSD = $Cancel['totalUSD'];
	        $totalCancelHargaIDR = $Cancel['totalHargaIDR'];
	        $totalCancelHargaUSD = $Cancel['totalHargaUSD'];

            $Sal = $this->getSal('summary', $prjKode, $sitKode);
	        $totalSalIDR = $Sal['totalIDR'];
	        $totalSal = $Sal['totalIDR'];
	        $totalSalHargaIDR = $Sal['totalHargaIDR'];
//	        $Reimbursement = $this->getReimbursement('summary', $prjKode, $sitKode);
//	        $totalReimbursement = $Reimbursement['totalReimburesement'];
//	        $totalReimbursementIDR = $Reimbursement['totalIDR'];
//	        $totalReimbursementUSD = $Reimbursement['totalUSD'];
//	        $totalReimbursementHargaIDR = $Reimbursement['totalHargaIDR'];
//	        $totalReimbursementHargaUSD = $Reimbursement['totalHargaUSD'];

            $result[$i]['boq4_current'] = $totalASF  + $totalDO + $totalPieceMeal + $totalRPI + $totalSal;// + $totalPOsite;// + $totalReimbursement;
	        $result[$i]['boq4_currentIDR'] = $totalASFIDR  + $totalDOIDR + $totalPieceMeal + $totalRPIIDR + $totalSalIDR;// + $totalPOIDRsite; //+ $totalReimbursementIDR;
	        $result[$i]['boq4_currentUSD'] = $totalASFUSD  + $totalDOUSD + $totalRPIUSD;// + $totalPOUSDsite; //+ $totalReimbursementUSD;
	        $result[$i]['boq4_currentHargaIDR'] = $totalASFHargaIDR + $totalDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR  + $totalSalHargaIDR;// + $totalPOHargaIDRsite; //+ $totalReimbursementHargaIDR;
	        $result[$i]['boq4_currentHargaUSD'] = $totalASFHargaUSD + $totalDOHargaUSD + $totalRPIHargaUSD;// + $totalPOHargaUSDsite; //+  $totalReimbursementHargaUSD;

	        $result[$i]['return'] = $totalLeftOver + $totalCancel;
        	$result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
        	$result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
        	$result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
        	$result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;

            $result[$i]['boq4_current'] -= $result[$i]['return'];
            $result[$i]['boq4_currentIDR'] -= $result[$i]['returnIDR'];
            $result[$i]['boq4_currentUSD'] -= $result[$i]['returnUSD'];
            $result[$i]['boq4_currentHargaIDR'] -= $result[$i]['returnHargaIDR'];
            $result[$i]['boq4_currentHargaUSD'] -= $result[$i]['returnHargaUSD'];

        	$result[$i]['finalCost'] = $result[$i]['boq2_current'] - $result[$i]['boq3_current'] - $result[$i]['return'];

            $result[$i]['prj_kode'] = $prjKode;
            $result[$i]['sit_kode'] = $sitKode;
            $result[$i]['sit_nama'] = $master[$i]['sit_nama'];
            $result[$i]['stsoverhead'] = $master[$i]['stsoverhead'];
//            if (!$detail)
//            {
//
//	            //Start for BoQ3
//	            //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
//				$boq3_current =  $this->getBoq3('summary-current', $prjKode, $sitKode);
//	            $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
//	            $boq3 = $this->getBoq3('summary-ori', $prjKode, $sitKode);
//	            $result[$i]['boq3_ori'] = $boq3['grandTotal'];
//
//	            //Start for BoQ2
//	            //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
//
//	            $boq2 = $this->getBoq2('summary-ori', $prjKode, $sitKode);
//	            $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
//	            $boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode);
//	            $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
//
//	            //Start for MIP
//	            //Formula : MIP = (total PO + total ARF)
//				$POD = $this->getPod('summary', $prjKode, $sitKode);
//	            $totalPO =  $POD['totalPOD'];
//	            $ARF = $this->getArfd('summary', $prjKode, $sitKode);
//	            $totalARF = $ARF['totalARF'];
//	            $totalMIP = $totalARF + $totalPO;
//	            $result[$i]['mip_current'] = $totalMIP;
//
//	            //Start for Boq4
//	            //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)
//
//	            $ASF = $this->getAsfdd('summary', $prjKode, $sitKode);
//	            $totalASF = $ASF['totalASFDD'];
//	            $MDO =  $this->getMdod('summary', $prjKode, $sitKode);
//	            $totalMDO = $MDO['totalMDOD'];
//	            $pieceMeal = $this->getPiecemeal('summary', $prjKode, $sitKode);
//	            $totalPieceMeal = $pieceMeal;
//	            $RPI = $this->getRpid('summary', $prjKode, $sitKode,'service');
//	            $totalRPI = $RPI['totalRPI'];
//	            $PODsite = $this->getPod('summary', $prjKode, $sitKode,'Y'); //statussite = Y
//	       		$totalPOsite =  $PODsite['totalPOD'];
//	            $result[$i]['boq4_current'] = $totalASF + $totalMDO + $totalPieceMeal + $totalRPI + $totalPOsite;
//            }
//            else
//            {
//            	//Start for BoQ3
//	            //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
//				$boq3_current =  $this->getBoq3('summary-current', $prjKode, $sitKode);
//	            $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
//	            $result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
//	            $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
//	            $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
//	            $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
//	            $boq3 = $this->getBoq3('summary-ori', $prjKode, $sitKode);
//	            $result[$i]['boq3_ori'] = $boq3['grandTotal'];
//	            $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
//	            $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
//	            $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
//	            $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];
//
//	            //Start for BoQ2
//	            //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
//
//	            $boq2 = $this->getBoq2('summary-ori', $prjKode, $sitKode);
//	            $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
//	            $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
//	            $result[$i]['boq2_oriUSD'] = $boq2['totalOriginUSD'];
//	            $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
//	            $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
//	            $boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode);
//	            $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
//	            $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
//	            $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
//	            $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
//	            $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];
//
//	            //Start for MIP
//	            //Formula : MIP = (total PO + total ARF) - total ASFddCancel
//				$POD = $this->getPod('summary', $prjKode, $sitKode);
//	            $totalPO =  $POD['totalPOD'];
//	            $totalPOIDR = $POD['totalIDR'];
//	            $totalPOUSD = $POD['totalUSD'];
//	            $totalPOHargaIDR = $POD['totalHargaIDR'];
//	            $totalPOHargaUSD = $POD['totalHargaUSD'];
//	            $ARF = $this->getArfd('summary', $prjKode, $sitKode);
//	            $totalARF = $ARF['totalARF'];
//	            $totalARFIDR = $ARF['totalIDR'];
//	            $totalARFUSD = $ARF['totalUSD'];
//	            $totalARFHargaIDR = $ARF['totalHargaIDR'];
//	            $totalARFHargaUSD = $ARF['totalHargaUSD'];
//	            $ASFddCancel = $this->getAsfddCancel('summary', $prjKode, $sitKode);
//	            $totalASFddCancel = $ASFddCancel['totalAsfddCancel'];
//	            $totalASFddCancelIDR = $ASFddCancel['totalIDR'];
//	            $totalASFddCancelUSD = $ASFddCancel['totalUSD'];
//	            $totalASFddCancelHargaIDR = $ASFddCancel['totalHargaIDR'];
//	            $totalASFddCancelHargaUSD = $ASFddCancel['totalHargaUSD'];
//	            $totalMIP = $totalARF + $totalPO - $totalASFddCancel;
//	            $result[$i]['mip_current'] = $totalMIP;
//	            $result[$i]['mip_currentIDR'] = $totalPOIDR + $totalARFIDR;
//	            $result[$i]['mip_currentUSD'] = $totalPOUSD + $totalARFUSD;
//	            $result[$i]['mip_currentHargaIDR'] = $totalPOHargaIDR + $totalARFHargaIDR;
//	            $result[$i]['mip_currentHargaUSD'] = $totalPOHargaUSD + $totalARFHargaUSD;
//
//	            //Start for Boq4
//	            //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)
//
//	            $ASF = $this->getAsfdd('summary', $prjKode, $sitKode);
//	            $totalASF = $ASF['totalASFDD'];
//	            $totalASFIDR = $ASF['totalIDR'];
//	            $totalASFUSD = $ASF['totalUSD'];
//	            $totalASFHargaIDR = $ASF['totalHargaIDR'];
//	            $totalASFHargaUSD = $ASF['totalHargaUSD'];
//	            $MDO =  $this->getMdod('summary', $prjKode, $sitKode);
//	            $totalMDO = $MDO['totalMDOD'];
//	            $totalMDOIDR = $MDO['totalIDR'];
//	            $totalMDOUSD = $MDO['totalUSD'];
//	            $totalMDOHargaIDR = $MDO['totalHargaIDR'];
//	            $totalMDOHargaUSD = $MDO['totalHargaUSD'];
//	            $pieceMeal = $this->getPiecemeal('summary', $prjKode, $sitKode);
//	            $totalPieceMeal = $pieceMeal;
//	            $RPI = $this->getRpid('summary', $prjKode, $sitKode,'service');
//	            $totalRPI = $RPI['totalRPI'];
//	            $totalRPIIDR = $RPI['totalIDR'];
//	            $totalRPIUSD = $RPI['totalUSD'];
//	            $totalRPIHargaIDR = $RPI['totalHargaIDR'];
//	            $totalRPIHargaUSD = $RPI['totalHargaUSD'];
//
//	            $PODsite = $this->getPod('summary', $prjKode, $sitKode,'Y'); //statussite = Y
//		        $totalPOsite =  $PODsite['totalPOD'];
//		        $totalPOIDRsite = $PODsite['totalIDR'];
//		        $totalPOUSDsite = $PODsite['totalUSD'];
//		        $totalPOHargaIDRsite = $PODsite['totalHargaIDR'];
//		        $totalPOHargaUSDsite = $PODsite['totalHargaUSD'];
//
//		        $result[$i]['boq4_current'] = $totalASF + $totalMDO + $totalPieceMeal + $totalRPI + $totalPOsite;// + $totalReimbursement;
//		        $result[$i]['boq4_currentIDR'] = $totalASFIDR + $totalMDOIDR + $totalPieceMeal + $totalRPIIDR + $totalPOIDRsite; //+ $totalReimbursementIDR;
//		        $result[$i]['boq4_currentUSD'] = $totalASFUSD + $totalMDOUSD + $totalRPIUSD + $totalPOUSDsite; //+ $totalReimbursementUSD;
//		        $result[$i]['boq4_currentHargaIDR'] = $totalASFHargaIDR + $totalMDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR + $totalPOHargaIDRsite; //+ $totalReimbursementHargaIDR;
//		        $result[$i]['boq4_currentHargaUSD'] = $totalASFHargaUSD + $totalMDOHargaUSD + $totalRPIHargaUSD + $totalPOHargaUSDsite; //+  $totalReimbursementHargaUSD;
//
//
//	           }
        }
        return $result;
    }

    public function getBudgetProjectCFS($all=false,$prjKode='',$sitKode='',$detail=false,$offset=0,$allSite=false)
    {
    	if (!$allSite)
    		$limit = " LIMIT $offset,20";
        if ($all)
        {
            $master = $this->getBoq3All('all-current-by-cfskode',$prjKode,'',$offset,20);
        }
        else
        {
            $master = $this->getBoq3All('all-current-by-cfskode',$prjKode,$sitKode,$offset,20);
        }
        $result = array();
        for ($i=0;$i<count($master);$i++)
        {
            $sql = "SELECT
                    prj_nama
                FROM master_project
                WHERE prj_kode='$prjKode'";
            $fetch = $this->db->query($sql);
            $masters = $fetch->fetch();

            $cfsKode = $master[$i]['cfs_kode'];
			$prjNama = $masters[$i]['prj_nama'];
//            $tglAw	= $master[$i]['tglaw'];

			$result[$i]['prj_nama'] = $prjNama;
//			$result[$i]['tglaw']= $tglAw;

            //Start for BoQ3
            //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
			$boq3_current =  $this->getBoq3CFS('summary-current', $prjKode, $cfsKode);
            $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
			$result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
            $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
            $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
            $result[$i]['boq3_current1'] = $boq3_current['totalHargaIDR'];
            $result[$i]['boq3_current2'] = $boq3_current['totalHargaUSD'];
         	$boq3 = $this->getBoq3CFS('summary-ori', $prjKode, $cfsKode);
            $result[$i]['boq3_ori'] = $boq3['grandTotal'];
            $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
            $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
            $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
            $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];


            //Start for BoQ2
            //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
            $boq2 = $this->getBoq2('summary-ori', $prjKode, $sitKode);
            $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
            $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
            $result[$i]['boq2_oriUSD'] = $boq2['totalOriginUSD'];
            $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
            $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
            $boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode);
            $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
            $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
            $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
            $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
            $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];

            //Start for Boq4
            //Formula OLD : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)
            //Formula NEW : Boq4 = (total Payment ASF + total MDOd + total Piece Meal + total Payment RPI)

            $ASF = $this->getPaymentAsfdd('summary', $prjKode, $cfsKode);
            $totalASF = $ASF['totalASFDD'];
            $totalASFIDR = $ASF['totalIDR'];
            $totalASFUSD = $ASF['totalUSD'];
            $totalASFHargaIDR = $ASF['totalHargaIDR'];
            $totalASFHargaUSD = $ASF['totalHargaUSD'];
            $DO =  $this->getDoCFS('summary', $prjKode, $cfsKode);
            $totalDO = $DO['totalMDOD'];
            $totalDOIDR = $DO['totalIDR'];
            $totalDOUSD = $DO['totalUSD'];
            $totalDOHargaIDR = $DO['totalHargaIDR'];
            $totalDOHargaUSD = $DO['totalHargaUSD'];
            $pieceMeal = $this->getPiecemealCFS('summary', $prjKode, $cfsKode);
            $totalPieceMeal = $pieceMeal;
            $RPI = $this->getPaymentRpid('summary', $prjKode, $cfsKode);
            $totalRPI = $RPI['totalRPI'];
            $totalRPIIDR = $RPI['totalIDR'];
            $totalRPIUSD = $RPI['totalUSD'];
            $totalRPIHargaIDR = $RPI['totalHargaIDR'];
            $totalRPIHargaUSD = $RPI['totalHargaUSD'];

	        $LeftOver = $this->getLeftOverCFS('summary', $prjKode, $cfsKode);
	        $totalLeftOver = $LeftOver['totalLeftOver'];
	        $totalLeftOverIDR = $LeftOver['totalIDR'];
	        $totalLeftOverUSD = $LeftOver['totalUSD'];
	        $totalLeftOverHargaIDR = $LeftOver['totalHargaIDR'];
	        $totalLeftOverHargaUSD = $LeftOver['totalHargaUSD'];

            $Cancel = $this->getCancelCFS('summary', $prjKode, $cfsKode);
	        $totalCancel = $Cancel['totalCancel'];
	        $totalCancelIDR = $Cancel['totalIDR'];
	        $totalCancelUSD = $Cancel['totalUSD'];
	        $totalCancelHargaIDR = $Cancel['totalHargaIDR'];
	        $totalCancelHargaUSD = $Cancel['totalHargaUSD'];

            $Sal = $this->getSal('summary', $prjKode, $cfsKode);
            $totalSalIDR = $Sal['totalIDR'];
            $totalSal = $Sal['totalIDR'];
            $totalSalHargaIDR = $Sal['totalHargaIDR'];

            $result[$i]['boq4_current'] = $totalASF + $totalDO + $totalPieceMeal + $totalRPI+ $totalSalIDR;
	        $result[$i]['boq4_currentIDR'] = $totalASFIDR + $totalDOIDR + $totalPieceMeal + $totalRPIIDR + $totalSalIDR;
	        $result[$i]['boq4_currentUSD'] = $totalASFUSD + $totalDOUSD + $totalRPIUSD ;
	        $result[$i]['boq4_currentHargaIDR'] = $totalASFHargaIDR + $totalDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR+ $totalSalIDR ;
	        $result[$i]['boq4_currentHargaUSD'] = $totalASFHargaUSD + $totalDOHargaUSD + $totalRPIHargaUSD;

            $result[$i]['return'] = $totalLeftOver + $totalCancel;
            $result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
            $result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
            $result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
            $result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;
    
            $result[$i]['boq4_current'] -= $result[$i]['return'];
            $result[$i]['boq4_currentIDR'] -= $result[$i]['returnIDR'];
            $result[$i]['boq4_currentUSD'] -= $result[$i]['returnUSD'];
            $result[$i]['boq4_currentHargaIDR'] -= $result[$i]['returnHargaIDR'];
            $result[$i]['boq4_currentHargaUSD'] -= $result[$i]['returnHargaUSD'];

        //mencari nilai final
        $pod = $this->getPod('summary',$prjKode, $cfsKode);
        $totalPO = $pod['totalPOD'];
        $totalPOIDR = $pod['totalIDR'];
        $totalPOUSD = $pod['totalUSD'];
        $totalPOHargaIDR = $pod['totalHargaIDR'];
        $totalPOHargaUSD = $pod['totalHargaUSD'];
        $arfd = $this->getArfd('summary',$prjKode, $cfsKode);
        $totalARF = $arfd['totalARF'];
        $totalARFIDR = $arfd['totalIDR'];
        $totalARFUSD = $arfd['totalUSD'];
        $totalARFHargaIDR = $arfd['totalHargaIDR'];
        $totalARFHargaUSD = $arfd['totalHargaUSD'];

        // mencari nilai accrual (PO+ARF - Payment RPI+ASF)
        $result[$i]['accrual_current'] = ($totalPO + $totalARF) - ($totalASF + $totalRPI);
        $result[$i]['accrual_currentIDR'] = ($totalPOIDR + $totalARFIDR) - ($totalASFIDR + $totalRPIIDR);
        $result[$i]['accrual_currentUSD'] = ($totalPOUSD + $totalARFUSD) - ($totalASFUSD + $totalRPIUSD);
        $result[$i]['accrual_currentHargaIDR'] = ($totalPOHargaIDR + $totalARFHargaIDR) - ($totalASFHargaIDR + $totalRPIHargaIDR);
        $result[$i]['accrual_currentHargaUSD'] = ($totalPOHargaUSD + $totalARFHargaUSD) - ($totalASFHargaUSD + $totalRPIHargaUSD);


//	        $result[$i]['return'] = $totalLeftOver + $totalCancel;
//        	$result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
//        	$result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
//        	$result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
//        	$result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;

        	$result[$i]['finalCost'] = $result[$i]['boq2_current'] - $result[$i]['boq3_current'] - $result[$i]['return'];

            $result[$i]['prj_kode'] = $prjKode;
            if ($cfsKode == 'Invalid' )
            {
                $cfsKode = "<b><font color=\"red\">Invalid</font></b>";
                $cfsNama = "<b><font color=\"red\">Invalid CFS Code</font></b>";
            }
            else
                $cfsNama = $master[$i]['cfs_nama'];
            $result[$i]['cfs_kode'] = $cfsKode;
            $result[$i]['cfs_nama'] = $cfsNama;
            $result[$i]['stsoverhead'] = $master[$i]['stsoverhead'];
        }
        return $result;
    }

    public function getBudgetProjectAll($prjKode='',$old=false)
    {
        $i = 0;

        //Start for BoQ3
        //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
        $boq3_current =  $this->getBoq3All('summary-current', $prjKode,'',0,0,$old);
        $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
        $result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
        $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
        $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
        $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
        $result[$i]['boq3_current1'] = $boq3_current['totalHargaIDR'];
        $result[$i]['boq3_current2'] = $boq3_current['totalHargaUSD'];
        $boq3 = $this->getBoq3All('summary-ori', $prjKode,'',0,0,$old);
        $result[$i]['boq3_ori'] = $boq3['grandTotal'];
        $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
        $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
        $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
        $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];


        //Start for BoQ2
        //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
        $boq2 = $this->getBoq2All('summary-ori', $prjKode);
        $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
        $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
        $result[$i]['boq2_oriUSD'] = $boq2['totalOriginUSD'];
        $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
        $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
        $boq2_current = $this->getBoq2All('summary-current', $prjKode);
        $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
        $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
        $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
        $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
        $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];

        //Start for Boq4
        //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)

        $ASF = $this->getAsfdd('summary', $prjKode);
        $totalASF = $ASF['totalASFDD'];
        $totalASFIDR = $ASF['totalIDR'];
        $totalASFUSD = $ASF['totalUSD'];
        $totalASFHargaIDR = $ASF['totalHargaIDR'];
        $totalASFHargaUSD = $ASF['totalHargaUSD'];

//        $ASFCancel = $this->getAsfddCancel('summary', $prjKode);
//        $totalASFCancel = $ASFCancel['totalAsfddCancel'];
//        $totalASFCancelIDR = $ASFCancel['totalIDR'];
//        $totalASFCancelUSD = $ASFCancel['totalUSD'];
//        $totalASFCancelHargaIDR = $ASFCancel['totalHargaIDR'];
//        $totalASFCancelHargaUSD = $ASFCancel['totalHargaUSD'];

        $LeftOver = $this->getLeftOver('summary', $prjKode);
        $totalLeftOver = $LeftOver['totalLeftOver'];
        $totalLeftOverIDR = $LeftOver['totalIDR'];
        $totalLeftOverUSD = $LeftOver['totalUSD'];
        $totalLeftOverHargaIDR = $LeftOver['totalHargaIDR'];
        $totalLeftOverHargaUSD = $LeftOver['totalHargaUSD'];

        $Cancel = $this->getCancel('summary', $prjKode);
        $totalCancel = $Cancel['totalCancel'];
        $totalCancelIDR = $Cancel['totalIDR'];
        $totalCancelUSD = $Cancel['totalUSD'];
        $totalCancelHargaIDR = $Cancel['totalHargaIDR'];
        $totalCancelHargaUSD = $Cancel['totalHargaUSD'];


        $DO = $this->getDo('summary',$prjKode);
        $totalDO = $DO['totalDO'];
        $totalDOIDR = $DO['totalIDR'];
        $totalDOUSD = $DO['totalUSD'];
        $totalDOHargaIDR = $DO['totalHargaIDR'];
        $totalDOHargaUSD = $DO['totalHargaUSD'];

//        $MDO =  $this->getMdod('summary', $prjKode);
//        $totalMDO = $MDO['totalMDOD'];
//        $totalMDOIDR = $MDO['totalIDR'];
//        $totalMDOUSD = $MDO['totalUSD'];
//        $totalMDOHargaIDR = $MDO['totalHargaIDR'];
//        $totalMDOHargaUSD = $MDO['totalHargaUSD'];
        $pieceMeal = $this->getPiecemeal('summary', $prjKode);
        $totalPieceMeal = $pieceMeal;
//        $RPI = $this->getRpid('summary', $prjKode, '','service');
        $RPI = $this->getRpid('summary', $prjKode, '','');
        $totalRPI = $RPI['totalRPI'];
        $totalRPIIDR = $RPI['totalIDR'];
        $totalRPIUSD = $RPI['totalUSD'];
        $totalRPIHargaIDR = $RPI['totalHargaIDR'];
        $totalRPIHargaUSD = $RPI['totalHargaUSD'];

//        $PODsite = $this->getPod('summary', $prjKode, '','Y'); //statussite = Y
//        $totalPOsite =  $PODsite['totalPOD'];
//        $totalPOIDRsite = $PODsite['totalIDR'];
//        $totalPOUSDsite = $PODsite['totalUSD'];
//        $totalPOHargaIDRsite = $PODsite['totalHargaIDR'];
//        $totalPOHargaUSDsite = $PODsite['totalHargaUSD'];

        $Sal = $this->getSal('summary', $prjKode);
        $totalSalIDR = $Sal['totalIDR'];
        $totalSal = $Sal['totalIDR'];
        $totalSalHargaIDR = $Sal['totalHargaIDR'];

        $result[$i]['boq4_current'] = $totalASF + $totalDO + $totalPieceMeal + $totalRPI + $totalSal; // + $totalPOsite;// + $totalReimbursement;
        $result[$i]['boq4_currentIDR'] = $totalASFIDR  + $totalDOIDR + $totalPieceMeal + $totalRPIIDR + $totalSalIDR;// + $totalPOIDRsite; //+ $totalReimbursementIDR;
        $result[$i]['boq4_currentUSD'] = $totalASFUSD  + $totalDOUSD + $totalRPIUSD;;// + $totalPOUSDsite; //+ $totalReimbursementUSD;
        $result[$i]['boq4_currentHargaIDR'] = $totalASFHargaIDR + $totalDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR+ $totalSalIDR;// + $totalPOHargaIDRsite; //+ $totalReimbursementHargaIDR;
        $result[$i]['boq4_currentHargaUSD'] = $totalASFHargaUSD + $totalDOHargaUSD + $totalRPIHargaUSD;// + $totalPOHargaUSDsite; //+  $totalReimbursementHargaUSD;

        $result[$i]['return'] = $totalLeftOver + $totalCancel;
        $result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
        $result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
        $result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
        $result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;

        $result[$i]['boq4_current'] -= $result[$i]['return'];
        $result[$i]['boq4_currentIDR'] -= $result[$i]['returnIDR'];
        $result[$i]['boq4_currentUSD'] -= $result[$i]['returnUSD'];
        $result[$i]['boq4_currentHargaIDR'] -= $result[$i]['returnHargaIDR'];
        $result[$i]['boq4_currentHargaUSD'] -= $result[$i]['returnHargaUSD'];

        $pod = $this->getPod('summary',$prjKode);
        $totalPO = $pod['totalPOD'];
        $totalPOIDR = $pod['totalIDR'];
        $totalPOUSD = $pod['totalUSD'];
        $totalPOHargaIDR = $pod['totalHargaIDR'];
        $totalPOHargaUSD = $pod['totalHargaUSD'];
        $arfd = $this->getArfd('summary',$prjKode);
        $totalARF = $arfd['totalARF'];
        $totalARFIDR = $arfd['totalIDR'];
        $totalARFUSD = $arfd['totalUSD'];
        $totalARFHargaIDR = $arfd['totalHargaIDR'];
        $totalARFHargaUSD = $arfd['totalHargaUSD'];

        // mencari nilai accrual (PO+ARF - Payment RPI+ASF)
        $result[$i]['accrual_current'] = ($totalPO + $totalARF) - ($totalASF + $totalRPI);
        $result[$i]['accrual_currentIDR'] = ($totalPOIDR + $totalARFIDR) - ($totalASFIDR + $totalRPIIDR);
        $result[$i]['accrual_currentUSD'] = ($totalPOUSD + $totalARFUSD) - ($totalASFUSD + $totalRPIUSD);
        $result[$i]['accrual_currentHargaIDR'] = ($totalPOHargaIDR + $totalARFHargaIDR) - ($totalASFHargaIDR + $totalRPIHargaIDR);
        $result[$i]['accrual_currentHargaUSD'] = ($totalPOHargaUSD + $totalARFHargaUSD) - ($totalASFHargaUSD + $totalRPIHargaUSD);

        $result[$i]['finalCost'] = $result[$i]['boq2_current'] - $result[$i]['boq3_current'] - $result[$i]['return'];

        return $result;
    }

    public function getBudgetProjectAllCFS($prjKode='')
    {
        $i = 0;

        //Start for BoQ3
        //Formula : Boq3 = Boq3 detail original (boq3d) update & add with Boq3 detail koreksi (kboq3d)
        $boq3_current =  $this->getBoq3All('summary-current', $prjKode);
        $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
        $result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
        $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
        $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
        $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
        $result[$i]['boq3_current1'] = $boq3_current['totalHargaIDR'];
        $result[$i]['boq3_current2'] = $boq3_current['totalHargaUSD'];
        $boq3 = $this->getBoq3All('summary-ori', $prjKode);
        $result[$i]['boq3_ori'] = $boq3['grandTotal'];
        $result[$i]['boq3_oriIDR'] = $boq3['totalIDR'];
        $result[$i]['boq3_oriUSD'] = $boq3['totalUSD'];
        $result[$i]['boq3_oriHargaIDR'] = $boq3['totalHargaIDR'];
        $result[$i]['boq3_oriHargaUSD'] = $boq3['totalHargaUSD'];


        //Start for BoQ2
        //Formula : Boq2 = Boq2 header original (boq2h) append with Boq2 header koreksi (kboq2h)
        $boq2 = $this->getBoq2All('summary-ori', $prjKode);
        $result[$i]['boq2_ori'] = $boq2['totalOrigin'];
        $result[$i]['boq2_oriIDR'] = $boq2['totalOriginIDR'];
        $result[$i]['boq2_oriUSD'] = $boq2['totalOriginUSD'];
        $result[$i]['boq2_oriHargaIDR'] = $boq2['totalOriginHargaIDR'];
        $result[$i]['boq2_oriHargaUSD'] = $boq2['totalOriginHargaUSD'];
        $boq2_current = $this->getBoq2All('summary-current', $prjKode);
        $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
        $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
        $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
        $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
        $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];

        //Start for Boq4
        //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)

        $ASF = $this->getPaymentAsfdd('summary', $prjKode);
        $totalASF = $ASF['totalASFDD'];
        $totalASFIDR = $ASF['totalIDR'];
        $totalASFUSD = $ASF['totalUSD'];
        $totalASFHargaIDR = $ASF['totalHargaIDR'];
        $totalASFHargaUSD = $ASF['totalHargaUSD'];
//        $MDO =  $this->getMdodCFS('summary', $prjKode);
//        $totalMDO = $MDO['totalMDOD'];
//        $totalMDOIDR = $MDO['totalIDR'];
//        $totalMDOUSD = $MDO['totalUSD'];
//        $totalMDOHargaIDR = $MDO['totalHargaIDR'];
//        $totalMDOHargaUSD = $MDO['totalHargaUSD'];

//        $ASFCancel = $this->getAsfddCancelCFS('summary', $prjKode);
//        $totalASFCancel = $ASFCancel['totalAsfddCancel'];
//        $totalASFCancelIDR = $ASFCancel['totalIDR'];
//        $totalASFCancelUSD = $ASFCancel['totalUSD'];
//        $totalASFCancelHargaIDR = $ASFCancel['totalHargaIDR'];
//        $totalASFCancelHargaUSD = $ASFCancel['totalHargaUSD'];

        $DO = $this->getDoCFS('summary',$prjKode);
        $totalDO = $DO['totalDO'];
        $totalDOIDR = $DO['totalIDR'];
        $totalDOUSD = $DO['totalUSD'];
        $totalDOHargaIDR = $DO['totalHargaIDR'];
        $totalDOHargaUSD = $DO['totalHargaUSD'];

        $pieceMeal = $this->getPiecemeal('summary', $prjKode);
        $totalPieceMeal = $pieceMeal;
        $RPI = $this->getPaymentRpid('summary', $prjKode);
        $totalRPI = $RPI['totalRPI'];
        $totalRPIIDR = $RPI['totalIDR'];
        $totalRPIUSD = $RPI['totalUSD'];
        $totalRPIHargaIDR = $RPI['totalHargaIDR'];
        $totalRPIHargaUSD = $RPI['totalHargaUSD'];

        $LeftOver = $this->getLeftOverCFS('summary', $prjKode);
        $totalLeftOver = $LeftOver['totalLeftOver'];
        $totalLeftOverIDR = $LeftOver['totalIDR'];
        $totalLeftOverUSD = $LeftOver['totalUSD'];
        $totalLeftOverHargaIDR = $LeftOver['totalHargaIDR'];
        $totalLeftOverHargaUSD = $LeftOver['totalHargaUSD'];

        $Cancel = $this->getCancelCFS('summary', $prjKode);
        $totalCancel = $Cancel['totalCancel'];
        $totalCancelIDR = $Cancel['totalIDR'];
        $totalCancelUSD = $Cancel['totalUSD'];
        $totalCancelHargaIDR = $Cancel['totalHargaIDR'];
        $totalCancelHargaUSD = $Cancel['totalHargaUSD'];

        $result[$i]['boq4_current'] = ($totalASF - $totalASFCancel) + $totalDO + $totalPieceMeal + $totalRPI+ $totalSalIDR;// + $totalReimbursement;
        $result[$i]['boq4_currentIDR'] = ($totalASFIDR - $totalASFCancelIDR) + $totalDOIDR + $totalPieceMeal + $totalRPIIDR + $totalSalIDR; //+ $totalReimbursementIDR;
        $result[$i]['boq4_currentUSD'] = ($totalASFUSD - $totalASFCancelUSD) + $totalDOUSD + $totalRPIUSD; //+ $totalReimbursementUSD;
        $result[$i]['boq4_currentHargaIDR'] = ($totalASFHargaIDR - $totalASFCancelHargaIDR) + $totalDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR + $totalSalIDR; //+ $totalReimbursementHargaIDR;
        $result[$i]['boq4_currentHargaUSD'] = ($totalASFHargaUSD - $totalASFCancelHargaUSD) + $totalDOHargaUSD + $totalRPIHargaUSD ; //+  $totalReimbursementHargaUSD;

        $result[$i]['return'] = $totalLeftOver + $totalCancel;
        $result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
        $result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
        $result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
        $result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;

        $result[$i]['boq4_current'] -= $result[$i]['return'];
        $result[$i]['boq4_currentIDR'] -= $result[$i]['returnIDR'];
        $result[$i]['boq4_currentUSD'] -= $result[$i]['returnUSD'];
        $result[$i]['boq4_currentHargaIDR'] -= $result[$i]['returnHargaIDR'];
        $result[$i]['boq4_currentHargaUSD'] -= $result[$i]['returnHargaUSD'];

        return $result;
    }

    public function compareBoq($prjKode='',$sitKode='')
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
                " ORDER BY s.sit_kode ASC";
            $fetch = $this->db->query($sql);
            $master = $fetch->fetchAll();
        $result = array();
        for ($i=0;$i<count($master);$i++)
        {
            $prjKode = $master[$i]['prj_kode'];
            $sitKode = $master[$i]['sit_kode'];

            $result[$i]['stsoverhead'] = $master[$i]['stsoverhead'];

            $result[$i]['prj_kode'] = $prjKode;
            $result[$i]['sit_kode'] = $sitKode;
            $result[$i]['prj_nama'] = $master[$i]['prj_nama'];
            $result[$i]['sit_nama'] = $master[$i]['sit_nama'];

	    	$boq3_current =  $this->getBoq3('summary-current', $prjKode, $sitKode);
	        $result[$i]['boq3_current'] = $boq3_current['grandTotal'];
	        $result[$i]['boq3_currentIDR'] = $boq3_current['totalIDR'];
	        $result[$i]['boq3_currentUSD'] = $boq3_current['totalUSD'];
	        $result[$i]['boq3_currentHargaIDR'] = $boq3_current['totalHargaIDR'];
	        $result[$i]['boq3_currentHargaUSD'] = $boq3_current['totalHargaUSD'];
	    	$boq2_current = $this->getBoq2('summary-current', $prjKode, $sitKode);
	        $result[$i]['boq2_current'] = $boq2_current['totalCurrent'];
	        $result[$i]['boq2_currentIDR'] = $boq2_current['totalCurrentIDR'];
	        $result[$i]['boq2_currentUSD'] = $boq2_current['totalCurrentUSD'];
	        $result[$i]['boq2_currentHargaIDR'] = $boq2_current['totalCurrentHargaIDR'];
	        $result[$i]['boq2_currentHargaUSD'] = $boq2_current['totalCurrentHargaUSD'];
	        $POD = $this->getPod('summary', $prjKode, $sitKode,'N'); //statussite = N
	        $totalPO =  $POD['totalPOD'];
	        $totalPOIDR = $POD['totalIDR'];
	        $totalPOUSD = $POD['totalUSD'];
	        $totalPOHargaIDR = $POD['totalHargaIDR'];
	        $totalPOHargaUSD = $POD['totalHargaUSD'];
	        $ARF = $this->getArfd('summary', $prjKode, $sitKode);
	        $totalARF = $ARF['totalARF'];
	        $totalARFIDR = $ARF['totalIDR'];
	        $totalARFUSD = $ARF['totalUSD'];
	        $totalARFHargaIDR = $ARF['totalHargaIDR'];
	        $totalARFHargaUSD = $ARF['totalHargaUSD'];
	        $ASFddCancel = $this->getAsfddCancel('summary', $prjKode, $sitKode);
            $totalASFddCancel = $ASFddCancel['totalAsfddCancel'];
            $totalASFddCancelIDR = $ASFddCancel['totalIDR'];
            $totalASFddCancelUSD = $ASFddCancel['totalUSD'];
            $totalASFddCancelHargaIDR = $ASFddCancel['totalHargaIDR'];
            $totalASFddCancelHargaUSD = $ASFddCancel['totalHargaUSD'];
            $totalMIP = $totalARF + $totalPO - $totalASFddCancel;
	        $result[$i]['mip_current'] = $totalMIP;
	        $result[$i]['mip_currentIDR'] = $totalPOIDR + $totalARFIDR - $totalASFddCancelIDR;
	        $result[$i]['mip_currentUSD'] = $totalPOUSD + $totalARFUSD - $totalASFddCancelUSD;
	        $result[$i]['mip_currentHargaIDR'] = $totalPOHargaIDR + $totalARFHargaIDR - $totalASFddCancelHargaIDR;
	        $result[$i]['mip_currentHargaUSD'] = $totalPOHargaUSD + $totalARFHargaUSD - $totalASFddCancelHargaUSD;
	        $ASF = $this->getAsfdd('summary', $prjKode, $sitKode);
	        $totalASF = $ASF['totalASFDD'];
	        $totalASFIDR = $ASF['totalIDR'];
	        $totalASFUSD = $ASF['totalUSD'];
	        $totalASFHargaIDR = $ASF['totalHargaIDR'];
	        $totalASFHargaUSD = $ASF['totalHargaUSD'];
	        $MDO =  $this->getMdod('summary', $prjKode, $sitKode);
	        $totalMDO = $MDO['totalMDOD'];
	        $totalMDOIDR = $MDO['totalIDR'];
	        $totalMDOUSD = $MDO['totalUSD'];
	        $totalMDOHargaIDR = $MDO['totalHargaIDR'];
	        $totalMDOHargaUSD = $MDO['totalHargaUSD'];
	        $pieceMeal = $this->getPiecemeal('summary', $prjKode, $sitKode);
	        $totalPieceMeal = $pieceMeal;
	        $RPI = $this->getRpid('summary', $prjKode, $sitKode,'service');
	        $totalRPI = $RPI['totalRPI'];
	        $totalRPIIDR = $RPI['totalIDR'];
	        $totalRPIUSD = $RPI['totalUSD'];
	        $totalRPIHargaIDR = $RPI['totalHargaIDR'];
	        $totalRPIHargaUSD = $RPI['totalHargaUSD'];

	        $LeftOver = $this->getLeftOver('summary', $prjKode, $sitKode);
	        $totalLeftOver = $LeftOver['totalLeftOver'];
	        $totalLeftOverIDR = $LeftOver['totalIDR'];
	        $totalLeftOverUSD = $LeftOver['totalUSD'];
	        $totalLeftOverHargaIDR = $LeftOver['totalHargaIDR'];
	        $totalLeftOverHargaUSD = $LeftOver['totalHargaUSD'];

            $Cancel = $this->getCancel('summary', $prjKode, $sitKode);
	        $totalCancel = $Cancel['totalCancel'];
	        $totalCancelIDR = $Cancel['totalIDR'];
	        $totalCancelUSD = $Cancel['totalUSD'];
	        $totalCancelHargaIDR = $Cancel['totalHargaIDR'];
	        $totalCancelHargaUSD = $Cancel['totalHargaUSD'];

	        $Reimbursement = $this->getReimbursement('summary', $prjKode, $sitKode);
	        $totalReimbursement = $Reimbursement['totalReimburesement'];
	        $totalReimbursementIDR = $Reimbursement['totalIDR'];
	        $totalReimbursementUSD = $Reimbursement['totalUSD'];
	        $totalReimbursementHargaIDR = $Reimbursement['totalHargaIDR'];
	        $totalReimbursementHargaUSD = $Reimbursement['totalHargaUSD'];

	        $PODsite = $this->getPod('summary', $prjKode, $sitKode,'Y'); //statussite = Y
	        $totalPOsite =  $PODsite['totalPOD'];
	        $totalPOIDRsite = $PODsite['totalIDR'];
	        $totalPOUSDsite = $PODsite['totalUSD'];
	        $totalPOHargaIDRsite = $PODsite['totalHargaIDR'];
	        $totalPOHargaUSDsite = $PODsite['totalHargaUSD'];

	        $result[$i]['boq4_current'] = $totalASF + $totalMDO + $totalPieceMeal + $totalRPI + $totalPOsite;// + $totalReimbursement;
	        $result[$i]['boq4_currentIDR'] = $totalASFIDR + $totalMDOIDR + $totalPieceMeal + $totalRPIIDR + $totalPOIDRsite; //+ $totalReimbursementIDR;
	        $result[$i]['boq4_currentUSD'] = $totalASFUSD + $totalMDOUSD + $totalRPIUSD + $totalPOUSDsite; //+ $totalReimbursementUSD;
	        $result[$i]['boq4_currentHargaIDR'] = $totalASFHargaIDR + $totalMDOHargaIDR + $totalPieceMeal + $totalRPIHargaIDR + $totalPOHargaIDRsite; //+ $totalReimbursementHargaIDR;
	        $result[$i]['boq4_currentHargaUSD'] = $totalASFHargaUSD + $totalMDOHargaUSD + $totalRPIHargaUSD + $totalPOHargaUSDsite; //+  $totalReimbursementHargaUSD;

        	$result[$i]['return'] = $totalLeftOver + $totalCancel;
        	$result[$i]['returnIDR'] = $totalLeftOverIDR + $totalCancelIDR;
        	$result[$i]['returnUSD'] = $totalLeftOverUSD + $totalCancelUSD;
        	$result[$i]['returnHargaIDR'] = $totalLeftOverHargaIDR + $totalCancelHargaIDR;
        	$result[$i]['returnHargaUSD'] = $totalLeftOverHargaUSD + $totalCancelHargaUSD;

        	$result[$i]['finalCost'] = $result[$i]['boq2_current'] - $result[$i]['boq4_current'] - $result[$i]['return'];

        	//Bypass for Overhead Site
        	if ($result[$i]['stsoverhead'] == 'Y')
            {
            	$result[$i]['boq2_ori'] = 0;
            	$result[$i]['boq2_current'] = 0;
            	$result[$i]['boq2_oriIDR'] = 0;
            	$result[$i]['boq2_oriUSD'] = 0;
            	$result[$i]['boq2_currentIDR'] = 0;
            	$result[$i]['boq2_currentUSD'] = 0;
            	$result[$i]['boq2_oriHargaIDR'] = 0;
            	$result[$i]['boq2_oriHargaUSD'] = 0;
            	$result[$i]['boq2_currentHargaIDR'] = 0;
            	$result[$i]['boq2_currentHargaUSD'] = 0;
            }


        }
        return $result;
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

    public function getLeftOverCFS($action='summary',$prjKode='',$cfsKode='')
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
                            $cfsKode
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
                        $cfsKode";
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

    public function getCancelCFS($action='summary',$prjKode='',$cfsKode='')
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
                            $cfsKode
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
                            $cfsKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

	public function getReimbursement($action='summary',$prjKode='',$sitKode='')
    {
    	 if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalReimbursement,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM procurement_reimbursementd
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM procurement_reimbursementd
                    WHERE
                            prj_kode = '$prjKode'
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

	public function getAsfddCancel($action='summary',$prjKode='',$sitKode='')
    {
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
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getMIP($prjKode, $sitKode)
    {
            $POD = $this->getPod('summary', $prjKode, $sitKode);
            $totalPO =  $POD['totalPOD'];
            $totalPOIDR = $POD['totalIDR'];
            $totalPOUSD = $POD['totalUSD'];
            $totalPOHargaIDR = $POD['totalHargaIDR'];
            $totalPOHargaUSD = $POD['totalHargaUSD'];
            $ARF = $this->getArfd('summary', $prjKode, $sitKode);
            $totalARF = $ARF['totalARF'];
            $totalARFIDR = $ARF['totalIDR'];
            $totalARFUSD = $ARF['totalUSD'];
            $totalARFHargaIDR = $ARF['totalHargaIDR'];
            $totalARFHargaUSD = $ARF['totalHargaUSD'];
            $totalMIP = $totalARF + $totalPO;
            $result['mip_current'] = $totalMIP;
            $result['mip_currentIDR'] = $totalPOIDR + $totalARFIDR;
            $result['mip_currentUSD'] = $totalPOUSD + $totalARFUSD;
            $result['mip_currentHargaIDR'] = $totalPOHargaIDR + $totalARFHargaIDR;
            $result['mip_currentHargaUSD'] = $totalPOHargaUSD + $totalARFHargaUSD;

        return $result;
    }

    public function transferTempBOQ3($trano)
    {
        $counter = new Default_Models_MasterCounter();
        $BOQ3h = new ProjectManagement_Models_BOQ3h();
        $BOQ3 = new ProjectManagement_Models_BOQ3();
        $sql = "SELECT * FROM transengineer_praboq3d WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        if ($fetch)
        {

            //Ubah tanggal awal & akhir untuk project & site yg di transfer..
            $projectTime = new ProjectManagement_Models_ProjectTime();
            $cek = $projectTime->fetchRow("trano = '$trano'");
            if ($cek)
            {
                $prjKode = $cek['prj_kode'];
                $sitKode = $cek['sit_kode'];
                $update = array(
                    "tglaw" => $cek['start_date'],
                    "tglak" => $cek['end_date']
                );
                $sites = new Default_Models_MasterSite();
                $sites->update($update,"prj_kode = '$prjKode' AND sit_kode = '$sitKode'");
                $projectTime->delete("trano = '$trano'");
            }
            
            $hasil = $fetch->fetchAll();
            $log['praboq3-detail-before'] = $hasil;
            $lastTrans = $counter->getLastTrans('BOQ3');
            $lastTrans['urut'] = $lastTrans['urut'] + 1;
            $newtrano = 'BOQ3-' . $lastTrans['urut'];
            $counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);
            foreach($hasil as $key => $val)
            {
                unset($hasil[$key]['id']);
                $hasil[$key]['trano'] = $newtrano;
                $hasil[$key]['petugas'] = $hasil[$key]['uid'];
                unset($hasil[$key]['uid']);
                unset($hasil[$key]['sit_kodeoh']);
                unset($hasil[$key]['sit_namaoh']);
                $log2['praboq3-detail-after'][] = $hasil[$key];
                $BOQ3->insert($hasil[$key]);
            }

            $sql = "SELECT * FROM projectmanagement_gantt_task WHERE boq_no = '$trano'";
            $fetch2 = $this->db->query($sql);
            $fetch2 = $fetch2->fetch();
            if ($fetch2)
            {
                $sql = "UPDATE projectmanagement_gantt_task SET boq_no = '$newtrano' WHERE boq_no = '$trano'";
                $fetch2 = $this->db->query($sql);
                $sql = "UPDATE projectmanagement_gantt_taskd SET boq_no = '$newtrano' WHERE boq_no = '$trano'";
                $fetch2 = $this->db->query($sql);
                $sql = "UPDATE projectmanagement_gantt_dependency SET boq_no = '$newtrano' WHERE boq_no = '$trano'";
                $fetch2 = $this->db->query($sql);
            }

            $sql = "DELETE FROM transengineer_praboq3d WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);

            $sql = "SELECT * FROM transengineer_praboq3h WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);
            if ($fetch)
            {
                $hasil = $fetch->fetch();
                $log['praboq3-header-before'] = $hasil;
                unset($hasil['id']);
                $hasil['trano'] = $newtrano;
                $hasil['user'] = $hasil['uid'];
                unset($hasil['uid']);
                unset($hasil['tgl_aw']);
                unset($hasil['tgl_ak']);
                $BOQ3h->insert($hasil);
                $log2['praboq3-header-after'] = $hasil;
                $sql = "DELETE FROM transengineer_praboq3h WHERE trano = '$trano'";
                $fetch = $this->db->query($sql);
            }
            $jsonLog = Zend_Json::encode($log);
            $jsonLog2 = Zend_Json::encode($log2);
            $arrayLog = array (
                  "trano" => $trano,
                  "uid" => $this->session->userName,
                  "tgl" => date('Y-m-d H:i:s'),
                  "prj_kode" => $hasil['prj_kode'],
                  "sit_kode" => $hasil['sit_kode'],
                  "action" => "TRANSFER",
                  "data_before" => $jsonLog,
                  "data_after" => $jsonLog2,
                  "ip" => $_SERVER["REMOTE_ADDR"],
                  "computer_name" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
            );
            $this->log->insert($arrayLog);
        }
    }

    public function getPaymentRPI($prjKode='',$sitKode='')
    {
//        $sql = "SELECT SUM(total) FROM finance_payment_rpid"

    }

    public function getBoq3CFS($action='summary',$prjKode='',$cfsKode='')
    {
        $sql = "SELECT COALESCE(NUll,rateidr) as rateidr FROM transengineer_boq3h WHERE prj_kode = '$prjKode' LIMIT 1;";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();
        if ($fetch)
            $rateUSD = $fetch['rateidr'];
        else
            $rateUSD = 0;

        if ($cfsKode == 'Invalid')
            $cfsKode = "null,'','x','X','\"\"'";
        else
            $cfsKode = "'$cfsKode'";
        $sql = "

            DROP TEMPORARY TABLE IF EXISTS boq3_ori ;
            CREATE TEMPORARY TABLE boq3_ori
                SELECT * FROM (
                SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
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
                    a.cfs_kode IN ($cfsKode)
                and
                    a.rev = 'N'
                ORDER BY a.tgl DESC) b GROUP BY b.cfs_kode,b.sit_kode,b.workid,b.kode_brg;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_koreksi ;
            CREATE TEMPORARY TABLE boq3_koreksi
                SELECT * FROM (
                    SELECT b.trano,
                    (IF(b.workid IN (1100,2100,3100,4100,5100,6100),'XX',b.kode_brg)) AS kode_brg,
                    (IF(b.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
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
                        b.cfs_kode IN ($cfsKode)
                    ORDER BY tgl DESC,b.trano DESC,b.urut DESC
                ) a GROUP BY a.cfs_kode,a.sit_kode,a.workid,a.kode_brg;";
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
			        (IF(b.val_kode != a.val_kode,b.val_kode,a.val_kode)) as val_kode,
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
                    (a.cfs_kode = b.cfs_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND b.kode_brg = a.kode_brg);";
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
                    (a.cfs_kode = b.cfs_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND b.kode_brg = a.kode_brg)
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
//                case 'all-current-by-workid':
//
//                    $query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','$action')");
//					$query->execute();
//					$workid = $query->fetchAll();
//					$query->closeCursor();
//					$hasil = array();
//					$query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','all-current')");
//					$query->execute();
//                    $data = $query->fetchAll();
//					$query->closeCursor();
//					for($j=0;$j<count($workid);$j++)
//                    {
//                    	$workid_cari = $workid[$j]['workid'];
//                    	$workid_result = $this->project->getWorkDetail($workid_cari);
//                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
//						$indeks = 0;
//                    	foreach ($data as $key => $key2)
//                    	{
//                    		if ($data[$key]['workid'] == $workid_cari)
//                    		{
//                    			$hasil[$workid_cari][$indeks] = $data[$key];
//                    			$indeks++;
//                    		}
//                    	}
//                    }
//                    return $hasil;
//                break;
//                case 'all-ori-by-workid':
//                     $query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','$action')");
//					$query->execute();
//					$workid = $query->fetchAll();
//					$query->closeCursor();
//					$hasil = array();
//					$query=$this->db->prepare("call procurement_boq3revisi_CFS('$prjKode','$cfsKode','all-ori')");
//					$query->execute();
//                    $data = $query->fetchAll();
//					$query->closeCursor();
//					for($j=0;$j<count($workid);$j++)
//                    {
//                    	$workid_cari = $workid[$j]['workid'];
//                    	$workid_result = $this->project->getWorkDetail($workid_cari);
//                    	$hasil[$workid_cari]['workname'] = $workid_result['workname'];
//						$indeks = 0;
//                    	foreach ($data as $key => $key2)
//                    	{
//                    		if ($data[$key]['workid'] == $workid_cari)
//                    		{
//                    			$hasil[$workid_cari][$indeks] = $data[$key];
//                    			$indeks++;
//                    		}
//                    	}
//                    }
//                    return $hasil;
//                break;
//                case 'all-current-by-workid':
//                	break;
                default:
                    return '';
                break;
            }
    }

    public function getBoq2CFS($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
            $sitKode = " AND sit_kode='$sitKode'";
        if ($action == 'summary-ori' || $action == 'summary-current')
        {

            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_budget ;
            CREATE TEMPORARY TABLE boq2_budget
                SELECT (
                    a.totalOriginIDR+a.totalOriginUSD) as totalOrigin,
                    ((a.totalCurrentIDR+a.totalCurrentUSD)+(a.totalOriginIDR+a.totalOriginUSD)) as totalCurrent,
                    (a.totalOriginIDR) AS totalOriginIDR,
                    (a.totalOriginIDR) AS totalOriginHargaIDR,
                    (a.totalOriginHargaUSD) AS totalOriginHargaUSD,
                    (a.totalOriginUSD) AS totalOriginUSD,
                    (a.totalCurrentIDR + a.totalOriginIDR) AS totalCurrentIDR,
                    (a.totalCurrentUSD + a.totalOriginUSD) AS totalCurrentUSD,
                    (a.totalOriginIDR + a.totalCurrentIDR) AS totalCurrentHargaIDR,
                    (a.totalOriginHargaUSD + a.totalCurrentHargaUSD) AS totalCurrentHargaUSD
                FROM (
                    SELECT
                        total AS totalOriginIDR,
                        (totalusd * rateidr) AS totalOriginUSD,
                        totalusd AS totalOriginHargaUSD,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambah)
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode= a.sit_kode
                                )
                            ,0) AS totalCurrentIDR,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambahusd)*rateidr
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode=a.sit_kode
                                )
                            ,0) AS totalCurrentUSD,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambahusd)
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode=a.sit_kode
                                )
                            ,0) AS totalCurrentHargaUSD
                    FROM
                        transengineer_boq2h a
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode
                    ORDER BY sit_kode ASC
                ) a;
            ";
            $this->db->query($sql);
            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_ori ;
            CREATE TEMPORARY TABLE boq2_ori
                SELECT *
                FROM
                    transengineer_boq2h a
                WHERE
                    prj_kode = '$prjKode'
                    $sitKode
                ORDER BY
                    sit_kode ASC;
            ";
            $this->db->query($sql);
            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_koreksi ;
            CREATE TEMPORARY TABLE boq2_koreksi
                SELECT *
                FROM
                    transengineer_kboq2h a
                WHERE
                    prj_kode = '$prjKode'
                    $sitKode
                ORDER BY
                    sit_kode ASC;
            ";
            $this->db->query($sql);
            $sql = "
            SELECT
                *
            FROM boq2_budget;
            ";
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

    public function getPaymentAsfdd($action='summary',$prjKode='',$cfsKode='')
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
                        FROM finance_settled_detail
                        WHERE
                            prj_kode = '$prjKode'
                            $cfsKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
            $sql = "SELECT *
                    FROM finance_settled_detail
                    WHERE
                            prj_kode = '$prjKode'
                        AND cfs_kode = '$cfsKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
    }

    public function getPaymentRPId($action='summary',$prjKode = '', $cfsKode = '')
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
        if($action == 'summary')
         {
             $sql = "SELECT
                        a.totalIDR+a.totalUSD as totalRPI,
                        a.totalIDR as totalIDR,
                        a.totalUSD as totalUSD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
                    FROM(
                        SELECT
                            SUM(CASE val_kode WHEN 'IDR' THEN (total) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (total*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (total) ElSE 0.00 END) AS totalHargaUSD
                        FROM finance_payment_rpid
                        WHERE
                            prj_kode = '$prjKode'
                            $cfsKode
                        ) a ;";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;

         }
         else
         {
            $sql = "SELECT *
                    FROM finance_payment_rpid
                    WHERE
                            prj_kode = '$prjKode'
                        $cfsKode
                    ";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    }

    public function getProjectCFS($prjKode='')
    {
        $sql = "SELECT
                    cfs_kode,
                    cfs_nama
                FROM transengineer_boq3d
                WHERE prj_kode='$prjKode'
                GROUP BY cfs_kode
                ORDER BY cfs_kode ASC";
        $fetch = $this->db->query($sql);
        $hasil = $fetch->fetchAll();

        return $hasil;

    }

    public function getMdodCFS($action='summary',$prjKode='',$cfsKode='')
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
        if ($action == 'summary')
        {
//            $sql = "SELECT (a.totalIDR+a.totalUSD) as totalMDOD
//                    FROM (
//                    SELECT
//                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*CAST(qty AS SIGNED)) ElSE 0.00 END) AS totalIDR,
//                        SUM(CASE val_kode WHEN 'USD' THEN (harga*CAST(qty AS SIGNED)*rateidr) ElSE 0.00 END) AS totalUSD
//                    FROM procurement_mdod
//                    WHERE
//                            prj_kode = '$prjKode'
//                        AND sit_kode = '$sitKode'
//                        AND asalbarang='WH'
//                    ) a;";
            $sql = "SELECT
            			a.totalIDR,
            			a.totalUSD,
            			(a.totalIDR + a.totalUSD) as totalMDOD,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
            		FROM
            		(
            		SELECT
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_mdod
                    WHERE
                            prj_kode = '$prjKode'
                        $cfsKode
                        AND asalbarang='WH') a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
            $sql = "SELECT *
                    FROM procurement_mdod
                    WHERE
                            prj_kode = '$prjKode'
                    $cfsKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
    }

    public function getBoq3All($action='summary',$prjKode='',$sitKode='', $offset=0,$limit=0,$old=false)
    {
        $sql = "SELECT COALESCE(rateidr,0) as rateidr FROM transengineer_boq3h WHERE prj_kode = '$prjKode' LIMIT 1;";
        $fetch = $this->db->query($sql);
        $fetch = $fetch->fetch();

        if ($fetch)
            $rateUSD = $fetch['rateidr'];
        else
            $rateUSD = 0;

        if ($sitKode != '')
        {
            $sitKode0 = " AND sit_kode = '$sitKode'";
            $sitKode1 = " AND a.sit_kode = '$sitKode'";
            $sitKode2 = " AND b.sit_kode = '$sitKode'";
        }

        if ($limit > 0)
        {
            $limits = " LIMIT $offset,$limit";
        }

        if ($old)
        {
            $where1 = "a.sit_kode,a.workid,a.kode_brg";
            $where2 = "a.sit_kode = b.sit_kode AND a.workid = b.workid AND b.kode_brg = a.kode_brg";
        }
        else
        {
            $where1 = "a.cfs_kode,a.sit_kode,a.workid,a.kode_brg";
            $where2 = "a.cfs_kode = b.cfs_kode AND a.sit_kode = b.sit_kode AND a.workid = b.workid AND b.kode_brg = a.kode_brg";
        }

        $sql = "

            DROP TEMPORARY TABLE IF EXISTS boq3_ori_sort ;
            CREATE TEMPORARY TABLE boq3_ori_sort
                SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
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
                    $sitKode1
                and
                    a.rev = 'N'
                ORDER BY a.tgl DESC;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_ori ;
            CREATE TEMPORARY TABLE boq3_ori
                SELECT * FROM boq3_ori_sort a GROUP BY $where1;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_koreksi_sort ;
            CREATE TEMPORARY TABLE boq3_koreksi_sort
            SELECT b.trano,
                (IF(b.workid IN (1100,2100,3100,4100,5100,6100),'XX',b.kode_brg)) AS kode_brg,
                (IF(b.workid IN (1100,2100,3100,4100,5100,6100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
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
                    $sitKode2
                ORDER BY tgl DESC,b.trano DESC,b.urut DESC;
        ";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_koreksi ;
            CREATE TEMPORARY TABLE boq3_koreksi
                SELECT * FROM boq3_koreksi_sort a GROUP BY $where1;";
        $this->db->query($sql);
        $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq3_revisi;
            CREATE TEMPORARY TABLE boq3_revisi
                SELECT
                    a.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,
			        a.sit_kode,
                    a.cfs_kode,
		    	    a.cfs_nama,
			        a.kode_brg,
                    a.nama_brg,
                    (IF(b.qty IS NOT  NULL,b.qty,a.qty))as qty,
			        (IF(b.val_kode != a.val_kode,b.val_kode,a.val_kode)) as val_kode,
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
                    ($where2);";
        $this->db->query($sql);
        $sql = "
            INSERT INTO boq3_revisi
                SELECT
                    b.workid,
			        (SELECT workname FROM masterengineer_work WHERE  workid = b.workid) as workname,
			        b.sit_kode,
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
                    ($where2)
                WHERE
                    a.qty IS NULL;

        ";
        $this->db->query($sql);
            switch ($action)
            {
                case 'summary-all':
					$sql = "
					    SELECT
                            SUM(totalIDR)+ SUM(totalUSD)as grandTotal,
                            COALESCE(SUM(totalIDR),0) as totalIDR,
                            COALESCE(SUM(totalUSD),0) as totalUSD,
                            COALESCE(SUM(totalHargaIDR),0) as totalHargaIDR,
                            COALESCE(SUM(totalHargaUSD),0) as totalHargaUSD
                        FROM boq3_revisi;";
                    $fetch = $this->db->query($sql);
                    $gTotal['current'] = $fetch->fetch();
                    $sql = "
					    SELECT
                            SUM(totalIDR)+ SUM(totalUSD)as grandTotal,
                            COALESCE(SUM(totalIDR),0) as totalIDR,
                            COALESCE(SUM(totalUSD),0) as totalUSD,
                            COALESCE(SUM(totalHargaIDR),0) as totalHargaIDR,
                            COALESCE(SUM(totalHargaUSD),0) as totalHargaUSD
                        FROM boq3_ori";
                    $fetch = $this->db->query($sql);
                    $gTotal['ori'] = $fetch->fetch();
                    return $gTotal;
                break;
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
                case 'all-ori-by-cfskode':
				    $sql = "
                        SELECT * FROM
				            (SELECT
                                (IF (cfs_kode is null OR cfs_kode = '' OR cfs_kode = '\"\"' OR cfs_kode = 'x' OR cfs_kode = 'X', 'Invalid', cfs_kode)) AS cfs_kode,
                            	(IF (cfs_kode is null OR cfs_kode = '' OR cfs_kode = '\"\"' OR cfs_kode = 'x' OR cfs_kode = 'X', 'Invalid CFS Code', cfs_nama)) AS cfs_nama
                            FROM boq3_ori) a
                        GROUP BY a.cfs_kode $limits;";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetchAll();
                    return $gTotal;
                break;
                case 'all-current-by-cfskode':
				    $sql = "
                        SELECT * FROM
				            (SELECT
                                (IF (cfs_kode is null OR cfs_kode = '' OR cfs_kode = '\"\"' OR cfs_kode = 'x' OR cfs_kode = 'X', 'Invalid', cfs_kode)) AS cfs_kode,
                            	(IF (cfs_kode is null OR cfs_kode = '' OR cfs_kode = '\"\"' OR cfs_kode = 'x' OR cfs_kode = 'X', 'Invalid CFS Code', cfs_nama)) AS cfs_nama
                            FROM boq3_revisi) a
                        GROUP BY a.cfs_kode $limits;";
                    $fetch = $this->db->query($sql);
                    $gTotal = $fetch->fetchAll();
                    return $gTotal;
                break;
                default:
                    return '';
                break;
            }
    }

     public function getBoq2All($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
            $sitKode = " AND sit_kode='$sitKode'";
        if ($action == 'summary-ori' || $action == 'summary-current')
        {
            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_budget ;
            CREATE TEMPORARY TABLE boq2_budget
                SELECT (
                    a.totalOriginIDR+a.totalOriginUSD) as totalOrigin,
            		((a.totalCurrentIDR+a.totalCurrentUSD)+(a.totalOriginIDR+a.totalOriginUSD)) as totalCurrent,
            		(a.totalOriginIDR) AS totalOriginIDR,
            		(a.totalOriginIDR) AS totalOriginHargaIDR,
            		(a.totalOriginHargaUSD) AS totalOriginHargaUSD,
            		(a.totalOriginUSD) AS totalOriginUSD,
            		(a.totalCurrentIDR + a.totalOriginIDR) AS totalCurrentIDR,
            		(a.totalCurrentUSD + a.totalOriginUSD) AS totalCurrentUSD,
            		(a.totalOriginIDR + a.totalCurrentIDR) AS totalCurrentHargaIDR,
            		(a.totalOriginHargaUSD + a.totalCurrentHargaUSD) AS totalCurrentHargaUSD
                FROM (
                    SELECT
                        SUM(total) AS totalOriginIDR,
                        SUM(totalusd * rateidr) AS totalOriginUSD,
                        SUM(totalusd) AS totalOriginHargaUSD,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambah)
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode= a.sit_kode
                                )
                            ,0) AS totalCurrentIDR,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambahusd)*rateidr
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode=a.sit_kode
                                )
                            ,0) AS totalCurrentUSD,
                        COALESCE
                            (
                                (
                                    SELECT
                                        SUM(totaltambahusd)
                                    FROM
                                        transengineer_kboq2h
                                    WHERE
                                        prj_kode = a.prj_kode
                                    AND
                                        sit_kode=a.sit_kode
                                )
                            ,0) AS totalCurrentHargaUSD
                    FROM
                        transengineer_boq2h a
                    WHERE
                        prj_kode = '$prjKode'
                        $sitKode
                    ORDER BY sit_kode ASC
                ) a;
            ";
            $this->db->query($sql);
            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_ori ;
            CREATE TEMPORARY TABLE boq2_ori
                SELECT *
                FROM
                    transengineer_boq2h a
                WHERE
                    prj_kode = '$prjKode'
                    $sitKode
                ORDER BY
                    sit_kode ASC;
            ";
            $this->db->query($sql);
            $sql = "
            DROP TEMPORARY TABLE IF EXISTS boq2_koreksi ;
            CREATE TEMPORARY TABLE boq2_koreksi
                SELECT *
                FROM
                    transengineer_kboq2h a
                WHERE
                    prj_kode = '$prjKode'
                    $sitKode
                ORDER BY
                    sit_kode ASC;
            ";
            $this->db->query($sql);
            if ($sitKode == '')
            {
                $sql = "
                    SELECT
                        SUM(totalOrigin) as totalOrigin,
                        SUM(totalCurrent) as totalCurrent,
                        SUM(totalOriginIDR) AS totalOriginIDR,
                        SUM(totalOriginIDR) AS totalOriginHargaIDR,
                        SUM(totalOriginHargaUSD) AS totalOriginHargaUSD,
                        SUM(totalOriginUSD) AS totalOriginUSD,
                        SUM(totalCurrentIDR) AS totalCurrentIDR,
                        SUM(totalCurrentUSD) AS totalCurrentUSD,
                        SUM(totalCurrentHargaIDR) AS totalCurrentHargaIDR,
                        SUM(totalCurrentHargaUSD) AS totalCurrentHargaUSD
                    FROM boq2_budget;
                ";
                $fetch = $this->db->query($sql);
                $gTotal = $fetch->fetch();
            }
            else
            {
                $sql = "
                    SELECT
                        *
                    FROM boq2_budget;
                ";
                $fetch = $this->db->query($sql);
                $gTotal = $fetch->fetch();
            }
            return $gTotal;

        }
        else
        {
            $sql = "CREATE TEMPORARY TABLE boq2_ori
                    SELECT *
                    FROM
                     transengineer_boq2h a
                    WHERE
                      prj_kode = '$prjKode'
                    ORDER BY
                      sit_kode ASC";
            $fetch = $this->db->query($sql);
            $sql = "CREATE TEMPORARY TABLE boq2_koreksi
                    SELECT *
                    FROM
                     transengineer_kboq2h a
                    WHERE
                      prj_kode = '$prjKode'
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

    public function getPiecemealCFS($action='summary',$prjKode='',$cfsKode='')
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
        if ($action == 'summary')
        {
            $sql = "SELECT
                        SUM(harga_borong * qty) AS totalPieceMeal
                    FROM boq_dboqpasang
                    WHERE
                            prj_kode = '$prjKode'
                    $cfsKode;";
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
                    $cfsKode";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }

    }

    public function getDor($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
            $sitKode = " AND sit_kode = '$sitKode'";
        $sql = "
        DROP TEMPORARY TABLE IF EXISTS dorTemp;
        CREATE TEMPORARY TABLE dorTemp
        SELECT
            trano,
            qty,
            (CASE val_kode WHEN 'IDR' THEN (qty * harga) ELSE 0.00 END)as totalHargaIDR,
            (CASE val_kode WHEN 'USD' THEN (qty * harga) ELSE 0.00 END)as totalHargaUSD
        FROM
            procurement_pointd
        WHERE
            prj_kode = '$prjKode'
            $sitKode;
        ";
        $query=$this->db->prepare($sql);
        $query->execute();
        $query->closeCursor();
        if ($action == 'summary')
        {
            $sql = "SELECT
                        SUM(qty) AS totalQty,
                        SUM(totalHargaIDR) AS totalIDR,
                        SUM(totalHargaUSD) AS totalUSD
                    FROM dorTemp";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
             $sql = "SELECT
                        *
                    FROM dorTemp";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getDo($action='summary',$prjKode='',$sitKode='')
    {
        if ($sitKode != '')
            $sitKode = " AND sit_kode = '$sitKode'";
        $sql = "
        DROP TEMPORARY TABLE IF EXISTS doTemp;
        CREATE TEMPORARY TABLE doTemp
        SELECT
            qty,
            (CASE val_kode WHEN 'IDR' THEN (qty * harga) ELSE 0.00 END)as totalHargaIDR,
            (CASE val_kode WHEN 'USD' THEN (qty * harga) ELSE 0.00 END)as totalHargaUSD
        FROM
            procurement_whod
        WHERE
            prj_kode = '$prjKode'
            $sitKode;
        ";
        $query=$this->db->prepare($sql);
        $query->execute();
        $query->closeCursor();
        if ($action == 'summary')
        {

           $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
           $fetch = $this->db->query($sql);
           $data = $fetch->fetch();

            $rateidr = $data['rateidr'];

            $sql = "SELECT
            			a.totalIDR,
            			a.totalUSD,
            			(a.totalIDR + a.totalUSD) as totalDO,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
            		FROM
            		(
            		SELECT
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*$rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_whod
                    WHERE
                            prj_kode = '$prjKode'
                        $sitKode) a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
             $sql = "SELECT
                        *
                    FROM doTemp";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getDoCFS($action='summary',$prjKode='',$cfsKode='')
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
        $sql = "
        DROP TEMPORARY TABLE IF EXISTS doTemp;
        CREATE TEMPORARY TABLE doTemp
        SELECT
            qty,
            (CASE val_kode WHEN 'IDR' THEN (qty * harga) ELSE 0.00 END)as totalHargaIDR,
            (CASE val_kode WHEN 'USD' THEN (qty * harga) ELSE 0.00 END)as totalHargaUSD
        FROM
            procurement_whod
        WHERE
            prj_kode = '$prjKode'
            $cfsKode;
        ";
        $query=$this->db->prepare($sql);
        $query->execute();
        $query->closeCursor();
        if ($action == 'summary')
        {

           $sql = "SELECT rateidr, DATE_FORMAT(tgl, '%d-%m-%Y %H:%i:%s') as tgl_rate
       			FROM exchange_rate
       			WHERE val_kode='USD'
       			ORDER BY tgl DESC
       			LIMIT 0,1";
           $fetch = $this->db->query($sql);
           $data = $fetch->fetch();

            $rateidr = $data['rateidr'];

            $sql = "SELECT
            			a.totalIDR,
            			a.totalUSD,
            			(a.totalIDR + a.totalUSD) as totalDO,
                        a.totalIDR as totalHargaIDR,
                        a.totalHargaUSD as totalHargaUSD
            		FROM
            		(
            		SELECT
                        SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*$rateidr) ElSE 0.00 END) AS totalUSD,
                        SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                    FROM procurement_whod
                    WHERE
                            prj_kode = '$prjKode'
                        $cfsKode) a";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetch();
            return $gTotal;
        }
        else
        {
             $sql = "SELECT
                        *
                    FROM doTemp";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    public function getPaymentRPIdNonCFS($prjKode = '', $sitKode = '',$workId='',$kodeBrg='')
    {
        if ($workId != '')
    		$query = " AND workid='$workId'";
    	if ($kodeBrg != '' && ($workId != 1100 && $workId != 2100 && $workId != 3100 && $workId != 4100 && $workId != 5100 && $workid != 6100))
    		$query .= " AND kode_brg='$kodeBrg'";
        $sql = "SELECT
    						SUM(qty) AS qty,
    						SUM((harga*qty)) AS totalHargaIDR,
                            SUM(CASE val_kode WHEN 'IDR' THEN (harga*qty) ElSE 0.00 END) AS totalIDR,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty*rateidr) ElSE 0.00 END) AS totalUSD,
                            SUM(CASE val_kode WHEN 'USD' THEN (harga*qty) ElSE 0.00 END) AS totalHargaUSD
                        FROM finance_payment_rpid
                        WHERE
                            prj_kode = '$prjKode'
                            AND sit_kode = '$sitKode'
                            $query";
    	$fetch = $this->db->query($sql);
        $gTotal = $fetch->fetch();
        if ($gTotal['qty'] != '' && ($gTotal['totalIDR'] != '' || $gTotal['totalUSD'] != ''))
    		return $gTotal;
    	else
    		return '';
    }

    public function getSal($action='summary',$prjKode='',$sitKode='',$statussite='', $cfsKode='')
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
                        a.totalIDR as totalIDR,
                        a.totalIDR as totalHargaIDR
                    FROM(
                        SELECT
                            SUM(harga*qty) AS totalIDR
                        FROM procurement_sald
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
                     FROM procurement_sald
                     WHERE
                            prj_kode = '$prjKode'
                        $sitKode
                        $sqlSite";
             $fetch = $this->db->query($sql);
             $gTotal = $fetch->fetchAll();
             return $gTotal;
        }
    }
}