<?php
/**
 * Invalid Composer vendor exception.
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
