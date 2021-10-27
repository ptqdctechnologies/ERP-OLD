<?php
class ProjectManagement_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected function _initAppAutoload()
	{
	    $autoloader = new Zend_Application_Module_Autoloader(array(
		'namespace' => 'ProjectManagement',
		'basePath' => dirname(__FILE__),
	    ));
	    return $autoloader;
	}
	
}



?>