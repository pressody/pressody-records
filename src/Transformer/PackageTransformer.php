<?php
/**
 * Package transformer.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Transformer;

use Pressody\Records\Package;

/**
 * Package transformer interface.
 *
 * @since 0.1.0
 */
interface PackageTransformer {
	/**
	 * Transform a package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 */
	public function transform( Package $package );
}
