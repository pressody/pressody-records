<?php
/**
 * Manual uploaded part (package) builder.
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

namespace Pressody\Records\PackageType\Builder;

use Pressody\Records\Utils\ArrayHelpers;

/**
 * Manual part (package) builder class for parts with zips manually uploaded to the post.
 *
 * @since 0.9.0
 */
class ManualPartBuilder extends ManualBasePackageBuilder {

	/**
	 * Set properties from a package data array.
	 *
	 * @since 0.9.0
	 *
	 * @param array $package_data Package data.
	 *
	 * @return $this
	 */
	public function from_package_data( array $package_data ): self {
		parent::from_package_data( $package_data );

		if ( ! empty( $package_data['required_parts'] ) ) {
			// We need to normalize before the merge since we need the keys to be in the same format.
			// A bit inefficient, I know.
			$package_data['required_parts'] = $this->normalize_dependency_packages( $package_data['required_parts'] );
			// We will merge the required parts into the existing required packages.
			$this->set_required_packages(
				ArrayHelpers::array_merge_recursive_distinct(
					$this->package->get_required_packages(),
					$package_data['required_parts']
				)
			);
		}

		if ( ! empty( $package_data['replaced_parts'] ) ) {
			// We need to normalize before the merge since we need the keys to be in the same format.
			// A bit inefficient, I know.
			$package_data['replaced_parts'] = $this->normalize_dependency_packages( $package_data['replaced_parts'] );
			// We will merge the replaced parts into the existing replaced packages.
			$this->set_replaced_packages(
				ArrayHelpers::array_merge_recursive_distinct(
					$this->package->get_replaced_packages(),
					$package_data['replaced_parts']
				)
			);
		}

		return $this;
	}
}
