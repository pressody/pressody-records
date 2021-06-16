<?php
/**
 * String crypter interface.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use Psr\Log\LoggerInterface;

/**
 * String crypter interface.
 *
 * @since 0.9.0
 */
interface CrypterInterface {

	public function __construct();

	/**
	 * Encode a given positive int into a string hash.
	 *
	 * @param string $secretString
	 *
	 * @return string The cipher text corresponding to the provided string.
	 */
	public function encrypt( string $secretString ): string;

	/**
	 * Decrypt a cipher text into the secret string.
	 *
	 * @param string $cipherText
	 *
	 * @return string The decrypted string.
	 */
	public function decrypt( string $cipherText ): string;
}
