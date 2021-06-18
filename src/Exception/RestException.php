<?php
/**
 * REST exception.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.10.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Exception;

use Throwable;
use WP_Http as HTTP;

/**
 * REST exception class.
 *
 * @since 0.10.0
 */
class RestException extends \Exception implements PixelgradeltRecordsException {
	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	protected int $status_code;

	/**
	 * Constructor.
	 *
	 * @since 0.10.0
	 *
	 * @param string         $message     Message.
	 * @param int            $status_code Optional. HTTP status code. Defaults to 500.
	 * @param int            $code        Exception code.
	 * @param Throwable|null $previous    Previous exception.
	 */
	public function __construct(
		string $message,
		int $status_code = HTTP::INTERNAL_SERVER_ERROR,
		int $code = 0,
		Throwable $previous = null
	) {
		$this->status_code = $status_code;
		$message           = $message ?: esc_html__( 'Internal Server Error', 'pixelgradelt_records' );

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Create an exception for invalid composer.json LT user details.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forInvalidComposerUserDetails(
		string $message = '',
		int $code = 0,
		Throwable $previous = null
	): RestException {
		if ( empty( $message ) ) {
			$message = esc_html__( 'The provided composer JSON data has invalid LT user details.', 'pixelgradelt_records' );
		}

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for missing composer.json LT user details.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forMissingComposerUserDetails(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data is missing some LT user details.', 'pixelgradelt_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for invalid composer.json LT fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forInvalidComposerFingerprint(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data has an invalid LT fingerprint.', 'pixelgradelt_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for missing composer.json LT fingerprint.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forMissingComposerFingerprint(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'The provided composer JSON data is missing the LT fingerprint.', 'pixelgradelt_records' );

		return new static( $message, HTTP::NOT_ACCEPTABLE, $code, $previous );
	}

	/**
	 * Create an exception for broken encryption environment.
	 *
	 * @since 0.10.0
	 *
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return RestException
	 */
	public static function forBrokenEncryptionEnvironment(
		int $code = 0,
		Throwable $previous = null
	): RestException {
		$message = esc_html__( 'We could not run encryption. Please contact the administrator and let them know that something is wrong. Thanks in advance!', 'pixelgradelt_records' );

		return new static( $message, HTTP::INTERNAL_SERVER_ERROR, $code, $previous );
	}

	/**
	 * Retrieve the HTTP status code.
	 *
	 * @since 0.10.0
	 *
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->status_code;
	}
}
