<?php
/* 
	Created @ Mar 28, 2010 9:37:35 PM
 */

class Zend_Controller_Action_Helper_Budget extends
                Zend_Controller_Action_Helper_Abstract
{

    private $db;
    private $project;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->project = Zend_Controller_Action_HelperBroker::getStaticHelper('project');
        
    }    
    
    function getBoq3ByOne($prjKode='',$sitKode='',$workid='',$kodeBrg='')
    {
    	
    	if ($workid != 1100 && $workid != 2100 && $workid != 3100 && $workid != 4100 && $workid != 5100)
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
    	$sql = "DROP TEMPORARY TABLE IF EXISTS boq3_ori ;
                CREATE TEMPORARY TABLE boq3_ori
                    SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
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
                        a.rev = 'N' $query;";
    	$this->db->query($sql);
		$sql = 	"DROP TEMPORARY TABLE IF EXISTS boq3_koreksi ;
                 CREATE TEMPORARY TABLE boq3_koreksi SELECT * FROM (
                        SELECT b.trano,
                        (IF(b.workid IN (1100,2100,3100,4100,5100),'XX',b.kode_brg)) AS kode_brg,
                    	(IF(b.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
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
		$sql = "DROP TEMPORARY TABLE IF EXISTS boq3_revisi ;
                CREATE TEMPORARY TABLE boq3_revisi
                    SELECT
                      a.workid,
					 (SELECT workname FROM masterengineer_work WHERE  workid = a.workid) as workname,	
                      a.kode_brg,
                      a.nama_brg,
                      (IF(b.qty IS NOT  NULL,b.qty,a.qty))as qty,
					a.val_kode,
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

    function getBoq3PmealByOne($prjKode='',$sitKode='',$kodeBrg='')
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
                    SELECT a.trano,
                    (IF(a.workid IN (1100,2100,3100,4100,5100),'XX',a.kode_brg)) AS kode_brg,
                    (IF(a.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=a.kode_brg LIMIT 0,1)))as nama_brg,
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
                        a.rev = 'N'";
    	$this->db->query($sql);
		$sql = 	"CREATE TEMPORARY TABLE boq3_koreksi SELECT * FROM (
                        SELECT b.trano,
                        (IF(b.workid IN (1100,2100,3100,4100,5100),'XX',b.kode_brg)) AS kode_brg,
                    	(IF(b.workid IN (1100,2100,3100,4100,5100), 'Others', (SELECT nama_brg FROM master_barang_project_2009 WHERE kode_brg=b.kode_brg LIMIT 0,1)))as nama_brg,
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

    function getBudgetOverhead($prjKode='',$sitKode='',$budgetID='')
    {
        if ($budgetID != '')
        $query .= " AND budgetid ='$budgetID'";

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
                        sit_kode = '$sitKode' $query
                   " ;

        $fetch  = $this->db->query($sql);
    	$gTotal = $fetch->fetchAll();
    	return $gTotal;
    }
    
    function getBoq3($action='summary',$prjKode='',$sitKode='')
    {
            switch ($action)
            {
                case 'summary-current':
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$gTotal = $query->fetch();
					$query->closeCursor();
                    return $gTotal;
                break;
                case 'summary-ori':
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$gTotal = $query->fetch();
					$query->closeCursor();
                    return $gTotal;
                break;
                case 'all-ori':
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$gTotal = $query->fetchAll();
					$query->closeCursor();
                    return $gTotal;
                break;
                case 'all-current':
					$query=$this->db->prepare("call procurement_boq3revisi('$prjKode','$sitKode','$action')");
					$query->execute();
					$gTotal = $query->fetchAll();
					$query->closeCursor();
                    return $gTotal;
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

    function getBoq2($action='summary',$prjKode='',$sitKode='')
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

    function getPod($action='summary',$prjKode='',$sitKode='',$statussite='')
    {
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
                            AND sit_kode = '$sitKode'
                            $sqlSite
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
                        AND 
                        	sit_kode = '$sitKode'
                        $sqlSite";
             $fetch = $this->db->query($sql);
             $gTotal = $fetch->fetchAll();
             return $gTotal;
        }
    }

    function getArfd($action='summary',$prjKode='',$sitKode='')
    {
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
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    function getAsfdd($action='summary',$prjKode='',$sitKode='')
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
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;

        }
    }

    function getMdod($action='summary',$prjKode='',$sitKode='')
    {
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
                        AND sit_kode = '$sitKode'                        
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
                        AND sit_kode = '$sitKode'";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
            
        }
    }

    function getPiecemeal($action='summary',$prjKode='',$sitKode='')
    {
        if ($action == 'summary')
        {
            $sql = "SELECT
                        SUM(harga_borong * qty) AS totalPieceMeal
                    FROM boq_dboqpasang
                    WHERE
                            prj_kode = '$prjKode'
                        AND sit_kode = '$sitKode';";
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
                        AND sit_kode = '$sitKode';";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
        }
    }

    function getRpid($action='summary',$prjKode='',$sitKode='',$status)
    {
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
                            AND sit_kode = '$sitKode'
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
                        AND sit_kode = '$sitKode'
                        $SQLsite";
            $fetch = $this->db->query($sql);
            $gTotal = $fetch->fetchAll();
            return $gTotal;
         }
    } 
    

    function getBudgetProject($all=false,$prjKode='',$sitKode='',$detail=false,$offset=0,$allSite=false)
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
            $result[$i]['mip_current'] = $totalMIP;
            $result[$i]['mip_currentIDR'] = $totalPOIDR + $totalARFIDR;
            $result[$i]['mip_currentUSD'] = $totalPOUSD + $totalARFUSD;
            $result[$i]['mip_currentHargaIDR'] = $totalPOHargaIDR + $totalARFHargaIDR;
            $result[$i]['mip_currentHargaUSD'] = $totalPOHargaUSD + $totalARFHargaUSD;
            
            //Start for Boq4
            //Formula : Boq4 = (total ASFdd + total MDOd + total Piece Meal + total RPId)

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
            
            $PODsite = $this->getPod('summary', $prjKode, $sitKode,'Y'); //statussite = Y
	        $totalPOsite =  $PODsite['totalPOD'];
	        $totalPOIDRsite = $PODsite['totalIDR'];
	        $totalPOUSDsite = $PODsite['totalUSD'];
	        $totalPOHargaIDRsite = $PODsite['totalHargaIDR'];
	        $totalPOHargaUSDsite = $PODsite['totalHargaUSD'];
            
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
    
    function compareBoq($prjKode='',$sitKode='')
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
    
    function getLeftOver($action='summary',$prjKode='',$sitKode='')
    {
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
                            AND sit_kode = '$sitKode'
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
    
	function getCancel($action='summary',$prjKode='',$sitKode='')
    {
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
                            AND sit_kode = '$sitKode'
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
    
	function getReimbursement($action='summary',$prjKode='',$sitKode='')
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
    
	function getAsfddCancel($action='summary',$prjKode='',$sitKode='')
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

    function getMIP($prjKode, $sitKode)
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

    function transferTempBOQ3($trano)
    {
        $counter = new Default_Models_MasterCounter();
        $BOQ3h = new ProjectManagement_Models_BOQ3h();
        $BOQ3 = new ProjectManagement_Models_BOQ3();
        $sql = "SELECT * FROM transengineer_praboq3d WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        if ($fetch)
        {
            $hasil = $fetch->fetchAll();

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
                $BOQ3->insert($hasil[$key]);
            }
            $sql = "DELETE FROM transengineer_praboq3d WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);

            $sql = "SELECT * FROM transengineer_praboq3h WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);
            if ($fetch)
            {
                $hasil = $fetch->fetch();
                unset($hasil['id']);
                $hasil['trano'] = $newtrano;
                $hasil['user'] = $hasil['uid'];
                unset($hasil['uid']);
                $BOQ3h->insert($hasil);
                $sql = "DELETE FROM transengineer_praboq3h WHERE trano = '$trano'";
                $fetch = $this->db->query($sql);
            }
        }
    }

    function transferTempOHP($trano)
    {
        $counter = new Default_Models_MasterCounter();
        $OHP = new HumanResource_Models_OHP();
        $sql = "SELECT * FROM procurement_praohpd WHERE trano = '$trano'";
        $fetch = $this->db->query($sql);
        if ($fetch)
        {
            $hasil = $fetch->fetchAll();

            $lastTrans = $counter->getLastTrans('OHP');
            $lastTrans['urut'] = $lastTrans['urut'] + 1;
            $newtrano = 'OHP01-' . $lastTrans['urut'];

            foreach($hasil as $key => $val)
            {
                $hasil[$key]['trano'] = $newtrano;
              
                $OHP->insert($hasil[$key]);
            }
            $counter->update(array("urut" => $lastTrans['urut']),"id=".$lastTrans['id']);
            
            $sql = "DELETE FROM procurement_praohpd WHERE trano = '$trano'";
            $fetch = $this->db->query($sql);

        }
    }

function super_unique($data)
                    {
                      $result = array_map("unserialize", array_unique(array_map("serialize", $data)));

                      foreach ($result as $key => $value)
                      {
                        if ( is_array($value) )
                        {
                          $result[$key] = $this->super_unique($value);
                        }
                      }

                      return $result;
                    }
}

?>
