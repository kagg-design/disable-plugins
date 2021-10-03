<?php
/**
 * KAGGTestCase class file.
 *
 * @package kagg/disable_plugins
 */

namespace KAGG\DisablePlugins\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

/**
 * Class KAGGTestCase
 */
abstract class KAGGTestCase extends TestCase {
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
