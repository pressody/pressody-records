<?php
/**
 * Pressody Records
 *
 * @package Pressody
 * @author  Vlad Olaru <vladpotter85@gmail.com>
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Pressody Records
 * Plugin URI: https://github.com/pressody/pressody-records
 * Description: Define and manage Pressody modules and packages (plugins and themes) to be used on customers' websites. Also, provide a Composer repository for the defined WordPress plugins and themes.
 * Version: 0.17.0
 * Author: Pressody
 * Author URI: https://getpressody.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pressody_records
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Network: false
 * GitHub Plugin URI: pressody/pressody-records
 * Release Asset: true
 */

declare ( strict_types=1 );

namespace Pressody\Records;

// Exit if accessed directly.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
const VERSION = '0.17.0';

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
$dotenv = \Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->load();
$dotenv->required( 'PDRECORDS_PHP_AUTH_USER' )->notEmpty();
// Read environment variables from the $_ENV array also.
\Env\Env::$options |= \Env\Env::USE_ENV_ARRAY;

// Load the WordPress plugin administration API.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Load the Action Scheduler directly since it does not use Composer autoload.
// @link https://github.com/woocommerce/action-scheduler/issues/471
if ( file_exists( __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php' ) ) {
	require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
}

// Create a container and register a service provider.
$pressody_records_container = new Container();
$pressody_records_container->register( new ServiceProvider() );

// Initialize the plugin and inject the container.
$pressody_records = plugin()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __DIR__ . '/pressody-records.php' )
	->set_slug( 'pressody-records' )
	->set_url( plugin_dir_url( __FILE__ ) )
	->define_constants()
	->set_container( $pressody_records_container )
	->register_hooks( $pressody_records_container->get( 'hooks.activation' ) )
	->register_hooks( $pressody_records_container->get( 'hooks.deactivation' ) )
	->register_hooks( $pressody_records_container->get( 'hooks.authentication' ) );

add_action( 'plugins_loaded', [ $pressody_records, 'compose' ], 5 );
