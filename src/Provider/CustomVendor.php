<?php
/**
 * Custom vendor provider.
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
