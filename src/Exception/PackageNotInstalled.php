<?php
/**
 * Package not installed exception.
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

use Pressody\Records\Package;
use Throwable;

/**
 * Package not installed exception class.
 *
 * @since 0.1.0
 */
class PackageNotInstalled extends \RuntimeException implements PressodyRecordsException {
	/**
	 * Create an exception for an invalid method call.
	 *
	 * @since 0.1.0.
	 *
	 * @param string          $method   Method name.
	 * @param Package         $package  Package.
	 * @param int             $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return PackageNotInstalled
	 */
	public static function forInvalidMethodCall(
		string $method,
		Package $package,
		int $code = 0,
		Throwable $previous = null
	): PackageNotInstalled {
		$name    = $package->get_name();
		$message = "Cannot call method {$method} for a package that is not installed; Package: {$name}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for an invalid installed version.
	 *
	 * @since 0.1.0.
	 *
	 * @param string          $method   Method name.
	 * @param Package         $package  Package.
	 * @param int             $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return PackageNotInstalled
	 */
	public static function forInvalidInstalledVersion(
		string $method,
		Package $package,
		int $code = 0,
		Throwable $previous = null
	): PackageNotInstalled {
		$name    = $package->get_name();
		$message = "Cannot call method {$method} for a package with an invalid installed version; Package: {$name}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to archive a package from source.
	 *
	 * @since 0.1.0.
	 *
	 * @param Package        $package  Package.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return PackageNotInstalled
	 */
	public static function unableToArchiveFromSource(
		Package $package,
		int $code = 0,
		Throwable $previous = null
	): PackageNotInstalled {
		$name    = $package->get_name();
		$message = "Unable to archive the '{$name}' package; source does not exist.";

		return new static( $message, $code, $previous );
	}
}
