<?php
/**
 * REST exception.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.10.0
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
use WP_Http as HTTP;

/**
 * REST exception class.
 *
 * @since 0.10.0
 */
class RestException extends \Exception implements PressodyRecordsException {
	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	protected int $status_code;

	/**
	 * Constructor.
	 *
	 * @since 0.10.0
	 *
	 * @param string         $message     Message.
	 * @param int            $status_code Optional. HTTP status code. Defaults to 500.
	 * @param int            $code        Exception code.
	 * @param Throwable|null $previous    Previous exception.
	 */
	public function __construct(
		string $message,
		int $status_code = HTTP::INTERNAL_SERVER_ERROR,
		int $code = 0,
		Throwable $previous = null
	) {
		$this->status_code = $status_code;
		$message           = $message ?: esc_html__( 'Internal Server Error', 'pressody_records' );

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Create an exception for invalid composer.json PD details.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forInvalidCompositionPDDetails(
		string $message = '',
		int $code = 0,
		Throwable $previous = null
	): RestException {
		if ( empty( $message ) ) {
			$message = esc_html__( 'The provided composer JSON data has invalid PD details.', 'pressody_records' );
		}

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for missing composer.json PD details.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forMissingCompositionPDDetails(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data is missing some PD details.', 'pressody_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for invalid composer.json PD fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forInvalidComposerFingerprint(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data has an invalid PD fingerprint.', 'pressody_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for missing composer.json PD fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forMissingComposerFingerprint(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data is missing the PD fingerprint.', 'pressody_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for broken encryption environment.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forBrokenEncryptionEnvironment(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'We could not run encryption. Please contact the administrator and let them know that something is wrong. Thanks in advance!', 'pressody_records' );

		return new static( $message, HTTP::INTERNAL_SERVER_ERROR, $code, $previous );
	}

	/**
	 * Retrieve the HTTP status code.
	 *
	 * @since 0.10.0
	 *
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->status_code;
	}
}
