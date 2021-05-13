<?php
/**
 * Manual parts (plugins) repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageType\PackageTypes;
use PixelgradeLT\Records\PartFactory;
use PixelgradeLT\Records\PartManager;

/**
 * Manual parts (plugins) repository class.
 *
 * @since 0.9.0
 */
class ManualParts extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PartFactory
	 */
	protected PartFactory $factory;

	/**
	 * Part (Package) manager.
	 *
	 * @var PartManager
	 */
	protected PartManager $part_manager;

	/**
	 * Create a repository.
	 *
	 * @since 0.9.0
	 *
	 * @param PartFactory $factory Package factory.
	 * @param PartManager $package_manager
	 */
	public function __construct(
		PartFactory $factory,
		PartManager $package_manager
	) {
		$this->factory      = $factory;
		$this->part_manager = $package_manager;
	}

	/**
	 * Retrieve all manual plugins.
	 *
	 * @since 0.9.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		$args = [
			'package_type'        => [ PackageTypes::PLUGIN ],
			'package_source_type' => [ 'local.manual', ],
		];
		foreach ( $this->part_manager->get_package_ids_by( $args ) as $post_id ) {
			$post = get_post( $post_id );
			if ( empty( $post ) || $this->part_manager::PACKAGE_POST_TYPE !== $post->post_type ) {
				continue;
			}

			$package = $this->build( $post_id, $this->part_manager->get_post_package_source_type( $post_id ) );
			$items[] = $package;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Build an manual part (plugin).
	 *
	 * @since 0.9.0
	 *
	 * @param int    $post_id
	 * @param string $source_type
	 *
	 * @return Package
	 */
	protected function build( int $post_id, string $source_type = '' ): Package {
		return $this->factory->create( PackageTypes::PLUGIN, $source_type )
			// Then add managed data.
			->from_manager( $post_id )
			->add_cached_releases()
			->build();
	}
}
