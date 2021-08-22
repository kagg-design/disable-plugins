<?php
/**
 * Bootstrap file for Disable Plugins phpunit tests.
 *
 * @package kagg/disable_plugins
 */

/**
 * Test constants.
 */
const PLUGIN_TESTS_DIR = __DIR__;
define( 'PLUGIN_MAIN_FILE', dirname( dirname( __DIR__ ) ) . '/disable-plugins.php' );
define( 'PLUGIN_PATH', dirname( PLUGIN_MAIN_FILE ) );

/**
 * Autoload test classes.
 */
require_once PLUGIN_PATH . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', PLUGIN_PATH . '/../../' );
}

/**
 * Bootstrap WP Mock.
 */
WP_Mock::bootstrap();
