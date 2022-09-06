<?php
/**
 * Composer Client Custom Token Authentication provider.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Client;

use Cedaro\WP\Plugin\AbstractHookProvider;
use function Pressody\Records\get_setting;

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
		add_filter( 'pressody_records/composer_client_config', [ $this, 'filter_config' ], 5 );
	}

	/**
	 * Update the Composer Client config based on the credentials (OAuth tokens) saved in the Pressody Records settings.
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
