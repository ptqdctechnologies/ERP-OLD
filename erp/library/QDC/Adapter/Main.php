<?php

class QDC_Adapter_Main
{

    public $databaseConfig;
    public $dbInstance;

    public function __construct($required=array())
    {
        $this->zendConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'production');
        $this->databaseConfig = $this->zendConfig->resources->db->params;
        $this->dbInstance = Zend_Registry::get('db');
    }

    public static function init($requiredArray=array())
    {
        return new self($requiredArray);
    }


}

?>