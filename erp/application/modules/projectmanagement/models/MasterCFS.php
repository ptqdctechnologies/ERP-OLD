<?php

class ProjectManagement_Models_MasterCFS extends Zend_Db_Table_Abstract
{
    protected $_name = 'master_cfs_kode';

    protected $db;
    

 
    public function __construct()
    {
	parent::__construct($this->_option);
	$this->db = Zend_Registry::get('db');
        
    }

    


}?>