<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	public function run()
    {
//	    	$options = array(
//	    	'layout'     => 'layout',
//	    	'layoutPath' => APPLICATION_PATH .'/layouts/scripts/',
//			);
//                var_dump(Zend_Layout::isEnabled());
//                $layout = Zend_Layout::startMvc($options);
//            if (Zend_Layout::isEnabled)
//                $layout->disableLayout();

		Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH .'/modules/default/controllers/helpers');
		Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH .'/modules/procurement/controllers/helpers');
		Zend_Session::start();
		parent::run();
    }
    protected function _initConnection()
    {
        //misc workid..
        $misc = array(
                    1100,2100,3100,4100
                    );
        Zend_Registry::set('misc', $misc);
        
    	//Define our Constants here....
		$const = array(
						'DOCUMENT_SUBMIT' => 100
                        ,'DOCUMENT_RESUBMIT' => 150
						,'DOCUMENT_APPROVE' => 200
						,'DOCUMENT_REJECT' => 300
						,'DOCUMENT_FINAL' => 400
						,'DOCUMENT_EXECUTE' => 500
						);
		Zend_Registry::set('constant', $const);

    	//Define our COA here....
		$const = array(
						'AP_IDR' => '2-1100',
                        'AP_USD' => '2-1120'
						);
		Zend_Registry::set('constant_coa', $const);
		
		//Define controller & action for bypass session checking
		$bypass = array(
						array("module" => "default","controller" => "login", "action" => "index"),
						array("module" => "default","controller" => "login", "action" => "cek"),
						array("module" => "default","controller" => "login", "action" => "ldapauth"),
						array("module" => "default","controller" => "home", "action" => "documenttoprocess"),
						array("module" => "mobile","controller" => "login", "action" => "index"), //mobile
						array("module" => "mobile","controller" => "login", "action" => "cek"), //mobile
						array("module" => "mobile","controller" => "login", "action" => "ldapauth"), //mobile
						array("module" => "mobile","controller" => "login", "action" => "submit"), //mobile
						array("module" => "mc","controller" => "login", "action" => "index"), //mobile client
						array("module" => "mc","controller" => "login", "action" => "cek"), //mobile client
						array("module" => "mc","controller" => "login", "action" => "ldapauth"), //mobile client
						array("module" => "mc","controller" => "login", "action" => "submit") //mobile client			
						);
		Zend_Registry::set('bypass', $bypass);	
		
		$validFile = array(
                        'XLS' => 'application/vnd.ms-excel',
                        'XLS2' => 'application/xls',
                        'XLSX' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'DOC' => 'application/msword',
                        'DOCX' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'PDF' => 'application/pdf',
                        'GIF' => 'image/gif',
                        'JPG' => 'image/jpeg',
                        'JPEG' => 'image/jpeg',
                        'TIF' => 'image/tif',
                        'BMP' => 'image/x-windows-bmp',
                        'PNG' => 'image/png',
                        'ZIP' => 'application/x-compressed',
                        'RAR' => 'application/x-compressed',
                        'TAR' => 'application/x-compressed',
                        'GZ' => 'application/x-compressed',
                        '7Z' => 'application/x-compressed'
        );
        Zend_Registry::set('validFile', $validFile);

        $options = $this->getOption('uploadfile');
        $uploadPath = $options['uploadPath'];
        $previewPath = $options['previewPath'];
        if (substr($uploadPath, -1, 1) != "/")
            $uploadPath .= "/";
        if (substr($previewPath, -1, 1) != "/")
            $previewPath .= "/";
        Zend_Registry::set('uploadPath', $uploadPath);
        Zend_Registry::set('previewPath', $previewPath);

        $options = $this->getOption('phpexcel');
        $phpexcelPath = $options['path'];
        Zend_Registry::set('phpexcel', $phpexcelPath);

        $options = $this->getOption('server');
        $servertest = $options['test'];
        Zend_Registry::set('servertest', $servertest);

        /** Define for Db Connection * */
        $options = $this->getOption('resources');
        $db_adapter = $options['db']['adapter'];
        $params = $options['db']['params'];

        try {

            $db = Zend_Db::factory($db_adapter, $params);
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
            $db->getConnection();
            Zend_Registry::set('db', $db);
            return $db;
        } catch (Zend_Exception $e) {
            var_dump($e);
            echo'Error in MySQL Connection';
            exit;
        }
        /** End of Define for Db Connection * */
    }

    protected function _initMainUrl() {
        //Set our URL / Domain
        return 'qdc-erp.local';
    }

    public static function __autoload($className) {
        include_once(str_replace('_', '/', $className) . '.php');
    }

    protected function _initAutoloaders() {

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setDefaultAutoloader(array('Bootstrap', '__autoload')); //Setting __autoload() function as a default autoloader.
        $autoloader->suppressNotFoundWarnings(true);
        $autoloader->setFallbackAutoloader(true);
        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Default_',
                    'basePath' => APPLICATION_PATH . '/modules/default',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Logistic_',
                    'basePath' => APPLICATION_PATH . '/modules/logistic',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );
        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Admin_',
                    'basePath' => APPLICATION_PATH . '/modules/admin',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Procurement_',
                    'basePath' => APPLICATION_PATH . '/modules/procurement',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'ProjectManagement_',
                    'basePath' => APPLICATION_PATH . '/modules/projectmanagement',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Finance_',
                    'basePath' => APPLICATION_PATH . '/modules/finance',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'HumanResource_',
                    'basePath' => APPLICATION_PATH . '/modules/humanresource',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Mobile_',
                    'basePath' => APPLICATION_PATH . '/modules/mobile',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Sales_',
                    'basePath' => APPLICATION_PATH . '/modules/sales',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );


        // Mobile Client
        
        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Mc_',
                    'basePath' => APPLICATION_PATH . '/modules/mc',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );

        // Extjs 4

        $autoloader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Extjs4_',
                    'basePath' => APPLICATION_PATH . '/modules/extjs4',
                    'resourceTypes' => array(
                        'models' => array('path' => '/models',
                            'namespace' => 'Models')
                    )
                        )
        );
        
    }

    protected function _initPlugins() {
        // Access plugin
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new MyApp_Plugin_Module());
        $acl = new MyApp_Acl();
        $front->setParam('acl', $acl);
        $front->registerPlugin(
                new MyApp_Plugin_Acl($acl)
        );
    }

    /**
     * Initialize the cache
     * return object Zend_Cache
     *
     * for detail utilisation please refer to
     * http://dev.qdc.co.id:3000/projects/erp/wiki/memcache
     *
     */
    protected function _initCache() {

        $frontendOptions= array(
                            'caching' => true,
                            'lifetime' => 1800,
                            'automatic_serialization' => true
                        );
                        
        $backendOptions = array(
                            'servers' =>array(
                                array(
                                'host' => '127.0.0.1',
                                'port' => 11211
                                )
                            ),
                            'compression' => false
                        );

        $cacheBackend = 'Memcached';

        $cache = Zend_Cache::factory(
                        'Core',
                        $cacheBackend,
                        $frontendOptions,
                        $backendOptions
        );

//        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        Zend_Registry::set('Memcache', $cache);

        $frontendOptions2 = $frontendOptions;
        $frontendOptions2['lifetime'] = 3600;

        $cache2 = Zend_Cache::factory(
            'Core',
            $cacheBackend,
            $frontendOptions2,
            $backendOptions
        );

        Zend_Registry::set('MemcacheWorkflow', $cache2);

        $frontendOptions3 = $frontendOptions;
        $frontendOptions3['lifetime'] = 7200;

        $cache3 = Zend_Cache::factory(
            'Core',
            $cacheBackend,
            $frontendOptions3,
            $backendOptions
        );

        Zend_Registry::set('MemcacheGantt', $cache3);
        Zend_Registry::set('MemcacheGeneral', $cache3);
        return $cache;
    }
}
?>