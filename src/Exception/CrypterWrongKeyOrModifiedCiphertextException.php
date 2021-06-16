<?php
/**
 * Decryption exception for when the wrong encryption key or a modified cipher text was provided.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.9.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Exception;

/**
 * Decryption exception class for when the wrong encryption key or a modified cipher text was provided.
 *
 * @since 0.9.0
 */
class CrypterWrongKeyOrModifiedCiphertextException extends CrypterException {

}
