<?php
/**
 * Test_Filters class file
 *
 * @package kagg/disable_plugins
 */

use KAGG\DisablePlugins\Filters;
use KAGG\DisablePlugins\Tests\Unit\KAGGTestCase;

/**
 * Class Test_Filters
 *
 * @group filters
 */
class FiltersTest extends KAGGTestCase {

	/**
	 * Test get_frontend_filters() with wrong filter filename.
	 */
	public function test_get_frontend_filters_with_wrong_filter_filename() {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/non.existing.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters() with empty filter file.
	 */
	public function test_get_frontend_filters_with_empty_filter_file() {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/tests/empty.file.test.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters() with empty json.
	 */
	public function test_get_frontend_filters_with_empty_json() {
		$subject = new Filters( PLUGIN_TESTS_DIR . '/tests/empty.json.test.json' );
		self::assertSame( [], $subject->get_frontend_filters() );
	}

	/**
	 * Test get_frontend_filters().
	 */
	public function test_get_frontend_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_frontend_filters() );

		// Check that at second time we have the same result.
		$this->check_filters( $subject->get_frontend_filters() );
	}

	/**
	 * Test get_backend_filters().
	 */
	public function test_get_backend_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_backend_filters() );
	}

	/**
	 * Test get_ajax_filters().
	 */
	public function test_get_ajax_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_ajax_filters() );
	}

	/**
	 * Test get_rest_filters().
	 */
	public function test_get_rest_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_rest_filters() );
	}

	/**
	 * Test get_cli_filters().
	 */
	public function test_get_cli_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_cli_filters() );
	}

	/**
	 * Test get_xml_rpc_filters().
	 */
	public function test_get_xml_rpc_filters() {
		$subject = $this->get_subject();
		$this->check_filters( $subject->get_xml_rpc_filters() );
	}

	/**
	 * Get subject.
	 *
	 * @return Filters
	 */
	private function get_subject() {
		return new Filters( PLUGIN_TESTS_DIR . '/tests/filters.test.json' );
	}

	/**
	 * Check filters.
	 *
	 * @param array $filters Filters.
	 *
	 * @noinspection PhpUnitTestsInspection
	 */
	private function check_filters( $filters ) {
		$this->assertTrue( is_array( $filters ) );
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
