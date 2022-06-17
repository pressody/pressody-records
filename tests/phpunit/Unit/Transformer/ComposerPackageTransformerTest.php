<?php
declare ( strict_types = 1 );

namespace Pressody\Records\Tests\Unit\Transformer;

use Composer\IO\NullIO;
use Pressody\Records\Archiver;
use Pressody\Records\PackageFactory;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Transformer\ComposerPackageTransformer;
use Pressody\Records\Tests\Unit\TestCase;
use Psr\Log\NullLogger;

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

		$archiver                = new Archiver( new NullLogger() );
		$logger = new NullIO();

		$factory = new PackageFactory( $package_manager, $release_manager, $archiver, $logger );

		$this->package = $factory->create( PackageTypes::PLUGIN )
			->set_slug( 'AcmeCode' )
			->build();

		$this->transformer = new ComposerPackageTransformer( $factory );
	}

	public function test_package_name_is_lowercased() {
		$package = $this->transformer->transform( $this->package );
		$this->assertSame( 'pressody-records/acmecode', $package->get_name() );
	}
}
