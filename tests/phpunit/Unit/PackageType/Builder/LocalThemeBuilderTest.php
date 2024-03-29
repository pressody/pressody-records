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

namespace Pressody\Records\Tests\Unit\PackageType\Builder;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Pressody\Records\Archiver;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\Builder\LocalBasePackageBuilder;
use Pressody\Records\PackageType\Builder\LocalThemeBuilder;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Tests\Unit\TestCase;
use Psr\Log\NullLogger;

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

		$archiver                = new Archiver( new NullLogger() );
		$logger = new NullIO();

		$this->builder = new LocalThemeBuilder( new LocalTheme(), $package_manager, $release_manager, $archiver, $logger );
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

		$this->assertSame( PackageTypes::THEME, $package->get_type() );
		$this->assertSame( $slug, $package->get_slug() );
		$this->assertSame( 'local-theme/' . $slug, $package->get_source_name() );
		$this->assertSame( 'local.theme', $package->get_source_type() );
		$this->assertSame( get_theme_root( $slug ) . '/' . $slug . '/', $package->get_directory() );
		$this->assertTrue( $package->is_installed() );
	}
}
