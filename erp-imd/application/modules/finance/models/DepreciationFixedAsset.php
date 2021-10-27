<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/2/12
 * Time: 10:39 AM
 * To change this template use File | Settings | File Templates.
 */

class Finance_Models_DepreciationFixedAsset extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_depreciation_fixed_asset';

    protected $_primary = 'id';
    protected $db;

    public function getPrimaryKey ()
    {
        return $this->_primary;
    }

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }
}
?>