<?php
/**
 * KAGG_TestCase class file.
 *
 * @package kagg/disable_plugins
 */

namespace KAGG;

use PHPUnit\Framework\TestCase;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

/**
 * Class KAGG_TestCase
 */
abstract class KAGG_TestCase extends TestCase {
	/**
	 * Setup test
	 */
	public function setUp(): void {
		FunctionMocker::setUp();
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * End test
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
		FunctionMocker::tearDown();
	}
}