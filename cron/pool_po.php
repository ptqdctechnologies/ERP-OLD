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
$prd = $db->query('CREATE TEMPORARY TABLE pod SELECT trano,SUM(COALESCE(qty,0)) AS qty,SUM(COALESCE((qty * harga),0)) AS total,val_kode,tgl AS tgl_po,prj_kode,sit_kode,prj_nama,sit_nama FROM procurement_pod GROUP BY trano ORDER BY trano');
$prd = $db->query('CREATE TEMPORARY TABLE rpid SELECT po_no,SUM(COALESCE(qty,0)) AS qty,SUM(COALESCE((qty * harga),0)) AS total,val_kode,tgl AS tgl_rpi FROM procurement_rpid GROUP BY po_no ORDER BY trano');
$data['posts'] = $db->query('SELECT a.trano,a.total AS total_po, b.total AS total_rpi,(COALESCE(a.qty,0) - COALESCE(b.qty,0)) AS balance,COALESCE((a.total - b.total),0) AS balance_total, b.tgl_rpi AS tgl_last_rpi, a.val_kode,a.tgl_po,a.prj_kode,a.sit_kode,a.prj_nama,a.sit_nama FROM pod a LEFT JOIN rpid b ON a.trano = b.po_no WHERE (COALESCE(a.qty,0) - COALESCE(b.qty,0) > 0) ORDER BY a.tgl_po DESC,b.tgl_rpi DESC');

$i = 1;
foreach ($data['posts'] as $key => $val)
{
	$trano = $val['trano'];
	$data['posts'][$key]['pic'] = '';
	$data['posts'][$key]['id'] = $i;
	$data['posts'][$key]['balance_total'] = number_format($data['posts'][$key]['balance_total'],2);
	$data['posts'][$key]['total_rpi'] = number_format($data['posts'][$key]['total_rpi'],2);
	$data['posts'][$key]['total_po'] = number_format($data['posts'][$key]['total_po'],2);
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

$data['count'] = count($data['posts']);
$data['time'] = date('d-m-Y H:i:s');
$json = json_encode($data);
$myFile = EXPORT_FILE_PATH . "/pool_po.json";
if (file_exists($myFile))
	unlink($myFile);
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $json);
fclose($fh);

$db = Database::dbConnectionClose();
$end = microtime_float();
// Print results.
echo 'Script Execution Time: ' . round($end - $start, 3) . " seconds\n";   
?>
