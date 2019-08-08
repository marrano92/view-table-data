<?php
/*
Plugin Name: Andt
Plugin URI: http://example.com
Description: Engine for Analyze data from database
Version: 1.0
Author: jubstuff, realloc
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: andt
*/

/********************************
 * Globals definitions *
 *******************************/

defined( 'ABSPATH' ) || die( 'Error 403: Access Denied/Forbidden!' );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
define( 'DKWP_PLUGIN_DIR', ( function_exists( 'plugin_dir_path' ) ? plugin_dir_path( __FILE__ ) : __DIR__ . '/' ) );

/**
 * Autoloader init
 */
if ( file_exists( DKWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once DKWP_PLUGIN_DIR . 'vendor/autoload.php';
}

add_action( 'admin_menu', function () {
	\Andt\DataPage::init();
} );