<?php
/**
 * Members plugin integration.
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

namespace Pressody\Records\Integration;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\Capabilities;

/**
 * Members plugin integration provider class.
 *
 * @since 0.1.0
 */
class Members extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_action( 'members_register_cap_groups', [ $this, 'register_capability_group' ] );
		add_action( 'members_register_caps', [ $this, 'register_capabilities' ] );
	}

	/**
	 * Register a capability group for the Members plugin.
	 *
	 * @since 0.1.0
	 *
	 * @link https://wordpress.org/plugins/members/
	 */
	public function register_capability_group() {
		members_register_cap_group(
			'pressody_records',
			[
				'label'    => esc_html__( 'Pressody Records', 'pressody_records' ),
				'caps'     => [],
				'icon'     => 'dashicons-admin-generic',
				'priority' => 50,
			]
		);
	}

	/**
	 * Register capabilities for the Members plugin.
	 *
	 * @since 0.1.0
	 *
	 * @link https://wordpress.org/plugins/members/
	 */
	public function register_capabilities() {
		members_register_cap(
			Capabilities::DOWNLOAD_PACKAGES,
			[
				'label' => esc_html__( 'Download Packages', 'pressody_records' ),
				'group' => 'pressody_records',
			]
		);

		members_register_cap(
			Capabilities::VIEW_PACKAGES,
			[
				'label' => esc_html__( 'View Packages', 'pressody_records' ),
				'group' => 'pressody_records',
			]
		);

		members_register_cap(
			Capabilities::MANAGE_OPTIONS,
			[
				'label' => esc_html__( 'Manage Options', 'pressody_records' ),
				'group' => 'pressody_records',
			]
		);

		members_register_cap(
			Capabilities::MANAGE_PACKAGE_TYPES,
			[
				'label' => esc_html__( 'Manage Package Types', 'pressody_records' ),
				'group' => 'pressody_records',
			]
		);
	}
}
