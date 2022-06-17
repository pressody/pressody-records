<?php
/**
 * Invalid package artifact exception.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Exception;

use Throwable;

/**
 * Invalid package artifact exception class.
 *
 * @since 0.1.0
 */
class InvalidPackageArtifact extends \RuntimeException implements PressodyRecordsException {
	/**
	 * Create an exception for artifact that is unreadable as a zip archive.
	 *
	 * @since 0.1.0
	 *
	 * @param string         $filename File name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidPackageArtifact
	 */
	public static function unreadableZip(
		string $filename,
		int $code = 0,
		Throwable $previous = null
	): InvalidPackageArtifact {
		$message = "Unable to parse {$filename} as a valid zip archive.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for artifact with a top level __MACOSX directory.
	 *
	 * @since 0.1.0
	 *
	 * @param string         $filename File name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidPackageArtifact
	 */
	public static function containsMacOsxDirectory(
		string $filename,
		int $code = 0,
		Throwable $previous = null
	): InvalidPackageArtifact {
		$message = "Package artifact {$filename} has a top level __MACOSX directory.";

		return new static( $message, $code, $previous );
	}
}
