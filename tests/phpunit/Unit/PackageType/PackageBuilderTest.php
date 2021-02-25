<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Unit\PackageType;

use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\Builder\PackageBuilder;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Test\Unit\TestCase;

class PackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

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

		$logger = $this->getMockBuilder( Logger::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$this->builder = new PackageBuilder( $package, $package_manager, $release_manager, $logger );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_author() {
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

	public function test_directory() {
		$expected = 'directory';
		$package  = $this->builder->set_directory( $expected )->build();

		$this->assertSame( $expected . '/', $package->directory );
	}

	public function test_homepage() {
		$expected = 'https://www.cedaro.com/';
		$package  = $this->builder->set_homepage( $expected )->build();

		$this->assertSame( $expected, $package->homepage );
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

	public function test_name() {
		$expected = 'PixelgradeLT Records';
		$package  = $this->builder->set_name( $expected )->build();

		$this->assertSame( $expected, $package->name );
	}

	public function test_slug() {
		$expected = 'pixelgradelt_records';
		$package  = $this->builder->set_slug( $expected )->build();

		$this->assertSame( $expected, $package->slug );
	}

	public function test_type() {
		$expected = 'plugin';
		$package  = $this->builder->set_type( $expected )->build();

		$this->assertSame( $expected, $package->type );
	}
}
