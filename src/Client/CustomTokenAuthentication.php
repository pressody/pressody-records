<?php
/**
 * Composer Client Custom Token Authentication provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Client;

use Cedaro\WP\Plugin\AbstractHookProvider;
use function PixelgradeLT\Records\get_setting;

/**
 * Composer Client Custom Token Authentication provider class.
 *
 * @since 0.1.0
 */
class CustomTokenAuthentication extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_filter( 'pixelgradelt_records/composer_client_config', [ $this, 'filter_config' ], 5 );
	}

	/**
	 * Update the Composer Client config based on the credentials (OAuth tokens) saved in the PixelgradeLT Records settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array $config Composer client string.
	 * @return array
	 */
	public function filter_config( array $config ): array {
		if ( ! empty( $github_oauth_token = get_setting( 'github-oauth-token' ) ) ) {
			if ( empty( $config['config'] ) ) {
				$config['config'] = [];
			}

			$config['config']['github-oauth'] = [
				'github.com' => $github_oauth_token,
			];
		}

		return $config;
	}
}
