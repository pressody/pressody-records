<?php
/**
 * Assets provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Assets provider class.
 *
 * @since 0.1.0
 */
class AdminAssets extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 1 );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 0.1.0
	 */
	public function register_assets() {
		wp_register_script(
			'pixelgradelt_records-admin',
			$this->plugin->get_url( 'assets/js/admin.js' ),
			[ 'jquery', 'wp-backbone', 'wp-util' ],
			'20210210',
			true
		);

		wp_register_script(
			'pixelgradelt_records-api-keys',
			$this->plugin->get_url( 'assets/js/api-keys.js' ),
			[ 'wp-backbone', 'wp-util' ],
			'20210210',
			true
		);

		wp_localize_script(
			'pixelgradelt_records-api-keys',
			'_pixelgradelt_recordsApiKeySettings',
			[
				'createApiKeyNonce' => wp_create_nonce( 'create-api-key' ),
				'deleteApiKeyNonce' => wp_create_nonce( 'delete-api-key' ),
				'l10n'              => [
					'aysDeleteApiKey' => esc_html__( 'Are you sure you want to delete this API Key?', 'pixelgradelt_records' ),
				],
			]
		);

		wp_register_script(
			'pixelgradelt_records-package-settings',
			$this->plugin->get_url( 'assets/js/package-settings.js' ),
			[ 'wp-backbone', 'wp-util' ],
			'20180708',
			true
		);

		wp_register_style(
			'pixelgradelt_records-admin',
			$this->plugin->get_url( 'assets/css/admin.css' ),
			[],
			'20210210'
		);
	}
}
