<?php
/**
 * Views: Repository tab content.
 *
 * @since   1.0.0
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

declare ( strict_types = 1 );

namespace Pressody\Records;

/**
 * @global string $packages_permalink
 */
?>

<div class="pressody_records-card">
	<p>
		<?php
		printf( __( 'These are <strong>all the packages</strong> that Pressody Records makes available as Composer packages, regardless of their configuration.<br>
This view is primarily available to assist in <strong>double-checking that things work properly.</strong><br>
If you want to <strong>dig deeper,</strong> check <a href="%s" target="_blank">the actual JSON</a> of the Pressody Records repo.', 'pressody_records' ), esc_url( $packages_permalink ) ); ?>
	</p>
</div>

<div id="pressody_records-repository-container" class="pressody_records-repository-container"></div>
