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
        return $this->accountLdap['displayname'][0];
    }

}


?>