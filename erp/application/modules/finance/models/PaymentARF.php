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

    public function __name()
    {
        return $this->_name;
    }
    
    public function getPayment($trano){
 
        $query = "SELECT SUM(COALESCE(total_bayar,0)) AS total_payment FROM $this->_name WHERE doc_trano='$trano'";
        $fetch = $this->db->query($query);
        $data = $fetch->fetch();
        return $data['total_payment'];     
    }

    

}

?>