<?php
/**
 * External parts (plugins) repository.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Repository;

use Pressody\Records\Package;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\PartFactory;
use Pressody\Records\PartManager;

/**
 * External parts (plugins) repository.
 *
 * @since 0.9.0
 */
class ExternalParts extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PartFactory
	 */
	protected PartFactory $factory;

	/**
	 * Parts (Package) manager.
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
	 * Retrieve all external parts (plugins).
	 *
	 * @since 0.9.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		$args = [
			'package_type'        => [ PackageTypes::PLUGIN ],
			'package_source_type' => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
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
	 * Build an external part (plugin).
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
			// Then add managed data, if this plugin is managed.
			->from_manager( $post_id )
			->add_cached_releases()
			->build();
	}
}
