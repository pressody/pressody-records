<?php
/**
 * Capabilities.
 *
 * Meta capabilities are mapped to primitive capabilities in
 * \Pressody\Records\Provider\Capabilities.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
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

declare ( strict_types=1 );

namespace Pressody\Records;

/**
 * Capabilities.
 *
 * @since 0.1.0
 */
final class Capabilities {
	/**
	 * Primitive capability for downloading packages.
	 *
	 * @var string
	 */
	const DOWNLOAD_PACKAGES = 'pressody_records_download_packages';

	/**
	 * Meta capability for downloading a specific package.
	 *
	 * @var string
	 */
	const DOWNLOAD_PACKAGE = 'pressody_records_download_package';

	/**
	 * Primitive capability for viewing packages.
	 *
	 * @var string
	 */
	const VIEW_PACKAGES = 'pressody_records_view_packages';

	/**
	 * Meta capability for viewing a specific package.
	 *
	 * @var string
	 */
	const VIEW_PACKAGE = 'pressody_records_view_package';

	/**
	 * Primitive capability for managing options.
	 *
	 * @var string
	 */
	const MANAGE_OPTIONS = 'pressody_records_manage_options';

	/**
	 * Primitive capability for managing package types.
	 *
	 * @var string
	 */
	const MANAGE_PACKAGE_TYPES = 'pressody_records_manage_package_types';

	/**
	 * Register roles and capabilities.
	 *
	 * @since 0.1.0
	 */
	public static function register() {
		$wp_roles = wp_roles();
		// Add all capabilities to the administrator role.
		$wp_roles->add_cap( 'administrator', self::DOWNLOAD_PACKAGES );
		$wp_roles->add_cap( 'administrator', self::VIEW_PACKAGES );
		$wp_roles->add_cap( 'administrator', self::MANAGE_OPTIONS );

		// Create a special role for users intended to be used by clients.
		$wp_roles->add_role( 'pressody_records_client', 'PD Records Client', [
			self::DOWNLOAD_PACKAGES => true,
			self::VIEW_PACKAGES     => true,
		] );
	}
}
