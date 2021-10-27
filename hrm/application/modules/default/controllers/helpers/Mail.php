<?php
class Zend_Controller_Action_Helper_Mail extends
                Zend_Controller_Action_Helper_Abstract
{
	
    private $db;
    private $send;
    
    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->send = new Zend_Mail_Transport_Smtp('192.168.1.227');
      	Zend_Mail::setDefaultTransport($this->send);
    }   
    
    function sendMail($from='',$fromName='',$dest='',$destName='',$subject='',$msg='')
    {
    	$mail = new Zend_Mail();
		$mail->setBodyHtml($msg);
		$mail->setFrom($from);
		$mail->addTo($dest, $destName);
		$mail->setSubject($subject);
		$mail->send();
    }
}
?>