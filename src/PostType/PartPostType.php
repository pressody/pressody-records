<?php
/**
 * The Part custom post type.
 *
 * A part is a special kind of package.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package Pressody
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

declare ( strict_types=1 );

namespace Pressody\Records\PostType;

use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\PartManager;

/**
 * The Part custom post type provider: provides the interface for and stores the information about each managed part.
 *
 * @since 0.9.0
 */
class PartPostType extends PackagePostType {

	/**
	 * Constructor.
	 *
	 * @since 0.9.0
	 *
	 * @param PartManager $package_manager Parts manager.
	 */
	public function __construct(
		PartManager $package_manager
	) {
		parent::__construct( $package_manager );
	}

	/**
	 * Insert the terms for the package type taxonomy defined by the package manager.
	 *
	 * @since 0.9.0
	 */
	protected function insert_package_type_taxonomy_terms() {
		// Force the insertion of needed terms matching the PACKAGE TYPES.
		foreach ( PackageTypes::DETAILS as $term_slug => $term_details ) {
			// For parts, we only want plugins.
			if ( ! in_array( $term_slug, [ PackageTypes::PLUGIN, PackageTypes::MUPLUGIN, PackageTypes::DROPINPLUGIN ] ) ) {
				continue;
			}

			if ( ! term_exists( $term_slug, $this->package_manager::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term_details['name'], $this->package_manager::PACKAGE_TYPE_TAXONOMY, [
					'slug'        => $term_slug,
					'description' => $term_details['description'],
				] );
			}
		}
	}
}
