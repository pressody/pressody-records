<?php
declare ( strict_types=1 );

namespace PixelgradeLT\Records\Tests\Integration\PackageType;

use PixelgradeLT\Records\PackageType\LocalTheme;
use PixelgradeLT\Records\Tests\Unit\TestCase;

use function PixelgradeLT\Records\plugin;

class LocalThemeTest extends TestCase {
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

	public function test_get_theme_from_slug() {
		/** @var LocalTheme $package */
		$package = $this->factory->create( 'theme', 'local.theme' )
		                         ->from_slug( 'ovation' )
		                         ->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( 'theme', $package->get_type() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( 'local-theme/ovation', $package->get_source_name() );
		$this->assertSame( 'ovation', $package->get_slug() );
		$this->assertTrue( $package->is_installed() );
	}

	public function test_get_theme_from_source() {
		$package = $this->factory->create( 'theme', 'local.theme' )
		                         ->from_slug( 'ovation' )
		                         ->from_source( 'ovation' )
		                         ->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( 'Ovation', $package->get_name() );
		$this->assertSame( 'theme', $package->get_type() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( 'local-theme/ovation', $package->get_source_name() );
		$this->assertSame( 'ovation', $package->get_slug() );
		$this->assertSame( [
			[
				'name'     => 'AudioTheme',
				'homepage' => 'https://audiotheme.com/',
			],
		], $package->get_authors() );
		$this->assertSame(
			'Ovation helps you create a well-considered, immersive website that extends beyond just the homepage. Highlights include a front page section layout, media-rich header, and parallax-like scrolling effect.',
			$package->get_description()
		);
		$this->assertSame( 'https://audiotheme.com/view/ovation/', $package->get_homepage() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertSame( [ 'accessibility-ready', 'flexible-header', 'one-column', ], $package->get_keywords() );
		$this->assertFalse( $package->is_managed() );
		$this->assertSame( get_theme_root() . '/ovation/', $package->get_directory() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( '1.1.1', $package->get_installed_version() );
	}
}
