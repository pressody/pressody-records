<?php

namespace PixelgradeLT\Records\Screen\CustomCarbonField\UploadZip;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Field\Field;

class Upload_Zip_Field extends Field {
	/**
	 * Prepare the field type for use.
	 * Called once per field type when activated.
	 *
	 * @static
	 * @access public
	 *
	 * @return void
	 */
	public static function field_type_activated() {
		$dir = DIR . '/languages/';
		$locale = get_locale();
		$path = $dir . $locale . '.mo';
		load_textdomain( 'carbon-field-upload-zip', $path );
	}

	/**
	 * Enqueue scripts and styles in admin.
	 * Called once per field type.
	 *
	 * @static
	 * @access public
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts() {
		$root_uri = Carbon_Fields::directory_to_url( DIR );

		// Enqueue field styles.
		wp_enqueue_style( 'carbon-field-upload-zip', $root_uri . '/build/bundle.css' );

		// Enqueue field scripts.
		wp_enqueue_script( 'carbon-field-upload-zip', $root_uri . '/build/bundle.js', array( 'carbon-fields-core' ) );
	}
}
