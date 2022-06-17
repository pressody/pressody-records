<?php
declare ( strict_types=1 );

namespace Pressody\Records\Tests\Unit\PackageType\Builder;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use Pressody\Records\Archiver;
use Pressody\Records\Client\ComposerClient;
use Pressody\Records\ComposerVersionParser;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\BasePackage;
use Pressody\Records\PackageType\Builder\BasePackageBuilder;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Queue\ActionQueue;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Storage\Local as LocalStorage;
use Pressody\Records\StringHashes;
use Pressody\Records\Tests\Unit\TestCase;
use Pressody\Records\WordPressReadmeParser;
use Psr\Log\NullLogger;

class BasePackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Mock the WordPress sanitize_text_field() function.
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		// Mock the WordPress get_post_status() function.
		Functions\when( 'get_post_status' )->justReturn( 'publish' );

		// Provide direct getters.
		$package = new class extends BasePackage {
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

		$this->builder = new BasePackageBuilder( $package, $package_manager, $release_manager, $archiver, $logger );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_name() {
		$expected = 'Pressody Records';
		$package  = $this->builder->set_name( $expected )->build();

		$this->assertSame( $expected, $package->name );
	}

	public function test_type() {
		$expected = PackageTypes::PLUGIN;
		$package  = $this->builder->set_type( $expected )->build();

		$this->assertSame( $expected, $package->type );
	}

	public function test_source_type() {
		$expected = 'local.source';
		$package  = $this->builder->set_source_type( $expected )->build();

		$this->assertSame( $expected, $package->source_type );
	}

	public function test_source_name() {
		$expected = 'source/name';
		$package  = $this->builder->set_source_name( $expected )->build();

		$this->assertSame( $expected, $package->source_name );
	}

	public function test_slug() {
		$expected = 'pressody_records';
		$package  = $this->builder->set_slug( $expected )->build();

		$this->assertSame( $expected, $package->slug );
	}

	public function test_authors() {
		$expected = [
			[
				'name'     => 'Pressody',
				'email'    => 'contact@pixelgrade.com',
				'homepage' => 'https://pressody.com',
				'role'     => 'Maker',
			],
		];
		$package  = $this->builder->set_authors( $expected )->build();

		$this->assertSame( $expected, $package->authors );
	}

	public function test_string_authors() {
		$expected = [
			[ 'name' => 'Pressody', ],
			[ 'name' => 'Wordpressorg', ],
		];

		$authors = [ 'Pressody ', ' Wordpressorg', '' ];

		$package = $this->builder->set_authors( $authors )->build();

		$this->assertSame( $expected, $package->authors );
	}

	public function test_clean_authors() {
		$expected = [
			[ 'name' => 'Pressody', ],
			[ 'name' => 'Wordpressorg', ],
		];

		$authors = [
			'Pressody',
			[],
			'Wordpressorg',
			'',
			[ 'name' => '' ],
			[ 'homepage' => 'https://pressody.com' ],
		];

		$package = $this->builder->set_authors( $authors )->build();

		$this->assertSame( $expected, $package->authors );
	}

	public function test_description() {
		$expected = 'A package description.';
		$package  = $this->builder->set_description( $expected )->build();

		$this->assertSame( $expected, $package->description );
	}

	public function test_keywords_as_string() {
		$keywords_comma_string = 'key1,key0, key2, key3   , ,,';

		// We expect the keywords to be alphabetically sorted.
		$expected = [ 'key0', 'key1', 'key2', 'key3', ];
		$package  = $this->builder->set_keywords( $keywords_comma_string )->build();

		$this->assertSame( $expected, $package->keywords );
	}

	public function test_keywords_as_array() {
		$keywords = [ 'first' => 'key2', 'key3 ', 'some' => 'key0', ' key1 ', ];

		// We expect the keywords to be alphabetically sorted.
		$expected = [ 'key0', 'key1', 'key2', 'key3', ];
		$package  = $this->builder->set_keywords( $keywords )->build();

		$this->assertSame( $expected, $package->keywords );
	}

	public function test_clean_keywords() {
		$keywords = [ 'first' => 'key2', '', 'key3 ', false, 'some' => 'key0', ' key1 ', ];

		// We expect the keywords to be alphabetically sorted.
		$expected = [ 'key0', 'key1', 'key2', 'key3', ];
		$package  = $this->builder->set_keywords( $keywords )->build();

		$this->assertSame( $expected, $package->keywords );
	}

	public function test_homepage() {
		$expected = 'https://www.cedaro.com/';
		$package  = $this->builder->set_homepage( $expected )->build();

		$this->assertSame( $expected, $package->homepage );
	}

	public function test_license_standard() {
		$expected = 'GPL-2.0-only';
		$package  = $this->builder->set_license( $expected )->build();

		$this->assertSame( $expected, $package->license );
	}

	public function test_license_nonstandard() {
		// Some widely used licenses should be normalized to the SPDX format.
		$license_string = 'GNU GPLv2 or later';
		$expected       = 'GPL-2.0-or-later';
		$package        = $this->builder->set_license( $license_string )->build();

		$this->assertSame( $expected, $package->license );
	}

	public function test_license_notknown() {
		// This license won't be normalized in the SPDX format. It will be kept the same.
		$expected = 'Some license 3.0';
		$package  = $this->builder->set_license( $expected )->build();

		$this->assertSame( $expected, $package->license );
	}

	public function test_is_managed() {
		$expected = true;
		$package  = $this->builder->set_is_managed( $expected )->build();

		$this->assertSame( $expected, $package->is_managed );
	}

	public function test_managed_post_id() {
		$expected = 123;
		$package  = $this->builder->set_managed_post_id( $expected )->build();

		$this->assertSame( $expected, $package->managed_post_id );
	}

	public function test_managed_post_id_hash() {
		$expected = 'we45df';
		$package  = $this->builder->set_managed_post_id_hash( $expected )->build();

		$this->assertSame( $expected, $package->managed_post_id_hash );
	}

	public function test_visibility() {
		$expected = 'draft';
		$package  = $this->builder->set_visibility( $expected )->build();

		$this->assertSame( $expected, $package->visibility );
	}

	public function test_composer_require() {
		$expected = [ 'test/test' => '*' ];
		$package  = $this->builder->set_composer_require( $expected )->build();

		$this->assertSame( $expected, $package->composer_require );
	}

	public function test_from_package_data() {
		$expected['name']                 = 'Plugin Name';
		$expected['slug']                 = 'slug';
		$expected['type']                 = PackageTypes::PLUGIN;
		$expected['source_type']          = 'local.plugin';
		$expected['source_name']          = 'local-plugin/slug';
		$expected['authors']              = [
			[
				'name'     => 'Name',
				'email'    => 'email@example.com',
				'homepage' => 'https://pressody.com',
				'role'     => 'Dev',
			],
		];
		$expected['homepage']             = 'https://pressody.com';
		$expected['description']          = 'Some description.';
		$expected['keywords']             = [ 'keyword' ];
		$expected['license']              = 'GPL-2.0-or-later';
		$expected['requires_at_least_wp'] = '6.0.0';
		$expected['tested_up_to_wp']      = '6.6.0';
		$expected['requires_php']         = '6.6.4';
		$expected['is_managed']           = true;
		$expected['managed_post_id']      = 234;
		$expected['managed_post_id_hash'] = 'e163a'; // This is the actual hash of 234.
		$expected['visibility']           = 'draft';
		$expected['composer_require']     = [ 'test/test' => '*' ];
		$expected['required_packages']    = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];
		$expected['replaced_packages']    = [
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 456,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$package = $this->builder->from_package_data( $expected )->build();

		$this->assertSame( $expected['name'], $package->name );
		$this->assertSame( $expected['slug'], $package->slug );
		$this->assertSame( $expected['type'], $package->type );
		$this->assertSame( $expected['source_type'], $package->source_type );
		$this->assertSame( $expected['source_name'], $package->source_name );
		$this->assertSame( $expected['authors'], $package->authors );
		$this->assertSame( $expected['homepage'], $package->homepage );
		$this->assertSame( $expected['description'], $package->description );
		$this->assertSame( $expected['keywords'], $package->keywords );
		$this->assertSame( $expected['license'], $package->license );
		$this->assertSame( $expected['requires_at_least_wp'], $package->requires_at_least_wp );
		$this->assertSame( $expected['tested_up_to_wp'], $package->tested_up_to_wp );
		$this->assertSame( $expected['requires_php'], $package->requires_php );
		$this->assertSame( $expected['is_managed'], $package->is_managed );
		$this->assertSame( $expected['managed_post_id'], $package->managed_post_id );
		$this->assertSame( $expected['managed_post_id_hash'], $package->managed_post_id_hash );
		$this->assertSame( $expected['visibility'], $package->visibility );
		$this->assertSame( $expected['composer_require'], $package->composer_require );
		$this->assertSame( $expected['required_packages'], $package->required_packages );
		$this->assertSame( $expected['replaced_packages'], $package->replaced_packages );
	}

	public function test_from_package_data_do_not_overwrite() {
		$expected                       = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name                 = 'Theme';
		$expected->slug                 = 'theme-slug';
		$expected->type                 = PackageTypes::THEME;
		$expected->source_type          = 'local.theme';
		$expected->source_name          = 'local-theme/slug';
		$expected->authors              = [
			[
				'name' => 'Some Theme Author',
			],
		];
		$expected->homepage             = 'https://getpressody.com';
		$expected->description          = 'Some awesome description.';
		$expected->keywords             = [ 'keyword1', 'keyword2' ];
		$expected->license              = 'GPL-2.0-only';
		$expected->requires_at_least_wp = '5.0.0';
		$expected->tested_up_to_wp      = '5.6.0';
		$expected->requires_php         = '5.6.4';
		$expected->managed_post_id      = 123;
		$expected->managed_post_id_hash = 'e163a';
		$expected->visibility           = 'draft';
		$expected->composer_require     = [ 'test/test' => '*' ];

		$package_data['name']                 = 'Plugin Name';
		$package_data['slug']                 = 'slug';
		$package_data['type']                 = PackageTypes::PLUGIN;
		$package_data['source_type']          = 'local.plugin';
		$package_data['source_name']          = 'local-plugin/slug';
		$package_data['authors']              = [];
		$package_data['homepage']             = 'https://pressody.com';
		$package_data['description']          = 'Some description.';
		$package_data['keywords']             = [ 'keyword' ];
		$package_data['license']              = 'GPL-2.0-or-later';
		$package_data['requires_at_least_wp'] = '6.0.0';
		$package_data['tested_up_to_wp']      = '6.6.0';
		$package_data['requires_php']         = '6.6.4';
		$package_data['managed_post_id']      = 234;
		$package_data['managed_post_id_hash'] = 'sdfsdf11';
		$package_data['visibility']           = 'public';
		$package_data['composer_require']     = [ 'test2/test2' => '*' ];

		$package = $this->builder->with_package( $expected )->from_package_data( $package_data )->build();

		$this->assertSame( $expected->name, $package->name );
		$this->assertSame( $expected->slug, $package->slug );
		$this->assertSame( $expected->type, $package->type );
		$this->assertSame( $expected->source_type, $package->source_type );
		$this->assertSame( $expected->source_name, $package->source_name );
		$this->assertSame( $expected->authors, $package->authors );
		$this->assertSame( $expected->homepage, $package->homepage );
		$this->assertSame( $expected->description, $package->description );
		$this->assertSame( $expected->keywords, $package->keywords );
		$this->assertSame( $expected->license, $package->license );
		$this->assertSame( $expected->requires_at_least_wp, $package->requires_at_least_wp );
		$this->assertSame( $expected->tested_up_to_wp, $package->tested_up_to_wp );
		$this->assertSame( $expected->requires_php, $package->requires_php );
		$this->assertSame( $expected->managed_post_id, $package->managed_post_id );
		$this->assertSame( $expected->managed_post_id_hash, $package->managed_post_id_hash );
		$this->assertSame( $expected->visibility, $package->visibility );
		$this->assertSame( $expected->composer_require, $package->composer_require );
	}

	public function test_from_package_data_merge_required_packages() {
		$initial_package                    = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$initial_package->name              = 'Theme';
		$initial_package->slug              = 'theme-slug';
		$initial_package->required_packages = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];

		$package_data['name']              = 'Plugin Name';
		$package_data['slug']              = 'slug';
		$package_data['required_packages'] = [
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$expected = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$package = $this->builder->with_package( $initial_package )->from_package_data( $package_data )->build();

		$this->assertSame( $expected, $package->required_packages );
	}

	public function test_from_package_data_merge_replaced_packages() {
		$initial_package                    = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$initial_package->name              = 'Theme';
		$initial_package->slug              = 'theme-slug';
		$initial_package->replaced_packages = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];

		$package_data['name']              = 'Plugin Name';
		$package_data['slug']              = 'slug';
		$package_data['replaced_packages'] = [
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$expected = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$package = $this->builder->with_package( $initial_package )->from_package_data( $package_data )->build();

		$this->assertSame( $expected, $package->replaced_packages );
	}

	public function test_from_package_data_merge_overwrite_required_packages() {
		$initial_package                    = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$initial_package->name              = 'Theme';
		$initial_package->slug              = 'theme-slug';
		$initial_package->required_packages = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];

		$package_data['name']              = 'Plugin Name';
		$package_data['slug']              = 'slug';
		$package_data['required_packages'] = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id3' => [
				'composer_package_name' => 'pixelgrade/test3',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test3',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id3',
			],
		];

		$expected = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id3' => [
				'composer_package_name' => 'pixelgrade/test3',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test3',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id3',
			],
		];

		$package = $this->builder->with_package( $initial_package )->from_package_data( $package_data )->build();

		$this->assertSame( $expected, $package->required_packages );
	}

	public function test_from_package_data_merge_overwrite_replaced_packages() {
		$initial_package                    = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$initial_package->name              = 'Theme';
		$initial_package->slug              = 'theme-slug';
		$initial_package->replaced_packages = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];

		$package_data['name']              = 'Plugin Name';
		$package_data['slug']              = 'slug';
		$package_data['replaced_packages'] = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id3' => [
				'composer_package_name' => 'pixelgrade/test3',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test3',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id3',
			],
		];

		$expected = [
			'some_pseudo_id'  => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id',
			],
			'some_pseudo_id3' => [
				'composer_package_name' => 'pixelgrade/test3',
				'version_range'         => '1.1',
				'stability'             => 'dev',
				'source_name'           => 'local-plugin/test3',
				'managed_post_id'       => 234,
				'pseudo_id'             => 'some_pseudo_id3',
			],
		];

		$package = $this->builder->with_package( $initial_package )->from_package_data( $package_data )->build();

		$this->assertSame( $expected, $package->replaced_packages );
	}

	public function test_from_header_data_plugin() {
		$expected = [
			'Name'              => 'Plugin Name',
			'Author'            => 'Author',
			'AuthorURI'         => 'https://home.org',
			'PluginURI'         => 'https://pressody.com',
			'Description'       => 'Some description.',
			'Tags'              => [ 'keyword1', 'keyword2' ],
			'License'           => 'GPL-2.0-or-later',
			'Requires at least' => '4.9.9',
			'Tested up to'      => '4.9.9',
			'Requires PHP'      => '8.0.0',
		];

		$package = $this->builder->from_header_data( $expected )->build();

		$this->assertSame( $expected['Name'], $package->name );
		$this->assertSame( $expected['PluginURI'], $package->homepage );
		$this->assertSame( [
			[
				'name'     => $expected['Author'],
				'homepage' => $expected['AuthorURI'],
			],
		], $package->authors );
		$this->assertSame( $expected['Description'], $package->description );
		$this->assertSame( $expected['Tags'], $package->keywords );
		$this->assertSame( $expected['Requires at least'], $package->requires_at_least_wp );
		$this->assertSame( $expected['Tested up to'], $package->tested_up_to_wp );
		$this->assertSame( $expected['Requires PHP'], $package->requires_php );
	}

	public function test_from_header_data_theme() {
		$expected = [
			'Name'              => 'Plugin Name',
			'Author'            => 'Author',
			'AuthorURI'         => 'https://home.org',
			'ThemeURI'          => 'https://pressody.com',
			'Description'       => 'Some description.',
			'Tags'              => [ 'keyword1', 'keyword2' ],
			'License'           => 'GPL-2.0-or-later',
			'Requires at least' => '4.9.9',
			'Tested up to'      => '4.9.9',
			'Requires PHP'      => '8.0.0',
		];

		$package = $this->builder->from_header_data( $expected )->build();

		$this->assertSame( $expected['Name'], $package->name );
		$this->assertSame( $expected['ThemeURI'], $package->homepage );
		$this->assertSame( [
			[
				'name'     => $expected['Author'],
				'homepage' => $expected['AuthorURI'],
			],
		], $package->authors );
		$this->assertSame( $expected['Description'], $package->description );
		$this->assertSame( $expected['Tags'], $package->keywords );
		$this->assertSame( $expected['Requires at least'], $package->requires_at_least_wp );
		$this->assertSame( $expected['Tested up to'], $package->tested_up_to_wp );
		$this->assertSame( $expected['Requires PHP'], $package->requires_php );
	}

	public function test_from_header_data_do_not_overwrite() {
		$expected                       = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name                 = 'Plugin';
		$expected->authors              = [
			[
				'name' => 'Some Author',
			],
		];
		$expected->homepage             = 'https://getpressody.com';
		$expected->description          = 'Some awesome description.';
		$expected->keywords             = [ 'keyword' ];
		$expected->license              = 'GPL-2.0-only';
		$expected->requires_at_least_wp = '5.0.0';
		$expected->tested_up_to_wp      = '5.6.0';
		$expected->requires_php         = '5.6.4';

		$header_data = [
			'Name'              => 'Plugin Name',
			'Author'            => 'Author',
			'AuthorURI'         => 'https://home.org',
			'ThemeURI'          => 'https://pressody.com',
			'Description'       => 'Some description.',
			'Tags'              => [ 'keyword1', 'keyword2' ],
			'License'           => 'GPL-2.0-or-later',
			'Requires at least' => '4.9.9',
			'Tested up to'      => '4.9.9',
			'Requires PHP'      => '8.0.0',
		];

		$package = $this->builder->with_package( $expected )->from_header_data( $header_data )->build();

		$this->assertSame( $expected->name, $package->name );
		$this->assertSame( $expected->authors, $package->authors );
		$this->assertSame( $expected->homepage, $package->homepage );
		$this->assertSame( $expected->description, $package->description );
		$this->assertSame( $expected->keywords, $package->keywords );
		$this->assertSame( $expected->license, $package->license );
		$this->assertSame( $expected->requires_at_least_wp, $package->requires_at_least_wp );
		$this->assertSame( $expected->tested_up_to_wp, $package->tested_up_to_wp );
		$this->assertSame( $expected->requires_php, $package->requires_php );
	}

	public function test_from_readme_data() {
		$readme_data = [
			'name'              => 'Plugin Name',
			'contributors'      => [ 'wordpressdotorg', 'pixelgrade', ],
			'short_description' => 'Some description.',
			'tags'              => [ 'keyword1', 'keyword2' ],
			'license'           => 'GPL-2.0-or-later',
			'requires_at_least' => '4.9.9',
			'tested_up_to'      => '5.7',
			'requires_php'      => '7',
			'stable_tag'        => '1.0.2',
		];

		$package = $this->builder->from_readme_data( $readme_data )->build();

		$this->assertSame( $readme_data['name'], $package->name );
		$this->assertSame( [
			[ 'name' => 'wordpressdotorg', ],
			[ 'name' => 'pixelgrade', ],
		], $package->authors );
		$this->assertSame( $readme_data['short_description'], $package->description );
		$this->assertSame( $readme_data['tags'], $package->keywords );
		$this->assertSame( $readme_data['license'], $package->license );
		$this->assertSame( $readme_data['requires_at_least'], $package->requires_at_least_wp );
		$this->assertSame( $readme_data['tested_up_to'], $package->tested_up_to_wp );
		$this->assertSame( $readme_data['requires_php'], $package->requires_php );
	}

	public function test_from_readme_data_do_not_overwrite() {
		$expected                       = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name                 = 'Plugin';
		$expected->authors              = [
			[
				'name' => 'Some Author',
			],
		];
		$expected->homepage             = 'https://getpressody.com';
		$expected->description          = 'Some awesome description.';
		$expected->keywords             = [ 'keyword' ];
		$expected->license              = 'GPL-2.0-only';
		$expected->requires_at_least_wp = '5.0.0';
		$expected->tested_up_to_wp      = '5.6.0';
		$expected->requires_php         = '5.6.4';

		$readme_data = [
			'name'              => 'Plugin Name',
			'contributors'      => [ 'wordpressdotorg', 'pixelgrade', ],
			'short_description' => 'Some description.',
			'tags'              => [ 'keyword1', 'keyword2' ],
			'license'           => 'GPL-2.0-or-later',
			'requires_at_least' => '4.9.9',
			'tested_up_to'      => '5.7',
			'requires_php'      => '7',
			'stable_tag'        => '1.0.2',
		];

		$package = $this->builder->with_package( $expected )->from_readme_data( $readme_data )->build();

		$this->assertSame( $expected->name, $package->name );
		$this->assertSame( $expected->authors, $package->authors );
		$this->assertSame( $expected->homepage, $package->homepage );
		$this->assertSame( $expected->description, $package->description );
		$this->assertSame( $expected->keywords, $package->keywords );
		$this->assertSame( $expected->license, $package->license );
		$this->assertSame( $expected->requires_at_least_wp, $package->requires_at_least_wp );
		$this->assertSame( $expected->tested_up_to_wp, $package->tested_up_to_wp );
		$this->assertSame( $expected->requires_php, $package->requires_php );
	}

	public function test_with_package() {
		$expected                       = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}

			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name                 = 'Plugin Name';
		$expected->slug                 = 'slug';
		$expected->type                 = PackageTypes::PLUGIN;
		$expected->source_type          = 'local.plugin';
		$expected->source_name          = 'local-plugin/slug';
		$expected->authors              = [];
		$expected->homepage             = 'https://pressody.com';
		$expected->description          = 'Some description.';
		$expected->keywords             = [ 'keyword' ];
		$expected->license              = 'GPL-2.0-or-later';
		$expected->requires_at_least_wp = '5.0.0';
		$expected->tested_up_to_wp      = '5.6.0';
		$expected->requires_php         = '5.6.4';
		$expected->is_managed           = true;
		$expected->managed_post_id      = 123;
		$expected->managed_post_id_hash = 'asdasd12';
		$expected->visibility           = 'draft';
		$expected->composer_require     = [ 'test/test' => '*' ];
		$expected->required_packages    = [
			'some_pseudo_id' => [
				'composer_package_name' => 'pixelgrade/test',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id',
			],
		];
		$expected->replaced_packages    = [
			'some_pseudo_id2' => [
				'composer_package_name' => 'pixelgrade/test2',
				'version_range'         => '*',
				'stability'             => 'stable',
				'source_name'           => 'local-plugin/test2',
				'managed_post_id'       => 123,
				'pseudo_id'             => 'some_pseudo_id2',
			],
		];

		$package = $this->builder->with_package( $expected )->build();

		$this->assertSame( $expected->name, $package->name );
		$this->assertSame( $expected->slug, $package->slug );
		$this->assertSame( $expected->type, $package->type );
		$this->assertSame( $expected->source_type, $package->source_type );
		$this->assertSame( $expected->source_name, $package->source_name );
		$this->assertSame( $expected->authors, $package->authors );
		$this->assertSame( $expected->homepage, $package->homepage );
		$this->assertSame( $expected->description, $package->description );
		$this->assertSame( $expected->keywords, $package->keywords );
		$this->assertSame( $expected->license, $package->license );
		$this->assertSame( $expected->requires_at_least_wp, $package->requires_at_least_wp );
		$this->assertSame( $expected->tested_up_to_wp, $package->tested_up_to_wp );
		$this->assertSame( $expected->requires_php, $package->requires_php );
		$this->assertSame( $expected->is_managed, $package->is_managed );
		$this->assertSame( $expected->managed_post_id, $package->managed_post_id );
		$this->assertSame( $expected->managed_post_id_hash, $package->managed_post_id_hash );
		$this->assertSame( $expected->visibility, $package->visibility );
		$this->assertSame( $expected->composer_require, $package->composer_require );
		$this->assertSame( $expected->required_packages, $package->required_packages );
		$this->assertSame( $expected->replaced_packages, $package->replaced_packages );
	}
}
