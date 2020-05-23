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
	 * It gets plugin filters
	 *
	 * @param array $filters Plugin filters.
	 *
	 * @test
	 * @dataProvider dp_it_gets_filters
	 */
	public function it_gets_filters( $filters ) {
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

	/**
	 * Data provider for it_gets_filters
	 */
	public function dp_it_gets_filters() {
		$filters_instance = new Filters( PLUGIN_TESTS_ROOT . '/tests/filters.test.json' );

		return [
			[ $filters_instance->get_frontend_filters() ],
			[ $filters_instance->get_backend_filters() ],
			[ $filters_instance->get_ajax_filters() ],
			[ $filters_instance->get_rest_filters() ],
			[ $filters_instance->get_cli_filters() ],
		];
	}
}
