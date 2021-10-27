<?php

Class QDC_User_Session
{
    protected $session;

    public function __construct($params='')
    {
        //panggil session login
        $this->session = new Zend_Session_Namespace('login');
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getCurrentUID()
    {
        return $this->session->userName;
    }

    public function getCurrentEmail()
    {
        return $this->session->mail;
    }

    public function getCurrentID()
    {
        return $this->session->idUser;
    }

    public function isAdmin()
    {
        if ($this->session->privilege != 500)
            return false;
        else
            return true;

    }

    public function getCurrentName()
    {
        return QDC_User_Ldap::factory(array("uid" => $this->session->userName))->getName();
    }
}

?>