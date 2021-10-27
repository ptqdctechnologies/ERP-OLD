<?php

class Mc_LoginController extends Zend_Controller_Action
{
	private $session;
    private $db;
	
    public function init()
    {
        /* Initialize action controller here */
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');
//        if (isset($this->session->isLogin))
//        {
//            $this->_helper->redirector('index','index','mc');
//        }
    }

    public function indexAction()
    {
        	// action body
        	$this->_helper->viewRenderer->setNoRender();
        
            $feedBack = array(
		    	'success' => false,
		    	'account' => array(
		    		'Require Login'
		    		)
		    );    	
		            
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($feedBack);
            
			$this->getResponse()->setHeader('Content-Type', 'text/javascript');
	        $this->getResponse()->setBody($json);		            
    }
        
    public function submitAction()
    {
        $this->_helper->viewRenderer->setNoRender();
               
    	$username= mysql_escape_string($_POST['username']);
        $password= mysql_escape_string($_POST['password']);
    	
    	if (!isset($this->session->isLogin)){
    		            
            $ip = $_SERVER['REMOTE_ADDR'];
    		$browser = $_SERVER["HTTP_USER_AGENT"];
            
            $sql = "SELECT a.uid,a.id_privilege,a.id AS id_user,b.id AS id_role FROM master_login a LEFT JOIN master_role b ON (a.id = b.id_user) WHERE a.uid='" . $username . "'";
            
            $fetch = $this->db->query($sql);
            $login = $fetch->fetch();
            
            // TODO : Create log entries for failed logins
            
            if ($login['uid'] != ''){

                if ($login['token'] == '')
                {
                    $token = QDC_User_Token::factory()->getRandomToken();
                    $data = array(
                        "token" => $token
                    );
                    $this->db->update('master_login',$data,"id = {$login['id_user']}");
                }
                else
                    $token = $login['token'];

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
                	$loginMessage = 'Mobile Client: Login by ' . $username ;
                	$success = 1; 
                	
                	$displayName = $account['displayname'][0];

                    $feedBack = array(
                        'success' => true,
                        'text' => array(
                            'Welcome ' . $displayName
                        ),
                        'token' => $token
                    );
                	
            	} else {
            		           		            		
            		$errorMessage = $authResult->getMessages();
            		$loginMessage = 'Mobile Client: ' . $errorMessage[3];
            		$success = 0;
            		
                	$feedBack = array(
		                'success' => false,
		                'text' => array(
		                    'Wrong Password'
		                )
		            );            	
            		
            	}
            } else {
            	
                $loginMessage = 'Mobile Client: No such user ' . $username;
                $success = 0;
                
                $feedBack = array(
                	'success' => false, 
                	'text' => array(
                	'Unknown User'
                	)
                );
            
            }	
            
            $userLoginLog = new Default_Models_UserLoginLog();
            $userLoginLog->addUserLoginLog($username,$success,$loginMessage,$ip,$browser);
            
            Zend_Loader::loadClass('Zend_Json');
            $json = Zend_Json::encode($feedBack);
            
			$this->getResponse()->setHeader('Content-Type', 'text/javascript');
	        $this->getResponse()->setBody($json);
        
    	}
	}

    //Login untuk local development, without LDAP
    public function cekAction()
    {

        $this->_helper->viewRenderer->setNoRender();
        $username= mysql_escape_string($_POST['username']);
        $password= mysql_escape_string($_POST['password']);


        $sql = "SELECT a.uid,a.master_login,a.Name,a.id_privilege,a.id AS id_user,b.id AS id_role, a.token FROM master_login a LEFT JOIN master_role b ON (a.id = b.id_user) WHERE a.master_login='" . $username . "' AND a.Pass=MD5('" . $password . "')";

        $fetch = $this->db->query($sql);
        $login = $fetch->fetch();
        if ($login['Name'] != '')
        {
            if ($login['token'] == '')
            {
                $token = QDC_User_Token::factory()->getRandomToken();
                $data = array(
                    "token" => $token
                );
                $this->db->update('master_login',$data,"id = {$login['id_user']}");
            }
            else
                $token = $login['token'];

            $this->session->isLogin = true;
            $this->session->name = $login['Name'];
            $this->session->mail = 'diyah.martinas@qdc.co.id';
            $this->session->idUser = $login['id_user'];
            $this->session->userName = $login['uid'];
            $this->session->privilege = $login['id_privilege'];
            $this->session->role = $login['id_role'];
            $this->session->setExpirationSeconds(3600);

            $loginMessage = 'Mobile Client: Login by ' . $username ;
            $success = 1;

            $displayName = $login['Name'];

            $feedBack = array(
                'success' => true,
                'text' => array(
                    'Welcome ' . $displayName
                ),
                'token' => $token
            );
        }
        else
        {
            $loginMessage = 'Mobile Client: ';
            $success = 0;

            $feedBack = array(
                'success' => false,
                'text' => array(
                    'Wrong Password'
                )
            );
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($feedBack);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }
}

