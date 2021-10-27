<?php

class Finance_Models_PaymentRPI extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_rpi';

    protected $db;
    protected $const;
	
    public function __construct()
    {
        parent::__construct($this->_option);
        $this->db = Zend_Registry::get('db');
        $this->const = Zend_Registry::get('constant');
    }

    public function getName()
    {
        return $this->_name;
    }

}

?>
