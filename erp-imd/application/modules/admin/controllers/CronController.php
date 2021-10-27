<?php
/** */

class CronController extends Zend_Controller_Action
{
    private $db;
    private $memcache;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->memcache = Zend_Registry::get('Memcache');
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $session = new Zend_Session_Namespace('login');

    }

    

}