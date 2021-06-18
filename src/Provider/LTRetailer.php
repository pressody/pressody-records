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

/**
 * LT Retailer input provider class.
 *
 * When it is suitable, communicate with LT Retailer and let it have its say over matters.
 * We will only use hooks to intervene.
 *
 * @since 0.10.0
 */
class LTRetailer extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$this->add_filter( 'pixelgradelt_records/composition_validate_user_details', 'composition_validate_user_details', 10, 3 );
		$this->add_filter( 'pixelgradelt_records/composition_new_details', 'composition_new_details', 10, 3 );
	}

	/**
	 * Validate the composition's user details.
	 *
	 * @since 0.10.0
	 *
	 * @param bool|\WP_Error $valid       Whether the user details are valid.
	 * @param array          $details     The user details as decrypted from the composition details.
	 * @param array          $composition The full composition details.
	 *
	 * @return bool|\WP_Error
	 */
	protected function composition_validate_user_details( $valid, array $details, array $composition ) {
		// Don't do anything if we have a WP_Error.
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Don't do anything if we don't have the needed LT Records endpoint.


		return $valid;
	}

	/**
	 * Maybe update the composition's details.
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

		// Don't do anything if we don't have the needed LT Records endpoint.


		return $new_details;
	}
}
