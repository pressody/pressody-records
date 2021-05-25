<?php
/**
 * Hidden directory validator.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Validator;

use PclZip;
use PixelgradeLT\Records\Exception\InvalidPackageArtifact;
use PixelgradeLT\Records\Release;

/**
 * Hidden directory validator class.
 *
 * @since 0.1.0
 */
class HiddenDirectoryValidator implements ArtifactValidator {
	/**
	 * Check for top level hidden directories in a package artifact.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $filename Path to the file to validate.
	 * @param Release $release Release instance.
	 * @throws InvalidPackageArtifact If an artifact contains a top level hidden directory.
	 * @return bool
	 */
	public function validate( string $filename, Release $release ): bool {
		$zip = new PclZip( $filename );

		if ( $this->has_top_level_macosx_directory( $zip ) ) {
			throw InvalidPackageArtifact::containsMacOsxDirectory( $filename );
		}

		return true;
	}

	/**
	 * Whether a zip contains a top level __MACOSX directory.
	 *
	 * @since 0.1.0
	 *
	 * @param  PclZip $zip PclZip instance.
	 * @return bool
	 */
	protected function has_top_level_macosx_directory( PclZip $zip ): bool {
		$directories = [];

		$contents = $zip->listContent();
		foreach ( $contents as $file ) {
			if ( '__MACOSX/' === substr( $file['filename'], 0, 9 ) ) {
				return true;
			}
		}

		return false;
	}
}
