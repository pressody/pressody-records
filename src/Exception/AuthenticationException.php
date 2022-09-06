<?php
/**
 * Authentication exception.
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
use WP_Http as HTTP;

/**
 * Authentication exception class.
 *
 * @since 0.1.0
 */
class AuthenticationException extends HttpException {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * Response headers.
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string         $code        Exception code.
	 * @param string         $message     Message.
	 * @param int            $status_code Optional. HTTP status code. Defaults to 500.
	 * @param array          $headers     Optional. Response headers.
	 * @param Throwable|null $previous    Optional. Previous exception.
	 */
	public function __construct(
		string $code,
		string $message,
		int $status_code = HTTP::INTERNAL_SERVER_ERROR,
		array $headers = [],
		Throwable $previous = null
	) {
		$this->code    = $code;
		$this->headers = $headers;

		parent::__construct( $message, $status_code, 0, $previous );
	}

	/**
	 * Create an exception for requests that require authentication.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $headers  Response headers.
	 * @param string         $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return AuthenticationException
	 */
	public static function forAuthenticationRequired(
		array $headers = [],
		string $code = 'invalid_request',
		Throwable $previous = null
	): AuthenticationException {
		$headers = $headers ?: [ 'WWW-Authenticate' => 'Basic realm="Pressody Records"' ];
		$message = 'Authentication is required for this resource.';

		return new static( $code, $message, HTTP::UNAUTHORIZED, $headers, $previous );
	}

	/**
	 * Create an exception for invalid credentials.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $headers  Response headers.
	 * @param string         $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return AuthenticationException
	 */
	public static function forInvalidCredentials(
		array $headers = [],
		string $code = 'invalid_credentials',
		Throwable $previous = null
	): AuthenticationException {
		$headers = $headers ?: [ 'WWW-Authenticate' => 'Basic realm="Pressody Records"' ];
		$message = 'Invalid credentials.';

		return new static( $code, $message, HTTP::UNAUTHORIZED, $headers, $previous );
	}

	/**
	 * Create an exception for a missing authorization header.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $headers  Response headers.
	 * @param string         $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return AuthenticationException
	 */
	public static function forMissingAuthorizationHeader(
		array $headers = [],
		string $code = 'invalid_credentials',
		Throwable $previous = null
	): AuthenticationException {
		$headers = $headers ?: [ 'WWW-Authenticate' => 'Basic realm="Pressody Records"' ];
		$message = 'Missing authorization header.';

		return new static( $code, $message, HTTP::UNAUTHORIZED, $headers, $previous );
	}

	/**
	 * Retrieve the response headers.
	 *
	 * @since 0.1.0
	 *
	 * @return array Map of header name to header value.
	 */
	public function getHeaders(): array {
		return $this->headers;
	}
}
