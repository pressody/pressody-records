<?php
/**
 * Hidden directory validator.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
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

declare ( strict_types = 1 );

namespace Pressody\Records\Validator;

use PclZip;
use Pressody\Records\Exception\InvalidPackageArtifact;
use Pressody\Records\Release;

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
