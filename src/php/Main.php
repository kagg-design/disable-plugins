<?php
/**
 * Main class file
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\DisablePlugins;

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
	 * WooCommerce ajax $_GET argument.
	 */
	const WC_AJAX = 'wc-ajax';

	/**
	 * Instance of class Filters, providing plugin filters
	 *
	 * @var Filters $filters
	 */
	private $filters;

	/**
	 * REST route.
	 *
	 * @var string
	 */
	private $rest_route = '';

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
	 * @param array|mixed $plugins Plugins.
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

		if ( $this->is_ajax() ) {
			$allowed_plugins = $this->disable_on_ajax( $plugins );
		} elseif ( $this->is_wc_ajax() ) {
			$allowed_plugins = $this->disable_on_wc_ajax( $plugins );
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
	protected function disable_on_frontend( array $plugins ): array {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return $plugins;
		}

		$uri  = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
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
	private function disable_on_backend( array $plugins ): array {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return $plugins;
		}

		$uri = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		return $this->filter_plugins( $plugins, $uri, $this->filters->get_backend_filters() );
	}

	/**
	 * Disable plugins on ajax
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_ajax( array $plugins ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['action'] ) ?
			filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $this->filter_plugins( $plugins, $action, $this->filters->get_ajax_filters() );
	}

	/**
	 * Disable plugins on WooCommerce ajax
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_wc_ajax( array $plugins ): array {
		$action = filter_input( INPUT_GET, self::WC_AJAX, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		return $this->filter_plugins( $plugins, $action, $this->filters->get_ajax_filters() );
	}

	/**
	 * Disable plugins on REST
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_rest( array $plugins ): array {
		return $this->filter_plugins( $plugins, $this->rest_route, $this->filters->get_rest_filters() );
	}

	/**
	 * Disable plugins on CLI
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_cli( array $plugins ): array {
		$argv    = array_slice( $GLOBALS['argv'], 1 );
		$command = implode( ' ', $argv );

		return $this->filter_plugins( $plugins, $command, $this->filters->get_cli_filters() );
	}

	/**
	 * Disable plugins on XML-RPC
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return array
	 */
	private function disable_on_xml_rpc( array $plugins ): array {
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
	private function filter_plugins( array $plugins, string $current_pattern, array $filters ): array {
		if ( ! $current_pattern ) {
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
	private function is_frontend_ajax(): bool {
		$ref = '';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
			$ref = filter_var( wp_unslash( $_REQUEST['_wp_http_referer'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		if ( ! $ref && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = filter_var( wp_unslash( $_SERVER['HTTP_REFERER'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// If referer does not contain admin URL, this is likely a frontend AJAX request.
		return strpos( $ref, admin_url() ) === false;
	}

	/**
	 * Check of it is a frontend ajax request
	 *
	 * @return bool
	 */
	protected function is_ajax(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return wp_doing_ajax() && $this->is_frontend_ajax();
	}

	/**
	 * Check of it is a frontend WooCommerce ajax request
	 *
	 * @return bool
	 */
	protected function is_wc_ajax(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return ! empty( $_GET[ self::WC_AJAX ] ) && $this->is_frontend_ajax();
	}

	/**
	 * Get REST route.
	 * Returns route if it is a REST request, otherwise empty string.
	 *
	 * @return string
	 */
	protected function get_rest_route(): string {
		$current_path = wp_parse_url( add_query_arg( [] ), PHP_URL_PATH );
		$rest_path    = wp_parse_url( trailingslashit( rest_url() ), PHP_URL_PATH );

		$is_rest = 0 === strpos( $current_path, $rest_path );

		return $is_rest ? (string) substr( $current_path, strlen( $rest_path ) ) : '';
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
	protected function is_rest(): bool {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// Case #1.
		if ( defined( 'REST_REQUEST' ) && constant( 'REST_REQUEST' ) ) {
			$this->rest_route = $this->get_rest_route();

			return true;
		}

		// Case #2.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rest_route = isset( $_GET['rest_route'] ) ?
			filter_input( INPUT_GET, 'rest_route', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) :
			'';

		if ( $rest_route ) {
			$this->rest_route = ltrim( $rest_route, '/' );

			return true;
		}

		// Case #3.
		global $wp_rewrite;

		if ( null === $wp_rewrite ) {
			// @codeCoverageIgnoreStart
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_rewrite = new WP_Rewrite();
			// @codeCoverageIgnoreEnd
		}

		$this->rest_route = $this->get_rest_route();

		// Case #4.
		return (bool) $this->rest_route;
	}

	/**
	 * Check of it is a CLI request
	 *
	 * @return bool
	 */
	protected function is_cli(): bool {
		return defined( 'WP_CLI' ) && constant( 'WP_CLI' );
	}

	/**
	 * Check of it is a xml-rpc request
	 *
	 * @return bool
	 */
	protected function is_xml_rpc(): bool {
		return defined( 'XMLRPC_REQUEST' ) && constant( 'XMLRPC_REQUEST' );
	}
}
