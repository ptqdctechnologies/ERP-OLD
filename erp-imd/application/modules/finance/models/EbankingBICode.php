<?php

class Finance_Models_EbankingBICode extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_ebanking_bi_code';

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