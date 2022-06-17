<?php
/**
 * API Key repository interface.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Authentication\ApiKey;

use WP_User;

/**
 * API Key repository interface.
 *
 * @since 0.1.0
 */
interface ApiKeyRepository {
	/**
	 * Find an API Key by its token value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token API Key token.
	 * @return ApiKey|null
	 */
	public function find_by_token( string $token );

	/**
	 * Retrieve all API keys for a given user.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User $user WordPress user.
	 * @return ApiKey[] List of API keys.
	 */
	public function find_for_user( WP_User $user ): array;

	/**
	 * Revoke an API Key.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKey $api_key API Key.
	 */
	public function revoke( ApiKey $api_key );

	/**
	 * Save an API Key.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKey $api_key API Key.
	 * @return ApiKey API Key.
	 */
	public function save( ApiKey $api_key ): ApiKey;
}
