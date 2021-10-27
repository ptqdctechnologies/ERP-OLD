<?php

class Mobile_IndexController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    	$this->session = new Zend_Session_Namespace('login');

    }

    public function indexAction()
    {
        // action body
        $account[name] = $this->session->name;
        $account[image] = $this->session->userName . '.jpg';
        
    	$this->view->account = $account;
    }
}

