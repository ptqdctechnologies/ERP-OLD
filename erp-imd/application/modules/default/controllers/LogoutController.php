<?php

class LogoutController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $this->session = new Zend_Session_Namespace('login');
    }
    public function indexAction()
    {
         $this->_helper->viewRenderer->setNoRender();
         if (isset($this->session->isLogin))
         {
             Zend_Session::destroy();
         }

         //Clear our cookie, Especially for treePanel...
         if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    setcookie($name, '', time()-1000);
                    setcookie($name, '', time()-1000, '/');
                }
        }
             $this->_helper->redirector('index', 'login');
    }
}
?>
