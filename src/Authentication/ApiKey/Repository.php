<?php
/**
 * API Key repository.
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

use WP_User;
use WP_User_Query;

/**
 * API Key repository class.
 *
 * @since 0.1.0
 */
class Repository implements ApiKeyRepository {
	/**
	 * Prefix for user meta keys.
	 *
	 * @var string
	 */
	const META_PREFIX = 'pressody_records_api_key.';

	/**
	 * API Key factory.
	 *
	 * @var Factory
	 */
	protected $factory;

	/**
	 * Create the API Key repository.
	 *
	 * @since 0.1.0
	 *
	 * @param Factory $factory API Key factory.
	 */
	public function __construct( Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Find an API Key by its token value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token API Key token.
	 * @return ApiKey|null
	 */
	public function find_by_token( string $token ) {
		$meta_key = static::get_meta_key( $token );

		$query = new WP_User_Query(
			[
				'number'      => 1,
				'count_total' => false,
				'meta_query'  => [
					[
						'key'     => $meta_key,
						'compare' => 'EXISTS',
					],
				],
			]
		);

		$users = $query->get_results();
		if ( empty( $users ) ) {
			return null;
		}

		$user = $users[0];
		$data = get_user_meta( $user->ID, wp_slash( $meta_key ), true );

		return $this->factory->create( $user, $data, $token );
	}

	/**
	 * Retrieve all API keys for a given user.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User $user WordPress user.
	 * @return ApiKey[] List of API keys.
	 */
	public function find_for_user( WP_User $user ): array {
		$meta = get_user_meta( $user->ID );
		$keys = [];

		foreach ( $meta as $meta_key => $values ) {
			if ( 0 !== strpos( (string) $meta_key, static::META_PREFIX ) ) {
				continue;
			}

			$token  = substr( $meta_key, \strlen( static::META_PREFIX ) );
			$data   = maybe_unserialize( $values[0] );
			$keys[] = $this->factory->create( $user, $data, $token );
		}

		return $keys;
	}

	/**
	 * Revoke an API Key.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKey $api_key API Key.
	 */
	public function revoke( ApiKey $api_key ) {
		delete_user_meta(
			$api_key->get_user()->ID,
			static::get_meta_key( $api_key->get_token() )
		);
	}

	/**
	 * Save an API Key.
	 *
	 * @since 0.1.0
	 *
	 * @param ApiKey $api_key API Key.
	 * @return ApiKey API Key.
	 */
	public function save( ApiKey $api_key ): ApiKey {
		update_user_meta(
			$api_key->get_user()->ID,
			static::get_meta_key( $api_key->get_token() ),
			$api_key->get_data()
		);

		return $api_key;
	}

	/**
	 * Retrieve the meta key for saving API key data.
	 *
	 * @since 0.1.0
	 *
	 * @param string $token API key token.
	 * @return string
	 */
	protected static function get_meta_key( string $token ): string {
		return static::META_PREFIX . $token;
	}
}
