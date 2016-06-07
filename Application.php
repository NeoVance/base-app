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
 */
class Application {
    
    protected static $_class;
    protected static $_instance;
    protected static $_di;
    
    protected function __construct() {
    }
    
    public static function instance( $appClass, $diClass ) {
        if( $appClass !== self::$_class ) {
            self::$_class = $appClass;
            
            delete self::$_di;
            delete self::$_instance;
        }
        
        self::di( $diClass );
        
        if( !isset( self::$_instance ) ) {
            self::$_instance = new $appClass( self::$_di );
        }
        
        return self::$_instance;
    }
    
    public static function micro() {
        $app = self::instance( "\Phalcon\MVC\Micro" );
        return $app;
    }
    
    public static function main() {
        $app = self::instance( "\Phalcon\MVC\Application" );
        return $app;
    }
    
    public static function cli() {
        $app = self::instance( "\Phalcon\CLI\Console", "\Phalcon\Di\FactoryDefault\CLI" );
        return $app;
    }
    
    public static function di( $diClass = "\Phalcon\Di\FactoryDefault" ) {
        if( !isset( self::$_di ) ) {
            self::$_di = new $diClass();
            delete self::$_instance;
        }
        
        return self::$_di;
    }
    
}