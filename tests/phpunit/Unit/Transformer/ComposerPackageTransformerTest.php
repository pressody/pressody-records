<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Unit\Transformer;

use Brain\Monkey\Functions;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Transformer\ComposerPackageTransformer;
use PixelgradeLT\Records\Test\Unit\TestCase;

class ComposerPackageTransformerTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		$manager = $this->getMockBuilder( ReleaseManager::class )
			->disableOriginalConstructor()
			->getMock();

		$factory = new PackageFactory( $manager );

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
