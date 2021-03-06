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
$dord = $db->query('CREATE TEMPORARY TABLE dord SELECT trano,SUM(COALESCE(qty,0)) AS qty,tgl AS tgl_dor,prj_kode,sit_kode,prj_nama,sit_nama FROM procurement_pointd GROUP BY trano ORDER BY trano');
$dod = $db->query('CREATE TEMPORARY TABLE dod SELECT mdi_no,SUM(COALESCE(qty,0)) AS qty,tgl AS tgl_do FROM procurement_whod GROUP BY mdi_no ORDER BY trano');
$data['posts'] = $db->query('SELECT a.trano,(COALESCE(a.qty,0) - COALESCE(b.qty,0)) AS balance,b.qty AS totalDO,a.qty AS totalDOR, b.tgl_do AS tgl_last_do,a.tgl_dor,a.prj_kode,a.sit_kode,a.prj_nama,a.sit_nama FROM dord a LEFT JOIN dod b ON a.trano = b.mdi_no WHERE (COALESCE(a.qty,0) - COALESCE(b.qty,0) > 0) ORDER BY a.tgl_dor DESC,b.tgl_do DESC');

$i = 1;
foreach ($data['posts'] as $key => $val)
{
	$trano = $val['trano'];
	$data['posts'][$key]['pic'] = '';
	$data['posts'][$key]['id'] = $i;
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
$myFile = EXPORT_FILE_PATH . "/pool_do.json";
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
