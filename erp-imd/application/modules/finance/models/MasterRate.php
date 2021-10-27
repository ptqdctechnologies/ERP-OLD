<?php

class Finance_Models_MasterRate extends Zend_Db_Table_Abstract
{
    protected $_name = 'finance_exchange_rate';

    protected $db;
    

 
    public function __construct($name='')
    {
	parent::__construct($this->_option);
        $this->_name = $name=='' ? $this->_name :$name;
	$this->db = Zend_Registry::get('db');
        
    }
    
    

    


}?>