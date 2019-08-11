<?php
/**
 * Main class file
 *
 * @package KAGG\Disable_Plugins
 */

namespace KAGG\Disable_Plugins;

/**
 * Class Main
 *
 * @package KAGG\Disable_Plugins
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
		add_filter( 'option_active_plugins', [ $this, 'disable' ], PHP_INT_MIN );

		add_filter( 'option_hack_file', [ $this, 'remove_plugin_filters' ], PHP_INT_MIN );
		add_action( 'plugins_loaded', [ $this, 'remove_plugin_filters' ], PHP_INT_MIN );
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
		remove_filter( 'option_active_plugins', [ $this, 'disable' ], PHP_INT_MIN );
	}

	/**
	 * Disable plugins on frontend
	 *
	 * @param array $plugins Plugins.
	 *
	 * @return mixed
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
	 * @return mixed
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
		if ( ! $this->is_frontend_ajax() || ! isset( $_POST['action'] ) ) {
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
		$enabled_plugins  = $plugins;
		$found            = false;

		foreach ( $filters as $filter ) {
			if ( ! isset( $filter['patterns'] ) || ! is_array( $filter['patterns'] ) ) {
				continue;
			}

			foreach ( $filter['patterns'] as $pattern ) {
				if ( mb_ereg_match( $pattern, $current_pattern ) ) {
					if ( isset( $filter['disabled_plugins'] ) ) {
						$disabled_plugins = $filter['disabled_plugins'];
					}
					if ( isset( $filter['enabled_plugins'] ) ) {
						$enabled_plugins = $filter['enabled_plugins'];
					}
					$found = true;
					break;
				}
			}
		}

		if ( ! $found ) {
			return $plugins;
		}

		$plugins = array_diff( $plugins, $disabled_plugins );
		$plugins = array_intersect( $plugins, $enabled_plugins );

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

		// If referer does not contain admin URL and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request.
		if ( ( ( strpos( $ref, admin_url() ) === false ) && ( basename( $script_filename ) === 'admin-ajax.php' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if it is a REST request
	 *
	 * @return bool
	 */
	private function is_rest() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Check of it is a CLI request
	 *
	 * @return bool
	 */
	private function is_cli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
}
