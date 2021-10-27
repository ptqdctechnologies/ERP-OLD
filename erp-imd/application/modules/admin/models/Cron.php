<?php
class Admin_Model_Cron extends Zend_Db_Table_Abstract
{

    private $db;
    private $memcache;

    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        
    }
}
 
