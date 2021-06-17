<?php
/**
 * Helper functions
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\Exception\InvalidComposerVendor;

/**
 * Retrieve the main plugin instance.
 *
 * @since 0.1.0
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $instance;
	$instance = $instance ?: new Plugin();

	return $instance;
}

/**
 * Autoload mapped classes.
 *
 * @since 0.1.0
 *
 * @param string $class Class name.
 */
function autoloader_classmap( string $class ) {
	$class_map = [
		'PclZip' => ABSPATH . 'wp-admin/includes/class-pclzip.php',
	];

	if ( isset( $class_map[ $class ] ) ) {
		require_once $class_map[ $class ];
	}
}

/**
 * Generate a random string.
 *
 * @since 0.1.0
 *
 * @param int $length Length of the string to generate.
 *
 * @throws \Exception
 * @return string
 */
function generate_random_string( int $length = 12 ): string {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	$str = '';
	$max = \strlen( $chars ) - 1;
	for ( $i = 0; $i < $length; $i ++ ) {
		$str .= $chars[ random_int( 0, $max ) ];
	}

	return $str;
}

/**
 * Retrieve the authorization header.
 *
 * On certain systems and configurations, the Authorization header will be
 * stripped out by the server or PHP. Typically this is then used to
 * generate `PHP_AUTH_USER`/`PHP_AUTH_PASS` but not passed on. We use
 * `getallheaders` here to try and grab it out instead.
 *
 * From https://github.com/WP-API/OAuth1
 *
 * @return string|null Authorization header if set, null otherwise
 */
function get_authorization_header(): ?string {
	if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
		return stripslashes( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	if ( \function_exists( 'getallheaders' ) ) {
		// Check for the authorization header case-insensitively.
		foreach ( getallheaders() as $key => $value ) {
			if ( 'authorization' === strtolower( $key ) ) {
				return $value;
			}
		}
	}

	return null;
}

/**
 * Retrieve the permalink for all the packages JSON (packages.json).
 *
 * @since 0.1.0
 *
 * @param array|null $args Optional. Query string parameters. Default is an empty array.
 *
 * @return string
 */
function get_packages_permalink( array $args = null ): string {
	if ( null === $args ) {
		$args = [];
	}

	$permalink = get_option( 'permalink_structure' );
	if ( empty( $permalink ) ) {
		$url = add_query_arg( 'pixelgradelt_records_route', 'composer_packages', home_url( '/' ) );
	} else {
		// Leave off the packages.json if 'base' arg is true.
		$suffix = isset( $args['base'] ) && $args['base'] ? '' : 'packages.json';
		$url    = sprintf( network_home_url( '/ltpackagist/%s' ), $suffix );
	}

	return $url;
}

/**
 * Retrieve the permalink for all the part packages JSON (packages.json).
 *
 * @since 0.1.0
 *
 * @param array|null $args Optional. Query string parameters. Default is an empty array.
 *
 * @return string
 */
function get_parts_permalink( array $args = null ): string {
	if ( null === $args ) {
		$args = [];
	}

	$permalink = get_option( 'permalink_structure' );
	if ( empty( $permalink ) ) {
		$url = add_query_arg( 'pixelgradelt_records_route', 'composer_parts', home_url( '/' ) );
	} else {
		// Leave off the packages.json if 'base' arg is true.
		$suffix = isset( $args['base'] ) && $args['base'] ? '' : 'packages.json';
		$url    = sprintf( network_home_url( '/ltparts/%s' ), $suffix );
	}

	return $url;
}

/**
 * Retrieve the PixelgradeLT Records Composer vendor for use with our packages.
 *
 * @throws InvalidComposerVendor
 * @return string
 */
function get_composer_vendor(): string {
	/**
	 * The custom vendor configured via the Settings page is hooked through @see CustomVendor::register_hooks()
	 */
	$vendor = apply_filters( 'pixelgradelt_records/vendor', 'pixelgradelt-records' );
	if ( empty( $vendor ) || ! is_string( $vendor ) ) {
		throw new InvalidComposerVendor( "The PixelgradeLT Records Composer vendor must be a string and it can't be empty or falsy." );
	}

	if ( strlen( $vendor ) < 10 ) {
		throw new InvalidComposerVendor( "The PixelgradeLT Records Composer vendor must be at least 10 characters long. Please make sure that it is as unique as possible, in the entire Composer ecosystem." );
	}

	// This is the same partial pattern used by Composer.
	// @see https://getcomposer.org/schema.json
	if ( ! preg_match( '/^[a-z0-9]([_.-]?[a-z0-9]+)*$/i', $vendor ) ) {
		throw InvalidComposerVendor::wrongFormat( $vendor );
	}

	return $vendor;
}

/**
 * Retrieve ID for the user being edited.
 *
 * @since 0.1.0
 *
 * @return int
 */
function get_edited_user_id(): int {
	// phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
	return empty( $_GET['user_id'] ) ? get_current_user_id() : (int) $_GET['user_id'];
}

/**
 * Whether a plugin identifier is the main plugin file.
 *
 * Plugins can be identified by their plugin file (relative path to the main
 * plugin file from the root plugin directory) or their slug.
 *
 * This doesn't validate whether or not the plugin actually exists.
 *
 * @since 0.1.0
 *
 * @param string $plugin_file Plugin slug or relative path to the main plugin file.
 *
 * @return bool
 */
function is_plugin_file( string $plugin_file ): bool {
	return '.php' === substr( $plugin_file, - 4 );
}

/**
 * Display a notice about missing dependencies.
 *
 * @since 0.3.1
 */
function display_missing_dependencies_notice() {
	$message = sprintf(
	/* translators: %s: documentation URL */
		__( 'PixelgradeLT Records is missing required dependencies. <a href="%s" target="_blank" rel="noopener noreferer">Learn more.</a>', 'pixelgradelt_records' ),
		'https://github.com/pixelgradelt/pixelgradelt-records/blob/master/docs/installation.md'
	);

	printf(
		'<div class="pixelgradelt_records-compatibility-notice notice notice-error"><p>%s</p></div>',
		wp_kses(
			$message,
			[
				'a' => [
					'href'   => true,
					'rel'    => true,
					'target' => true,
				],
			]
		)
	);
}

/**
 * Whether debug mode is enabled.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function is_debug_mode(): bool {
	return \defined( 'WP_DEBUG' ) && true === WP_DEBUG;
}

function doing_it_wrong( $function, $message, $version ) {
	// @codingStandardsIgnoreStart
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( wp_doing_ajax() || is_rest_request() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}

function is_rest_request() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return false;
	}

	$rest_prefix         = trailingslashit( rest_get_url_prefix() );
	$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	return apply_filters( 'pixelgradelt_records/is_rest_api_request', $is_rest_api_request );
}

/**
 * Whether we are running unit tests.
 *
 * @since 0.9.0
 *
 * @return bool
 */
function is_running_unit_tests(): bool {
	return \defined( 'PIXELGRADELT_RECORDS_RUNNING_UNIT_TESTS' ) && true === PIXELGRADELT_RECORDS_RUNNING_UNIT_TESTS;
}

/**
 * Test if a given URL is one that we identify as a local/development site.
 *
 * @since 0.5.0
 *
 * @return bool
 */
function is_dev_url( string $url ): bool {
	// Local/development url parts to match for
	$devsite_needles = array(
		'localhost',
		':8888',
		'.local',
		'pixelgrade.dev',
		'.dev',
		':8082',
		'staging.',
	);

	foreach ( $devsite_needles as $needle ) {
		if ( false !== strpos( $url, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Preload REST API data.
 *
 * @since 1.0.0
 *
 * @param array $paths Array of REST paths.
 */
function preload_rest_data( $paths ) {
	$preload_data = array_reduce(
		$paths,
		'rest_preload_api_request',
		[]
	);

	wp_add_inline_script(
		'wp-api-fetch',
		sprintf( 'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );', wp_json_encode( $preload_data ) ),
		'after'
	);
}
