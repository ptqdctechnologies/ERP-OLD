<?php
class Admin_ApprovalController extends Zend_Controller_Action
{
    
    private $db;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
 
    }
    
    public function bypassAction()
    {
        
    }
    
    public function showprocessdocumentAction() {
        $type = $this->getRequest()->getParam("type");
        $userid = $this->getRequest()->getParam("userid");
        if ($type != '') {
            $this->view->isType = true;
            $this->view->type = $type;
        }

        $this->view->userId = $userid;
    }
    
}