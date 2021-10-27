<?php

class IndexController extends Zend_Controller_Action
{

    private $userName;
    private $session;

    public function init()
    {
        /* Initialize action controller here */
        $this->session = new Zend_Session_Namespace('login');

    }

    public function indexAction()
    {
        // action body
         $this->_helper->redirector('menu', 'index');
    }

    
    public function menuAction()
    {
        

    }
}

