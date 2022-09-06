<?php
/**
 * API Key authentication server.
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

namespace Pressody\Records\Authentication\ApiKey;

use Pressody\Records\Authentication\ServerInterface;
use Pressody\Records\Exception\AuthenticationException;
use Pressody\Records\HTTP\Request;

/**
 * API Key authentication server class.
 *
 * @since 0.1.0
 */
class Server implements ServerInterface {

	const AUTH_PWD = 'pressody_records';

	/**
	 * API Key repository.
	 *
	 * @var ApiKeyRepository
	 */
	protected ApiKeyRepository $repository;

	/**
	 * Constructor method.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKeyRepository $repository API Key repository.
	 */
	public function __construct( ApiKeyRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Check if the server should handle the current request.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request Request instance.
	 * @return bool
	 */
	public function check_scheme( Request $request ): bool {
		$header = $request->get_header( 'authorization' );

		// Bail if the authorization header doesn't exist.
		if ( null === $header || 0 !== stripos( $header, 'basic ' ) ) {
			return false;
		}

		// The password part of the authorization header isn't used for API Key authentication.
		// We use instead the PHP_AUTH_PW header.
		// The password is hardcoded because the username is a private API Key.
		$auth_password = $request->get_header( 'PHP_AUTH_PW' );

		// Bail if this isn't a Pressody Records authentication request.
		if ( self::AUTH_PWD !== $auth_password ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle authentication.
	 *
	 * @since 0.1.0
	 *
	 * @param Request $request Request instance.
	 *
	 * @throws AuthenticationException If authentication fails.
	 * @return int A user ID.
	 */
	public function authenticate( Request $request ): int {
		$api_key_id = $request->get_header( 'PHP_AUTH_USER' );

		// Bail if an API Key wasn't provided.
		if ( null === $api_key_id ) {
			throw AuthenticationException::forMissingAuthorizationHeader();
		}

		$api_key = $this->repository->find_by_token( $api_key_id );

		// Bail if the API Key doesn't exist.
		if ( null === $api_key ) {
			throw AuthenticationException::forInvalidCredentials();
		}

		$user = $api_key->get_user();

		// Bail if the user couldn't be determined.
		if ( ! $this->validate_user( $user ) ) {
			throw AuthenticationException::forInvalidCredentials();
		}

		$this->maybe_update_last_used_time( $api_key );

		return $user->ID;
	}

	/**
	 * Update the last used time if it's been more than a minute.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKey $api_key API Key.
	 */
	protected function maybe_update_last_used_time( ApiKey $api_key ) {
		$timestamp = time();
		$last_used = $api_key['last_used'] ?? 0;

		if ( $timestamp - $last_used < MINUTE_IN_SECONDS ) {
			return;
		}

		$api_key['last_used'] = $timestamp;
		$this->repository->save( $api_key );
	}

	/**
	 * Whether a user is valid.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $user WordPress user instance.
	 * @return bool
	 */
	protected function validate_user( $user ): bool {
		return ! empty( $user ) && ! is_wp_error( $user ) && $user->exists();
	}
}
