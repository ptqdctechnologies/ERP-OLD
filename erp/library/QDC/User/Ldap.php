<?php

Class QDC_User_Ldap
{
    private $accountLdap;

    public function __construct($params='')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $ldap = new Default_Models_Ldap();
        if ($this->uid != '')
            $this->accountLdap = $ldap->getAccount($this->uid);
    }

    /**
     * @static
     * @param $params
     * @return QDC_User_Ldap
     *
     * Method factory dipanggil apabila QDC_USER_LDAP di inisialisasi secara statik
     */
    public static function factory($params=array())
    {
        return new self($params);
    }

    public function getName()
    {
//        return $this->accountLdap['displayname'][0];
        return ($this->accountLdap['cn'][0] != '') ? $this->accountLdap['cn'][0] : $this->uid . " (DELETED ON LDAP)";
    }

    public function getEmail()
    {
        $m = $this->accountLdap['mail'];
        if (is_array($m))
            $m = $this->accountLdap['mail'][0];
        return $m;
    }

    public function isExist()
    {
        return ($this->accountLdap == null) ? false : true;
    }

}


?>