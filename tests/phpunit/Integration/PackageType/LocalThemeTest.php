<?php
/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types=1 );

namespace Pressody\Records\Tests\Integration\PackageType;

use Pressody\Records\PackageFactory;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Tests\Integration\TestCase;

use function Pressody\Records\plugin;

class LocalThemeTest extends TestCase {
	protected $original_theme_directories = null;

	/**
	 * @var PackageFactory|null
	 */
	protected $factory = null;

	public function setUp(): void {
		parent::setUp();

		$this->original_theme_directories = $GLOBALS['wp_theme_directories'];
		register_theme_directory( \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/themes' );
		delete_site_transient( 'theme_roots' );

		$this->factory = plugin()->get_container()->get( 'package.factory' );
	}

	public function teardDown() {
		delete_site_transient( 'theme_roots' );
		$GLOBALS['wp_theme_directories'] = $this->original_theme_directories;
	}

	public function test_get_theme_from_slug() {
		/** @var LocalTheme $package */
		$package = $this->factory->create( PackageTypes::THEME, 'local.theme' )
		                         ->from_slug( 'ovation' )
		                         ->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( PackageTypes::THEME, $package->get_type() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( 'local-theme/ovation', $package->get_source_name() );
		$this->assertSame( 'ovation', $package->get_slug() );
		$this->assertTrue( $package->is_installed() );
	}

	public function test_get_theme_from_source() {
		/** @var LocalTheme $package */
		$package = $this->factory->create( PackageTypes::THEME, 'local.theme' )
		                         ->from_slug( 'ovation' )
		                         ->from_source( 'ovation' )
		                         ->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( 'Ovation', $package->get_name() );
		$this->assertSame( PackageTypes::THEME, $package->get_type() );
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
		$this->assertSame( '5.2', $package->get_requires_at_least_wp() );
		$this->assertSame( '5.6.2', $package->get_tested_up_to_wp() );
		$this->assertSame( '7.2.0', $package->get_requires_php() );

		$this->assertFalse( $package->is_managed() );
		$this->assertSame( get_theme_root( 'ovation' ) . '/ovation/', $package->get_directory() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( '1.1.1', $package->get_installed_version() );
	}

	public function test_get_plugin_from_readme() {
		/** @var LocalTheme $package */
		$package = $this->factory->create( PackageTypes::THEME, 'local.theme' )
		                         ->from_slug( 'ovation' )
		                         ->from_readme( 'ovation' )
		                         ->build();

		$this->assertInstanceOf( LocalTheme::class, $package );

		$this->assertSame( 'Ovation', $package->get_name() );
		$this->assertSame( PackageTypes::THEME, $package->get_type() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( 'local-theme/ovation', $package->get_source_name() );
		$this->assertSame( 'ovation', $package->get_slug() );
		$this->assertSame( [
			[ 'name' => 'wordpressdotorg', ],
		], $package->get_authors() );
		$this->assertSame( 'Our test theme readme short description.', $package->get_description() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertSame( [ 'accessibility-ready', 'flexible-header', 'one-column' ], $package->get_keywords() );
		$this->assertSame( '4.9.6', $package->get_requires_at_least_wp() );
		$this->assertSame( '5.6', $package->get_tested_up_to_wp() );
		$this->assertSame( '5.6', $package->get_requires_php() );

		$this->assertFalse( $package->is_managed() );
	}
}
