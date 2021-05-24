<?php
/**
 * Views: Settings tab content.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

?>

<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
	<?php settings_fields( 'pixelgradelt_records' ); ?>
	<?php do_settings_sections( 'pixelgradelt_records' ); ?>
	<?php submit_button(); ?>
</form>
