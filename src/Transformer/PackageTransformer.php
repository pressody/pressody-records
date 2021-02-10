<?php
/**
 * Package transformer.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Transformer;

use PixelgradeLT\Records\Package;

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
