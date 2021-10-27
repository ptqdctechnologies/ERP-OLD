<?php

class QDC_Adapter_Autoload
{
    public $modelsFound;

    public function __construct($dirPath='')
    {

        if ($dirPath != '')
        {
            $this->modelsFound = $this->listing($dirPath);
        }
        else
            $this->modelsFound = false;
    }

    public static function factory($params)
    {
        return new self($params);
    }

    private function listing($dirPath='')
    {
        $dir = new DirectoryIterator($dirPath);

        $files = array();
        foreach($dir as $fileInfo) {

            $prefix = substr($fileInfo->__toString(),0,1);
            if($fileInfo->isDot() || $prefix == "." || strtolower($fileInfo->getExtension()) != 'php') {

            // do nothing

            } else {
                $fileName = $fileInfo->__toString();
                $fileName = explode(".",$fileName);
                $files[] = $fileName[0];
            }

        }

        return $files;
    }
}

?>