<?php

class Finance_Models_PaymentPBOQ3D extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_payment_pmeald';

    protected $db;
    protected $const;

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

?>
