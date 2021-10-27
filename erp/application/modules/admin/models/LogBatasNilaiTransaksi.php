<?php
class Admin_Models_LogBatasNilaiTransaksi extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_batas_nilai_transaksi';
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
}
?>