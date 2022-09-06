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

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use Pressody\Records\Client\ComposerClient;
use Pressody\Records\ComposerVersionParser;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Queue\ActionQueue;
use Pressody\Records\StringHashes;
use Pressody\Records\WordPressReadmeParser;
use Psr\Log\NullLogger;
use Pressody\Records\Archiver;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\Builder\LocalPluginBuilder;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Storage\Local as LocalStorage;
use Pressody\Records\Tests\Unit\TestCase;

class LocalPluginTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Mock the WordPress sanitize_text_field() function.
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		Functions\when( 'get_plugin_data' )->justReturn( $this->get_plugin_data() );
		Functions\when( 'get_site_transient' )->justReturn( new \stdClass() );

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

		$package = new LocalPlugin();

		$this->builder = new LocalPluginBuilder( $package, $package_manager, $release_manager, $archiver, $logger );
	}

	public function test_get_plugin_from_basename() {
		/** @var LocalPlugin $package */
		$package = $this->builder
			->from_basename( 'basic/basic.php' )
			->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( PackageTypes::PLUGIN, $package->get_type() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( 'local-plugin/basic', $package->get_source_name() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
	}

	public function test_get_plugin_from_source() {
		$package = $this->builder
			->from_basename( 'basic/basic.php' )
			->from_source( 'basic/basic.php' )
			->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( PackageTypes::PLUGIN, $package->get_type() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( 'local-plugin/basic', $package->get_source_name() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( 'Basic Plugin', $package->get_name() );
		$this->assertSame( [
			[
				'name'     => 'Basic, Inc.',
				'homepage' => 'https://example.com/',
			],
		], $package->get_authors() );
		$this->assertSame( '', $package->get_description() );
		$this->assertSame( 'https://example.com/plugin/basic/', $package->get_homepage() );
		$this->assertSame( '', $package->get_license() );
		$this->assertSame( [ 'tag0', 'tag1', 'tag2', 'tag3', ], $package->get_keywords() );
		$this->assertSame( '4.9.9', $package->get_requires_at_least_wp() );
		$this->assertSame( '5.2.1', $package->get_tested_up_to_wp() );
		$this->assertSame( '8.0.0', $package->get_requires_php() );
		$this->assertFalse( $package->is_managed() );
		$this->assertSame( WP_PLUGIN_DIR . '/basic/', $package->get_directory() );
		$this->assertSame( '1.3.1', $package->get_installed_version() );
	}

	public function test_is_single_file_plugin() {
		$package = $this->builder
			->from_basename( 'basic/basic.php' )
			->from_source( 'basic/basic.php' )
			->build();

		$this->assertFalse( $package->is_single_file() );

		$package = $this->builder
			->from_basename( 'hello.php' )
			->from_source( 'hello.php' )
			->build();

		$this->assertTrue( $package->is_single_file() );
	}

	public function test_get_files_for_single_file_plugin() {
		$package = $this->builder
			->from_basename( 'hello.php' )
			->from_source( 'hello.php' )
			->build();

		$this->assertSame( 1, count( $package->get_files() ) );
	}

	protected function get_plugin_data() {
		return [
			'Author'            => 'Basic, Inc.',
			'AuthorURI'         => 'https://example.com/',
			'PluginURI'         => 'https://example.com/plugin/basic/',
			'Name'              => 'Basic Plugin',
			'Description'       => '',
			'Version'           => '1.3.1',
			'Tags'              => 'tag2,tag0, tag3, tag3, tag1, tag3 ,   ',
			'Requires at least' => '4.9.9',
			'Tested up to'      => '5.2.1',
			'Requires PHP'      => '8.0.0',
		];
	}
}
