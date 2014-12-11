<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    public $_acl;
    
    public function _initMemory()
    {
        ini_set('memory_limit' , '128M');
    }
    
    protected function _initModuleAutoLoad()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(
                            array(
                                "namespace" => '',
                                "basePath" => APPLICATION_PATH . '/modules/default'
                            ));                
    }
    
    protected function _initRegisterNamespace()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Iati_');
        $autoloader->registerNamespace('App_');
        $autoloader->registerNamespace('Ckan_');
        $autoloader->registerNamespace('Oipa_');
    }
    
    protected function _initRegistry()
    {
        $registry = Zend_Registry::getInstance();
        $registry->config = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/application.ini',
            APPLICATION_ENV,
            true
        );

        return $registry;
    }
    
    protected function _initSession()
    {
        $sessionOptions = Zend_Registry::get('config')->resources->session->toArray();
        Zend_Session::setOptions($sessionOptions);
    }

    function _initFrontController()
    {
        // The Zend_Front_Controller class implements the Singleton pattern
        $frontController = Zend_Controller_Front::getInstance();

        // look in the modules directory and automatically make modules out of all folders found
        $frontController->addModuleDirectory(APPLICATION_PATH . '/modules');

        // forces the front controller to forward all errors to the default error
        // controller (may already be false by default)
        $frontController->throwExceptions(false);

        return $frontController;
    }

    function _initViewHelpers()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();
        // Add script path for custom scripts.
        $view->addScriptPath(APPLICATION_PATH .'/../library/Iati/WEP/ViewScripts/');
        $view->addScriptPath(APPLICATION_PATH .'/../library/Iati/Aidstream/ViewScripts/');
        $view->addScriptPath(APPLICATION_PATH .'/../library/Iati/Core/ViewScripts/');
        $view->addScriptPath(APPLICATION_PATH .'/../library/Iati/Aidstream/Form/ViewScripts/');

        $view->doctype('HTML4_STRICT');
        $view->headMeta()->appendHttpEquiv('Content-type', 'text/html; charset=utf-8');
        $view->headTitle('ZendFramework');
       
        // Initialize Zendx jquery viewHelper
        $view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
    }

    function _initRouting()
    {
        $router = $this->getResource('frontController')->getRouter();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routing.ini', 'routing');
        $router->addConfig($config, 'routes');

        $route = new Zend_Controller_Router_Route_Static(
            'snapshot',
            array('controller' => 'index', 'action' => 'organisation')
        );
        $router->addRoute('snapshot', $route);
    }


    protected function _initAcl()
    {
        $this->_acl = new App_Acl();
        //$auth = Zend_Auth::getInstance();
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->registerPlugin(new App_AccessCheck($this->_acl));
        
        //for the guest user role is assigned as guest rather than null and for
        //other roles it is fetched from zend_auth
        if (Zend_Auth::getInstance()->hasIdentity()) {
            Zend_Registry::set('role', Zend_Auth::getInstance()->getStorage()->read()->role);
        } else {
            Zend_Registry::set('role', 'guest');
        }

        // writing the Zend_Acl to registry allowing to use it any where in the model

        Zend_Registry::set('acl', $this->_acl);
    }

    protected function _initLogger()
    {
        $config = $this->getOption('email');
        $logger = new Zend_Log();
        
        // register our custom error handler
        App_Error_Handler::register();
        
        $writer_filesys = new Zend_Log_Writer_Stream(APPLICATION_PATH .
                                                     '/../data/log/zf.iati.log');
        $writer_filesys->addFilter(Zend_Log::WARN);
        
        $logger->addWriter($writer_filesys);

        if (APPLICATION_ENV == 'production') {
            $email = new Zend_Mail();

            $email->setFrom($config['fromAddress'])->addTo($config['errLogging']);

            $writer_email = new Zend_Log_Writer_Mail($email);
            $writer_email->setSubjectPrependText('Urgent: IATI Server Error!');

            // only email warning level "errors" or higher
            $writer_email->addFilter(Zend_Log::WARN);
            $logger->addWriter($writer_email);
        }

        Zend_Registry::set('logger', $logger);

        return $logger;
    }

    function _initappEmail()
    {
        $registry = Zend_Registry::getInstance();
        $registry->mailer = new App_Email();
        return $registry;
    }

}
