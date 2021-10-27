<?php

class Finance_ReimburseController extends Zend_Controller_Action {
    
    private $db;
    private $session;
    
    public function init() {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
    }
    
    public function indexAction() {
        
    }
}

