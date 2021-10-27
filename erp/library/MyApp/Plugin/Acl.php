<?php
class MyApp_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{

    protected $_privilege = null;
    protected $_acl = null;
    protected $_session = null;

    public function __construct(Zend_Acl $acl)
    {		
    	$this->session = new Zend_Session_Namespace('login');
//        $this->_privilege = $this->session->privilege;
        $this->_acl = $acl;
    }

    private function checkRequest($request)
    {
    	$found = false;
    	$bypass = Zend_Registry::get('bypass');
    	
    	$moduleName = $request->getModuleName();
    	$controllerName = $request->getControllerName();
    	$actionName = $request->getActionName();
    	
    	if ($moduleName == '')
    		$moduleName = 'default';
    	
    	foreach ($bypass as $key => $val)
    	{
    		if ($val['module'] == $moduleName)
    		{
	    		if ($val['controller'] == $controllerName)
	    		{
		    		if ($val['action'] == $actionName)
		    		{
		    			$found = true;
		    			break;
		    		}
	    		}
    		}
    	}	
    	
    	return $found;
    }
    
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        //Mobile version
        $modName = $request->getModuleName();
        $options = array(
            'layout'     => 'layout',
            'layoutPath' => APPLICATION_PATH . "/modules/$modName/layouts/scripts/",
        );
        $layout = Zend_Layout::startMvc($options);

        // check module and automatically set layout
        if($modName == 'mobile' || $modName == 'extjs4') {
//            $paramsChart = ($request->getParams("use_layout_421") == 'true') ? true : false;
//            if (!$paramsChart)
//            {
//                $options['layout'] = 'layout-421';
//                $layout->startMvc($options);
//            }
//            else
                $layout->startMvc($options);
        } else {
            $layout->disableLayout();
        }

        //...
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        switch ($controller)
        {
            case 'get-app':
                if ($action == 'data')
                {
                    $hash = $request->getParam("hash");
                    $url = QDC_Common_Url::factory(array("url" => $hash))->unhash();
                    echo QDC_Common_Url::factory(array("url" => $url))->getContent();
                    die;
                }
                break;
            case 'cron':
//                $this->bypassSession(10);
                return true;
                break;
            case 'gantt':
//                $this->bypassSession(10);
                return true;
                break;
        }

        $extToken = $request->getParam("external_token");
        if ($extToken)
        {
            $wTransID = QDC_User_Token::factory()->decodeToken($extToken);
            $w = new Admin_Models_Workflowtrans();
            $d = $w->fetchRow("workflow_trans_id = $wTransID");
            if ($d)
            {
                $this->bypassSession();
                return true;
            }
        }

    	if ($this->session->isLogin && !$this->checkRequest($request)) 
    	{
    		//reset the session
    		$sess = new Zend_Session_Namespace('login');
    		$sess->isLogin = $this->session->isLogin;
            $sess->name = $this->session->name;
            $sess->idUser = $this->session->idUser;
            $sess->userName = $this->session->userName;
            $sess->privilege = $this->session->privilege;
            $sess->role = $this->session->role;
            $sess->setExpirationSeconds(7200);
    		
	        if ($this->_privilege == 'public') {
	            $role = 'public';
	        } elseif ($this->_privilege == 'user') {
	            $role = 'user';
	        } else {
	            $role = 'public';
	        }

	        $resource = $request->module;
	        if (!$this->_acl->has($resource)) {
	            $resource = null;
	        }
			//disable ACL first...
			return;
	        // ACL Access Check
	        if (!$this->_acl->isAllowed($role, $resource)) {
	            if ($this->_privilege) {
	                // authenticated, denied access, forward to index
	                $request->setModuleName('default');
	                $request->setControllerName('index');
	                $request->setActionName('menu');
	
	            } else {
	                // not authenticated, forward to login form
	                $request->setModuleName('default');
	                $request->setControllerName('login');
	                $request->setActionName('index');
	            }
	        }
    	}
    	else 
    	{
    		//Session timeout, forward to login form
            $r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    		$cek = $this->checkRequest($request);
            if ($cek === false)
    		{
                if ($this->checkToken($request->getParam("token")))
                    return true;

//                if ($this->isLocalhost())
//                {
//                    return true;
//                }

//                if ($modName != 'mobile')
//   				    $r->gotoUrl('/login')->redirectAndExist();
//                elseif ($modName == 'mc')
//                {
//   				    $r->gotoUrl('/mc/login')->redirectAndExist();
//                }   				    
//    			else
//                {
//                    $r->gotoUrl('/mobile/login')->redirectAndExist();
//                }
				if ($modName == 'mobile'){
					$r->gotoUrl('/mobile/login')->redirectAndExist();
				}elseif($modName == 'mc'){
                    $r->gotoUrl('/mc/login')->redirectAndExist();
				}else{
					$r->gotoUrl('/login')->redirectAndExist();
				}
	    		
    		}
    		
    	}
    }

    private function checkToken($token)
    {
        $user = QDC_User_Token::factory()->getUserByToken($token);

        if ($user)
        {
            $this->session->isLogin = true;
            $this->session->name = $user['Name'];
            $this->session->idUser = $user['id'];
            $this->session->userName = $user['uid'];
            $this->session->privilege = $user['id_privilege'];
            $this->session->role = $user['id_role'];
            $this->session->setExpirationSeconds(3600);

            return true;
        }

        return false;
    }

    private function isLocalhost()
    {
        return ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == 'localhost') ? true : false;

    }

    private function bypassSession($expire=5)
    {
        $sess = new Zend_Session_Namespace('login');
        $sess->isLogin = true;
        $sess->idUser = 999999;
        $sess->setExpirationSeconds($expire);
    }
}
?>