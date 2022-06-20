<?php
/**
 * Assets provider.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Assets provider class.
 *
 * @since 0.1.0
 */
class AdminAssets extends AbstractHookProvider {
	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 1 );
		add_filter( 'script_loader_tag', [ $this, 'filter_script_type' ], 10, 3 );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 0.1.0
	 */
	public function register_assets() {
		wp_register_script(
			'pressody_records-admin',
			$this->plugin->get_url( 'assets/js/admin.js' ),
			[ 'jquery' ],
			'20220620',
			true
		);

		wp_register_script(
			'pressody_records-access',
			$this->plugin->get_url( 'assets/js/access.js' ),
			[ 'wp-components', 'wp-data', 'wp-data-controls', 'wp-element', 'wp-i18n' ],
			'20220620',
			true
		);

		wp_set_script_translations(
			'pressody_records-access',
			'pressody_records',
			$this->plugin->get_path( 'languages' )
		);

		wp_register_script(
			'pressody_records-repository',
			$this->plugin->get_url( 'assets/js/repository.js' ),
			[ 'wp-components', 'wp-data', 'wp-data-controls', 'wp-element', 'wp-i18n' ],
			'20220620',
			true
		);

		wp_set_script_translations(
			'pressody_records-repository',
			'pressody_records',
			$this->plugin->get_path( 'languages' )
		);

		wp_register_script(
			'pressody_records-edit-package',
			$this->plugin->get_url( 'assets/js/edit-package.js' ),
			[ 'wp-components', 'wp-data', 'wp-data-controls', 'wp-element', 'wp-i18n' ],
			'20220620',
			true
		);

		wp_set_script_translations(
			'pressody_records-edit-package',
			'pressody_records',
			$this->plugin->get_path( 'languages' )
		);

		wp_register_style(
			'pressody_records-admin',
			$this->plugin->get_url( 'assets/css/admin.css' ),
			[ 'wp-components' ],
			'20220620'
		);
	}

	/**
	 * Filter script tag type attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script identifier.
	 * @return string
	 */
	public function filter_script_type( string $tag, string $handle ): string {
		$modules = [
			'pressody_records-access',
			'pressody_records-edit-package',
			'pressody_records-repository',
		];

		if ( in_array( $handle, $modules, true ) ) {
			$tag = str_replace( '<script', '<script type="module"', $tag );
		}

		return $tag;
	}
}
