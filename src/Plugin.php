<?php
/**
 * Main plugin class
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use Cedaro\WP\Plugin\Plugin as BasePlugin;
use Psr\Container\ContainerInterface;

/**
 * Main plugin class - composition root.
 *
 * @since 0.1.0
 */
class Plugin extends BasePlugin implements Composable {
	/**
	 * Compose the object graph.
	 *
	 * @since 0.1.0
	 */
	public function compose() {
		$container = $this->get_container();

		/**
		 * Start composing the object graph in PixelgradeLT Records.
		 *
		 * @since 0.1.0
		 *
		 * @param Plugin             $plugin    Main plugin instance.
		 * @param ContainerInterface $container Dependency container.
		 */
		do_action( 'pixelgradelt_records/compose', $this, $container );

		// Register hook providers.
		$this
			->register_hooks( $container->get( 'hooks.i18n' ) )
			->register_hooks( $container->get( 'integration.ltretailer' ) )
			->register_hooks( $container->get( 'hooks.capabilities' ) )
			->register_hooks( $container->get( 'hooks.rewrite_rules' ) )
			->register_hooks( $container->get( 'hooks.custom_vendor' ) )
			->register_hooks( $container->get( 'hooks.health_check' ) )
			->register_hooks( $container->get( 'hooks.request_handler' ) )
			->register_hooks( $container->get( 'hooks.rest' ) )
			// Register the post type early.
			->register_hooks( $container->get( 'hooks.part_post_type' ) )
			->register_hooks( $container->get( 'hooks.package_post_type' ) )
			->register_hooks( $container->get( 'hooks.package_archiver' ) )
			->register_hooks( $container->get( 'hooks.part_archiver' ) )
			->register_hooks( $container->get( 'client.composer.custom_token_auth' ) )

			->register_hooks( $container->get( 'logs.manager' ) )

			->register_hooks( $container->get( 'screen.edit_package' ) )
			->register_hooks( $container->get( 'screen.list_packages' ) )
			->register_hooks( $container->get( 'screen.edit_part' ) )
			->register_hooks( $container->get( 'screen.list_parts' ) );


		if ( is_admin() ) {
			$this
				->register_hooks( $container->get( 'hooks.upgrade' ) )
				->register_hooks( $container->get( 'hooks.admin_assets' ) )
				->register_hooks( $container->get( 'screen.edit_user' ) )
				->register_hooks( $container->get( 'screen.manage_plugins' ) )
				->register_hooks( $container->get( 'screen.settings' ) );
		}

		if ( \function_exists( 'envato_market' ) ) {
			$this->register_hooks( $container->get( 'plugin.envato_market' ) );
		}

		if ( \function_exists( 'members_plugin' ) ) {
			$this->register_hooks( $container->get( 'plugin.members' ) );
		}

		if ( \function_exists( 'gv_api_manager' ) ) {
			$this->register_hooks( $container->get( 'plugin.gpl_vault' ) );
		}

		/**
		 * Finished composing the object graph in PixelgradeLT Records.
		 *
		 * @since 0.1.0
		 *
		 * @param Plugin             $plugin    Main plugin instance.
		 * @param ContainerInterface $container Dependency container.
		 */
		do_action( 'pixelgradelt_records/composed', $this, $container );
	}

	public function define_constants(): Plugin {
		$upload_dir = wp_upload_dir( null, false );

		if ( ! defined( 'PixelgradeLT\Records\LOG_DIR' ) ) {
			define( 'PixelgradeLT\Records\LOG_DIR', $upload_dir['basedir'] . '/pixelgradelt-records-logs/' );
		}

		return $this;
	}
}
