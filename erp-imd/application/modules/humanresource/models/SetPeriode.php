<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 7/22/11
 * Time: 1:58 PM
 * To change this template use File | Settings | File Templates.
 */

class HumanResource_Model_SetPeriode extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_setperiode_hrd';
    
    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

}
