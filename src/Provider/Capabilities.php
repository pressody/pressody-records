<?php
/**
 * Capabilities provider.
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
use Pressody\Records\Capabilities as Caps;

/**
 * Capabilities provider class.
 *
 * @since 0.1.0
 */
class Capabilities extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
	}

	/**
	 * Map meta capabilities to primitive capabilities.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $caps Returns the user's actual capabilities.
	 * @param string $cap  Capability name.
	 * @return array
	 */
	public function map_meta_cap( array $caps, string $cap ): array {
		switch ( $cap ) {
			case Caps::DOWNLOAD_PACKAGE:
				$caps = [ Caps::DOWNLOAD_PACKAGES ];
				break;

			case Caps::VIEW_PACKAGE:
				$caps = [ Caps::VIEW_PACKAGES ];
				break;
		}

		return $caps;
	}
}
