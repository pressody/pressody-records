<?php
/**
 * Installed themes repository.
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
use Pressody\Records\PackageFactory;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\PackageType\PackageTypes;
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
	protected PackageFactory $factory;

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
	 * @return LocalTheme|Package
	 */
	protected function build( string $slug, WP_Theme $theme ): LocalTheme {
		return $this->factory->create( PackageTypes::THEME, 'local.theme' )
			// Fill package details in a cascade.
			// First from just the plugin file.
			->from_slug( $slug )
			// Then from the managed data, if this theme is managed.
			->from_manager( 0, [ 'package_source_type' => 'local.theme', 'local_theme_slug' => $slug ] )
			// Then from the theme source files, if there is anything left to fill.
			->from_source( $slug, $theme )
			->add_cached_releases()
			->build();
	}
}
