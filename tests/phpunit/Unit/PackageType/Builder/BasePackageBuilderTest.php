<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Tests\Unit\PackageType\Builder;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\Builder\BasePackageBuilder;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Tests\Unit\TestCase;

class BasePackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Mock the WordPress sanitize_text_field() function.
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		// Provide direct getters.
		$package = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
		};


		$package_manager = $this->getMockBuilder( PackageManager::class )
		                ->disableOriginalConstructor()
		                ->getMock();

		$release_manager = $this->getMockBuilder( ReleaseManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$logger = new NullIO();

		$this->builder = new BasePackageBuilder( $package, $package_manager, $release_manager, $logger );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_name() {
		$expected = 'PixelgradeLT Records';
		$package  = $this->builder->set_name( $expected )->build();

		$this->assertSame( $expected, $package->name );
	}

	public function test_type() {
		$expected = 'plugin';
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
		$expected = 'pixelgradelt_records';
		$package  = $this->builder->set_slug( $expected )->build();

		$this->assertSame( $expected, $package->slug );
	}

	public function test_authors() {
		$expected = [
			[
				'name'     => 'Pixelgrade',
				'email'    => 'contact@pixelgrade.com',
				'homepage' => 'https://pixelgrade.com',
				'role'     => 'Maker',
			]
		];
		$package  = $this->builder->set_authors( $expected )->build();

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
		$expected = 'GPL-2.0-or-later';
		$package  = $this->builder->set_license( $license_string )->build();

		$this->assertSame( $expected, $package->license );
	}

	public function test_is_managed() {
		$expected = true;
		$package  = $this->builder->set_is_managed( $expected )->build();

		$this->assertSame( $expected, $package->is_managed );
	}

	public function test_from_package_data() {
		$expected['name'] = 'Plugin Name';
		$expected['slug'] = 'slug';
		$expected['type'] = 'plugin';
		$expected['source_type'] = 'local.plugin';
		$expected['source_name'] = 'local-plugin/slug';
		$expected['authors'] = [];
		$expected['homepage'] = 'https://pixelgrade.com';
		$expected['description'] = 'Some description.';
		$expected['keywords'] = ['keyword'];
		$expected['license'] = 'GPL-2.0-or-later';

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
	}

	public function test_from_package_data_do_not_overwrite() {
		$expected = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name = 'Theme';
		$expected->slug = 'theme-slug';
		$expected->type = 'theme';
		$expected->source_type = 'local.theme';
		$expected->source_name = 'local-theme/slug';
		$expected->authors = [
			[
				'name' => 'Some Theme Author',
			]
		];
		$expected->homepage = 'https://pixelgradelt.com';
		$expected->description = 'Some awesome description.';
		$expected->keywords = ['keyword1', 'keyword2'];
		$expected->license = 'GPL-2.0-only';

		$package_data['name'] = 'Plugin Name';
		$package_data['slug'] = 'slug';
		$package_data['type'] = 'plugin';
		$package_data['source_type'] = 'local.plugin';
		$package_data['source_name'] = 'local-plugin/slug';
		$package_data['authors'] = [];
		$package_data['homepage'] = 'https://pixelgrade.com';
		$package_data['description'] = 'Some description.';
		$package_data['keywords'] = ['keyword'];
		$package_data['license'] = 'GPL-2.0-or-later';

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
	}

	public function test_from_header_data_plugin() {
		$expected = [
			'Name'        => 'Plugin Name',
			'Author'      => 'Author',
			'AuthorURI'   => 'https://home.org',
			'PluginURI'    => 'https://pixelgrade.com',
			'Description' => 'Some description.',
			'Tags'        => [ 'keyword1', 'keyword2' ],
			'License'     => 'GPL-2.0-or-later',
		];

		$package = $this->builder->from_header_data( $expected )->build();

		$this->assertSame( $expected['Name'], $package->name );
		$this->assertSame( $expected['PluginURI'], $package->homepage );
		$this->assertSame( [
			[
				'name'     => $expected['Author'],
				'homepage' => $expected[ 'AuthorURI'],
			],
		], $package->authors );
		$this->assertSame( $expected['Description'], $package->description );
		$this->assertSame( $expected['Tags'], $package->keywords );
	}

	public function test_from_header_data_theme() {
		$expected = [
			'Name'        => 'Plugin Name',
			'Author'      => 'Author',
			'AuthorURI'   => 'https://home.org',
			'ThemeURI'    => 'https://pixelgrade.com',
			'Description' => 'Some description.',
			'Tags'        => [ 'keyword1', 'keyword2' ],
			'License'     => 'GPL-2.0-or-later',
		];

		$package = $this->builder->from_header_data( $expected )->build();

		$this->assertSame( $expected['Name'], $package->name );
		$this->assertSame( $expected['ThemeURI'], $package->homepage );
		$this->assertSame( [
			[
				'name'     => $expected['Author'],
				'homepage' => $expected[ 'AuthorURI'],
			],
		], $package->authors );
		$this->assertSame( $expected['Description'], $package->description );
		$this->assertSame( $expected['Tags'], $package->keywords );
	}

	public function test_from_header_data_do_not_overwrite() {
		$expected = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name = 'Plugin';
		$expected->authors = [
			[
				'name' => 'Some Author',
			]
		];
		$expected->homepage = 'https://pixelgradelt.com';
		$expected->description = 'Some awesome description.';
		$expected->keywords = ['keyword'];
		$expected->license = 'GPL-2.0-only';

		$header_data = [
			'Name'        => 'Plugin Name',
			'Author'      => 'Author',
			'AuthorURI'   => 'https://home.org',
			'ThemeURI'    => 'https://pixelgrade.com',
			'Description' => 'Some description.',
			'Tags'        => [ 'keyword1', 'keyword2' ],
			'License'     => 'GPL-2.0-or-later',
		];

		$package = $this->builder->with_package( $expected )->from_header_data( $header_data )->build();

		$this->assertSame( $expected->name, $package->name );
		$this->assertSame( $expected->authors, $package->authors );
		$this->assertSame( $expected->homepage, $package->homepage );
		$this->assertSame( $expected->description, $package->description );
		$this->assertSame( $expected->keywords, $package->keywords );
		$this->assertSame( $expected->license, $package->license );
	}

	public function test_with_package() {
		$expected = new class extends BasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->name = 'Plugin Name';
		$expected->slug = 'slug';
		$expected->type = 'plugin';
		$expected->source_type = 'local.plugin';
		$expected->source_name = 'local-plugin/slug';
		$expected->authors = [];
		$expected->homepage = 'https://pixelgrade.com';
		$expected->description = 'Some description.';
		$expected->keywords = ['keyword'];
		$expected->license = 'GPL-2.0-or-later';

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
	}
}
