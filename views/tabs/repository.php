<?php
/**
 * Views: Repository tab content.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package Pressody
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
