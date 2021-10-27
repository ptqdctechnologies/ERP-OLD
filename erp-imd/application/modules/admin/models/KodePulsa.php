<?php
class Admin_Models_KodePulsa extends Zend_Db_Table_Abstract
{
    protected $_name = 'kode_pulsa';
    private $db;
    
	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function __name()
    {
        return $this->_name;
    }

    public function isExist($kode_brg='')
    {
        $cek = $this->fetchRow("kode = '$kode_brg'");
        if ($cek)
            return true;

        return false;
    }

}
?>