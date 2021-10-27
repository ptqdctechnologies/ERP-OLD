<?php

class Mc_IndexController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    	$this->session = new Zend_Session_Namespace('login');

    }

    public function indexAction()
    {
        // action body
        $this->_helper->viewRenderer->setNoRender();
    	
        $account[name] = $this->session->name;
        $account[image] = $this->session->userName . '.jpg';
    	        
        $feedBack = array(
		   	'success' => true,
		   	'account' => array(
        			array(
			   		'name' => $account[name],
	           		'photo' => $account[image]
		   			)
		   		)
		);    	
		            
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($feedBack);
            
		$this->getResponse()->setHeader('Content-Type', 'text/javascript');
	    $this->getResponse()->setBody($json);	
    	
    }
}

