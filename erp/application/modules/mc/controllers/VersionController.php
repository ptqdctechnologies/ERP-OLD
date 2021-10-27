<?php

class Mc_VersionController extends Zend_Controller_Action
{
    private $DEFAULT, $mobileApps, $path;

    public function init()
    {
        /* Initialize action controller here */
        $this->mobileApps['binary'] = 'mobileERP.apk';
        $this->mobileApps['version'] = 'mobileERP.VERSION';
        $this->path = APPLICATION_PATH . "/../public/apk/";
    }

    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        if (file_exists($this->path . $this->mobileApps['version']))
        {
            if($fh = fopen($this->path . $this->mobileApps['version'],"r")){
                while (!feof($fh)){
                    $data = fgets($fh,9999);
                }
                fclose($fh);

                if ($data)
                {
                    $result = array("version" => $data);
                }
            }
        }

        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($result);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }
}
?>