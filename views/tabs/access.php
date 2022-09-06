<?php
/**
 * Views: Access tab content.
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

?>
<div class="pressody_records-card">
	<p>
		<?php esc_html_e( 'API Keys are used to access your Pressody Records repository and download packages. Your personal API keys appear below or you can create keys for other users by editing their accounts.', 'pressody_records' ); ?>
	</p>

	<p>
		<?php
		/* translators: %s: <code>pressody_records</code> */
		printf( esc_html__( 'The password for all API Keys is %s. Use the API key as the username.', 'pressody_records' ), '<code>pressody_records</code>' );
		?>
	</p>
</div>

<div id="pressody_records-api-key-manager"></div>

<p>
	<a href="https://github.com/pressody/pressody-records/blob/develop/docs/security.md" target="_blank" rel="noopener noreferer"><em><?php esc_html_e( 'Read more about securing your Pressody Records repository.', 'pressody_records' ); ?></em></a>
</p>
