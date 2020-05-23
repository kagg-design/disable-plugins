<?php
/**
 * Plugin Name: Disable Plugins
 * Description: MU-Plugin to disable some plugins under certain conditions.
 * Version: 1.1
 * Author: KAGG Design
 * Author URI: https://kagg.eu/en/
 * License: GPL2
 * Requires at least: 4.4
 * Tested up to: 5.2
 * Requires PHP: 7.0
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\Disable_Plugins;

define( 'KAGG_DISABLE_PLUGINS_PATH', dirname( __FILE__ ) . '/disable-plugins' );

/**
 * Init plugin class on plugin load.
 */
require_once KAGG_DISABLE_PLUGINS_PATH . '/vendor/autoload.php';

$filters = new Filters();
$plugin  = new Main( $filters );
$plugin->init();
