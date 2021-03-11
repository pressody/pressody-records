<?php

use Carbon_Fields\Carbon_Fields;
use PixelgradeLT\Records\Screen\CustomCarbonField\UploadZip\Upload_Zip_Field;

define( 'PixelgradeLT\\Records\\Screen\\CustomCarbonField\\UploadZip\\DIR', __DIR__ );

Carbon_Fields::extend( Upload_Zip_Field::class, function( $container ) {
	return new Upload_Zip_Field(
		$container['arguments']['type'],
		$container['arguments']['name'],
		$container['arguments']['label']
	);
} );
