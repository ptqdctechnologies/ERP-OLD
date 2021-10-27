<?php

/**
 * Default_Models_UserLoginLog
 *  
 * @author wisu
 * @version 
 */


class Default_Models_UserLoginLog extends Zend_Db_Table_Abstract
{
    protected $_name = 'user_login_log';
    
    /**
     * addUserLoginLog
     * 
     * @param $uid -> user login name
     * @param $success -> 1 = success 0 = failed
     * @param $message -> feedback text from login
     * @param $ip
     * @param $browser
     */
    
    public function addUserLoginLog($uid,$success,$message,$ip,$browser){
    	
    	$data = array(
    		'uid' => $uid,
    		'success' => $success,
    		'message' => $message,
    		'ip' => $ip,
    		'date' => date("Y-m-d H:i:s"),
    		'browser' => $browser,
    	);
    	
    	$this->insert($data);
    	
    }

    public function getLastLoginByUid($uid)
    {
        $fetch = $this->fetchRow("uid = '$uid'",array("date DESC"),1,0);
        if ($fetch)
        {
            $date = date("d M Y H:i:s",strtotime($fetch['date']));
        }
        return $date;
    }

}

