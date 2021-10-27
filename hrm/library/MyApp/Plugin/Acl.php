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
            $layout->startMvc($options);
        } else {
            $layout->disableLayout();
        }

        //...

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
    		$cek = $this->checkRequest($request);
    		if ($cek === false)
    		{
	    		$r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
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
}
?>