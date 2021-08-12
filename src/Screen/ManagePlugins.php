<?php
/**
 * Manage Plugins screen provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Repository\PackageRepository;

/**
 * Manage Plugins screen provider class.
 *
 * @since 0.1.0
 */
class ManagePlugins extends AbstractHookProvider {
	/**
	 * Whitelisted packages repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

	/**
	 * Create the Manage Plugins screen provider.
	 *
	 * @param PackageRepository $repository Whitelisted packages repository.
	 */
	public function __construct( PackageRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		if ( is_multisite() ) {
			add_filter( 'manage_plugins-network_columns', [ $this, 'register_columns' ] );
		} else {
			add_filter( 'manage_plugins_columns', [ $this, 'register_columns' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'manage_plugins_custom_column', [ $this, 'display_columns' ], 10, 2 );
	}

	/**
	 * Enqueue assets for the screen.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix Screen hook id.
	 */
	public function enqueue_assets( string $hook_suffix ) {
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script( 'pixelgradelt_records-admin' );
		wp_enqueue_style( 'pixelgradelt_records-admin' );
	}

	/**
	 * Register admin columns.
	 *
	 * @since 0.1.0
	 *
	 * @param array $columns List of admin columns.
	 * @return array
	 */
	public function register_columns( array $columns ): array {
		if ( current_user_can( Capabilities::MANAGE_OPTIONS ) ) {
			$columns['pixelgradelt_records'] = 'LT Package Source';
		}

		return $columns;
	}

	/**
	 * Display admin columns.
	 *
	 * @since 0.1.0
	 *
	 * @throws \Exception If package type not known.
	 *
	 * @param string $column_name Column identifier.
	 * @param string $plugin_file Plugin file basename.
	 */
	public function display_columns( string $column_name, string $plugin_file ) {
		if ( 'pixelgradelt_records' !== $column_name ) {
			return;
		}

		$output = '<span>';
		if ( $this->repository->contains( [ 'slug' => $plugin_file ] ) ) {
			$output .= '<span class="dashicons dashicons-yes-alt wp-ui-text-highlight"></span>';
		} else {
			$output .= '&nbsp;';
		}
		$output .= '</span>';

		echo $output;
	}
}
