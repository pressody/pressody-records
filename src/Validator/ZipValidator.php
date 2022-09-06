<?php
/**
 * Zip artifact validator.
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
 * Zip artifact validator class.
 *
 * @since 0.1.0
 */
class ZipValidator implements ArtifactValidator {
	/**
	 * Validate that a file is a readable zip archive.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $filename Path to the file to validate.
	 * @param Release $release Release instance.
	 * @throws InvalidPackageArtifact If a file cannot be parsed as a zip file.
	 * @return bool
	 */
	public function validate( string $filename, Release $release ): bool {
		$zip = new PclZip( $filename );

		if ( 0 === $zip->properties() ) {
			throw InvalidPackageArtifact::unreadableZip( $filename );
		}

		return true;
	}
}
