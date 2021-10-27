<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 5/29/12
 * Time: 1:56 PM
 * To change this template use File | Settings | File Templates.
 */

class Logistic_Models_MasterFixedAsset extends Zend_Db_Table_Abstract
{
	protected $_name ='master_fixed_asset';
	protected $db;


	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}

    public function isExist($token='')
    {
        $cek = $this->fetchRow("token = '$token'");
        if ($cek)
            return true;
        else
            return false;
    }
    public function name()
    {
        return $this->_name;
    }
}