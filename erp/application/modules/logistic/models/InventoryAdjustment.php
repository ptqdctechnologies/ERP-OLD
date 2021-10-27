<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 6/10/11
 * Time: 2:01 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Logistic_Models_InventoryAdjustment extends Zend_Db_Table_Abstract
{
	protected $_name ='inventory_adjustment';
    protected $_primary = 'trano';
    protected $db;
    protected $const;

    public function getPrimaryKey()
    {
        return $this->_primary;
    }


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