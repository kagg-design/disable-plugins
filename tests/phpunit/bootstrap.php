<?php
/**
 * Bootstrap file for Disable Plugins phpunit tests.
 *
 * @package kagg/disable_plugins
 */

use tad\FunctionMocker\FunctionMocker;

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

FunctionMocker::init(
	[
		'blacklist'             => [
			realpath( PLUGIN_PATH ),
		],
		'whitelist'             => [
			realpath( PLUGIN_PATH . '/disable-plugins.php' ),
			realpath( PLUGIN_PATH . '/includes' ),
		],
		'redefinable-internals' => [
			'defined',
			'constant',
			'filter_input',
		],
	]
);

WP_Mock::bootstrap();
