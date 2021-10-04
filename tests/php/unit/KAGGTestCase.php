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
	 *
	 * @noinspection PhpLanguageLevelInspection
	 * @noinspection PhpUndefinedClassInspection
	 * phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
	 */
	public function setUp(): void {
		// phpcs:enable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
		FunctionMocker::setUp();
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * End test
	 *
	 * @noinspection PhpLanguageLevelInspection
	 * @noinspection PhpUndefinedClassInspection
	 * phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
	 */
	public function tearDown(): void {
		// phpcs:enable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound
		WP_Mock::tearDown();
		parent::tearDown();
		FunctionMocker::tearDown();
	}
}
