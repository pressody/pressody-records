<?php
/**
 * External package builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\Release;

/**
 * External package builder class for packages with a source like Packagist.org, WPackagist.org, or a VCS url.
 *
 * @since 0.1.0
 */
class ExternalPackageBuilder extends PackageBuilder {

	/**
	 * Add cached releases to a package.
	 *
	 * @since 0.1.0
	 *
	 * @return $this
	 */
	public function add_cached_releases(): PackageBuilder {
		$releases = $this->release_manager->all( $this->package );

		foreach ( $releases as $release ) {
			$this->add_release( $release->get_version(), $release->get_source_url() );
		}

		return $this;
	}
}
