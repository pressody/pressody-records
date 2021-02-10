<?php
/**
 * Register rewrite rules.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use WP_Rewrite;

/**
 * Class to register rewrite rules.
 *
 * @since 0.1.0
 */
class RewriteRules extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_action( 'generate_rewrite_rules', [ $this, 'register_external_rewrite_rules' ] );
		add_action( 'wp_loaded', [ $this, 'maybe_flush_rewrite_rules' ] );
	}

	/**
	 * Register query variables.
	 *
	 * @since 0.1.0
	 *
	 * @param array $vars List of query variables.
	 * @return array
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'pixelgradelt_records_params';
		$vars[] = 'pixelgradelt_records_route';
		return $vars;
	}

	/**
	 * Register rewrite rules.
	 *
	 * @since 0.1.0
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule(
			'ltpackagist/packages.json$',
			'index.php?pixelgradelt_records_route=composer',
			'top'
		);

		// Don't add a file extension. Some servers don't route file extensions
		// through WordPress' front controller.
		add_rewrite_rule(
			'ltpackagist/([^/]+)(/([^/]+))?$',
			'index.php?pixelgradelt_records_route=download&pixelgradelt_records_params[slug]=$matches[1]&pixelgradelt_records_params[version]=$matches[3]',
			'top'
		);
	}

	/**
	 * Register external rewrite rules.
	 *
	 * This added to .htaccess on Apache servers to account for cases where
	 * WordPress doesn't handle the .json file extension.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Rewrite $wp_rewrite WP rewrite API.
	 */
	public function register_external_rewrite_rules( WP_Rewrite $wp_rewrite ) {
		$wp_rewrite->add_external_rule(
			'ltpackagist/packages.json$',
			'index.php?pixelgradelt_records_route=composer'
		);
	}

	/**
	 * Flush the rewrite rules if needed.
	 *
	 * @since 0.1.0
	 */
	public function maybe_flush_rewrite_rules() {
		if ( is_network_admin() || 'no' === get_option( 'pixelgradelt_records_flush_rewrite_rules' ) ) {
			return;
		}

		update_option( 'pixelgradelt_records_flush_rewrite_rules', 'no' );
		flush_rewrite_rules();
	}
}
