<?php
declare ( strict_types = 1 );

use PixelgradeLT\Records\Tests\Framework\PHPUnitUtil;
use PixelgradeLT\Records\Tests\Framework\TestSuite;
use Psr\Log\NullLogger;

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

define( 'PixelgradeLT\Records\RUNNING_UNIT_TESTS', true );
define( 'PixelgradeLT\Records\TESTS_DIR', __DIR__ );
define( 'WP_PLUGIN_DIR', __DIR__ . '/Fixture/wp-content/plugins' );

if ( 'Unit' === PHPUnitUtil::get_current_suite() ) {
	// For the Unit suite we shouldn't need WordPress loaded.
	// This keeps them fast.
	return;
}

require_once dirname( __DIR__, 2 ) . '/vendor/antecedent/patchwork/Patchwork.php';

$suite = new TestSuite();

$GLOBALS['wp_tests_options'] = [
	'active_plugins'  => [ 'pixelgradelt-records/pixelgradelt-records.php' ],
	'timezone_string' => 'Europe/Bucharest',
];

$suite->addFilter( 'muplugins_loaded', function() {
	require dirname( __DIR__, 2 ) . '/pixelgradelt-records.php';
} );

$suite->addFilter( 'pixelgradelt_records/compose', function( $plugin, $container ) {
	$container['logger'] = new NullLogger();
	$container['storage.working_directory'] = \PixelgradeLT\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/';
}, 10, 2 );

$suite->bootstrap();
