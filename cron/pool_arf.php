<?php
include_once('config.php');

function microtime_float ()
{
    list ($msec, $sec) = explode(' ', microtime());
    $microtime = (float)$msec + (float)$sec;
    return $microtime;
}
// Get starting time.
$start = microtime_float();

include_once('db_class.php');
include_once('zend.php');

$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',SERVER_ENV);
$options = $config->ldap->ldapvm->toArray();
$ldapdir = new Zend_Ldap($options);
$ldap = $ldapdir->bind();

$db = Database::dbConnectionInit();
$arfd = $db->query('CREATE TEMPORARY TABLE arfd SELECT trano,SUM(COALESCE(qty,0)) AS qty,SUM(COALESCE((qty * harga),0)) AS total,val_kode,tgl AS tgl_arf,prj_kode,sit_kode,prj_nama,sit_nama, workid, workname, kode_brg, nama_brg FROM procurement_arfd GROUP BY trano ORDER BY trano');
$arfd = $db->query('CREATE TEMPORARY TABLE asfdd SELECT arf_no,SUM(COALESCE(qty,0)) AS qty,SUM(COALESCE((qty * harga),0)) AS total,val_kode,tgl AS tgl_asf FROM procurement_asfdd GROUP BY arf_no ORDER BY trano');
$arfd = $db->query('CREATE TEMPORARY TABLE asfddcancel SELECT arf_no,SUM(COALESCE(qty,0)) AS qty,SUM(COALESCE((qty * harga),0)) AS total,val_kode,tgl AS tgl_asf_cancel FROM procurement_asfddcancel GROUP BY arf_no ORDER BY trano');


$a = $db->query("CREATE TEMPORARY TABLE ab SELECT a.trano,b.arf_no AS arfnob,a.total AS totalA, b.total AS totalB, a.val_kode AS val_kode,b.tgl_asf AS tgl_asf, a.prj_kode AS prj_kode, a.sit_kode AS sit_kode, a.prj_nama AS prj_nama,a.sit_nama AS sit_nama, a.workid AS workid, a.workname AS workname, a.kode_brg AS kode_brg, a.nama_brg AS nama_brg FROM arfd a INNER JOIN asfdd b ON a.trano = b.arf_no");

$b = $db->query("CREATE TEMPORARY TABLE ac SELECT a.trano,c.arf_no AS arfnoc,a.total AS totalA, c.total AS totalC, a.val_kode AS val_kode,c.tgl_asf_cancel AS tgl_asf_cancel, a.prj_kode AS prj_kode, a.sit_kode AS sit_kode, a.prj_nama AS prj_nama,a.sit_nama AS sit_nama, a.workid AS workid, a.workname AS workname, a.kode_brg AS kode_brg, a.nama_brg AS nama_brg FROM arfd a INNER JOIN asfddcancel c ON a.trano = c.arf_no");

$b = $db->query("CREATE TEMPORARY TABLE abc SELECT a.arfnob AS trano ,a.totalA AS totalA, a.totalB AS totalB, COALESCE(b.totalC,0) AS totalC, a.val_kode, a.tgl_asf AS tgl_last_asf, a.prj_kode,a.sit_kode,a.prj_nama,a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg FROM ab a LEFT JOIN ac b ON a.trano = b.arfnoc");

$b = $db->query("INSERT INTO abc SELECT b.arfnoc AS trano, COALESCE(a.totalB,0) AS totalB, b.totalA AS totalA, b.totalC AS totalC, b.val_kode, a.tgl_asf, b.prj_kode, b.sit_kode, b.prj_nama, b.sit_nama, b.workid, b.workname, b.kode_brg, b.nama_brg FROM ab a RIGHT JOIN ac b ON a.trano = b.arfnoc WHERE a.trano IS NULL ");

$data['posts'] = $db->query('SELECT a.trano, (a.totalA -(a.totalB + a.totalC)) AS total, a.val_kode, a.tgl_last_asf, a.prj_kode, a.sit_kode, a.prj_nama, a.sit_nama, a.workid, a.workname, a.kode_brg, a.nama_brg, (SELECT request FROM procurement_arfh WHERE trano = a.trano) AS request FROM abc a where (a.totalA -(a.totalB + a.totalC)) > 0 ORDER BY a.tgl_last_asf DESC');


$i = 1;
if ($data['posts'])
{
foreach ($data['posts'] as $key => $val)
{
	$trano = $val['trano'];
	$data['posts'][$key]['pic'] = '';
	$data['posts'][$key]['id'] = $i;
	$data['posts'][$key]['total'] = number_format($data['posts'][$key]['total'],2);
	
	$i++;
	$docs = $db->query("SELECT * FROM workflow_trans WHERE item_id='$trano' AND approve = 400");
	if ($docs != false)
	{ 
		$workflow_id = $docs['workflow_id'];
		$workflow_trans_id = $docs['workflow_trans_id'];
		$pic = $db->query("SELECT 
					wt.*,ml.uid
				FROM
					workflow_trans wt
				LEFT JOIN
				    workflow w
				ON
				    wt.workflow_id = w.workflow_id
				LEFT JOIN
					master_role mr
				ON
				    mr.id = w.master_role_id
				LEFT JOIN
				    master_role_type mrt
				ON
				    mr.id_role = mrt.id
				LEFT JOIN
					master_login ml
				ON
				    mr.id_user = ml.id
				WHERE
					wt.item_id = '$trano'
				AND 
					w.workflow_id = $workflow_id
				AND
					wt.workflow_trans_id = $workflow_trans_id");
		if ($pic['uid'] != '')
		{
			$account = $ldapdir->getEntry('uid=' . $pic['uid'] . ',ou=users,dc=qdc,dc=co,dc=id');
			$data['posts'][$key]['pic'] = $account['displayname'][0];
		}
	}

}
}
$data['count'] = count($data['posts']);
$data['time'] = date('d-m-Y H:i:s');
$json = json_encode($data);
$myFile = EXPORT_FILE_PATH . "/pool_arf.json";
if (file_exists($myFile))
	unlink($myFile);
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $json);
fclose($fh);

$db = Database::dbConnectionClose();
$end = microtime_float();
// Print results.
echo 'Script Execution Time: ' . round($end - $start, 3) . ' seconds';   
?>
