<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/21/11
 * Time: 10:48 AM
 * To change this template use File | Settings | File Templates.
 */

class Procurement_Models_Procurementasfh extends Zend_Db_Table_Abstract
{
    protected $_name = 'procurement_asfh';

    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function __name()
    {
        return $this->_name;
    }

}
 
