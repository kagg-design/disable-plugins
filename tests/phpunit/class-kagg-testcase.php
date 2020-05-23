<?php
/**
 * KAGG_TestCase class file.
 *
 * @package kagg/disable_plugins
 */

namespace KAGG;
use PHPUnit\Framework\TestCase;

/**
 * Class KAGG_TestCase
 */
class KAGG_TestCase extends TestCase {
	/**
	 * Setup test
	 */
	public function setUp() {
		parent::setUp();
		\WP_Mock::setUp();
	}

	/**
	 * End test
	 */
	public function tearDown() {
		\WP_Mock::tearDown();
		parent::tearDown();
	}
}
