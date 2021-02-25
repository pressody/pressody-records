<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Integration\PackageType;

use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\Test\Unit\TestCase;

use function PixelgradeLT\Records\plugin;

class ThemeTest extends TestCase {
	protected $original_theme_directories = null;
	protected $factory = null;

	public function setUp(): void {
		parent::setUp();

		$this->original_theme_directories = $GLOBALS['wp_theme_directories'];
		register_theme_directory( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/themes' );
		delete_site_transient( 'theme_roots' );

		$this->factory = plugin()->get_container()->get( 'package.factory' );
	}

	public function teardDown() {
		delete_site_transient( 'theme_roots' );
		$GLOBALS['wp_theme_directories'] = $this->original_theme_directories;
	}

	public function test_get_theme_from_source() {
		$package = $this->factory->create( 'theme' )
			->from_source( 'ovation' )
			->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( 'AudioTheme', $package->get_author() );
		$this->assertSame( 'https://audiotheme.com/', $package->get_author_url() );
		$this->assertSame( get_theme_root() . '/ovation/', $package->get_directory() );
		$this->assertSame( 'https://audiotheme.com/view/ovation/', $package->get_homepage() );
		$this->assertSame( 'Ovation', $package->get_name() );
		$this->assertSame( '1.1.1', $package->get_installed_version() );
		$this->assertSame( 'ovation', $package->get_slug() );
		$this->assertSame( 'theme', $package->get_type() );
		$this->assertTrue( $package->is_installed() );
	}
}
