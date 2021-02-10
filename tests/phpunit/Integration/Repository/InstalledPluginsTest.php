<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Test\Integration\Repository;

use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageType\Plugin;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Repository\InstalledPlugins;
use PixelgradeLT\Records\Test\Integration\TestCase;

use function PixelgradeLT\Records\plugin;

class InstalledPluginsTest extends TestCase {
	public function test_get_plugin_from_source() {
		$repository = plugin()->get_container()['repository.plugins'];
		$package    = $repository->first_where( [ 'slug' => 'basic/basic.php' ] );

		$this->assertInstanceOf( Plugin::class, $package );
	}
}
