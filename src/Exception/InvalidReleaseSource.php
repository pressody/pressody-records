<?php
/**
 * Invalid release source exception.
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

use Pressody\Records\Release;
use Throwable;

/**
 * Invalid release source exception class.
 *
 * @since 0.1.0
 */
class InvalidReleaseSource extends \LogicException implements PressodyRecordsException {
	/**
	 * Create an exception for an invalid release source.
	 *
	 * @since 0.1.0.
	 *
	 * @param Release        $release  Release instance.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidReleaseSource
	 */
	public static function forRelease(
		Release $release,
		int $code = 0,
		Throwable $previous = null
	): InvalidReleaseSource {
		$name = $release->get_package()->get_name();

		$message = "Unable to create release artifact for {$name}; source could not be determined.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception when we don't have a source cached package data for the release originating on an external Composer Repo (Packagist, VCS).
	 *
	 * @since 0.1.0.
	 *
	 * @param Release        $release  Release instance.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidReleaseSource
	 */
	public static function missingSourceCachedPackage(
		Release $release,
		int $code = 0,
		Throwable $previous = null
	): InvalidReleaseSource {
		$name = $release->get_package()->get_name();

		$message = "Unable to create release artifact for {$name}; source cached package data could not be found.";

		return new static( $message, $code, $previous );
	}
}
