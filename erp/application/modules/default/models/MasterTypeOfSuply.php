<?php

class Default_Models_MasterTypeOfSuply extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_typeofsuply';

    protected $_primary = 'tos_kode';
	protected $db;

	public function __construct()
    {
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
    }

    public function getTosData($tosKode='')
    {
        $sql = "SELECT * FROM master_typeofsuply WHERE tos_kode='$tosKode'";

        $fetch = $this->db->query($sql);
        $return= $fetch->fetch();

		return $return;
    }


}
?>