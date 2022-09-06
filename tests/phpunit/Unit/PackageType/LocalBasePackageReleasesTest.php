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

namespace Pressody\Records\Tests\Unit\PackageType;

use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use Pressody\Records\Client\ComposerClient;
use Pressody\Records\ComposerVersionParser;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\Builder\LocalBasePackageBuilder;
use Pressody\Records\PackageType\LocalBasePackage;
use Pressody\Records\Queue\ActionQueue;
use Pressody\Records\StringHashes;
use Pressody\Records\WordPressReadmeParser;
use Psr\Log\NullLogger;
use Pressody\Records\Archiver;
use Pressody\Records\Exception\InvalidReleaseVersion;
use Pressody\Records\Exception\PackageNotInstalled;
use Pressody\Records\Release;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Storage\Local as LocalStorage;
use Pressody\Records\Tests\Unit\TestCase;

class LocalBasePackageReleasesTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		$archiver                = new Archiver( new NullLogger() );
		$storage                 = new LocalStorage( \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pressody-records/packages' );
		$package                 = new LocalBasePackage();
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

	public function test_package_has_no_releases() {
		$package = $this->builder->build();
		$this->assertFalse( $package->has_releases() );
	}

	public function test_package_has_releases() {
		$package = $this->builder->add_release( '1.0.0' )->build();
		$this->assertTrue( $package->has_releases() );
	}

	public function test_get_release_by_version() {
		$version = '1.0.0';
		$package = $this->builder->add_release( $version )->build();

		$this->assertSame( 1, count( $package->get_releases() ) );

		$release = $package->get_release( $version );
		$this->assertInstanceOf( Release::class, $release );
		$this->assertSame( $version, $release->get_version() );
	}

	public function test_get_installed_release() {
		$installed_version = '0.4.0';
		$latest_version    = '1.0.0';

		$package = $this->builder
			->set_installed( true )
			->set_installed_version( $installed_version )
			->add_release( $installed_version )
			->add_release( $latest_version )
			->build();

		$release = $package->get_installed_release();
		$this->assertInstanceOf( Release::class, $release );
		$this->assertTrue( $package->is_installed_release( $release ) );

		$release = $package->get_release( $latest_version );
		$this->assertFalse( $package->is_installed_release( $release ) );
	}

	public function test_get_latest_release() {
		$version = '0.4.0';
		$package = $this->builder
			->add_release( '0.3.2' )
			->add_release( $version )
			->add_release( '0.3.0' )
			->build();

		$release = $package->get_latest_release();

		$this->assertInstanceOf( Release::class, $release );
		$this->assertSame( $version, $release->get_version() );
	}

	public function test_is_update_available() {
		$installed_version = '0.4.0';
		$latest_version    = '1.0.0';

		$package = $this->builder
			->set_installed( true )
			->set_installed_version( $installed_version )
			->add_release( $installed_version )
			->add_release( $latest_version )
			->build();

		$this->assertSame( $installed_version, $package->get_installed_version() );
		$this->assertSame( $latest_version, $package->get_latest_version() );
		$this->assertTrue( $package->is_update_available() );
	}

	public function test_get_latest_release_throws_exception_when_there_are_no_releases() {
		$this->expectException( InvalidReleaseVersion::class );

		$package = $this->builder->build();
		$package->get_latest_release();
	}

	public function test_get_unknown_release_throws_exception() {
		$this->expectException( InvalidReleaseVersion::class );

		$package = $this->builder->build();
		$package->get_release( '0.4.0' );
	}

	public function test_get_not_installed_release_throws_exception() {
		$this->expectException( PackageNotInstalled::class );

		$package = $this->builder->build();
		$package->get_installed_release();
	}
}
