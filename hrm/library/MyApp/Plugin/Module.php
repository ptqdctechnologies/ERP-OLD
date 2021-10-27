<?php
class MyApp_Plugin_Module extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $front_controller = Zend_Controller_Front::getInstance();
        $error_handler = $front_controller->getPlugin('Zend_Controller_Plugin_ErrorHandler');
        $error_handler->setErrorHandlerModule($module);
        
//        $app = APPLICATION_PATH; // you should define this in your bootstrap
//$d = DIRECTORY_SEPARATOR;
//$module = $this->_request->getModuleName(); // available after routing
//set_include_path(
//  join(PATH_SEPARATOR,
//    array(
//      "$app{$d}library",
//      "$app{$d}modules{$d}$module{$d}models",
//      get_include_path()
//    )
//  )
//);
		
	}
}
?>
