<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Tests\Unit\PackageType\Builder;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\Builder\LocalBasePackageBuilder;
use PixelgradeLT\Records\PackageType\Builder\LocalThemeBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Tests\Unit\TestCase;

class LocalThemeBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Mock the WordPress get_theme_root() function.
		Functions\when( 'get_theme_root' )->justReturn( 'wp-content/themes');

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                ->disableOriginalConstructor()
		                ->getMock();

		$release_manager = $this->getMockBuilder( ReleaseManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$logger = new NullIO();

		$this->builder = new LocalThemeBuilder( new LocalTheme(), $package_manager, $release_manager, $logger );
	}

	public function test_extends_package_builder() {
		$this->assertInstanceOf( LocalBasePackageBuilder::class, $this->builder );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_from_slug() {
		$slug = 'theme-slug';

		$package = $this->builder->from_slug( $slug )->build();

		$this->assertSame( 'theme', $package->get_type() );
		$this->assertSame( $slug, $package->get_slug() );
		$this->assertSame( 'local-theme/' . $slug, $package->get_source_name() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( get_theme_root( $slug ) . '/' . $slug . '/', $package->get_directory() );
		$this->assertTrue( $package->is_installed() );
	}
}