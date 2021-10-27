<?php

class Logistic_Models_FixedAssetStatus extends Zend_Db_Table_Abstract
{
	protected $_name ='logistic_fixed_asset_status';
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

    public function getAssetStatus($token='',$limit='')
    {
        $select = $this->db->select()
            ->from(array($this->_name))
            ->where("token = ?", $token)
            ->order(array("tgl DESC"));

        if ($limit)
        {
            $select = $select->limit($limit,0);
            if ($limit > 1)
                $data = $this->db->fetchAll($select);
            else
                $data = $this->db->fetchRow($select);
        }
        else
            $data = $this->db->fetchAll($select);

        return $data;
    }
}