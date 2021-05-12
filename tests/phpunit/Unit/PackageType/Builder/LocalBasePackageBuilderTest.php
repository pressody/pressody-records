<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Tests\Unit\PackageType\Builder;

use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\Client\ComposerClient;
use PixelgradeLT\Records\ComposerVersionParser;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\Builder\LocalBasePackageBuilder;
use PixelgradeLT\Records\PackageType\Builder\BasePackageBuilder;
use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Storage\Local as LocalStorage;
use PixelgradeLT\Records\StringHashes;
use PixelgradeLT\Records\Tests\Unit\TestCase;
use PixelgradeLT\Records\WordPressReadmeParser;
use Psr\Log\NullLogger;

class LocalBasePackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Provide direct getters.
		$package = new class extends LocalBasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
		};


		$archiver = new Archiver( new NullLogger() );
		$storage  = new LocalStorage( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages' );
		$composer_version_parser = new ComposerVersionParser( new VersionParser() );
		$composer_client = new ComposerClient();
		$logger = new NullIO();

		$hasher = new StringHashes();
		$readme_parser = new WordPressReadmeParser();
		$package_manager = new PackageManager( $composer_client, $composer_version_parser, $readme_parser, $logger, $hasher );

		$release_manager = new ReleaseManager( $storage, $archiver, $composer_version_parser, $composer_client, $logger );

		$this->builder = new LocalBasePackageBuilder( $package, $package_manager, $release_manager, $archiver, $logger );
	}

	public function test_extends_package_builder() {
		$this->assertInstanceOf( BasePackageBuilder::class, $this->builder );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_directory() {
		$expected = 'directory';
		$package  = $this->builder->set_directory( $expected )->build();

		$this->assertSame( $expected . '/', $package->directory );
	}

	public function test_is_installed() {
		$package = $this->builder->build();
		$this->assertFalse( $package->is_installed );

		$package = $this->builder->set_installed( true )->build();
		$this->assertTrue( $package->is_installed );
	}

	public function test_installed_version() {
		$expected = '1.0.0';
		$package  = $this->builder->set_installed( true )->set_installed_version( $expected )->build();

		$this->assertSame( $expected, $package->installed_version );
	}

	public function test_invalid_installed_version() {
		$invalid = '2-0-0';
		$package  = $this->builder->set_installed( true )->set_installed_version( $invalid )->build();

		$this->assertSame( '', $package->installed_version );
	}

	public function test_with_package() {
		$expected = new class extends LocalBasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->is_installed = true;
		$expected->directory = 'directory/';
		$expected->installed_version = '2.0.0';

		$package = $this->builder->with_package( $expected )->build();

		$this->assertSame( $expected->is_installed, $package->is_installed );
		$this->assertSame( $expected->directory, $package->directory );
		$this->assertSame( $expected->installed_version, $package->installed_version );
	}
}
