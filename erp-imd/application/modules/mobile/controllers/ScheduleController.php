<?php

class Mobile_IndexController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    	$this->session = new Zend_Session_Namespace('login');

    }

    

}