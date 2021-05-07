<?php
/**
 * String hashes encode and decode provider.
 *
 * @link    https://github.com/vinkla/hashids
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since   0.9.0
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

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
		int $minHashLength = 3,
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
