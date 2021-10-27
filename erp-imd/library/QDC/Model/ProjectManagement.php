<?php

class QDC_Model_ProjectManagement
{
    public $db;
    private $MODULE_NAME;
    private $DIR_PATH;

    /**
     *
     */
    public function __construct($required=array())
    {
        $this->MODULE_NAME = "ProjectManagement_Models_";
        $this->DIR_PATH = APPLICATION_PATH . "/modules/projectmanagement/models";

        if (count($required) == 0)
        {
            $required = QDC_Adapter_Autoload::factory($this->DIR_PATH)->modelsFound;
        }

        $this->getModels($required);

        $this->db = QDC_Adapter_Main::init()->dbInstance;
    }

    public static function init($requiredArray = array())
    {

        return new self($requiredArray);
    }

    private function getModels($required)
    {
        foreach($required as $k => $v)
        {
            $className = $this->MODULE_NAME  . $v;
            $this->{$v} = new $className;
        }
    }
}

?>