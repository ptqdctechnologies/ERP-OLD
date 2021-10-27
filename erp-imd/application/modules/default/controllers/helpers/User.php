<?php
class Zend_Controller_Action_Helper_User extends
                Zend_Controller_Action_Helper_Abstract
{

    public function init()
    {
        $this->session = new Zend_Session_Namespace('login');
    }
    public function getuserProfile()
    {
        return $this->session->name;
    }
}

?>
