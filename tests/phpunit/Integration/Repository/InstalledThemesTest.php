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

declare ( strict_types = 1 );

namespace Pressody\Records\Tests\Integration\Repository;

use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\Tests\Integration\TestCase;

use function Pressody\Records\plugin;

class InstalledThemesTest extends TestCase {
	protected $original_theme_directories = null;

	public function setUp(): void {
		parent::setUp();

		$this->original_theme_directories = $GLOBALS['wp_theme_directories'];
		register_theme_directory( \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/themes' );
		delete_site_transient( 'theme_roots' );
	}

	public function teardDown() {
		delete_site_transient( 'theme_roots' );
		$GLOBALS['wp_theme_directories'] = $this->original_theme_directories;
	}

	public function test_get_theme_from_source() {
		$repository = plugin()->get_container()['repository.local.themes'];

		// theme1 is part of the WordPress Core Unit Test package.
		$package    = $repository->first_where( [ 'slug' => 'theme1', 'source_type' => 'local.theme' ] );
		$this->assertInstanceOf( LocalTheme::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'ovation', 'source_type' => 'local.theme' ] );
		$this->assertInstanceOf( LocalTheme::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'ovation', 'source_type' => 'local.plugin' ] );
		$this->assertNull( $package );
	}
}
