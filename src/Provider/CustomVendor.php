<?php
/**
 * Custom vendor provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Custom vendor provider class.
 *
 * @since 0.1.0
 */
class CustomVendor extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_filter( 'pixelgradelt_records_vendor', [ $this, 'filter_vendor' ], 5, 1 );
	}

	/**
	 * Update the vendor string based on the vendor setting value.
	 *
	 * @since 0.1.0
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
