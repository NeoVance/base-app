<?php_sapi_name

define( 'ROOT_PATH', __DIR__ );
// Environment type should be defined via environment, not configuration
define( 'APP_ENV', php_sapi_name == 'cli' ? getenv('APP_ENV') : $_SERVER['APP_ENV'] );

require_once( './Application.php' );

try {
    // TODO: Handle
    $app = Application::main();
} catch( $e ) {
    Application::exception( $e );
}
