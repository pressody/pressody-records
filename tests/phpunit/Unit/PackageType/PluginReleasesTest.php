<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Unit\PackageType;

use Brain\Monkey\Functions;
use Psr\Log\NullLogger;
use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\PackageType\Plugin;
use PixelgradeLT\Records\PackageType\PluginBuilder;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Storage\Local as LocalStorage;
use PixelgradeLT\Records\Test\Unit\TestCase;

class PluginReleasesTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		Functions\when( 'get_site_transient' )->justReturn( $this->get_update_transient() );

		$archiver = new Archiver( new NullLogger() );
		$storage  = new LocalStorage( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages' );
		$manager  = new ReleaseManager( $storage, $archiver );
		$package  = new Plugin();

		$this->builder = ( new PluginBuilder( $package, $manager ) )
			->set_basename( 'basic/basic.php' )
			->set_slug( 'basic' );
	}

	public function test_get_cached_releases_from_storage() {
		$package = $this->builder
			->add_cached_releases()
			->build();

		$this->assertInstanceOf( Release::class, $package->get_release( '1.0.0' ) );
	}

	public function test_get_cached_releases_includes_installed_version() {
		$package = $this->builder
			->set_installed( true )
			->set_installed_version( '1.3.1' )
			->add_cached_releases()
			->build();

		$this->assertSame( '1.3.1', $package->get_installed_release()->get_version() );
	}

	public function test_get_cached_releases_includes_pending_update() {
		$package = $this->builder
			->set_installed( true )
			->set_installed_version( '1.3.1' )
			->add_cached_releases()
			->build();

		$this->assertSame( '2.0.0', $package->get_latest_release()->get_version() );
	}

	protected function get_update_transient() {
		return (object) [
			'response' => [
				'basic/basic.php' => (object) [
					'slug'        => 'basic',
					'plugin'      => 'basic/basic.php',
					'new_version' => '2.0.0',
					'package'     => 'https://example.org/download/basic/2.0.0.zip',
				],
			],
		];
	}
}
