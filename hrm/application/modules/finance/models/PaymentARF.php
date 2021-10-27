<?php

class Finance_Models_PaymentARF extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_arf';

    protected $db;
    protected $const;

    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    

}

?>
