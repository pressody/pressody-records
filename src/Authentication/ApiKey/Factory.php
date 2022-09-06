<?php
/**
 * API Key factory.
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

use function Pressody\Records\generate_random_string;

/**
 * API Key factory class.
 *
 * @since 0.1.0
 */
final class Factory {
	/**
	 * Create a new API key for a user.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User     $user  WordPress user.
	 * @param array|null  $data  Optional. Additional data associated with the API key.
	 * @param string|null $token Optional. API Key token.
	 *
	 * @return ApiKey
	 */
	public function create( WP_User $user, array $data = null, string $token = null ): ApiKey {
		$data = $data ?? [];

		if ( ! isset( $data['created'] ) ) {
			$data['created'] = time();
		}

		if ( empty( $token ) ) {
			$token = self::generate_token();
		}

		return new ApiKey( $user, $token, $data );
	}

	/**
	 * Generate an API key token.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private static function generate_token(): string {
		return generate_random_string( ApiKey::TOKEN_LENGTH );
	}
}
