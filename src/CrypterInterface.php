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

use PixelgradeLT\Records\Exception\CrypterBadFormatException;
use PixelgradeLT\Records\Exception\CrypterEnvironmentIsBrokenException;
use PixelgradeLT\Records\Exception\CrypterWrongKeyOrModifiedCiphertextException;

/**
 * String crypter interface.
 *
 * @since 0.9.0
 */
interface CrypterInterface {

	/**
	 * Encode a given positive int into a string hash.
	 *
	 * @param string $secretString
	 *
	 * @throws CrypterEnvironmentIsBrokenException
	 * @return string The cipher text corresponding to the provided string.
	 */
	public function encrypt( string $secretString ): string;

	/**
	 * Decrypt a cipher text into the secret string.
	 *
	 * @param string $cipherText
	 *
	 * @throws CrypterBadFormatException
	 * @throws CrypterEnvironmentIsBrokenException
	 * @throws CrypterWrongKeyOrModifiedCiphertextException
	 * @return string The decrypted string.
	 */
	public function decrypt( string $cipherText ): string;
}
