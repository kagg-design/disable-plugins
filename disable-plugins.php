<?php
/**
 * Plugin Name: Disable Plugins
 * Description: MU-Plugin to disable some plugins under certain conditions.
 * Version: 1.5.0
 * Author: KAGG Design
 * Author URI: https://kagg.eu/
 * License: GPL2
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.0
 *
 * @package kagg/disable_plugins
 */

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpIncludeInspection PhpIncludeInspection */

namespace KAGG\DisablePlugins;

define( 'KAGG_DISABLE_PLUGINS_PATH', __DIR__ . '/disable-plugins' );

/**
 * Init plugin class on the plugin load.
 */
require_once KAGG_DISABLE_PLUGINS_PATH . '/vendor/autoload.php';

( new Main( new Filters() ) )->init();
