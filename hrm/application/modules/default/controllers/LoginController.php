<?php

class LoginController extends Zend_Controller_Action
{

	private $session;
	
    public function init()
    {
        /* Initialize action controller here */
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');
        if (isset($this->session->isLogin))
        {
            $this->_helper->redirector('menu', 'index');
        }
    }

    public function indexAction()
    {
        // action body
         $this->initView();
         
    }

    public function cekAction()
    {
        
        $this->_helper->viewRenderer->setNoRender();
         if (!isset($this->session->isLogin))
         {
            
            $username= mysql_escape_string($_POST['username']);
            $password= mysql_escape_string($_POST['password']);


            $sql = "SELECT a.uid,a.master_login,a.Name,a.id_privilege,a.id AS id_user,b.id AS id_role FROM master_login a LEFT JOIN master_role b ON (a.id = b.id_user) WHERE a.master_login='" . $username . "' AND a.Pass=MD5('" . $password . "')";

            $fetch = $this->db->query($sql);
            $login = $fetch->fetch();
            if ($login['Name'] != '')
            {
                $this->session->isLogin = true;
                $this->session->name = $login['Name'];
                $this->session->mail = 'diyah.martinas@qdc.co.id';
                $this->session->idUser = $login['id_user'];
                $this->session->userName = $login['uid'];
                $this->session->privilege = $login['id_privilege'];
                $this->session->role = $login['id_role'];
                $this->session->setExpirationSeconds(3600);
                
                echo '{success:true, result: { message: \'welcome\'}}';
            }
            else
            {
                echo '{success: false, errors: { reason: \'Login failed. Try again.\' }}';
            }
        }
    }
    
    public function ldapauthAction(){

    	$this->_helper->viewRenderer->setNoRender();
    	
    	if (!isset($this->session->isLogin)){
    		
    		$username= mysql_escape_string($_POST['username']);
            $password= mysql_escape_string($_POST['password']);
            $ip = $_SERVER['REMOTE_ADDR'];
    		$browser = $_SERVER["HTTP_USER_AGENT"];
            
            $sql = "SELECT a.uid,a.id_privilege,a.id AS id_user,b.id AS id_role FROM master_login a LEFT JOIN master_role b ON (a.id = b.id_user) WHERE a.uid='" . $username . "'";
            
            $fetch = $this->db->query($sql);
            $login = $fetch->fetch();
            
            // TODO : Create log entries for failed logins
            
            if ($login['uid'] != ''){

            	$auth = Zend_Auth::getInstance();
            	
            	$config = new Zend_Config_Ini('../application/configs/application.ini',getenv('APPLICATION_ENV'));
				$option = $config->ldap->toArray();

    			$adapter = new Zend_Auth_Adapter_Ldap($option, $username, $password);

    			$authResult = $auth->authenticate($adapter);
    			           	
            	if ($authResult->isValid()){
            		
            		$ldapdir = new Default_Models_Ldap();
            		$account = $ldapdir->getAccount($username);
            		
					$this->session->isLogin = true;
                	$this->session->name = $account['displayname'][0];
                	$this->session->mail = $account['mail'][0];
                	$this->session->idUser = $login['id_user'];
                	$this->session->userName = $login['uid'];
                	$this->session->privilege = $login['id_privilege'];
                	$this->session->role = $login['id_role'];
                	$this->session->setExpirationSeconds(3600);
                	$loginMessage = 'Successfull login by ' . $username ;
                	$success = 1; 
                	                	
                	$feedBack = '{success:true, result: { message: \'Welcome\'}}';
                	
            	} else {
            		           		            		
            		$errorMessage = $authResult->getMessages();
            		$loginMessage = $errorMessage[3];
            		$success = 0;
           		           		
            		$feedBack = '{success:false, errors: { reason: \'Wrong Password\'}}';
            		
            	}
            	            	
            } else {
            	
            	$loginMessage = 'No such user ' . $username ;
            	$success = 0;
            	
            	$feedBack = '{success: false, errors: { reason: \'Unknown User\' }}';
      
            }	
            
            $userLoginLog = new Default_Models_UserLoginLog();
            $userLoginLog->addUserLoginLog($username,$success,$loginMessage,$ip,$browser);
            
            echo $feedBack ;	
    	}
    }
}

