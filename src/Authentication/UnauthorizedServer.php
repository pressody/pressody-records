<?php
/**
 * Unauthorized authentication server.
 *
 * Prevents access if all authentication methods have failed.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Authentication;

use PixelgradeLT\Records\Exception\AuthenticationException;
use PixelgradeLT\Records\HTTP\Request;

/**
 * Unauthorized authentication server class.
 *
 * @since 0.1.0
 */
class UnauthorizedServer implements ServerInterface {
	/**
	 * Check if the server should handle the current request.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request Request instance.
	 * @return bool
	 */
	public function check_scheme( Request $request ): bool {
		return true;
	}

	/**
	 * Handle authentication.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request Request instance.
	 *
	 * @throws AuthenticationException If the user has not been authenticated at this point.
	 */
	public function authenticate( Request $request ): int {
		throw AuthenticationException::forAuthenticationRequired();
	}
}
