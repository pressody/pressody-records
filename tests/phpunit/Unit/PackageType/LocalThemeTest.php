<?php
declare ( strict_types=1 );

namespace PixelgradeLT\Records\Tests\Unit\PackageType;

use Brain\Monkey\Functions;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use PixelgradeLT\Records\ComposerVersionParser;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\Builder\LocalThemeBuilder;
use PixelgradeLT\Records\PackageType\LocalTheme;
use Psr\Log\NullLogger;
use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Storage\Local as LocalStorage;
use PixelgradeLT\Records\Tests\Unit\TestCase;

class LocalThemeTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		$archiver                = new Archiver( new NullLogger() );
		$storage                 = new LocalStorage( PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages' );
		$composer_version_parser = new ComposerVersionParser( new VersionParser() );

		$package_manager = $this->getMockBuilder( PackageManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$release_manager = new ReleaseManager( $storage, $archiver, $composer_version_parser );

		$logger = new NullIO();

		$package = new LocalTheme();

		$this->builder = new LocalThemeBuilder( $package, $package_manager, $release_manager, $logger );
	}

	// No tests since LocalTheme is just a LocalBasePackage.
}
