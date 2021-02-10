<?php
/**
 * Invalid release version exception.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Exception;

use Throwable;

/**
 * Invalid release version exception class.
 *
 * @since 0.1.0
 */
class InvalidReleaseVersion extends \LogicException implements PixelgradeltRecordsException {
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
