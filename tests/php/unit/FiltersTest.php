<?php
/**
 * Test_Filters class file
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\DisablePlugins\Tests\Unit;

use KAGG\DisablePlugins\Filters;
use WP_Mock;

/**
 * Class Test_Filters
 *
 * @group filters
 */
class FiltersTest extends KAGGTestCase {

	/**
	 * Set up tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		WP_Mock::passthruFunction( 'wp_normalize_path' );
	}

	/**
	 * Test get_frontend_filters() with wrong filter filename.
	 */
	public function test_get_frontend_filters_with_wrong_filter_filename(): void {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/non.existing.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters() with an empty filter file.
	 */
	public function test_get_frontend_filters_with_empty_filter_file(): void {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/tests/empty.file.test.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters() with empty JSON.
	 */
	public function test_get_frontend_filters_with_empty_json(): void {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/tests/empty.json.test.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters().
	 */
	public function test_get_frontend_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_frontend_filters() );

		// Check that the second time we have the same result.
		$this->check_filters( $subject->get_frontend_filters() );
	}

	/**
	 * Test get_backend_filters().
	 */
	public function test_get_backend_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_backend_filters() );
	}

	/**
	 * Test get_ajax_filters().
	 */
	public function test_get_ajax_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_ajax_filters() );
	}

	/**
	 * Test get_rest_filters().
	 */
	public function test_get_rest_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_rest_filters() );
	}

	/**
	 * Test get_cli_filters().
	 */
	public function test_get_cli_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_cli_filters() );
	}

	/**
	 * Test get_xml_rpc_filters().
	 */
	public function test_get_xml_rpc_filters(): void {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_xml_rpc_filters() );
	}

	/**
	 * Get subject.
	 *
	 * @return Filters
	 */
	private function get_subject(): Filters {
		return new Filters( PLUGIN_TESTS_DIR . '/tests/filters.test.json' );
	}

	/**
	 * Check filters.
	 *
	 * @param array $filters Filters.
	 *
	 * @noinspection PhpUnitTestsInspection PhpUnitTestsInspection.
	 */
	private function check_filters( array $filters ): void {
		foreach ( $filters as $filter ) {
			$this->assertTrue( is_array( $filter ) );
			$this->assertArrayHasKey( 'patterns', $filter );
			$this->assertTrue( is_array( $filter['patterns'] ) );
			$this->assertArrayHasKey( 'locations', $filter );
			$this->assertTrue( is_array( $filter['locations'] ) );
			$this->assertTrue( isset( $filter['enabled_plugins'] ) || isset( $filter['disabled_plugins'] ) );
		}
	}
}
