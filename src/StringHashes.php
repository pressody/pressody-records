<?php
/**
 * String hashes encode and decode provider.
 *
 * @link    https://github.com/vinkla/hashids
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since   0.9.0
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

declare ( strict_types=1 );

namespace Pressody\Records;

use Hashids\Hashids;

/**
 * String hashes encode and decode provider class.
 *
 * @since 0.9.0
 */
class StringHashes implements HasherInterface {

	protected Hashids $engine;

	public function __construct(
		string $salt = '',
		int $minHashLength = 5,
		string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
	) {

		$this->engine = new Hashids( $salt, $minHashLength, $alphabet );
	}

	/**
	 * Encode a given positive int into a string hash.
	 *
	 * @param int $number
	 *
	 * @return string The string hash corresponding to the provided positive int.
	 */
	public function encode( int $number ): string {
		return $this->engine->encode( $number );
	}

	/**
	 * Decode a string hash into the original positive int.
	 *
	 * @param string $hash
	 *
	 * @return int The decoded number or zero on failure.
	 */
	public function decode( string $hash ): int {
		$result = $this->engine->decode( $hash );
		if ( is_array( $result ) ) {
			return (int) reset( $result );
		}

		return 0;
	}
}
