<?php
declare ( strict_types = 1 );

use Cedaro\WP\Tests\TestSuite;
use Dotenv\Dotenv;
use Psr\Log\NullLogger;

use function Cedaro\WP\Tests\get_current_suite;

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define( 'PIXELGRADELT_RECORDS_TESTS_DIR', __DIR__ );
define( 'WP_PLUGIN_DIR', __DIR__ . '/Fixture/wp-content/plugins' );

if ( 'Unit' === get_current_suite() ) {
	return;
}

require_once dirname( __DIR__, 2 ) . '/vendor/antecedent/patchwork/Patchwork.php';

$suite = new TestSuite();

$GLOBALS['wp_tests_options'] = [
	'active_plugins'  => [ 'pixelgradelt_records/pixelgradelt-records.php' ],
	'timezone_string' => 'Europe/Bucharest',
];

$suite->addFilter( 'muplugins_loaded', function() {
	require dirname( __DIR__, 2 ) . '/pixelgradelt-records.php';
} );

$suite->addFilter( 'pixelgradelt_records_compose', function( $plugin, $container ) {
	$container['logger'] = new NullLogger();
	$container['storage.working_directory'] = PIXELGRADELT_RECORDS_TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/';
}, 10, 2 );

$suite->bootstrap();
