<?php
/**
 * Cached repository.
 *
 * This repository will cache the items internally. Any changes during the request will not be taken into account.
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
 * Cached repository class.
 *
 * @since 0.1.0
 */
class CachedRepository extends AbstractRepository implements PackageRepository {
	/**
	 * Whether the repository has been initialized.
	 *
	 * @var bool
	 */
	protected bool $initialized = false;

	/**
	 * Items in the repository.
	 *
	 * @var Package[]
	 */
	protected array $items = [];

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $repository;

	/**
	 * Create a repository.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository $repository Package repository.
	 */
	public function __construct( PackageRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Retrieve all items.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		if ( $this->initialized ) {
			return $this->items;
		}

		$this->initialized = true;
		$this->items       = $this->repository->all();

		return $this->items;
	}

	/**
	 * Reinitialize by emptying the cached items.
	 *
	 * @since 0.9.0
	 */
	public function reinitialize() {
		$this->initialized = false;
		$this->items       = [];
	}
}
