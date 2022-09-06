<?php
/**
 * Invalid release version exception.
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

namespace Pressody\Records\Exception;

use Throwable;

/**
 * Invalid release version exception class.
 *
 * @since 0.1.0
 */
class InvalidReleaseVersion extends \LogicException implements PressodyRecordsException {
	/**
	 * Create an exception for an invalid release version string.
	 *
	 * @since 0.1.0.
	 *
	 * @param string          $version      Version string.
	 * @param string          $package_name Package name.
	 * @param int             $code         Optional. The Exception code.
	 * @param Throwable|null $previous     Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidReleaseVersion
	 */
	public static function fromVersion(
		string $version,
		string $package_name,
		int $code = 0,
		Throwable $previous = null
	): InvalidReleaseVersion {
		$message = "Invalid release version for {$package_name}: {$version}";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for a package that has no releases.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $package_name Package name.
	 * @param int            $code         Optional. The Exception code.
	 * @param Throwable|null $previous     Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidReleaseVersion
	 */
	public static function hasNoReleases(
		string $package_name,
		int $code = 0,
		Throwable $previous = null
	): InvalidReleaseVersion {
		$message = "Package {$package_name} has no releases.";

		return new static( $message, $code, $previous );
	}
}
