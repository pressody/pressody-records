<?php
/**
 * Manual WP Core repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.9.1
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\PackageTypes;

/**
 * Manual WP Core repository class.
 *
 * @since 0.9.1
 */
class ManualWPCore extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected PackageFactory $factory;

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Create a repository.
	 *
	 * @since 0.9.1
	 *
	 * @param PackageFactory $factory Package factory.
	 * @param PackageManager $package_manager
	 */
	public function __construct(
		PackageFactory $factory,
		PackageManager $package_manager
	) {
		$this->factory = $factory;
		$this->package_manager = $package_manager;
	}

	/**
	 * Retrieve all manual plugins.
	 *
	 * @since 0.9.1
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		$args = [
			'package_type'        => [ PackageTypes::WPCORE ],
			'package_source_type' => [ 'local.manual', ],
		];
		foreach ( $this->package_manager->get_package_ids_by( $args ) as $post_id ) {
			$post = get_post( $post_id );
			if ( empty( $post ) || $this->package_manager::PACKAGE_POST_TYPE !== $post->post_type ) {
				continue;
			}

			$package = $this->build( $post_id, $this->package_manager->get_post_package_source_type( $post_id ) );
			$items[] = $package;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Build a manual plugin.
	 *
	 * @since 0.9.1
	 *
	 * @param int    $post_id
	 * @param string $source_type
	 *
	 * @return Package
	 */
	protected function build( int $post_id, string $source_type = '' ): Package {
		return $this->factory->create( PackageTypes::WPCORE, $source_type )
			// Then add managed data.
			->from_manager( $post_id )
			->add_cached_releases()
			->build();
	}
}