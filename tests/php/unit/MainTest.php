<?php
/**
 * MainTest class file.
 *
 * @package kagg/disable_plugins
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpParamsInspection PhpParamsInspection. */
/** @noinspection PhpUndefinedMethodInspection PhpUndefinedMethodInspection. */
/** @noinspection PhpMethodParametersCountMismatchInspection PhpMethodParametersCountMismatchInspection. */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace KAGG\DisablePlugins\Tests\Unit;

use KAGG\DisablePlugins\Filters;
use KAGG\DisablePlugins\Main;
use Mockery;
use ReflectionException;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

/**
 * Class MainTest
 */
class MainTest extends KAGGTestCase {

	/**
	 * Finalise test
	 */
	public function tearDown(): void {
		unset(
			$_SERVER['REQUEST_URI'],
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$_GET['rest_route'],
			$_GET['wc-ajax'],
			$_REQUEST['_wp_http_referer'],
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$GLOBALS['HTTP_RAW_POST_DATA'],
			$GLOBALS['wp_rewrite'],
			$GLOBALS['argv']
		);

		parent::tearDown();
	}

	/**
	 * It inits
	 *
	 * @test
	 * @noinspection PhpUndefinedMethodInspection PhpUndefinedMethodInspection.
	 */
	public function it_inits(): void {
		$subject = Mockery::mock( Main::class )->makePartial();
		$subject->shouldReceive( 'add_hooks' )->once();

		WP_Mock::userFunction( 'wp_cache_add_non_persistent_groups' )->once()->with( [ Main::CACHE_GROUP ] );

		$subject->init();
	}

	/**
	 * It adds and removes hooks
	 *
	 * @test
	 */
	public function it_adds_and_removes_hooks(): void {
		$filters_instance = Mockery::mock( Filters::class );
		$subject          = new Main( $filters_instance );

		WP_Mock::expectFilterAdded( 'option_active_plugins', [ $subject, 'disable' ], -PHP_INT_MAX );
		WP_Mock::expectFilterAdded( 'option_hack_file', [ $subject, 'remove_plugin_filters' ], -PHP_INT_MAX );
		WP_Mock::expectActionAdded( 'plugins_loaded', [ $subject, 'remove_plugin_filters' ], -PHP_INT_MAX );

		$subject->add_hooks();

		WP_Mock::userFunction(
			'remove_filter',
			[
				'times' => 1,
				'args'  => [ 'option_active_plugins', [ $subject, 'disable' ], -PHP_INT_MAX ],
			]
		);

		$subject->remove_plugin_filters();
	}

	/**
	 * It removes plugin filters
	 *
	 * @test
	 */
	public function it_removes_plugin_filters(): void {
		$filters_instance = Mockery::mock( Filters::class );
		$subject          = new Main( $filters_instance );

		WP_Mock::expectFilterNotAdded( 'option_active_plugins', [ $subject, 'disable' ] );

		$subject->remove_plugin_filters();
	}

	/**
	 * It disables plugins saved in cache
	 *
	 * @test
	 */
	public function it_disables_plugins_saved_in_cache(): void {
		$filters_instance = Mockery::mock( Filters::class );
		$subject          = new Main( $filters_instance );

		$cached_plugins = [ 'sitepress-multilingual-cms/sitepress.php' ];

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( $cached_plugins );

		$plugins = [ 'wpml-string-translation/plugin.php' ];
		$this->assertSame( $cached_plugins, $subject->disable( $plugins ) );
	}

	/**
	 * It does nothing on frontend if no server uri
	 *
	 * @test
	 */
	public function it_does_nothing_on_frontend_if_no_server_uri(): void {
		$filters_instance = Mockery::mock( Filters::class );
		$subject          = new Main( $filters_instance );

		$plugins = [ 'sitepress-multilingual-cms/sitepress.php' ];

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		unset( $_SERVER['REQUEST_URI'] );

		$this->assertSame( $plugins, $subject->disable( $plugins ) );
	}

	/**
	 * It disables plugins on frontend
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_frontend
	 */
	public function it_disables_plugins_on_frontend( $plugins, $filters, $expected ): void {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$_SERVER['REQUEST_URI'] = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$_SERVER['REQUEST_URI'] = '/some_url';
		}

		WP_Mock::passthruFunction( 'wp_unslash' );
		WP_Mock::passthruFunction( 'wp_parse_url' );
		WP_Mock::userFunction(
			'trailingslashit',
			[
				'return' => function ( $url ) {
					return $url . '/';
				},
			]
		);

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_frontend_filters' )->andReturn( $filters );

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_frontend
	 */
	public function dp_it_disables_plugins_on_frontend(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'no patterns'                    => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'locations' => [ 'frontend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'frontend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'wrong pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '(wrong pattern)' ],
						'locations' => [ 'frontend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '/some_url2' ],
						'locations' => [ 'frontend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '/some_url3' ],
						'locations'        => [ 'frontend' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '.*' ],
						'locations'        => [ 'frontend' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ '/some_url4' ],
						'locations'       => [ 'frontend' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ '/some_url5' ],
						'locations'        => [ 'frontend' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * It does nothing on backend if no server uri
	 *
	 * @test
	 */
	public function it_does_nothing_on_backend_if_no_server_uri(): void {
		$filters_instance = Mockery::mock( Filters::class );
		$subject          = new Main( $filters_instance );

		$plugins = [ 'sitepress-multilingual-cms/sitepress.php' ];

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		unset( $_SERVER['REQUEST_URI'] );

		$this->assertSame( $plugins, $subject->disable( $plugins ) );
	}

	/**
	 * It disables plugins on backend
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_backend
	 */
	public function it_disables_plugins_on_backend( $plugins, $filters, $expected ): void {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$_SERVER['REQUEST_URI'] = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$_SERVER['REQUEST_URI'] = '/some_url';
		}

		WP_Mock::passthruFunction( 'wp_unslash' );

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_backend_filters' )->andReturn( $filters );

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_backend
	 */
	public function dp_it_disables_plugins_on_backend(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'no patterns'                    => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'locations' => [ 'backend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'backend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'wrong pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '(wrong pattern)' ],
						'locations' => [ 'backend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '/some_url2' ],
						'locations' => [ 'backend' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '/some_url3' ],
						'locations'        => [ 'backend' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '.*' ],
						'locations'        => [ 'backend' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ '/some_url4' ],
						'locations'       => [ 'backend' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ '/some_url5' ],
						'locations'        => [ 'backend' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * It does nothing on ajax if referer is admin_url()
	 *
	 * @test
	 */
	public function it_does_nothing_on_ajax_if_referer_is_admin_url(): void {
		$referer = 'https://www.example.com/wp-admin/';
		$action  = 'my-action';

		$plugins = [ 'sitepress-multilingual-cms/sitepress.php' ];

		$filters = [
			[
				'patterns'         => [ $action ],
				'disabled_plugins' => [
					'sitepress-multilingual-cms/sitepress.php',
				],
			],
		];

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_ajax_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( true );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		$_REQUEST['_wp_http_referer'] = $referer;
		unset( $_SERVER['HTTP_REFERER'] );

		$_POST['action'] = $action;

		WP_Mock::passthruFunction( 'wp_unslash' );
		WP_Mock::userFunction( 'admin_url' )->andReturn( $referer );

		$subject = new Main( $filters_instance );
		$this->assertSame( $plugins, $subject->disable( $plugins ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_REQUEST['_wp_http_referer'] );
		$_SERVER['HTTP_REFERER'] = $referer;

		$this->assertSame( $plugins, $subject->disable( $plugins ) );

		$this->assertSame( $plugins, $subject->disable( $plugins ) );
	}

	/**
	 * It disables plugins on ajax
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_ajax
	 */
	public function it_disables_plugins_on_ajax( $plugins, $filters, $expected ): void {
		$referer = 'https://www.example.com/some-page/';

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_ajax_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( true );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		$_REQUEST['_wp_http_referer'] = $referer;

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$action = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$action = 'my-action';
		}

		$_POST['action'] = $action;

		FunctionMocker::replace(
			'filter_input',
			static function ( $type, $var_name, $filter ) use ( $action ) {
				if ( INPUT_POST === $type && 'action' === $var_name && FILTER_SANITIZE_FULL_SPECIAL_CHARS === $filter ) {
					return $action;
				}

				return null;
			}
		);

		WP_Mock::userFunction( 'admin_url' )->andReturn( 'https://www.example.com/wp-admin/' );

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );

		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_ajax
	 */
	public function dp_it_disables_plugins_on_ajax(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'ajax' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ 'my-action2' ],
						'locations' => [ 'ajax' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ 'my-action3' ],
						'locations'        => [ 'ajax' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ 'my-action4' ],
						'locations'        => [ 'ajax' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ 'my-action4' ],
						'locations'       => [ 'ajax' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ 'my-action5' ],
						'locations'        => [ 'ajax' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * It disables plugins on WooCommerce ajax
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_ajax
	 */
	public function it_disables_plugins_on_wc_ajax( $plugins, $filters, $expected ): void {
		$referer = 'https://www.example.com/some-page/';

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_ajax_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		$_REQUEST['_wp_http_referer'] = $referer;

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$action = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$action = 'my-action';
		}

		$_GET['wc-ajax'] = $action;

		FunctionMocker::replace(
			'filter_input',
			static function ( $type, $var_name, $filter ) use ( $action ) {
				if ( INPUT_GET === $type && 'wc-ajax' === $var_name && FILTER_SANITIZE_FULL_SPECIAL_CHARS === $filter ) {
					return $action;
				}

				return null;
			}
		);

		WP_Mock::userFunction( 'admin_url' )->andReturn( 'https://www.example.com/wp-admin/' );

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * It disables plugins on rest, case 1
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_rest
	 */
	public function it_disables_plugins_on_rest_case1( $plugins, $filters, $expected ): void {
		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_rest_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$rest_route = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$rest_route = 'some-route';
		}

		$_SERVER['REQUEST_URI'] = '/wp-json/' . $rest_route;

		FunctionMocker::replace(
			'defined',
			static function ( $constant_name ) {
				return 'REST_REQUEST' === $constant_name;
			}
		);

		FunctionMocker::replace(
			'constant',
			static function ( $constant_name ) {
				return 'REST_REQUEST' === $constant_name;
			}
		);

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[get_rest_route]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'get_rest_route' )->andReturn( $rest_route );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * It disables plugins on rest, cases 2-4
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_rest
	 */
	public function it_disables_plugins_on_rest_case2_4( $plugins, $filters, $expected ): void {
		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_rest_filters' )->andReturn( $filters );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_rewrite'] = Mockery::mock( 'WP_Rewrite' );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$rest_route = $filters[ count( $filters ) - 1 ]['patterns'][0];
		} else {
			$rest_route = 'some-route';
		}

		$_SERVER['REQUEST_URI'] = '/some-uri';
		$_GET['rest_route']     = $rest_route;

		FunctionMocker::replace(
			'filter_input',
			static function ( $type, $var_name, $filter ) use ( $rest_route ) {
				if ( INPUT_GET === $type && 'rest_route' === $var_name && FILTER_SANITIZE_FULL_SPECIAL_CHARS === $filter ) {
					return $rest_route;
				}

				return 'wrong route';
			}
		);

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[get_rest_route,disable_on_frontend]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'get_rest_route' )->andReturn( $rest_route );
		$subject->shouldReceive( 'disable_on_frontend' )->with( $plugins )->andReturn( $plugins );

		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_rest
	 */
	public function dp_it_disables_plugins_on_rest(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'rest' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ 'wp/v2/route1' ],
						'locations' => [ 'rest' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ 'wp/v2/route2' ],
						'locations'        => [ 'rest' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '.*' ],
						'locations'        => [ 'rest' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ 'wp/v2/route3' ],
						'locations'       => [ 'rest' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ 'wp/v2/route4' ],
						'locations'        => [ 'rest' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * It disables plugins on cli
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_cli
	 */
	public function it_disables_plugins_on_cli( $plugins, $filters, $expected ): void {
		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_cli_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		FunctionMocker::replace(
			'defined',
			static function ( $constant_name ) {
				return 'WP_CLI' === $constant_name;
			}
		);

		FunctionMocker::replace(
			'constant',
			static function ( $constant_name ) {
				return 'WP_CLI' === $constant_name;
			}
		);

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$command         = $filters[ count( $filters ) - 1 ]['patterns'][0];
			$GLOBALS['argv'] = explode( ' ', 'wp ' . $command );
		}

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_cli
	 */
	public function dp_it_disables_plugins_on_cli(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'cli' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ 'command1' ],
						'locations' => [ 'cli' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ 'command2' ],
						'locations'        => [ 'cli' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '.*' ],
						'locations'        => [ 'cli' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ 'command3' ],
						'locations'       => [ 'cli' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ 'command4' ],
						'locations'        => [ 'cli' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * It disables plugins on xml-rpc
	 *
	 * @param array|mixed $plugins  Plugins.
	 * @param array|mixed $filters  $filters  Filters.
	 * @param array|mixed $expected Expected result.
	 *
	 * @test
	 * @dataProvider        dp_it_disables_plugins_on_xml_rpc
	 * @noinspection        RequiredAttributes RequiredAttributes.
	 * @noinspection        XmlDeprecatedElement XmlDeprecatedElement.
	 * @noinspection        HtmlDeprecatedTag HtmlDeprecatedTag.
	 */
	public function it_disables_plugins_on_xml_rpc( $plugins, $filters, $expected ): void {
		$http_raw_post_data = '
';

		$http_raw_post_data .= '<?xml version="1.0"?>
<methodCall>
    <methodName>someMethod</methodName>
    <params>
        <param>
            <value>
                <int>0</int>
            </value>
        </param>
        <param>
            <value>
                <string>username</string>
            </value>
        </param>
        <param>
            <value>
                <string>password</string>
            </value>
        </param>
    </params>
</methodCall>';

		$filters_instance = Mockery::mock( Filters::class );
		$filters_instance->shouldReceive( 'get_xml_rpc_filters' )->andReturn( $filters );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_doing_ajax' )->andReturn( false );
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::passthruFunction( 'wp_cache_set' );

		FunctionMocker::replace(
			'defined',
			static function ( $constant_name ) {
				return 'XMLRPC_REQUEST' === $constant_name;
			}
		);

		FunctionMocker::replace(
			'constant',
			static function ( $constant_name ) {
				return 'XMLRPC_REQUEST' === $constant_name;
			}
		);

		if ( is_array( $filters ) && isset( $filters[ count( $filters ) - 1 ]['patterns'] ) ) {
			$method = $filters[ count( $filters ) - 1 ]['patterns'][0];
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['HTTP_RAW_POST_DATA'] = str_replace( 'someMethod', $method, $http_raw_post_data );
		}

		$subject = Mockery::mock( '\KAGG\DisablePlugins\Main[is_rest,is_cli]', [ $filters_instance ] )
			->shouldAllowMockingProtectedMethods();
		$subject->shouldReceive( 'is_rest' )->andReturn( false );
		$subject->shouldReceive( 'is_cli' )->andReturn( false );
		$this->assertSame( $expected, $subject->disable( $plugins ) );
	}

	/**
	 * Data provider for it_disables_plugins_on_xml_rpc
	 */
	public function dp_it_disables_plugins_on_xml_rpc(): array {
		return [
			'not an array'                   => [ 'some string', null, 'some string' ],
			'empty array'                    => [ [], null, [] ],
			'empty pattern'                  => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ '' ],
						'locations' => [ 'xml-rpc' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'just patterns in filter'        => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'  => [ 'wp.getSome1' ],
						'locations' => [ 'xml-rpc' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
			'disabled in filter'             => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ 'wp.getSome2' ],
						'locations'        => [ 'xml-rpc' ],
						'disabled_plugins' => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'enabled in filter'              => [
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
				[
					[
						'patterns'         => [ '.*' ],
						'locations'        => [ 'xml-rpc' ],
						'disabled_plugins' => [
							'sitepress-multilingual-cms/sitepress.php',
							'wpml-string-translation/plugin.php',
						],
					],
					[
						'patterns'        => [ 'wp.getSome3' ],
						'locations'       => [ 'xml-rpc' ],
						'enabled_plugins' => [ 'wpml-string-translation/plugin.php' ],
					],
				],
				[ 1 => 'wpml-string-translation/plugin.php' ],
			],
			'disabled and enabled in filter' => [
				[
					'sitepress-multilingual-cms/sitepress.php',
					'wpml-string-translation/plugin.php',
					'wpml-translation-management/plugin.php',
				],
				[
					[
						'patterns'         => [ 'wp.getSome4' ],
						'locations'        => [ 'xml-rpc' ],
						'disabled_plugins' => [ 'wpml-translation-management/plugin.php' ],
						'enabled_plugins'  => [ 'sitepress-multilingual-cms/sitepress.php' ],
					],
				],
				[ 'sitepress-multilingual-cms/sitepress.php', 'wpml-string-translation/plugin.php' ],
			],
		];
	}

	/**
	 * Test get_rest_route().
	 *
	 * @param string $current_path Current path.
	 * @param string $expected     Expected.
	 *
	 * @test
	 * @dataProvider dp_it_gets_rest_route
	 * @throws ReflectionException Reflection exception.
	 */
	public function it_gets_rest_route( string $current_path, string $expected ): void {
		$current_url = 'https://test.test' . $current_path;

		$rest_path = '/wp-json';
		$rest_url  = 'https://test.test' . $rest_path . '/';

		WP_Mock::userFunction( 'add_query_arg' )->with( [] )->andReturn( $current_url );
		WP_Mock::userFunction( 'wp_parse_url' )->with( $current_url, PHP_URL_PATH )->andReturn( $current_path );

		WP_Mock::userFunction( 'rest_url' )->andReturn( $rest_url );
		WP_Mock::userFunction( 'trailingslashit' )->andReturnUsing(
			function ( $value ) {
				return rtrim( $value, '/' ) . '/';
			}
		);
		WP_Mock::userFunction( 'wp_parse_url' )->with( $rest_url, PHP_URL_PATH )->andReturn( $rest_path );

		$subject = Mockery::mock( Main::class )->makePartial();
		$method  = 'get_rest_route';
		$this->set_method_accessibility( $subject, $method );

		self::assertSame( $expected, $subject->$method() );
	}

	/**
	 * Data provider for it_gets_rest_route.
	 *
	 * @return array
	 */
	public function dp_it_gets_rest_route(): array {
		return [
			'rest request' => [ '/wp-json/wp/v2/posts', '/wp/v2/posts' ],
			'some request' => [ '/some-request', '' ],
		];
	}
}
