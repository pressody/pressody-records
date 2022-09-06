<?php
/**
 * Invalid package exception.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.9.0
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

use Pressody\Records\Package;
use Throwable;

/**
 * Invalid package exception class.
 *
 * @since 0.9.0
 */
class InvalidPackage extends \RuntimeException implements PressodyRecordsException {
	/**
	 * Create an exception for package that is missing the needed details to determine its storage relative directory.
	 *
	 * @since 0.9.0
	 *
	 * @param Package        $package
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidPackage
	 */
	public static function missingDetailsForStoreDir(
		Package $package,
		int $code = 0,
		Throwable $previous = null
	): InvalidPackage {
		$package_name = $package->get_name();
		$message = "Unable to determine the store dir for package '{$package_name}' since it is missing needed details.";

		return new static( $message, $code, $previous );
	}
}
