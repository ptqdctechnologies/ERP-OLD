<?php
/**
 *
 * @author wisu
 * @version 
 */

class Default_Models_Ldap
{

    private $dummy;
    private $fail;

	public function __construct(){
		$this->config = new Zend_Config_Ini('../application/configs/application.ini',getenv('APPLICATION_ENV'));
		$this->options = $this->config->ldap->ldapvm->toArray();
    	$this->ldapdir = new Zend_Ldap($this->options);
    	try
        {
            $this->ldapdir->bind();
            $this->fail = false;
        }
        catch (Exception $e)
        {
            $this->fail = true;
            $this->dummy['displayname'][0] = 'Dummy User';
        }
    }
    
    public function getAccount($uid){
        if (!$this->fail)
        {
            try
            {
                $account = $this->ldapdir->getEntry('uid=' . $uid . ',ou=users,dc=qdc,dc=co,dc=id');
            }
            catch (Exception $e)
            {
                $this->dummy['displayname'][0] = 'Dummy User';
                $account = $this->dummy;
            }
        }
        else
        {
            $users = new Admin_Model_User();
            $user = $users->fetchRow("uid = '$uid'");
            $account['displayname'][0] = $user['Name'];
        }
        return $account;
    }

    public function getAccountImage($uid){
    	
    	$imageStore = $this->config->staff->imagestore;
		$account = $this->ldapdir->getEntry('uid=' . $uid . ',ou=users,dc=qdc,dc=co,dc=id');
		
		if ($account['jpegphoto'][0] == NULL){
			$accountImage = '/images/staff/nophoto.jpg';
		
		// account image cached and changed only after 3 hours, 20 minutes (12000 Unix Epoch time) 
		}elseif (file_exists($imageStore . $uid . '.jpg') AND (time(now) - filemtime($imageStore . $uid . '.jpg') < 12000)) {
			$accountImage = '/images/staff/'. $uid . '.jpg';

		// get account image from LDAP Directory	
		}else{
			$fp = fopen($imageStore . $account['uid'][0] . '.jpg', 'wb');
			fwrite ($fp, $account['jpegphoto'][0]);
			fclose ($fp);
			
			// Resize image to 100 px width (maintain ascpect ratio)
			$newWidth = 100;
			$imageLdap = imagecreatefromjpeg($imageStore . $account['uid'][0] . '.jpg');		
			$width = imagesx($imageLdap);
			$height = imagesy($imageLdap);
			$ratio = $width/$newWidth ;
			$newHeight = $height/$ratio ;
			$image = imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($image,$imageLdap,0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			imagejpeg($image,$imageStore . $uid . '.jpg');
			
			$accountImage = '/images/staff/'. $uid . '.jpg';
		}
		return $accountImage;    	
    }
    
}


