<?php
/**
 * Manual themes repository.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.7.0
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
use Pressody\Records\PackageFactory;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\PackageTypes;

/**
 * Manual themes repository class.
 *
 * @since 0.7.0
 */
class ManualThemes extends AbstractRepository implements PackageRepository {
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
	 * @since 0.7.0
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
	 * Retrieve all manual themes.
	 *
	 * @since 0.7.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		$args = [
			'package_type'        => [ PackageTypes::THEME ],
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
	 * Build a manual theme.
	 *
	 * @since 0.7.0
	 *
	 * @param int    $post_id
	 * @param string $source_type Optional.
	 *
	 * @return Package
	 */
	protected function build( int $post_id, string $source_type = '' ): Package {
		return $this->factory->create( PackageTypes::THEME, $source_type )
			// Then add managed data.
			->from_manager( $post_id )
			->add_cached_releases()
			->build();
	}
}
