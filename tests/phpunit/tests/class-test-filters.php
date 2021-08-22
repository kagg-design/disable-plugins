<?php
/**
 * Test_Filters class file
 *
 * @package kagg/disable_plugins
 */

use KAGG\KAGG_TestCase;
use KAGG\Disable_Plugins\Filters;

/**
 * Class Test_Filters
 *
 * @group filters
 */
class Test_Filters extends KAGG_TestCase {

	/**
	 * Test get_frontend_filters().
	 */
	public function test_get_frontend_filters() {
		$subject = $this->get_subject();
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
