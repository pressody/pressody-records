<?php
/**
 * Edit User screen provider.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Authentication\ApiKey\ApiKey;
use PixelgradeLT\Records\Authentication\ApiKey\ApiKeyRepository;
use PixelgradeLT\Records\Capabilities;
use WP_User;

use function PixelgradeLT\Records\get_edited_user_id;
use function PixelgradeLT\Records\preload_rest_data;

/**
 * Edit User screen provider class.
 *
 * @since 0.1.0
 */
class EditUser extends AbstractHookProvider {
	/**
	 * API Key repository.
	 *
	 * @var ApiKeyRepository
	 */
	protected $api_keys;

	/**
	 * Create the setting screen.
	 *
	 * @param ApiKeyRepository $api_keys API Key repository.
	 */
	public function __construct( ApiKeyRepository $api_keys ) {
		$this->api_keys = $api_keys;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		$user_id = get_edited_user_id();

		// Only load the screen for users that can view or download packages.
		if (
			! user_can( $user_id, Capabilities::DOWNLOAD_PACKAGES )
			&& ! user_can( $user_id, Capabilities::VIEW_PACKAGES )
		) {
			return;
		}

		add_action( 'load-profile.php', [ $this, 'load_screen' ] );
		add_action( 'load-user-edit.php', [ $this, 'load_screen' ] );
	}

	/**
	 * Set up the screen.
	 *
	 * @since 0.1.0
	 */
	public function load_screen() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'edit_user_profile', [ $this, 'render_api_keys_section' ] );
		add_action( 'show_user_profile', [ $this, 'render_api_keys_section' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pixelgradelt_records-admin' );
		wp_enqueue_style( 'pixelgradelt_records-admin' );

		wp_enqueue_script( 'pixelgradelt_records-access' );

		wp_localize_script(
			'pixelgradelt_records-access',
			'_pixelgradeltRecordsAccessData',
			[
				'editedUserId' => get_edited_user_id(),
			]
		);

		preload_rest_data(
			[
				'/pixelgradelt_records/v1/apikeys?user=' . get_edited_user_id(),
			]
		);
	}

	/**
	 * Display the API Keys section.
	 *
	 * @param WP_User $user WordPress user instance.
	 */
	public function render_api_keys_section( WP_User $user ) {
		printf( '<h2>%s</h2>', esc_html__( 'PixelgradeLT Records API Keys', 'pixelgradelt_records' ) );

		printf(
			'<p><strong>%s</strong></p>',
			/* translators: %s: <code>pixelgradelt_records</code> */
			sprintf( esc_html__( 'The password for all API Keys is %s. Use the API key as the username.', 'pixelgradelt_records' ), '<code>pixelgradelt_records</code>' )
		);

		echo '<div id="pixelgradelt_records-api-key-manager"></div>';
	}
}
