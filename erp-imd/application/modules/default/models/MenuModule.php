<?php

class Default_Models_MenuModule extends Zend_Db_Table_Abstract
{
    private $db;

    protected $_name = 'menu_module';

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