<?php
/**
 * Authentication provider.
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

namespace Pressody\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pimple\ServiceIterator;
use Pressody\Records\Authentication\ServerInterface;
use Pressody\Records\Capabilities as Caps;
use Pressody\Records\Exception\AuthenticationException;
use Pressody\Records\HTTP\Request;
use WP_Error;

/**
 * Authentication provider class.
 *
 * @since 0.1.0
 */
class Authentication extends AbstractHookProvider {
	/**
	 * Errors that occurred during authentication.
	 *
	 * @var AuthenticationException|null Authentication exception.
	 */
	protected ?AuthenticationException $auth_status = null;

	/**
	 * Server request.
	 *
	 * @var Request
	 */
	protected Request $request;

	/**
	 * Authentication servers.
	 *
	 * @var ServiceIterator
	 */
	protected ServiceIterator $servers;

	/**
	 * Whether to attempt to authenticate.
	 *
	 * Helps prevent recursion and processing multiple times per request.
	 *
	 * @var bool
	 */
	protected bool $should_attempt = true;

	/**
	 * Constructor.
	 *
	 * @param ServiceIterator $servers Authentication servers.
	 * @param Request         $request Request instance.
	 */
	public function __construct( ServiceIterator $servers, Request $request ) {
		$this->servers = $servers;
		$this->request = $request;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		if ( ! $this->is_pressody_records_request() ) {
			return;
		}

		add_filter( 'determine_current_user', [ $this, 'determine_current_user' ] );
		add_filter( 'user_has_cap', [ $this, 'maybe_allow_public_access' ] );

		// Allow cookie authentication to work for our requests.
		remove_filter( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 );

		// Allow CORS requests.
		add_action('init', [ $this, 'allow_cors' ] );
	}

	/**
	 * Handle authentication.
	 *
	 * @since 0.1.0
	 *
	 * @param int|bool $user_id Current user ID or false if unknown.
	 * @throws \LogicException If a registered server doesn't implement the server interface.
	 * @return int|bool A user on success, or false on failure.
	 */
	public function determine_current_user( $user_id ) {
		if ( ! empty( $user_id ) || ! $this->should_attempt ) {
			return $user_id;
		}

		$this->should_attempt = false;

		foreach ( $this->servers as $server ) {
			if ( ! $server instanceof ServerInterface ) {
				throw new \LogicException( 'Authentication servers must implement \Pressody\Records\Authentication\ServerInterface.' );
			}

			if ( ! $server->check_scheme( $this->request ) ) {
				continue;
			}

			try {
				$user_id = $server->authenticate( $this->request );
			} catch ( AuthenticationException $e ) {
				$this->auth_status = $e;

				add_filter( 'rest_authentication_errors', [ $this, 'get_authentication_errors' ] );
			}

			break;
		}

		return $user_id;
	}

	/**
	 * Report authentication errors.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Error|mixed $value Error from another authentication handler,
	 *                              null if we should handle it, or another value if not.
	 * @throws AuthenticationException If this isn't a REST request.
	 * @return WP_Error|bool|null
	 */
	public function get_authentication_errors( $value ) {
		if ( null !== $value || is_user_logged_in() ) {
			return $value;
		}

		$e = $this->auth_status;

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			throw $e;
		}

		return new WP_Error(
			$e->getCode(),
			$e->getMessage(),
			[ 'status' => $e->getStatusCode() ]
		);
	}

	/**
	 * Whether the current request is for a Pressody Records route or REST endpoint.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	protected function is_pressody_records_request(): bool {
		$request_path = $this->get_request_path();

		// phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		if ( ! empty( $_GET['pressody_records_route'] ) ) {
			return true;
		}

		if ( 0 === strpos( $request_path, '/pdpackagist' ) || 0 === strpos( $request_path, '/pdparts' ) ) {
			return true;
		}

		if ( 0 === strpos( $request_path, '/wp-json/pressody_records/' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieve the request path.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function get_request_path(): string {
		$request_path = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		if ( ! $request_path ) {
			return '';
		}

		$wp_base = get_home_url( null, '/', 'relative' );
		if ( $request_path && 0 === strpos( $request_path, $wp_base ) ) {
			$request_path = substr( $request_path, \strlen( $wp_base ) );
		}

		return '/' . ltrim( $request_path, '/' );
	}

	/**
	 * Sets and returns all the capabilities the current user has and should have.
	 *
	 * Appends `allcaps` with pressody_records_download_packages
	 * as well as pressody_records_view_packages if there are no servers,
	 * meaning that authentication should be skipped.
	 *
	 * @since 0.1.0
	 *
	 * @param array $allcaps All capabilities the current user has.
	 * @return array
	 */
	public function maybe_allow_public_access( array $allcaps ): array {
		if ( 0 >= \iterator_count( $this->servers ) ) {
			$allcaps[ Caps::DOWNLOAD_PACKAGES ] = true;
			$allcaps[ Caps::VIEW_PACKAGES ]     = true;
		}

		return $allcaps;
	}

	/**
	 * Allow CORS requests.
	 *
	 * @since 0.11.0
	 */
	public function allow_cors() {
		$origin = get_http_origin();

		header("Access-Control-Allow-Origin: " . $origin);
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
		header("Access-Control-Allow-Credentials: true");
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization');
		if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
			status_header(200);
			exit();
		}
	}
}
