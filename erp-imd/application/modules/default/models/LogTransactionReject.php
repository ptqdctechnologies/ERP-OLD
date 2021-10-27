<?php

class Default_Models_LogTransactionReject extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_transaction_reject';

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
}
?>