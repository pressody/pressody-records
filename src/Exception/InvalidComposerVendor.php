<?php
/**
 * Invalid Composer vendor exception.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Exception;

use Throwable;

/**
 * Invalid package exception class.
 *
 * @since 0.9.0
 */
class InvalidComposerVendor extends \RuntimeException implements PressodyRecordsException {
	/**
	 * Create an exception for a Composer vendor that doesn't match the needed pattern.
	 *
	 * @since 0.9.0
	 *
	 * @param string         $vendor
	 * @param int            $code     Optional. The exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return InvalidComposerVendor
	 */
	public static function wrongFormat(
		string $vendor,
		int $code = 0,
		Throwable $previous = null
	): InvalidComposerVendor {
		$message = "The Composer vendor '{$vendor}' doesn't match the required Composer pattern. Check your vendor here: https://regexr.com/603co";

		return new static( $message, $code, $previous );
	}
}
