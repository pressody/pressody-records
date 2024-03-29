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

use Composer\IO\NullIO;
use Pressody\Records\Archiver;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\Builder\LocalBasePackageBuilder;
use Pressody\Records\PackageType\Builder\LocalPluginBuilder;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Tests\Unit\TestCase;
use Psr\Log\NullLogger;

class LocalPluginBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                ->disableOriginalConstructor()
		                ->getMock();

		$release_manager = $this->getMockBuilder( ReleaseManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();
		$archiver                = new Archiver( new NullLogger() );
		$logger = new NullIO();

		$this->builder = new LocalPluginBuilder( new LocalPlugin(), $package_manager, $release_manager, $archiver, $logger );
	}

	public function test_extends_package_builder() {
		$this->assertInstanceOf( LocalBasePackageBuilder::class, $this->builder );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_basename() {
		$expected = 'plugin/plugin.php';
		$package  = $this->builder->set_basename( $expected )->build();

		$this->assertSame( $expected, $package->get_basename() );
	}

	public function test_from_basename() {
		$plugin_file = 'plugin-name/plugin-name.php';
		$slug = 'plugin-name';

		$package = $this->builder->from_basename( $plugin_file )->build();

		$this->assertSame( PackageTypes::PLUGIN, $package->get_type() );
		$this->assertSame( $slug, $package->get_slug() );
		$this->assertSame( 'local-plugin/' . $slug, $package->get_source_name() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( $plugin_file, $package->get_basename() );
		$this->assertSame( WP_PLUGIN_DIR . '/' . 'plugin-name/', $package->get_directory() );
		$this->assertTrue( $package->is_installed() );
	}

	public function test_with_package() {
		$expected = 'plugin/plugin.php';
		$expected_package  = $this->builder->set_basename( $expected )->build();

		$package = $this->builder->with_package( $expected_package )->build();

		$this->assertSame( $expected, $package->get_basename() );
	}
}
