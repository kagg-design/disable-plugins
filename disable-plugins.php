<?php
/**
 * Plugin Name: Disable Plugins
 * Description: MU-Plugin to disable some plugins under certain conditions.
 * Version: 1.3
 * Author: KAGG Design
 * Author URI: https://kagg.eu/en/
 * License: GPL2
 * Requires at least: 4.4
 * Tested up to: 5.8
 * Requires PHP: 5.6
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\DisablePlugins;

define( 'KAGG_DISABLE_PLUGINS_PATH', __DIR__ . '/disable-plugins' );

/**
 * Init plugin class on plugin load.
 */
require_once KAGG_DISABLE_PLUGINS_PATH . '/vendor/autoload.php';

( new Main( new Filters() ) )->init();
