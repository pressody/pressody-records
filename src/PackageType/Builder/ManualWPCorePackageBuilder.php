<?php
/**
 * Manual uploaded WP Core package builder.
 *
 * @since   0.9.1
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\PackageType\Builder;

/**
 * Manual WP Core package builder class for packages with zips manually uploaded to the post.
 *
 * @since 0.9.1
 */
class ManualWPCorePackageBuilder extends BasePackageBuilder {

	public function from_manual_releases( array $releases ): BasePackageBuilder {
		// We don't want to extract any details from the WP Core release files.

		return $this;
	}
}
