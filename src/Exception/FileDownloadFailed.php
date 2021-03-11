<?php
/**
 * Failed file download exception.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Exception;

use Throwable;

/**
 * Failed file download exception class.
 *
 * @since 0.1.0
 */
class FileDownloadFailed extends \RuntimeException implements PixelgradeltRecordsException {
	/**
	 * Create an exception for artifact Filename download failure.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filename File name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileDownloadFailed
	 */
	public static function forFileName(
		string $filename,
		int $code = 0,
		Throwable $previous = null
	): FileDownloadFailed {
		$message = "Artifact download failed for file {$filename}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for artifact URL download failure.
	 *
	 * @since 0.7.0.
	 *
	 * @param string         $url
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileDownloadFailed
	 */
	public static function forUrl(
		string $url,
		int $code = 0,
		Throwable $previous = null
	): FileDownloadFailed {
		$message = "Artifact download failed for URL {$url}.";

		return new static( $message, $code, $previous );
	}
}
