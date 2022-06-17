<?php
/**
 * GPLVault Update Manager plugin integration.
 *
 * @since   0.6.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\Integration;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * GPLVault Update Manager plugin integration provider class.
 *
 * @since 0.6.0
 */
class GPLVault extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 0.6.0
	 */
	public function register_hooks() {
		add_filter( 'pressody_records/package_download_url', [ $this, 'filter_package_download_url' ] );
	}

	/**
	 * Filter the download URL for package updates from GPLVault.
	 *
	 * The GPLVault Update Manager plugin inserts a deferred URL in the plugin and
	 * theme update transients until just before a package is downloaded. It
	 * then requests the download URL from a remote API and swaps it out.
	 *
	 * @since 0.6.0
	 *
	 * @see   GPLVault_Admin::maybe_deferred_download()
	 *
	 * @link  https://github.com/envato/wp-envato-market
	 *
	 * @param string $download_url Download URL.
	 *
	 * @return string
	 */
	public function filter_package_download_url( string $download_url ): string {
		if ( false !== strrpos( $download_url, 'gv_delayed_download' ) && false !== strrpos( $download_url, 'gv_item_id' ) ) {
			parse_str( wp_parse_url( $download_url, PHP_URL_QUERY ), $vars );

			// Don't send a URL if the actual download URL can't be determined.
			$download_url = '';

			if ( $vars['gv_item_id'] ) {
				$download_url = gv_api_manager()->set_initials()->download( [ 'product_id' => $vars['gv_item_id'] ] );
			}
		}

		return $download_url;
	}
}
