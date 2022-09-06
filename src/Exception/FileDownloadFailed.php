<?php
/**
 * Failed file download exception.
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
 * Failed file download exception class.
 *
 * @since 0.1.0
 */
class FileDownloadFailed extends \RuntimeException implements PressodyRecordsException {
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
