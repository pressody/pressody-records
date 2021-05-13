<?php
/**
 * External part (package) builder.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use PixelgradeLT\Records\Utils\ArrayHelpers;

/**
 * External part (package) builder class for parts with a source like Packagist.org or a VCS url.
 *
 * @since 0.9.0
 */
class ExternalPartBuilder extends ExternalBasePackageBuilder {

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
			$package_data['required_parts'] = $this->normalize_required_packages( $package_data['required_parts'] );
			// We will merge the required parts into the existing required packages.
			$this->set_required_packages(
				ArrayHelpers::array_merge_recursive_distinct(
					$this->package->get_required_packages(),
					$package_data['required_parts']
				)
			);
		}

		return $this;
	}
}
