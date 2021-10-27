<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 8/18/11
 * Time: 8:58 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_Models_WorkflowTrans extends Zend_Db_Table_Abstract
{
    protected $_name = 'workflow_trans';

    protected $db;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }
}