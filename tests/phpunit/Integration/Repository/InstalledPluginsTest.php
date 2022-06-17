<?php
declare ( strict_types = 1 );

namespace Pressody\Records\Tests\Integration\Repository;

use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\Tests\Integration\TestCase;

use function Pressody\Records\plugin;

class InstalledPluginsTest extends TestCase {

	public function test_get_plugin_from_source() {
		$repository = plugin()->get_container()['repository.local.plugins'];

		$package    = $repository->first_where( [ 'slug' => 'basic/basic.php', 'source_type' => 'local.plugin' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'unmanaged/unmanaged.php' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'basename' => 'unmanaged/unmanaged.php' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'unmanaged/unmanaged.php', 'source_type' => 'local.theme' ] );
		$this->assertNull( $package );
	}
}
