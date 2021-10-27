<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 9/14/11
 * Time: 3:04 PM
 * To change this template use File | Settings | File Templates.
 */

class Extjs4_Models_Dependency extends Zend_Db_Table_Abstract
{
    protected $_name = 'projectmanagement_gantt_dependency';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }
}
