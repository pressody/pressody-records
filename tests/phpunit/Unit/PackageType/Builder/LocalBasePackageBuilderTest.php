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

namespace Pressody\Records\Tests\Unit\PackageType\Builder;

use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use Pressody\Records\Archiver;
use Pressody\Records\Client\ComposerClient;
use Pressody\Records\ComposerVersionParser;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\Builder\LocalBasePackageBuilder;
use Pressody\Records\PackageType\Builder\BasePackageBuilder;
use Pressody\Records\PackageType\LocalBasePackage;
use Pressody\Records\Queue\ActionQueue;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Storage\Local as LocalStorage;
use Pressody\Records\StringHashes;
use Pressody\Records\Tests\Unit\TestCase;
use Pressody\Records\WordPressReadmeParser;
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


		$archiver                = new Archiver( new NullLogger() );
		$storage                 = new LocalStorage( \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pressody-records/packages' );
		$composer_version_parser = new ComposerVersionParser( new VersionParser() );
		$composer_client         = new ComposerClient();
		$logger                  = new NullIO();
		$queue                   = new ActionQueue();

		$hasher          = new StringHashes();
		$readme_parser   = new WordPressReadmeParser();
		$package_manager = new PackageManager( $composer_client, $composer_version_parser, $readme_parser, $logger, $hasher, $queue );

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
		$package = $this->builder->set_installed( true )->set_installed_version( $invalid )->build();

		$this->assertSame( '', $package->installed_version );
	}

	public function test_with_package() {
		$expected                    = new class extends LocalBasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->is_installed      = true;
		$expected->directory         = 'directory/';
		$expected->installed_version = '2.0.0';

		$package = $this->builder->with_package( $expected )->build();

		$this->assertSame( $expected->is_installed, $package->is_installed );
		$this->assertSame( $expected->directory, $package->directory );
		$this->assertSame( $expected->installed_version, $package->installed_version );
	}
}
