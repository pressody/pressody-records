<?php
/**
 * Views: Status tab
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

/**
 * @global $system_checks
 */

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

<?php
if ( ! empty( $system_checks ) ) {
	foreach ( $system_checks as $system_check ) {
		require $this->plugin->get_path( 'views/system-check-details.php' );
	}
}
