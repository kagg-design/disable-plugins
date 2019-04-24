<?php
/**
 * Plugin Name: Disable Plugins
 * Description: MU-Plugin to disable some plugins under certain conditions.
 * Version: 1.0
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * License: GPL2
 * Requires at least: 4.4
 * Tested up to: 5.1
 * Requires PHP: 7.0
 *
 * @package OTGS\OurSystem\Disable_Plugins
 */

namespace OTGS\OurSystem\Disable_Plugins;

define( 'OTGS_OURSYSTEM_DISABLE_PLUGINS_PATH', dirname( __FILE__ ) . '/otgs-disable-plugins' );

/**
 * Init plugin class on plugin load.
 */
require_once OTGS_OURSYSTEM_DISABLE_PLUGINS_PATH . '/vendor/autoload.php';

$filters = new Filters();
$plugin  = new Main( $filters );
$plugin->init();
