<?php
declare ( strict_types=1 );

namespace PixelgradeLT\Records\Tests\Integration\Repository;

use PHPUnit\Util\Test;
use PixelgradeLT\Records\Container;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\ExternalBasePackage;
use PixelgradeLT\Records\ServiceProvider;
use PixelgradeLT\Records\Tests\Framework\PHPUnitUtil;
use PixelgradeLT\Records\Tests\Integration\TestCase;

use Psr\Container\ContainerInterface;
use function PixelgradeLT\Records\plugin;

class ExternalPluginsTest extends TestCase {
	protected static $posts_data;
	protected static $old_container;

	/**
	 * @param \WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		// We need to set a user with sufficient privileges to create packages and edit them.
		set_current_user( 1 );

		/** @var ContainerInterface $old_container */
		self::$old_container = plugin()->get_container();

		// Register ltpackage post type
		$register_post_type = PHPUnitUtil::getProtectedMethod( self::$old_container['hooks.package_post_type'], 'register_post_type' );
		$register_post_type->invoke( self::$old_container['hooks.package_post_type'] );

		// Register and populate the taxonomies.
		$register_taxonomy = PHPUnitUtil::getProtectedMethod( self::$old_container['hooks.package_post_type'], 'register_taxonomy' );
		$register_taxonomy->invoke( self::$old_container['hooks.package_post_type'] );

		// Set this package as a 'plugin' package type.
		$package_type = get_term_by( 'slug', 'plugin', self::$old_container['package.manager']::PACKAGE_TYPE_TAXONOMY );

		self::$posts_data = [
			'packagist_not_cached' => [
				'post_title'  => 'S3 Uploads',
				'post_status' => 'publish',
				'post_name'   => 's3-uploads-not-cached',
				'post_type'   => self::$old_container['package.manager']::PACKAGE_POST_TYPE,
				'tax_input'   => [
					self::$old_container['package.manager']::PACKAGE_TYPE_TAXONOMY    => [ $package_type->term_id ],
					self::$old_container['package.manager']::PACKAGE_KEYWORD_TAXONOMY => 'keyword1, keyword2, keyword3',
				],
				'meta_input'  => [
					'_package_source_type'                        => 'packagist.org',
					'_package_source_name'                        => 'humanmade/s3-uploads',
					'_package_source_version_range'               => '>2.0',
					'_package_source_stability'                   => 'stable',
					'_package_details_description'                => 'Package custom description.',
					'_package_details_homepage'                   => 'https://package.homepage',
					'_package_details_license'                    => 'GPL-2.0-or-later',
					'_package_details_authors|||0|value'          => '_',
					'_package_details_authors|name|0|0|value'     => 'HumanMade',
					'_package_details_authors|email|0|0|value'    => '',
					'_package_details_authors|homepage|0|0|value' => 'https://humanmade.com',
					'_package_details_authors|role|0|0|value'     => '',
					'_package_required_packages|||0|_empty'       => '',
				],
			],
			'packagist_cached_releases' => [
				'post_title'  => 'WP Minions',
				'post_status' => 'publish',
				'post_name'   => 'wp-minions-cached',
				'post_type'   => self::$old_container['package.manager']::PACKAGE_POST_TYPE,
				'tax_input'   => [
					self::$old_container['package.manager']::PACKAGE_TYPE_TAXONOMY    => [ $package_type->term_id ],
					self::$old_container['package.manager']::PACKAGE_KEYWORD_TAXONOMY => 'keyword4, keyword5, keyword6',
				],
				'meta_input'  => [
					'_package_source_type'                        => 'packagist.org',
					'_package_source_name'                        => '10up/wp-minions',
					'_package_source_version_range'               => '>1.0',
					'_package_source_stability'                   => 'stable',
					'_package_details_description'                => 'Package cached custom description.',
					'_package_details_homepage'                   => 'https://package.homepage',
					'_package_details_license'                    => 'GPL-2.0-or-later',
					'_package_details_authors|||0|value'          => '_',
					'_package_details_authors|||1|value'          => '_',
					'_package_details_authors|||2|value'          => '_',
					'_package_details_authors|name|0|0|value'     => '10up',
					'_package_details_authors|email|0|0|value'    => '',
					'_package_details_authors|homepage|0|0|value' => 'https://10up.com',
					'_package_details_authors|role|0|0|value'     => '',
					'_package_details_authors|name|1|0|value'     => 'Chris Marslender',
					'_package_details_authors|email|1|0|value'    => 'chrismarslender@gmail.com',
					'_package_details_authors|homepage|1|0|value' => 'https://chrismarslender.com',
					'_package_details_authors|role|1|0|value'     => 'Developer',
					'_package_details_authors|name|2|0|value'     => 'Darshan Sawardekar',
					'_package_details_authors|email|2|0|value'    => '',
					'_package_details_authors|homepage|2|0|value' => '""',
					'_package_details_authors|role|2|0|value'     => 'Developer',
					'_package_required_packages|||0|_empty'       => '',
				],
			],
		];

		// Create the test ltpackages posts.
		foreach ( self::$posts_data as $data ) {
			$factory->post->create_object( $data );
		}
	}

	public static function wpTearDownAfterClass() {
	}

	public function test_get_non_existent_plugin() {
		/** @var \PixelgradeLT\Records\Repository\CachedRepository $repository */
		$repository = plugin()->get_container()['repository.external.plugins'];
		$repository->reinitialize();

		$package = $repository->first_where( [ 'slug' => 'something-not-here', 'source_type' => 'packagist.org' ] );
		$this->assertNull( $package );
	}

	public function test_get_plugin_without_cached_releases() {
		/** @var \PixelgradeLT\Records\Repository\CachedRepository $repository */
		$repository = plugin()->get_container()['repository.external.plugins'];
		$repository->reinitialize();

		$package = $repository->first_where( [ 'source_name' => 'humanmade/s3-uploads', 'source_type' => 'packagist.org' ] );
		$this->assertInstanceOf( ExternalBasePackage::class, $package );

		$package = $repository->first_where( [ 'slug' => 's3-uploads-not-cached', 'source_type' => 'packagist.org' ] );
		$this->assertInstanceOf( ExternalBasePackage::class, $package );
		$this->assertFalse( $package->has_releases() );
		$this->assertFalse( $package->has_required_packages() );
		$this->assertCount( 1, $package->get_authors() );
		$this->assertSame( 'Package custom description.', $package->get_description() );
		$this->assertSame( 'https://package.homepage', $package->get_homepage() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertCount( 3, $package->get_keywords() );

		// Same source name, but different source_type.
		$package = $repository->first_where( [ 'source_name' => 'humanmade/s3-uploads', 'source_type' => 'vcs' ] );
		$this->assertNull( $package );
	}

	public function test_get_plugin_with_cached_releases() {
		/** @var \PixelgradeLT\Records\Repository\CachedRepository $repository */
		$repository = plugin()->get_container()['repository.external.plugins'];
		$repository->reinitialize();

		$package = $repository->first_where( [ 'source_name' => '10up/wp-minions', 'source_type' => 'packagist.org' ] );
		$this->assertInstanceOf( ExternalBasePackage::class, $package );

		$package = $repository->first_where( [ 'slug' => 'wp-minions-cached', 'source_type' => 'packagist.org' ] );
		$this->assertInstanceOf( ExternalBasePackage::class, $package );
		$this->assertTrue( $package->has_releases() );
		$this->assertFalse( $package->has_required_packages() );
		$this->assertCount( 3, $package->get_authors() );
		$this->assertSame( 'Package cached custom description.', $package->get_description() );
		$this->assertSame( 'https://package.homepage', $package->get_homepage() );
		$this->assertSame( 'GPL-2.0-or-later', $package->get_license() );
		$this->assertCount( 3, $package->get_keywords() );

		// Same source name, but different source_type.
		$package = $repository->first_where( [ 'source_name' => '10up/wp-minions', 'source_type' => 'vcs' ] );
		$this->assertNull( $package );
	}
}
