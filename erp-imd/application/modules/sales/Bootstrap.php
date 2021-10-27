<?php
class Sales_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected function _initAppAutoload()
	{
	    $autoloader = new Zend_Application_Module_Autoloader(array(
		'namespace' => 'Sales',
		'basePath' => dirname(__FILE__),
	    ));
	    return $autoloader;
	}
	
}



?>
