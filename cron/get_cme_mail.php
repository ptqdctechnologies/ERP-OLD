<?php
include_once('config.php');

function extract_emails_from($string){
  preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);
  return $matches[0];
}

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

$savefile = $config->uploadfile->uploadPath . "mail";

$db = Database::dbConnectionInit();
try {
$mail = new Zend_Mail_Storage_Pop3(array('host'     => 'mail.qdc.co.id',
	                                       'user'     => 'cme',
                                           'password' => 'cme.qdc'));
}
catch (Zend_Exception $e) {
	echo $e . "\n";die;
}
echo $mail->countMessages() . " messages found\n";
$indeks = 1;
foreach ($mail as $message) {

	$from = extract_emails_from($message->from);
    $mail_from = $from[0];       
    echo $indeks . ". Processing mail from : " . $mail_from . "\n";
	//Subject parser...
	$prjKode = '';
	$sitKode = '';
	$subject = trim($message->subject);
	if (strpos($subject,"-") !== false && strpos(strtolower($subject),'q') === 0)
	{
		$prj = explode("-",$subject);
		$prjKode = $prj[0];
		$sitKode = $prj[1];
	}
	
    $filter=Zend_Ldap_Filter::equals('objectClass', 'person')
		->addAnd(Zend_Ldap_Filter::equals('mail', $mail_from));
	$tmp = $ldap->searchEntries($filter, 'ou=Users,dc=qdc,dc=co,dc=id',Zend_Ldap::SEARCH_SCOPE_SUB);  

	$account = $tmp[0];
	$uid = $account['uid'][0];
	$now = date("Y-m-d H:i:s");
	$ID = $mail->getUniqueId($indeks);
	$tglEmail = date('d-m-Y H:i:s',strtotime($message->date));
	$tglEmail2 = date('Y-m-d H:i:s',strtotime($message->date));
	$cekMail = $db->query("SELECT * FROM projectmanagement_diary WHERE uid='$uid' AND tgl_sent = '$tglEmail' AND uniqueID = '$ID'");
	if ($cekMail !== false)
	{
		echo "Email from : $mail_from ($tglEmail) exists on database, Skipping...\n";
		$indeks++;
		continue; 
	}
	if (count($tmp) > 0)
	{
		if ($message->countParts() == 0)
		{
			echo "Invalid message... Skipping.\n";
			$indeks++;
			continue;
		} 
		if($message->isMultipart() && $message->countParts() > 1)	
		{
        $part    = $message->getPart(2);
        $type    = $part->getHeaders();
        $type    = $type['content-disposition'];
        $body    = $message->getPart(1);
        $bodyMsg = base64_decode($body->getContent());
		$bodyMsg = str_replace("'","",$bodyMsg);
		$db->query("INSERT INTO projectmanagement_diary(uid,tgl,aktifitas,aktifitas_type,tgl_sent,uniqueID,prj_kode,sit_kode) VALUES ('$uid','$tglEmail2','$bodyMsg','other','$tglEmail','$ID','$prjKode','$sitKode')");

			$last = $db->query("SELECT * FROM projectmanagement_diary WHERE uid='$uid' AND tgl = '$tglEmail2' AND aktifitas_type = 'other' ORDER BY id DESC LIMIT 1");
			$lastID = $last['id'];
            if($type !="inline" && $type != "")
            {
            	$content        = base64_decode($part->getContent());
                $cnt_typ        = explode(";" , $part->contentType);
                $name           = explode("=",$cnt_typ[1]);
                $filename       = $name[1];
                $filename       = str_replace('"',"",$filename);
                $filename       = str_replace("'","",$filename);
                $oriname		= $filename;
                $filename       = trim($filename);
				$filename       = date('d-m-Y_His') . "_" . strtolower(str_replace(" ","_",$filename));
				if (!file_exists($savefile))
					mkdir($savefile); //Buat direktori untuk menyimpan file khusus untuk fetching dari mail {uploadpath}/mail 

				if ($prjKode != '' && $sitKode != '')
				{
					$suffixPath = $prjKode . "/" . $sitKode;
				
					if (substr($suffixPath,-1,1) == "/")
                    $suffixPath = substr($suffixPath,0,-1);
                
		            $dirSplit = explode("/",$suffixPath);
		            $tmpDir = '';
		            $indexFile = "index.html";
		            foreach ($dirSplit as $key => $val)
		            {
		                 $tmpDir .= "/" . $val;   
		                 if (!file_exists($config->uploadfile->uploadPath .  $tmpDir))
		                    mkdir($config->uploadfile->uploadPath . $tmpDir);

		                if (!file_exists($config->uploadfile->uploadPath .  $tmpDir . "/" . $indexFile))
		                {
		                    $indexFileHandle = fopen($config->uploadfile->uploadPath .  $tmpDir . "/" . $indexFile, 'w');
		                    fclose($indexFileHandle);
		                }
		            }
					$savefile = $config->uploadfile->uploadPath . "/" . $suffixPath;
				}
				

                $savefile .= "/";
                $attachment    = $savefile . $filename;
                $fh = fopen($attachment, 'w');
				fwrite($fh, $content);
				fclose($fh);

				$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
				$mime = finfo_file($finfo, $attachment);
				finfo_close($finfo);
				$isImage = false;
				$tglOri = '';
				if (strpos($mime,"image/jpg") !== false || strpos($mime,"image/jpeg") !== false)
				{
					$exif = exif_read_data($attachment);
					if ($exif['DateTimeOriginal'] != '')
					{
						$tglTmp = $exif['DateTimeOriginal'];
						if (substr_count($tglTmp,":") > 3)
						{
							$ary = explode(" ",$tglTmp);
							$ary[0] = str_replace(":","-",$ary[0]);
							$tglTmp = $ary[0] . " " . $ary[1];
						}
						$tglOri = date('d-m-Y H:i:s',strtotime($tglTmp));
					}
				}
				else
				{
					$tglOri = '';
				}
				
				$db->query("INSERT INTO projectmanagement_diary_files(diary_id,savename,uid,tgl,filename,tgl_ori) VALUES ($lastID,'$filename','$uid','$now','$oriname','$tglOri')");
			}
		}
		elseif ($message->countParts() == 1)
		{
			$body    = $message->getPart(1);
        	$bodyMsg = $body->getContent();
			$bodyMsg = str_replace("'","",base64_decode($bodyMsg));
		$db->query("INSERT INTO projectmanagement_diary(uid,tgl,aktifitas,aktifitas_type,tgl_sent,uniqueID,prj_kode,sit_kode) VALUES ('$uid','$tglEmail2','$bodyMsg','other','$tglEmail','$ID','$prjKode','$sitKode')");
		}
    }       
	else
	{
		echo "Invalid Email address for : " . $mail_from . ", Reason : Not found in LDAP\n";
	}
	
	$indeks++;
 
}

$db = Database::dbConnectionClose();
$end = microtime_float();
// Print results.
echo 'Script Execution Time: ' . round($end - $start, 3) . " seconds\n";   
?>
