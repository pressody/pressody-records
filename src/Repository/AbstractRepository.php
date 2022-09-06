<?php
/**
 * Base repository.
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

use function Pressody\Records\is_plugin_file;

/**
 * Abstract repository class.
 *
 * @since 0.1.0
 */
abstract class AbstractRepository implements PackageRepository {
	/**
	 * Retrieve all packages.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	abstract public function all(): array;

	/**
	 * Whether an item with the supplied criteria exists.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Map of key/value pairs.
	 * @return bool
	 */
	public function contains( array $args ): bool {
		return ! empty( $this->where( $args ) );
	}

	/**
	 * Retrieve items that match a list of key/value pairs.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Map of key/value pairs.
	 * @return array
	 */
	public function where( array $args ): array {
		$args       = $this->parse_args( $args );
		$matches    = [];
		$args_count = count( $args );

		foreach ( $this->all() as $item ) {
			$matched = 0;

			foreach ( $args as $key => $value ) {
				if ( $item[ $key ] && $value === $item[ $key ] ) {
					$matched++;
				}
			}

			if ( $matched === $args_count ) {
				$matches[] = $item;
			}
		}

		return $matches;
	}

	/**
	 * Retrieve the first item to match a list of key/value pairs.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Map of key/value pairs.
	 * @return Package|null
	 */
	public function first_where( array $args ): ?Package {
		$items = $this->where( $args );
		return empty( $items ) ? null : reset( $items );
	}

	/**
	 * Apply a callback to a repository to filter items.
	 *
	 * @since 0.1.0
	 *
	 * @param callable $callback Filter callback.
	 * @return PackageRepository
	 */
	public function with_filter( callable $callback ): PackageRepository {
		return new FilteredRepository( $this, $callback );
	}

	/**
	 * Parse arguments used for filtering a collection.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args List of key/value pairs.
	 * @return array
	 */
	protected function parse_args( array $args ): array {
		// If a plugin file is passed as the slug value, convert it to a
		// basename argument.
		if ( isset( $args['slug'] ) && is_plugin_file( $args['slug'] ) ) {
			$args['basename'] = $args['slug'];
			unset( $args['slug'] );
		}

		return $args;
	}

	/**
	 * Reinitialize all packages in the repository.
	 *
	 * @since 0.9.0
	 */
	public function reinitialize() {
		// By default, nothing to do.
	}
}
