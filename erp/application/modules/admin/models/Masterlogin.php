<?php
class Admin_Models_Masterlogin extends Zend_Db_Table_Abstract
{
	protected $_name ='master_login';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}

    public function __name()
    {
        return $this->_name;
    }

    public function isExist($uid)
    {
        $cek = $this->fetchRow("uid = '$uid'");
        if ($cek)
            return true;

        return false;
    }

    public function getUserId($uid)
    {
        $cek = $this->fetchRow("uid = '$uid'");
        if ($cek)
        {
            $cek = $cek->toArray();
            return $cek['id'];
        }
        return false;
    }
}
?>