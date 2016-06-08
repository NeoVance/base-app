<?php
/**
 * @file Application.php
 * @since 3.0
 * @package base-app
 * 
 * Initializing common elements for the application.
 */
 
/**
 * @class Application
 * 
 * A singleton factory for application instances. The factory functions are
 * writen in such a way that you can pass in a custom application class name
 * to be instanciated.
 */
class Application {
    
    protected static $_class;
    protected static $_instance;
    protected static $_di;
    protected static $_type;
    protected static $_init;
    
    const CLI_TYPE = 'cli';
    const MAIN_TYPE = 'main';
    const MICRO_TYPE = 'micro';
    
    /**
     * @constructor
     * 
     * Prevent instanciation
     */
    protected function __construct( $appInstance ) {
        self::di()->set( 'app', $appInstance );
        
        $this->loader = new \Phalcon\Loader();
        $this->loader->registerNamespaces([
            'Baseapp\Models' => ROOT_PATH . '/app/common/models/',
            'Baseapp\Library' => ROOT_PATH . '/app/common/library/',
            'Baseapp\Extension' => ROOT_PATH . '/app/common/extension/'
        ])->register();
        self::di()->set( 'loader', $loader );
        
        $config = new \Phalcon\Config\Adapter\Ini(ROOT_PATH . '/app/common/config/config.ini');
        self::di()->set( 'config', $config );
    }
    
    /**
     * @function instance
     * @param (string) $appClass
     * @param (string) $diClass
     * @returns (object) An instance of $appClass
     */
    public static function instance( $appClass, $diClass = "Phalcon\Di\FactoryDefault" ) {
        if( $appClass !== self::$_class ) {
            self::$_class = $appClass;
            
            self::$_di = null;
            self::$_instance = null;
        }
        
        self::di( $diClass );
        
        if( !in_array( "Phalcon\Di\InjectionAwareInterface", class_implements( $appClass ) ) ) {
            throw new Exception( "$appClass should implement Phalcon\Di\InjectionAwareInterface" );
        }
        
        if( !isset( self::$_instance ) ) {
            self::$_instance = new $appClass( self::$_di );
        }
        
        if( !isset( self::$_init ) ) {
            self::initialize();
        }
        
        return self::$_instance;
    }
    
    /**
     * @function micro
     * @returns (object) An instance of \Phalcon\MVC\Micro
     */
    public static function micro( $appClass = "Phalcon\MVC\Micro" ) {
        $app = self::instance( $appClass );
        
        if( $app instanceof \Phalcon\MVC\Micro ) {
            self::$_type = self::MICRO_TYPE;
            return $app;
        }
        
        self::$_di = null;
        self::$_instance = null;
        
        throw new Exception( "$appClass should extend Phalcon\MVC\Micro." );
    }
    
    /**
     * @function main
     * @returns (object) An instance of \Phalcon\MVC\Application
     */
    public static function main( $appClass = "Phalcon\MVC\Application" ) {
        $app = self::instance( $appClass );
        
        if( $app instanceof Phalcon\MVC\Application ) {
            self::$_type = self::MAIN_TYPE;
            return $app;
        }
            
        self::$_di = null;
        self::$_instance = null;

        throw new Exception( "$appClass should extend Phalcon\MVC\Application." );
    }
    
    /**
     * @function cli
     * @returns (object) An instance of \Phalcon\CLI\Console
     */
    public static function cli( $appClass = "Phalcon\CLI\Console" ) {
        $app = self::instance( $appClass, "Phalcon\Di\FactoryDefault\CLI" );
        
        if( $app instanceof \Phalcon\CLI\Console ) {
            self::$_type = self::CLI_TYPE;
            return $app;
        }
            
        self::$_di = null;
        self::$_instance = null;
        
        throw new Exception( "$appClass should extend Phalcon\CLI\Console." );
    }
    
    /**
     * @function di
     * @param (string) $diClass
     * @returns (object) An instance of $diClass
     */
    public static function di( $diClass = "Phalcon\Di\FactoryDefault" ) {
        if( !in_array( "Phalcon\DiInterface", class_implements( $diClass ) ) ) {
            throw new Exception( "$diClass should implement Phalcon\DiInterface" );
        }
        
        if( !isset( self::$_di ) ) {
            self::$_di = new $diClass();
            self::$_instance = null;
        }
        
        return self::$_di;
    }
    
    /**
     * @function initialize
     */
    protected static function initialize() {
        self::$_init = new self( self::$_instance );
    }
    
    /**
     * @function exception
     * @description Catch the exception and log it, display pretty view
     *
     * @package     base-app
     * @version     3.0
     *
     * @param \Exception $e
     * 
     * Moved from application bootstrappers in 2.0
     */
    public static function exception( Exception $e ) {
        if( !isset( self::$_init ) ) {
            self::initialize();
        }
        
        $errors = [
            'error' => get_class($e) . '[' . $e->getCode() . ']: ' . $e->getMessage(),
            'info' => $e->getFile() . '[' . $e->getLine() . ']',
            'debug' => "Trace: \n" . $e->getTraceAsString() . "\n",
        ];
        
        if( !defined( 'APP_ENV' ) ) {
            define( 'APP_ENV', 'DEVELOPMENT' );
        }
        
        if( self::$_type == self::CLI_TYPE || !isset( self::$_type ) ) {
            if( APP_ENV == 'DEVELOPMENT' ) {
                print_r( $e );
            }
        } else {
            if( APP_ENV == 'DEVELOPMENT' ) {
                var_dump( $errors );
            } else {
                $di = new Phalcon\DI\FactoryDefault();
                $view = new Phalcon\Mvc\View\Simple();
                $view->setDI($di);
                $view->setViewsDir(ROOT_PATH . '/themes/default/');
                
                // TODO: View engines can be modularized...
                $view->registerEngines(Baseapp\Library\Tool::registerEngines($view, $di));
                
                echo $view->render( 'error', [ 'errors' => $errors ] );
            }
        }
        
        self::log( $errors );
    }
    
    /**
     * @function log
     * @description Log message into file, notify the admin on stagging/production
     *
     * @package     base-app
     * @version     3.0
     *
     * @param mixed $messages messages to log
     * 
     * Migrated from bootstrapper in version 2.0
     */
    public static function log( $messages ) {
        if( !isset( self::$_init ) ) {
            self::initialize();
        }
        
        $config = self::di()->get('config');
        
        $dump = new \Phalcon\Debug\Dump();
        if (APP_ENV == "DEVELOPMENT") {
            foreach ($messages as $key => $message) {
                echo $dump->one($message, $key);
            }
            exit();
        } else {
            // TODO: Log location could be configurable
            $logger = new \Phalcon\Logger\Adapter\File(ROOT_PATH . '/app/common/logs/' . date('Ymd') . '.log', array('mode' => 'a+'));
            $log = '';

            if (is_array($messages) || $messages instanceof \Countable) {
                foreach ($messages as $key => $message) {
                    if (in_array($key, array('alert', 'debug', 'error', 'info', 'notice', 'warning'))) {
                        $logger->$key($message);
                    } else {
                        $logger->log($message);
                    }
                    $log .= $dump->one($message, $key);
                }
            } else {
                $logger->log($messages);
                $log .= $dump->one($messages);
            }

            if (APP_ENV != "testing") {
                $email = new Email();
                $email->prepare(__('Something is wrong!'), $config->app->admin, 'error', array('log' => $log));

                if ($email->Send() !== true) {
                    $logger->log($email->ErrorInfo);
                }
            }

            $logger->close();
        }
    }
    
}