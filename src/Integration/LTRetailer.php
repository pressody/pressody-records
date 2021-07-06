<?php
/**
 * LT Retailer integration provider.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Integration;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Utils\ArrayHelpers;
use function PixelgradeLT\Records\get_setting;
use function PixelgradeLT\Records\is_debug_mode;
use function PixelgradeLT\Records\is_dev_url;
use WP_Http as HTTP;

/**
 * LT Retailer integration provider class.
 *
 * When it is suitable, communicate with LT Retailer and let it have its say over matters.
 * We will only use hooks to intervene.
 *
 * @since 0.10.0
 */
class LTRetailer extends AbstractHookProvider {

	const LTRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_LTDETAILS_PARTIAL = 'check_ltdetails';
	const LTRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL = 'instructions_to_update';

	const LTRETAILER_API_PWD = 'pixelgradelt_retailer';

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$this->add_filter( 'pixelgradelt_records/validate_encrypted_ltdetails', 'validate_encrypted_ltdetails', 10, 3 );
		$this->add_filter( 'pixelgradelt_records/composition_instructions_to_update', 'composition_instructions_to_update', 10, 2 );
	}

	/**
	 * Validate the encrypted composition LT details with LT Retailer.
	 *
	 * @since 0.10.0
	 *
	 * @param bool|\WP_Error $valid               Whether the LT details are valid.
	 * @param string         $encrypted_ltdetails Encrypted composition LT details.
	 * @param array          $composition         The full composition data.
	 *
	 * @return bool|\WP_Error
	 */
	protected function validate_encrypted_ltdetails( $valid, string $encrypted_ltdetails, array $composition ) {
		// Don't do anything if we have a WP_Error.
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Don't do anything if we don't have the needed settings.
		if ( ! $this->check_settings() ) {
			return $valid;
		}

		// Make the check LT details request to LT Retailer.
		$url          = path_join( get_setting( 'ltretailer-compositions-root-endpoint' ), self::LTRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_LTDETAILS_PARTIAL );
		$request_args = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'ltretailer-api-key' ) . ':' . self::LTRETAILER_API_PWD ),
			],
			'timeout'   => 5,
			'sslverify' => ! ( is_debug_mode() || is_dev_url( $url ) ),
			'body'      => [
				'ltdetails' => $encrypted_ltdetails,
				'composer'  => $composition,
			],
		];

		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			// Something went wrong with the request. Bail.
			return $valid;
		}

		// If we receive a 200 response, all is good.
		if ( HTTP::OK === wp_remote_retrieve_response_code( $response ) ) {
			return true;
		}

		// There was something wrong with the data that LT Retailer didn't like.
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		$error            = [];
		$error['code']    = 'ltretailer_invalid_ltdetails';
		$error['message'] = esc_html__( 'Sorry, LT Retailer didn\'t validate the composition\'s encrypted LT details.', 'pixelgradelt_records' );
		$error['data']    = [
			'status' => HTTP::NOT_ACCEPTABLE,
		];
		// Add the data we received from LT Retailer.
		$error['data'] = [
			'ltretailer_response' => [
				'code'    => $response_body['code'] ?? '',
				'message' => $response_body['message'] ?? '',
				'data'    => $response_body['data'] ?? '',
			],
		];

		return new \WP_Error(
			$error['code'],
			$error['message'],
			$error['data'],
		);
	}

	/**
	 * Maybe get instructions to update the composition from LT Retailer.
	 *
	 * @since 0.10.0
	 *
	 * @param false|array $instructions_to_update The instructions to update the composition by.
	 * @param array       $composition            The full composition data.
	 *
	 * @return false|\WP_Error|array
	 */
	protected function composition_instructions_to_update( $instructions_to_update, array $composition ) {
		// Don't do anything if we have a false or WP_Error value.
		if ( false === $instructions_to_update || is_wp_error( $instructions_to_update ) ) {
			return $instructions_to_update;
		}

		// Don't do anything if we don't have the needed settings.
		if ( ! $this->check_settings() ) {
			return $instructions_to_update;
		}

		// Check the composition's LT details with LT Retailer.
		$url          = path_join( get_setting( 'ltretailer-compositions-root-endpoint' ), self::LTRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL );
		$request_args = [
			'headers'   => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'ltretailer-api-key' ) . ':' . self::LTRETAILER_API_PWD ),
			],
			'timeout'   => 5,
			'sslverify' => ! ( is_debug_mode() || is_dev_url( $url ) ),
			'body'      => json_encode( [
				'composer' => $composition,
			] ),
		];

		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			// Something went wrong with the request. Bail.
			return $instructions_to_update;
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( HTTP::NO_CONTENT === $response_code ) {
			// Nothing to update according to LT Retailer.
			return $instructions_to_update;
		}

		if ( $response_code >= HTTP::BAD_REQUEST ) {
			$error            = [];
			$error['code']    = 'ltretailer_composition_instructions_to_update_error';
			$error['message'] = esc_html__( 'Sorry, LT Retailer didn\'t provide composition update instructions due to some errors.', 'pixelgradelt_records' );
			$error['data']    = [
				'status' => HTTP::NOT_ACCEPTABLE,
			];
			// Add the data we received from LT Retailer.
			$error['data'] = [
				'ltretailer_response' => [
					'code'    => $response_body['code'] ?? '',
					'message' => $response_body['message'] ?? '',
					'data'    => $response_body['data'] ?? '',
				],
			];

			return new \WP_Error(
				$error['code'],
				$error['message'],
				$error['data'],
			);
		} elseif ( $response_code !== HTTP::OK ) {
			// Bail.
			return $instructions_to_update;
		}

		// We have some instructions to update, in the response body
		return ArrayHelpers::array_merge_recursive_distinct( $instructions_to_update, $response_body );
	}

	/**
	 * Check that we have the needed settings in the plugin's settings.
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
