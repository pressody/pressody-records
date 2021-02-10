<?php
/**
 * Plugin activation routines.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Capabilities;

/**
 * Class to activate the plugin.
 *
 * @since 0.1.0
 */
class Activation extends AbstractHookProvider {
	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		register_activation_hook( $this->plugin->get_file(), [ $this, 'activate' ] );
	}

	/**
	 * Activate the plugin.
	 *
	 * - Sets a flag to flush rewrite rules after plugin rewrite rules have been
	 *   registered.
	 * - Registers capabilities for the admin role.
	 *
	 * @see \PixelgradeLT\Records\Provider\RewriteRules::maybe_flush_rewrite_rules()
	 *
	 * @since 0.1.0
	 */
	public function activate() {
		update_option( 'pixelgradelt_records_flush_rewrite_rules', 'yes' );
	}
}
