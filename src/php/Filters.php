<?php
/**
 * Filters class file
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\DisablePlugins;

/**
 * Class Filters
 *
 * @package kagg/disable_plugins
 */
class Filters {

	/**
	 * Name of the file containing filters in json format
	 *
	 * @var string
	 */
	private $filters_filename = KAGG_DISABLE_PLUGINS_PATH . '/../filters.json';

	/**
	 * All filters
	 *
	 * @var array
	 */
	private $filters = [];

	/**
	 * Filters constructor
	 *
	 * @param string $filters_filename Name of the file containing plugin filters.
	 */
	public function __construct( string $filters_filename = '' ) {
		if ( $filters_filename ) {
			$this->filters_filename = $filters_filename;
		}
	}

	/**
	 * Get frontend filters
	 *
	 * @return array
	 */
	public function get_frontend_filters(): array {
		return $this->get_filters_for_location( 'frontend' );
	}

	/**
	 * Get backend filters
	 *
	 * @return array
	 */
	public function get_backend_filters(): array {
		return $this->get_filters_for_location( 'backend' );
	}

	/**
	 * Get ajax filters
	 *
	 * @return array
	 */
	public function get_ajax_filters(): array {
		return $this->get_filters_for_location( 'ajax' );
	}

	/**
	 * Get rest filters
	 *
	 * @return array
	 */
	public function get_rest_filters(): array {
		return $this->get_filters_for_location( 'rest' );
	}

	/**
	 * Get cli filters
	 *
	 * @return array
	 */
	public function get_cli_filters(): array {
		return $this->get_filters_for_location( 'cli' );
	}

	/**
	 * Get xml-rpc filters
	 *
	 * @return array
	 */
	public function get_xml_rpc_filters(): array {
		return $this->get_filters_for_location( 'xml-rpc' );
	}

	/**
	 * Get filters for given location
	 *
	 * @param string $location Location name.
	 *
	 * @return array
	 */
	private function get_filters_for_location( string $location ): array {
		$all_filters = $this->load_filters();
		$filters     = [];

		foreach ( $all_filters as $filter ) {
			if ( ! isset( $filter['locations'] ) || ! is_array( $filter['locations'] ) ) {
				continue;
			}

			if ( in_array( $location, $filter['locations'], true ) ) {
				$filters[] = $filter;
			}
		}

		return $filters;
	}

	/**
	 * Load full set of filters from json file
	 *
	 * @return array|false|mixed|object|string
	 */
	private function load_filters() {
		if ( ! empty( $this->filters ) ) {
			return $this->filters;
		}

		if ( ! is_readable( $this->filters_filename ) ) {
			return $this->filters;
		}

		// @phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$filters = file_get_contents( $this->filters_filename );
		// @phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! $filters ) {
			return $this->filters;
		}

		$filters = json_decode( $filters, true );

		if ( ! $filters ) {
			return $this->filters;
		}

		$this->filters = $filters;

		return $filters;
	}
}
