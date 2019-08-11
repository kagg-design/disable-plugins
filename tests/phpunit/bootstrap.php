<?php
// This is global bootstrap for autoloading
define( 'PLUGIN_TESTS_ROOT', __DIR__ );

define( 'WP_PLUGIN_DIR', realpath( dirname( __FILE__ ) . '/../../' ) );

define( 'WPML_TESTS_SITE_DIR', __DIR__ . '/site' );
define( 'WPML_TESTS_SITE_URL', 'http://domain.tld' );
if ( ! defined( 'TESTS_SITE_URL' ) ) {
	define( 'TESTS_SITE_URL', WPML_TESTS_SITE_URL );
}

define( 'WPML_TESTS_MAIN_FILE', __DIR__ . '/../../disable-plugins.php' );
define( 'WPML_PATH', dirname( WPML_TESTS_MAIN_FILE ) );

/** WP Constants */
define( 'WP_CONTENT_URL', WPML_TESTS_SITE_URL . '/wp-content' );
define( 'WP_CONTENT_DIR', WPML_TESTS_SITE_DIR . '/wp-content' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );

/** WPML-Core constants */
define( 'WPML_PLUGIN_PATH', dirname( WPML_TESTS_MAIN_FILE ) );
define( 'WPML_PLUGIN_FILE', basename( WPML_TESTS_MAIN_FILE ) );
define( 'WPML_PLUGIN_BASENAME', basename( WPML_PLUGIN_PATH ) . '/' . WPML_PLUGIN_FILE );
define( 'WPML_PLUGIN_FOLDER', basename( WPML_PLUGIN_PATH ) );

$autoloader_dir = WPML_PATH . '/vendor';
$autoloader = $autoloader_dir . '/autoload.php';
require_once $autoloader;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', WPML_PATH . '/../../' );
}

// Now call the bootstrap method of WP Mock.
\WP_Mock::bootstrap();

