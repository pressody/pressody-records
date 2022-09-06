<?php
/**
 * Multi repository.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
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

/**
 * Multi repository class.
 *
 * @since 0.1.0
 */
class MultiRepository extends AbstractRepository implements PackageRepository {
	/**
	 * Array of package repositories.
	 *
	 * @var PackageRepository[]
	 */
	protected array $repositories = [];

	/**
	 * Create a multi repository.
	 *
	 * @since 0.1.0
	 *
	 * @param array $repositories Array of package repositories.
	 */
	public function __construct( array $repositories ) {
		$this->repositories = $repositories;
	}

	/**
	 * Retrieve all packages in the repository.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$packages = [];

		foreach ( $this->repositories as $repository ) {
			$packages = array_merge( $packages, $repository->all() );
		}

		return $packages;
	}

	/**
	 * Reinitialize all packages in the repository.
	 *
	 * @since 0.9.0
	 */
	public function reinitialize() {
		foreach ( $this->repositories as $repository ) {
			if ( method_exists( $repository, 'reinitialize' ) ) {
				$repository->reinitialize();
			}
		}
	}
}
