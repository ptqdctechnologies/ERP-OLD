<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 5/23/12
 * Time: 11:45 AM
 * To change this template use File | Settings | File Templates.
 */

class Logistic_Models_MasterGudang extends Zend_Db_Table_Abstract
{
	protected $_name ='master_gudang';
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

    public function getName($gdg_kode='')
    {
        $a = $this->fetchRow("gdg_kode = '$gdg_kode'");
        if ($a)
        {
            $a = $a->toArray();
            return $a['gdg_nama'];
        }

        return false;
    }
    public function isTemporary($gdg_kode='')
    {
        $a = $this->fetchRow("gdg_kode = '$gdg_kode'");
        if ($a)
        {
            $a = $a->toArray();
            return $a['sts_temporary'];
        }

        return false;
    }
}