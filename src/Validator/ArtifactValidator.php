<?php
/**
 * Artifact validator interface.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Validator;

use PixelgradeLT\Records\Release;

/**
 * Artifact validator interface.
 *
 * @since 0.1.0
 */
interface ArtifactValidator {
	/**
	 * Validate an artifact.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $filename Path to the file to validate.
	 * @param Release $release Optional. Release instance.
	 * @return bool
	 */
	public function validate( string $filename, Release $release ): bool;
}
