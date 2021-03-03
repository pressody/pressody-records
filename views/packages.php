<?php
/**
 * Views: Packages page
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

/**
 * @global $permalink
 */

$allowed_tags = [
		'a'    => [
				'href' => true,
		],
		'em'   => [],
		'code' => [],
];

if ( ! empty( $packages ) ) { ?>
	<div class="pixelgradelt_records-card">
		<p>
			<?php
			printf( __( 'These are <strong>all the packages</strong> that PixelgradeLT Records makes available as Composer packages, regardless of their configuration.<br>
This view is primarily available to assist in <strong>double-checking that things work properly.</strong><br>
If you want to <strong>dig deeper,</strong> check <a href="%s" target="_blank">the actual JSON</a> of the PixelgradeLT Records repo.', 'pixelgradelt_records' ), esc_url( $permalink ) ); ?>
		</p>
	</div>
	<?php

	/** @global Package[] $packages */
	foreach ( $packages as $package ) {
		require $this->plugin->get_path( 'views/package-details.php' );
	}
} else { ?>
	<div class="pixelgradelt_records-card">
		<h3><?php esc_html_e( 'No packages defined', 'pixelgradelt_records' ); ?></h3>
		<p>
			<?php esc_html_e( 'Plugins and themes need to be configured as Pixelgrade LT packages to make them available as Composer packages.', 'pixelgradelt_records' ); ?>
		</p>
		<p>
			<?php echo wp_kses( __( 'Go to <code>LT Packages > Add New</code> and start managing your first package.', 'pixelgradelt_records' ), $allowed_tags ); ?>
		</p>
	</div>
	<?php
}
