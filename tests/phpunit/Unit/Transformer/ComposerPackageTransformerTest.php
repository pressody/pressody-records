<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Unit\Transformer;

use Brain\Monkey\Functions;
use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Transformer\ComposerPackageTransformer;
use PixelgradeLT\Records\Test\Unit\TestCase;

class ComposerPackageTransformerTest extends TestCase {
	protected $package = null;
	protected $transformer = null;

	public function setUp(): void {
		parent::setUp();

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$release_manager = $this->getMockBuilder( ReleaseManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$logger = $this->getMockBuilder( Logger::class )
		               ->disableOriginalConstructor()
		               ->getMock();

		$factory = new PackageFactory( $package_manager, $release_manager, $logger );

		$this->package = $factory->create( 'plugin' )
			->set_slug( 'AcmeCode' )
			->build();

		$this->transformer = new ComposerPackageTransformer( $factory );
	}

	public function test_package_name_is_lowercased() {
		$package = $this->transformer->transform( $this->package );
		$this->assertSame( 'pixelgradelt_records/acmecode', $package->get_name() );
	}
}
