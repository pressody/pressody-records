<?php
/**
 * LT Retailer input provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.10.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * LT Retailer input provider class.
 *
 * When it is suitable, communicate with LT Retailer and let it have it's say over matters.
 *
 * @since 0.10.0
 */
class LTRetailer extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_filter( 'pixelgradelt_records/vendor', [ $this, 'filter_vendor' ], 5, 1 );
	}

	/**
	 * Update the vendor string based on the vendor setting value.
	 *
	 * @since 0.10.0
	 *
	 * @param string $vendor Vendor string.
	 * @return string
	 */
	public function filter_vendor( string $vendor ): string {
		$option = get_option( 'pixelgradelt_records' );
		if ( ! empty( $option['vendor'] ) ) {
			$vendor = $option['vendor'];
		}

		return $vendor;
	}
}
