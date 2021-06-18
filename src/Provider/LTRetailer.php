<?php
/**
 * LT Retailer input provider.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use function PixelgradeLT\Records\get_setting;
use function PixelgradeLT\Records\is_debug_mode;
use function PixelgradeLT\Records\is_dev_url;
use WP_Http as HTTP;

/**
 * LT Retailer input provider class.
 *
 * When it is suitable, communicate with LT Retailer and let it have its say over matters.
 * We will only use hooks to intervene.
 *
 * @since 0.10.0
 */
class LTRetailer extends AbstractHookProvider {

	const LTRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_USER_PARTIAL = 'check_user_details';
	const LTRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL = 'details_to_update';

	const LTRETAILER_API_PWD = 'pixelgradelt_retailer';

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$this->add_filter( 'pixelgradelt_records/composition_validate_user_details', 'composition_validate_user_details', 10, 3 );
		$this->add_filter( 'pixelgradelt_records/composition_new_details', 'composition_new_details', 10, 3 );
	}

	/**
	 * Validate the composition's user details with LT Retailer.
	 *
	 * @since 0.10.0
	 *
	 * @param bool|\WP_Error $valid        Whether the user details are valid.
	 * @param array          $user_details The user details as decrypted from the composition details.
	 * @param array          $composition  The full composition details.
	 *
	 * @return bool|\WP_Error
	 */
	protected function composition_validate_user_details( $valid, array $user_details, array $composition ) {
		// Don't do anything if we have a WP_Error.
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Don't do anything if we don't have the needed settings.
		if ( ! $this->check_settings() ) {
			return $valid;
		}

		// Make the check user details request to LT Retailer.
		$url          = path_join( get_setting( 'ltretailer-compositions-root-endpoint' ), self::LTRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_USER_PARTIAL );
		$request_args = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'ltretailer-api-key' ) . ':' . self::LTRETAILER_API_PWD ),
			],
			'timeout'   => 5,
			'sslverify' => ! ( is_debug_mode() || is_dev_url( $url ) ),
			'body'      => [
				'userDetails' => $user_details,
				'composer'    => $composition,
			],
		];

		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			// Something went wrong with the request. Bail.
			return $valid;
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $valid;
	}

	/**
	 * Maybe update the composition's details by LT Retailer.
	 *
	 * @since 0.10.0
	 *
	 * @param false|array $new_details  The new composition details.
	 * @param array       $user_details The user details as decrypted from the composition details.
	 * @param array       $composition  The full composition details.
	 *
	 * @return false|array
	 */
	protected function composition_new_details( $new_details, array $user_details, array $composition ) {
		// Don't do anything if we have a false value.
		if ( false === $new_details ) {
			return $new_details;
		}

		// Don't do anything if we don't have the needed settings.
		if ( ! $this->check_settings() ) {
			return $new_details;
		}

		// Make the check user details request to LT Retailer.
		$url          = path_join( get_setting( 'ltretailer-compositions-root-endpoint' ), self::LTRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL );
		$request_args = [
			'headers'   => [
				'Content-Type' => 'application/json' ,
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'ltretailer-api-key' ) . ':' . self::LTRETAILER_API_PWD ),
			],
			'timeout'   => 5,
			'sslverify' => ! ( is_debug_mode() || is_dev_url( $url ) ),
			'body'      => json_encode( [
				'user'     =>  $user_details,
				'composer' =>  $composition,
			] ),
		];

		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			// Something went wrong with the request. Bail.
			return $new_details;
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( HTTP::NO_CONTENT === $response_code ) {
			// Nothing to update according to LT Retailer.
			return $new_details;
		}

		// @todo See if we need to update something.

		return $new_details;
	}

	/**
	 * Check that we have needed data in the plugin's settings.
	 *
	 * @since 0.10.0
	 *
	 * @return bool
	 */
	protected function check_settings(): bool {
		if ( empty( get_setting( 'ltretailer-compositions-root-endpoint' ) )
		     || empty( get_setting( 'ltretailer-api-key' ) ) ) {

			return false;
		}

		return true;
	}
}
