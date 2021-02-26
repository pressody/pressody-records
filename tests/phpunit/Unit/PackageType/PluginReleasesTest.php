<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Unit\PackageType;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use PixelgradeLT\Records\ComposerVersionParser;
use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\PackageManager;
use Psr\Log\NullLogger;
use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\Builder\LocalPluginBuilder;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Storage\Local as LocalStorage;
use PixelgradeLT\Records\Test\Unit\TestCase;

class PluginReleasesTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		Functions\when( 'get_site_transient' )->justReturn( $this->get_update_transient() );

		$archiver = new Archiver( new NullLogger() );
		$storage  = new LocalStorage( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages' );
		$composer_version_parser = new ComposerVersionParser( new VersionParser() );

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$release_manager = new ReleaseManager( $storage, $archiver, $composer_version_parser );

		$logger = new NullIO();

		$package  = new LocalPlugin();

		$this->builder = ( new LocalPluginBuilder( $package, $package_manager, $release_manager, $logger ) )
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
