<?php
/**
 * Manual uploaded part (package) builder.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package Pressody
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
