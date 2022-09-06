<?php
/**
 * Edit User screen provider.
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

namespace Pressody\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\Authentication\ApiKey\ApiKey;
use Pressody\Records\Authentication\ApiKey\ApiKeyRepository;
use Pressody\Records\Capabilities;
use WP_User;

use function Pressody\Records\get_edited_user_id;
use function Pressody\Records\preload_rest_data;

/**
 * Edit User screen provider class.
 *
 * @since 0.1.0
 */
class EditUser extends AbstractHookProvider {
	/**
	 * API Key repository.
	 *
	 * @var ApiKeyRepository
	 */
	protected $api_keys;

	/**
	 * Create the setting screen.
	 *
	 * @param ApiKeyRepository $api_keys API Key repository.
	 */
	public function __construct( ApiKeyRepository $api_keys ) {
		$this->api_keys = $api_keys;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		$user_id = get_edited_user_id();

		// Only load the screen for users that can view or download packages.
		if (
			! user_can( $user_id, Capabilities::DOWNLOAD_PACKAGES )
			&& ! user_can( $user_id, Capabilities::VIEW_PACKAGES )
		) {
			return;
		}

		add_action( 'load-profile.php', [ $this, 'load_screen' ] );
		add_action( 'load-user-edit.php', [ $this, 'load_screen' ] );
	}

	/**
	 * Set up the screen.
	 *
	 * @since 0.1.0
	 */
	public function load_screen() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'edit_user_profile', [ $this, 'render_api_keys_section' ] );
		add_action( 'show_user_profile', [ $this, 'render_api_keys_section' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pressody_records-admin' );
		wp_enqueue_style( 'pressody_records-admin' );

		wp_enqueue_script( 'pressody_records-access' );

		wp_localize_script(
			'pressody_records-access',
			'_pressodyRecordsAccessData',
			[
				'editedUserId' => get_edited_user_id(),
			]
		);

		preload_rest_data(
			[
				'/pressody_records/v1/apikeys?user=' . get_edited_user_id(),
			]
		);
	}

	/**
	 * Display the API Keys section.
	 *
	 * @param WP_User $user WordPress user instance.
	 */
	public function render_api_keys_section( WP_User $user ) {
		printf( '<h2>%s</h2>', esc_html__( 'Pressody Records API Keys', 'pressody_records' ) );

		printf(
			'<p><strong>%s</strong></p>',
			/* translators: %s: <code>pressody_records</code> */
			sprintf( esc_html__( 'The password for all API Keys is %s. Use the API key as the username.', 'pressody_records' ), '<code>pressody_records</code>' )
		);

		echo '<div id="pressody_records-api-key-manager"></div>';
	}
}
