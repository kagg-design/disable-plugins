<?php
/**
 * Main class file
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\Disable_Plugins;

use WP_Rewrite;

/**
 * Class Main
 *
 * @package kagg/disable_plugins
 */
class Main {

	/**
	 * Plugin cache group
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'kagg_disable_plugins';

	/**
	 * Instance of class Filters, providing plugin filters
	 *
	 * @var Filters $filters
	 */
	private $filters;

	/**
	 * Main constructor.
	 *
	 * @param Filters $filters Instance of class Filters, providing plugin filters.
	 */
	public function __construct( Filters $filters ) {
		$this->filters = $filters;
	}

	/**
	 * Init plugin
	 */
	public function init() {
		wp_cache_add_non_persistent_groups( [ self::CACHE_GROUP ] );

		$this->add_hooks();
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_filter( 'option_active_plugins', [ $this, 'disable' ], - PHP_INT_MAX );

		add_filter( 'option_hack_file', [ $this, 'remove_plugin_filters' ], - PHP_INT_MAX );
		add_action( 'plugins_loaded', [ $this, 'remove_plugin_filters' ], - PHP_INT_MAX );
	}

	/**
	 * Disable plugins
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array|mixed
	 */
	public function disable( $plugins ) {
		if ( ! is_array( $plugins ) || empty( $plugins ) ) {
			return $plugins;
		}

		$key             = md5( wp_json_encode( $plugins ) );
		$allowed_plugins = wp_cache_get( $key, self::CACHE_GROUP );

		if ( false !== $allowed_plugins ) {
			return $allowed_plugins;
		}

		if ( wp_doing_ajax() ) {
			$allowed_plugins = $this->disable_on_ajax( $plugins );
		} elseif ( is_admin() ) {
			$allowed_plugins = $this->disable_on_backend( $plugins );
		} elseif ( $this->is_rest() ) {
			$allowed_plugins = $this->disable_on_rest( $plugins );
		} elseif ( $this->is_cli() ) {
			$allowed_plugins = $this->disable_on_cli( $plugins );
		} elseif ( $this->is_xml_rpc() ) {
			$allowed_plugins = $this->disable_on_xml_rpc( $plugins );
		} else {
			$allowed_plugins = $this->disable_on_frontend( $plugins );
		}

		wp_cache_set( $key, $allowed_plugins, self::CACHE_GROUP );

		return $allowed_plugins;
	}

	/**
	 * Remove plugin filters
	 */
	public function remove_plugin_filters() {
		remove_filter( 'option_active_plugins', [ $this, 'disable' ], - PHP_INT_MAX );
	}

	/**
	 * Disable plugins on frontend
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_frontend( $plugins ) {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return $plugins;
		}

		$uri  = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_STRING );
		$slug = urldecode( trailingslashit( wp_parse_url( $uri, PHP_URL_PATH ) ) );

		return $this->filter_plugins( $plugins, $slug, $this->filters->get_frontend_filters() );
	}

	/**
	 * Disable plugins on backend
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_backend( $plugins ) {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return $plugins;
		}

		$uri = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_STRING );

		return $this->filter_plugins( $plugins, $uri, $this->filters->get_backend_filters() );
	}

	/**
	 * Disable plugins on ajax
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_ajax( $plugins ) {
		// @phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['action'] ) || ! $this->is_frontend_ajax() ) {
			return $plugins;
		}

		$action = filter_var( wp_unslash( $_POST['action'] ), FILTER_SANITIZE_STRING );

		// @phpcs:enable WordPress.Security.NonceVerification.Missing

		return $this->filter_plugins( $plugins, $action, $this->filters->get_ajax_filters() );
	}

	/**
	 * Disable plugins on REST
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_rest( $plugins ) {
		return $plugins;
	}

	/**
	 * Disable plugins on CLI
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_cli( $plugins ) {
		return $plugins;
	}

	/**
	 * Disable plugins on XML-RPC
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_xml_rpc( $plugins ) {
		// Raw post data, set up in xmlrpc.php.
		// phpcs:disable PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		global $HTTP_RAW_POST_DATA;

		$method = '';
		if ( preg_match( '#<methodName>(.+)</methodName>#', $HTTP_RAW_POST_DATA, $matches ) ) {
			$method = trim( $matches[1] );
		}

		// phpcs:enable PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved

		return $this->filter_plugins( $plugins, $method, $this->filters->get_xml_rpc_filters() );
	}

	/**
	 * Get subset of plugins
	 *
	 * @param array  $plugins         Plugins.
	 * @param string $current_pattern Current pattern.
	 * @param array  $filters         Filters.
	 *
	 * @return array
	 */
	private function filter_plugins( $plugins, $current_pattern, $filters ) {
		if ( ! $current_pattern || ! is_array( $filters ) ) {
			return $plugins;
		}

		$disabled_plugins = [];
		$enabled_plugins  = [];
		$found            = false;

		foreach ( $filters as $filter ) {
			if ( ! isset( $filter['patterns'] ) || ! is_array( $filter['patterns'] ) ) {
				continue;
			}

			foreach ( $filter['patterns'] as $pattern ) {
				if ( ! mb_ereg_match( $pattern, $current_pattern ) ) {
					continue;
				}

				if ( isset( $filter['disabled_plugins'] ) ) {
					$disabled_plugins[] = $filter['disabled_plugins'];
				}

				if ( isset( $filter['enabled_plugins'] ) ) {
					$enabled_plugins[] = $filter['enabled_plugins'];
				}

				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return $plugins;
		}

		$disabled_plugins = array_merge( [], ...$disabled_plugins );
		$enabled_plugins  = array_merge( [], ...$enabled_plugins );

		$disabled_plugins = array_diff( $disabled_plugins, $enabled_plugins );
		$plugins          = array_diff( $plugins, $disabled_plugins );

		return array_unique( $plugins );
	}

	/**
	 * Check if it is an ajax call from frontend
	 *
	 * @return bool
	 */
	private function is_frontend_ajax() {
		$ref = '';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
			$ref = filter_var( wp_unslash( $_REQUEST['_wp_http_referer'] ), FILTER_SANITIZE_STRING );
		}
		if ( ! $ref && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = filter_var( wp_unslash( $_SERVER['HTTP_REFERER'] ), FILTER_SANITIZE_STRING );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
			return false;
		}
		$script_filename = filter_var( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ), FILTER_SANITIZE_STRING );

		// If referer does not contain admin URL, and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request.
		return ( strpos( $ref, admin_url() ) === false ) && ( basename( $script_filename ) === 'admin-ajax.php' );
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings
	 * Case #3: It can happen that WP_Rewrite is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in subfolders
	 *
	 * @return bool
	 * @author matzeeable
	 */
	protected function is_rest() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// Case #1.
		if ( defined( 'REST_REQUEST' ) && constant( 'REST_REQUEST' ) ) {
			return true;
		}

		// Case #2.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rest_route = isset( $_GET['rest_route'] ) ?
			filter_input( INPUT_GET, 'rest_route', FILTER_SANITIZE_STRING ) :
			'';

		if ( 0 === strpos( trim( $rest_route, '\\/' ), rest_get_url_prefix() ) ) {
			return true;
		}

		// Case #3.
		global $wp_rewrite;
		if ( null === $wp_rewrite ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_rewrite = new WP_Rewrite();
		}

		// Case #4.
		$current_url = wp_parse_url( add_query_arg( [] ), PHP_URL_PATH );
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ), PHP_URL_PATH );

		return 0 === strpos( $current_url, $rest_url );
	}

	/**
	 * Check of it is a CLI request
	 *
	 * @return bool
	 */
	protected function is_cli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Check of it is a xml-rpc request
	 *
	 * @return bool
	 */
	protected function is_xml_rpc() {
		return defined( 'XMLRPC_REQUEST' ) && constant( 'XMLRPC_REQUEST' );
	}
}
