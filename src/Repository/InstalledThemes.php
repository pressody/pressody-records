<?php
/**
 * Installed themes repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageType\Theme;
use WP_Theme;

/**
 * Installed themes repository class.
 *
 * @since 0.1.0
 */
class InstalledThemes extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected $factory;

	/**
	 * Create a repository.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageFactory $factory Package factory.
	 */
	public function __construct( PackageFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Retrieve all installed themes.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		foreach ( wp_get_themes() as $slug => $theme ) {
			$items[] = $this->build( $slug, $theme );
		}

		return $items;
	}

	/**
	 * Build a theme.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $slug  Theme slug.
	 * @param WP_Theme $theme WP theme instance.
	 *
	 * @throws \ReflectionException
	 * @return Theme|Package
	 */
	protected function build( string $slug, WP_Theme $theme ): Theme {
		return $this->factory->create( 'theme' )
			->from_source( $slug, $theme )
			->build();
	}
}
