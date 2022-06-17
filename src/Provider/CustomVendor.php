<?php
/**
 * Custom vendor provider.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use function Pressody\Records\get_setting;

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
		add_filter( 'pressody_records/vendor', [ $this, 'filter_vendor' ], 5, 1 );
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
		if ( ! empty( $configured_vendor = get_setting( 'vendor' ) ) ) {
			$vendor = $configured_vendor;
		}

		return $vendor;
	}
}
