<?php
/**
 * PD Retailer integration provider.
 *
 * @since   0.10.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\Integration;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\Utils\ArrayHelpers;
use function Pressody\Records\get_setting;
use function Pressody\Records\is_debug_mode;
use function Pressody\Records\is_dev_url;
use WP_Http as HTTP;

/**
 * PD Retailer integration provider class.
 *
 * When it is suitable, communicate with PD Retailer and let it have its say over matters.
 * We will only use hooks to intervene.
 *
 * @since 0.10.0
 */
class PDRetailer extends AbstractHookProvider {

	const PDRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_PDDETAILS_PARTIAL = 'check_pddetails';
	const PDRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL = 'instructions_to_update';

	const PDRETAILER_API_PWD = 'pressody_retailer';

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$this->add_filter( 'pressody_records/validate_encrypted_pddetails', 'validate_encrypted_pddetails', 10, 3 );
		$this->add_filter( 'pressody_records/composition_instructions_to_update', 'composition_instructions_to_update', 10, 2 );
	}

	/**
	 * Validate the encrypted composition PD details with PD Retailer.
	 *
	 * @since 0.10.0
	 *
	 * @param bool|\WP_Error $valid               Whether the PD details are valid.
	 * @param string         $encrypted_pddetails Encrypted composition PD details.
	 * @param array          $composition         The full composition data.
	 *
	 * @return bool|\WP_Error
	 */
	protected function validate_encrypted_pddetails( $valid, string $encrypted_pddetails, array $composition ) {
		// Don't do anything if we have a WP_Error.
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Don't do anything if we don't have the needed settings.
		if ( ! $this->check_settings() ) {
			return $valid;
		}

		// Make the check PD details request to PD Retailer.
		$url          = path_join( get_setting( 'pdretailer-compositions-root-endpoint' ), self::PDRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_PDDETAILS_PARTIAL );
		$request_args = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'pdretailer-api-key' ) . ':' . self::PDRETAILER_API_PWD ),
			],
			'timeout'   => 5,
			'sslverify' => ! ( is_debug_mode() || is_dev_url( $url ) ),
			'body'      => [
				'pddetails' => $encrypted_pddetails,
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

		// There was something wrong with the data that PD Retailer didn't like.
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		$error            = [];
		$error['code']    = 'pdretailer_invalid_pddetails';
		$error['message'] = esc_html__( 'Sorry, PD Retailer didn\'t validate the composition\'s encrypted PD details.', 'pressody_records' );
		$error['data']    = [
			'status' => HTTP::NOT_ACCEPTABLE,
		];
		// Add the data we received from PD Retailer.
		$error['data'] = [
			'pdretailer_response' => [
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
	 * Maybe get instructions to update the composition from PD Retailer.
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

		// Check the composition's PD details with PD Retailer.
		$url          = path_join( get_setting( 'pdretailer-compositions-root-endpoint' ), self::PDRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL );
		$request_args = [
			'headers'   => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( get_setting( 'pdretailer-api-key' ) . ':' . self::PDRETAILER_API_PWD ),
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
			// Nothing to update according to PD Retailer.
			return $instructions_to_update;
		}

		if ( $response_code >= HTTP::BAD_REQUEST ) {
			$error            = [];
			$error['code']    = 'pdretailer_composition_instructions_to_update_error';
			$error['message'] = esc_html__( 'Sorry, PD Retailer didn\'t provide composition update instructions due to some errors.', 'pressody_records' );
			$error['data']    = [
				'status' => HTTP::NOT_ACCEPTABLE,
			];
			// Add the data we received from PD Retailer.
			$error['data'] = [
				'pdretailer_response' => [
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
		if ( empty( get_setting( 'pdretailer-compositions-root-endpoint' ) )
		     || empty( get_setting( 'pdretailer-api-key' ) ) ) {

			return false;
		}

		return true;
	}
}
