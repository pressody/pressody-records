<?php
/**
 * Cached repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;

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
	protected $initialized = false;

	/**
	 * Items in the repository.
	 *
	 * @var Package[]
	 */
	protected $items = [];

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

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
}
