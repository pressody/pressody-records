<?php
declare ( strict_types=1 );

namespace Pressody\Records\Tests\Integration\PackageType;

use Pressody\Records\PackageFactory;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Release;
use Pressody\Records\Tests\Integration\TestCase;
use function Pressody\Records\plugin;

class LocalPluginTest extends TestCase {
	/**
	 * @var PackageFactory|null
	 */
	protected $factory = null;

	public function setUp(): void {
		parent::setUp();

		$this->factory = plugin()->get_container()->get( 'package.factory' );
	}

	public function test_get_plugin_from_source() {
		/** @var LocalPlugin $package */
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_source( 'basic/basic.php' )
		                         ->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( PackageTypes::PLUGIN, $package->get_type() );
		$this->assertSame( 'local.plugin', $package->get_source_type() );
		$this->assertSame( 'local-plugin/basic', $package->get_source_name() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( 'Basic Plugin', $package->get_name() );
		$this->assertSame( [
			[
				'name'     => 'Basic, Inc.',
				'homepage' => 'https://example.com/',
			],
		], $package->get_authors() );
		$this->assertSame( 'A basic plugin description.', $package->get_description() );
		$this->assertSame( 'https://example.com/plugin/basic/', $package->get_homepage() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertSame( [ 'admin', 'htaccess', 'post', 'redirect', ], $package->get_keywords() );
		$this->assertSame( '4.8.9', $package->get_requires_at_least_wp() );
		$this->assertSame( '5.6.1', $package->get_tested_up_to_wp() );
		$this->assertSame( '', $package->get_requires_php() );

		$this->assertFalse( $package->is_managed() );
		$this->assertSame( WP_PLUGIN_DIR . '/basic/', $package->get_directory() );
		$this->assertSame( '1.4.1', $package->get_installed_version() );
	}

	public function test_get_plugin_from_readme() {
		/** @var LocalPlugin $package */
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_readme( 'basic/basic.php' )
		                         ->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertSame( PackageTypes::PLUGIN, $package->get_type() );
		$this->assertSame( 'basic', $package->get_slug() );
		$this->assertSame( 'basic/basic.php', $package->get_basename() );
		$this->assertTrue( $package->is_installed() );
		$this->assertSame( 'Basic Plugin', $package->get_name() );
		$this->assertSame( [
			[ 'name' => 'johnny5', ],
			[ 'name' => 'wordpressdotorg', ],
		], $package->get_authors() );
		$this->assertSame( 'A basic plugin description.', $package->get_description() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertSame( [ 'admin', 'htaccess', 'post', 'redirect', ], $package->get_keywords() );
		$this->assertSame( '5.1', $package->get_requires_at_least_wp() );
		$this->assertSame( '5.6', $package->get_tested_up_to_wp() );
		$this->assertSame( '5.6.8', $package->get_requires_php() );

		$this->assertFalse( $package->is_managed() );
	}

	public function test_is_single_file_plugin() {
		/** @var LocalPlugin $package */
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_source( 'basic/basic.php' )
		                         ->build();

		$this->assertFalse( $package->is_single_file() );

		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'hello.php' )
		                         ->from_source( 'hello.php' )
		                         ->build();

		$this->assertTrue( $package->is_single_file() );
	}

	public function test_get_files_for_single_file_plugin() {
		/** @var LocalPlugin $package */
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'hello.php' )
		                         ->from_source( 'hello.php' )
		                         ->build();

		$this->assertSame( 1, count( $package->get_files() ) );
	}

	public function test_get_cached_releases_from_storage() {
		/** @var LocalPlugin $package */
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_source( 'basic/basic.php' )
		                         ->add_cached_releases()
		                         ->build();

		$this->assertInstanceOf( LocalPlugin::class, $package );

		$this->assertInstanceOf( Release::class, $package->get_release( '1.0.0' ) );
	}

	public function test_get_cached_releases_includes_installed_version() {
		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_source( 'basic/basic.php' )
		                         ->set_installed( true )
		                         ->set_installed_version( '1.3.1' )
		                         ->add_cached_releases()
		                         ->build();

		$this->assertSame( '1.3.1', $package->get_installed_release()->get_version() );
	}

	public function test_get_cached_releases_includes_pending_update() {
		add_filter( "pre_site_transient_update_plugins", [ $this, 'filter_add_plugin_update' ] );

		$package = $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
		                         ->from_basename( 'basic/basic.php' )
		                         ->from_source( 'basic/basic.php' )
		                         ->set_installed( true )
		                         ->set_installed_version( '1.3.1' )
		                         ->add_cached_releases()
		                         ->build();

		$this->assertSame( '2.0.0', $package->get_latest_release()->get_version() );

		remove_filter( "pre_site_transient_update_plugins", [ $this, 'filter_add_plugin_update' ] );
	}

	public function filter_add_plugin_update( $update ) {
		return $this->get_update_transient();
	}

	protected function get_update_transient() {
		return (object) [
			'response' => [
				'basic/basic.php' => (object) [
					'slug'        => 'basic',
					'plugin'      => 'basic/basic.php',
					'new_version' => '2.0.0',
					'package'     => 'https://example.org/download/basic/2.0.0.zip',
				],
			],
		];
	}
}
