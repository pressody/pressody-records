<?php
declare ( strict_types=1 );

namespace PixelgradeLT\Records\Tests\Unit\PackageType;

use Composer\IO\NullIO;
use Composer\Semver\Constraint\MultiConstraint;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\Builder\ExternalBasePackageBuilder;
use PixelgradeLT\Records\PackageType\Builder\BasePackageBuilder;
use PixelgradeLT\Records\PackageType\ExternalBasePackage;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Tests\Framework\PHPUnitUtil;
use PixelgradeLT\Records\Tests\Unit\TestCase;

class ExternalPackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Provide direct getters.
		$package = new class extends ExternalBasePackage {
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

		$this->builder = new ExternalBasePackageBuilder( $package, $package_manager, $release_manager, $logger );
	}

	public function test_extends_package_builder() {

		$this->assertInstanceOf( BasePackageBuilder::class, $this->builder );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_source_constraint() {
		$expected = $this->getMockBuilder( MultiConstraint::class )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$package = $this->builder->set_source_constraint( $expected )->build();

		$this->assertSame( $expected, $package->source_constraint );
	}

	public function test_get_tags_from_readme() {
		$plugin_file = 'basic/basic.php';
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		$expected    = [ 'admin', 'htaccess', 'post', 'redirect', ];

		$method      = PHPUnitUtil::getProtectedMethod( $this->builder, 'get_tags_from_readme' );
		$readme_tags = $method->invoke( $this->builder, dirname( $plugin_path ) );

		$this->assertSame( $expected, $readme_tags );
	}
}
