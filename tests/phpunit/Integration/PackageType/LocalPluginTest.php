<?php
declare ( strict_types=1 );

namespace PixelgradeLT\Records\Tests\Integration\PackageType;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use PixelgradeLT\Records\ComposerVersionParser;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\LocalTheme;
use Psr\Log\NullLogger;
use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\PackageType\LocalPlugin;
use PixelgradeLT\Records\PackageType\Builder\LocalPluginBuilder;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Storage\Local as LocalStorage;
use PixelgradeLT\Records\Tests\Unit\TestCase;

class LocalPluginTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		$archiver                = new Archiver( new NullLogger() );
		$storage                 = new LocalStorage( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages' );
		$composer_version_parser = new ComposerVersionParser( new VersionParser() );

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$release_manager = new ReleaseManager( $storage, $archiver, $composer_version_parser );

		$logger = new NullIO();

		$package = new LocalPlugin();

		$this->builder = new LocalPluginBuilder( $package, $package_manager, $release_manager, $logger );
	}

	public function test_get_plugin_from_file() {
		/** @var LocalPlugin $package */
		$package = $this->builder
						->from_file( 'basic/basic.php' )
						->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( 'plugin', $package->get_type() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( 'local-plugin/basic', $package->get_source_name() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
	}

	public function test_get_plugin_from_source() {
		$package = $this->builder
			->from_file( 'basic/basic.php' )
			->from_source( 'basic/basic.php' )
			->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( 'plugin', $package->get_type() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( 'local-plugin/basic', $package->get_source_name() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( 'Basic Plugin', $package->get_name() );
		$this->assertSame( [
			[
				'name'     => 'Basic, Inc.',
				'homepage' => 'https://example.com/',
			],
		], $package->get_authors() );
		$this->assertSame( 'A basic plugin description.', $package->get_description() );
		$this->assertSame( 'https://example.com/plugin/basic/', $package->get_homepage() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertSame( [ 'admin', 'htaccess', 'post', 'redirect', ], $package->get_keywords() );
		$this->assertFalse( $package->is_managed() );
		$this->assertSame( WP_PLUGIN_DIR . '/basic/', $package->get_directory() );
		$this->assertSame( '1.3.1', $package->get_installed_version() );
	}

	public function test_is_single_file_plugin() {
		$package = $this->builder
			->from_file( 'basic/basic.php' )
			->from_source( 'basic/basic.php' )
			->build();

		$this->assertFalse( $package->is_single_file() );

		$package = $this->builder
			->from_file( 'hello.php' )
			->from_source( 'hello.php' )
			->build();

		$this->assertTrue( $package->is_single_file() );
	}

	public function test_get_files_for_single_file_plugin() {
		$package = $this->builder
			->from_file( 'hello.php' )
			->from_source( 'hello.php' )
			->build();

		$this->assertSame( 1, count( $package->get_files() ) );
	}
}
