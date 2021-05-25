<?php
/**
 * Views: Access tab content.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

?>
<div class="pixelgradelt_records-card">
	<p>
		<?php esc_html_e( 'API Keys are used to access your PixelgradeLT Records repository and download packages. Your personal API keys appear below or you can create keys for other users by editing their accounts.', 'pixelgradelt_records' ); ?>
	</p>

	<p>
		<?php
		/* translators: %s: <code>pixelgradelt_records</code> */
		printf( esc_html__( 'The password for all API Keys is %s. Use the API key as the username.', 'pixelgradelt_records' ), '<code>pixelgradelt_records</code>' );
		?>
	</p>
</div>

<div id="pixelgradelt_records-api-key-manager"></div>

<p>
	<a href="https://github.com/pixelgradelt/pixelgradelt-records/blob/develop/docs/security.md" target="_blank" rel="noopener noreferer"><em><?php esc_html_e( 'Read more about securing your PixelgradeLT Records repository.', 'pixelgradelt_records' ); ?></em></a>
</p>
