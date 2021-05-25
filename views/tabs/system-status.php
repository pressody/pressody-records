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

$allowed_tags = [
		'a'    => [
				'href' => true,
		],
		'em'   => [],
		'strong'   => [],
		'code' => [],
];
?>
<div class="pixelgradelt_records-card">
	<p>
		<?php echo wp_kses( __( 'These are a series of system checks to reassure or warn you of <strong>how fit is the webserver for running PixelgradeLT Records.</strong>', 'pixelgradelt_records' ), $allowed_tags ); ?>
	</p>
</div>

<div class="pixelgradelt_records-card">
	<p>
		<?php echo wp_kses( __( 'None right now.', 'pixelgradelt_records' ), $allowed_tags ); ?>
	</p>
</div>

<div id="pixelgradelt_records-status"></div>
