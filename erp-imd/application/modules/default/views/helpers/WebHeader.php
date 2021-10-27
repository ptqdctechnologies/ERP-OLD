<?php

class Zend_View_Helper_WebHeader extends Zend_View_Helper_Abstract {

    public function init()
    {
        
        
    }
    public function webHeader()
    {
        $this->session = new Zend_Session_Namespace('login');
        $this->view->userName =  $this->session->name;
        return $this->view->render("webheader.phtml");
    }
}


?>