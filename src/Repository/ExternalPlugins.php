<?php
/**
 * External plugins repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\LocalPlugin;

/**
 * External plugins repository class.
 *
 * @since 0.1.0
 */
class ExternalPlugins extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected $factory;

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected $package_manager;

	/**
	 * Create a repository.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageFactory $factory Package factory.
	 */
	public function __construct(
		PackageFactory $factory,
		PackageManager $package_manager
	) {
		$this->factory = $factory;
		$this->package_manager = $package_manager;
	}

	/**
	 * Retrieve all external plugins.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		$args = [
			'package_type'        => 'plugin',
			'package_source_type' => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
		];
		foreach ( $this->package_manager->get_package_ids_by( $args ) as $post_id ) {
			$package = $this->build( $post_id );
			$items[] = $package;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Build an external plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id
	 *
	 * @return Package
	 */
	protected function build( int $post_id ): Package {
		return $this->factory->create( 'plugin' )
			// Then add managed data, if this plugin is managed.
			->from_manager( $post_id )
			->add_cached_releases()
			->build();
	}
}
