<?php
/**
 * PixelgradeLT Records
 *
 * @package PixelgradeLT
 * @author  Vlad Olaru <vlad@pixelgrade.com>
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: PixelgradeLT Records
 * Plugin URI: https://github.com/pixelgradelt/pixelgradelt-records
 * Description: Define and manage PixelgradeLT modules and packages (plugins and themes) to be used on customers' websites. Also, provide a Composer repository for the defined WordPress plugins and themes.
 * Version: 0.9.0
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pixelgradelt_records
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Network: false
 * GitHub Plugin URI: pixelgradelt/pixelgradelt-records
 * Release Asset: true
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

// Exit if accessed directly.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
const VERSION = '0.9.0';

// Load the Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Display a notice and bail if dependencies are missing.
if ( ! function_exists( __NAMESPACE__ . '\autoloader_classmap' ) ) {
	require_once __DIR__ . '/src/functions.php';
	add_action( 'admin_notices', __NAMESPACE__ . '\display_missing_dependencies_notice' );

	return;
}

// Autoload mapped classes.
spl_autoload_register( __NAMESPACE__ . '\autoloader_classmap' );

// Load the environment variables.
// We use immutable since we don't want to overwrite variables already set.
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load the WordPress plugin administration API.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Create a container and register a service provider.
$pixelgradelt_records_container = new Container();
$pixelgradelt_records_container->register( new ServiceProvider() );

// Initialize the plugin and inject the container.
$pixelgradelt_records = plugin()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __DIR__ . '/pixelgradelt-records.php' )
	->set_slug( 'pixelgradelt-records' )
	->set_url( plugin_dir_url( __FILE__ ) )
	->define_constants()
	->set_container( $pixelgradelt_records_container )
	->register_hooks( $pixelgradelt_records_container->get( 'hooks.activation' ) )
	->register_hooks( $pixelgradelt_records_container->get( 'hooks.deactivation' ) )
	->register_hooks( $pixelgradelt_records_container->get( 'hooks.authentication' ) );

add_action( 'plugins_loaded', [ $pixelgradelt_records, 'compose' ], 5 );
