<?php
/**
 * External WP Core package builder.
 *
 * @since   0.9.1
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\PackageType\Builder;

/**
 * External WP Core package builder class for packages with a source like Packagist.org, WPackagist.org, or a VCS url.
 *
 * @since 0.9.1
 */
class ExternalWPCorePackageBuilder extends ExternalBasePackageBuilder {

	public function from_source_cached_release_packages( array $cached_release_packages ): BasePackageBuilder {
		// We don't want to extract any details from the WP Core release files.

		return $this;
	}
}
