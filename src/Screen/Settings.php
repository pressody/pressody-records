<?php
/**
 * Settings screen provider.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types=1 );

namespace Pressody\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\Authentication\ApiKey\ApiKey;
use Pressody\Records\Authentication\ApiKey\ApiKeyRepository;
use Pressody\Records\Capabilities;
use Pressody\Records\Provider\HealthCheck;
use Pressody\Records\Integration\PDRetailer;
use Pressody\Records\Repository\PackageRepository;
use Pressody\Records\Transformer\PackageTransformer;

use function Pressody\Records\get_packages_permalink;
use function Pressody\Records\get_parts_permalink;
use function Pressody\Records\get_setting;
use function Pressody\Records\preload_rest_data;

/**
 * Settings screen provider class.
 *
 * @since 0.1.0
 */
class Settings extends AbstractHookProvider {
	/**
	 * API Key repository.
	 *
	 * @var ApiKeyRepository
	 */
	protected ApiKeyRepository $api_keys;

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer
	 */
	protected PackageTransformer $composer_transformer;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $packages;

	/**
	 * Create the setting screen.
	 *
	 * @param PackageRepository  $packages             Package repository.
	 * @param ApiKeyRepository   $api_keys             API Key repository.
	 * @param PackageTransformer $composer_transformer Package transformer.
	 */
	public function __construct(
			PackageRepository $packages,
			ApiKeyRepository $api_keys,
			PackageTransformer $composer_transformer
	) {

		$this->api_keys             = $api_keys;
		$this->packages             = $packages;
		$this->composer_transformer = $composer_transformer;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'add_menu_item' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'add_menu_item' ] );
		}

		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'add_sections' ] );
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Add the settings menu item.
	 *
	 * @since 0.1.0
	 */
	public function add_menu_item() {
		$parent_slug = 'options-general.php';
		if ( is_network_admin() ) {
			$parent_slug = 'settings.php';
		}

		$page_hook = add_submenu_page(
				$parent_slug,
				esc_html__( 'Pressody Records', 'pressody_records' ),
				esc_html__( 'PD Records', 'pressody_records' ),
				Capabilities::MANAGE_OPTIONS,
				'pressody_records',
				[ $this, 'render_screen' ]
		);

		add_action( 'load-' . $page_hook, [ $this, 'load_screen' ] );
	}

	/**
	 * Set up the screen.
	 *
	 * @since 0.1.0
	 */
	public function load_screen() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ HealthCheck::class, 'display_authorization_notice' ] );
		add_action( 'admin_notices', [ HealthCheck::class, 'display_permalink_notice' ] );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pressody_records-admin' );
		wp_enqueue_style( 'pressody_records-admin' );
		wp_enqueue_script( 'pressody_records-access' );
		wp_enqueue_script( 'pressody_records-repository' );

		wp_localize_script(
				'pressody_records-access',
				'_pressodyRecordsAccessData',
				[
						'editedUserId' => get_current_user_id(),
				]
		);

		wp_localize_script(
				'pressody_records-repository',
				'_pressodyRecordsRepositoryData',
				[
						'addNewPackageUrl' => admin_url('post-new.php?post_type=pdpackage'),
				]
		);

		$preload_paths = [
				'/pressody_records/v1/packages',
		];

		if ( current_user_can( Capabilities::MANAGE_OPTIONS ) ) {
			$preload_paths = array_merge(
					$preload_paths,
					[
							'/pressody_records/v1/apikeys?user=' . get_current_user_id(),
					]
			);
		}

		preload_rest_data( $preload_paths );
	}

	/**
	 * Register settings.
	 *
	 * @since 0.1.0
	 */
	public function register_settings() {
		register_setting( 'pressody_records', 'pressody_records', [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Add settings sections.
	 *
	 * @since 0.1.0
	 */
	public function add_sections() {
		add_settings_section(
				'default',
				esc_html__( 'General', 'pressody_records' ),
				'__return_null',
				'pressody_records'
		);

		add_settings_section(
				'pdretailer',
				esc_html__( 'PD Retailer Communication', 'pressody_records' ),
				'__return_null',
				'pressody_records'
		);
	}

	/**
	 * Register individual settings.
	 *
	 * @since 0.1.0
	 */
	public function add_settings() {
		add_settings_field(
				'vendor',
				'<label for="pressody_records-vendor">' . esc_html__( 'Vendor', 'pressody_records' ) . '</label>',
				[ $this, 'render_field_vendor' ],
				'pressody_records',
				'default'
		);

		add_settings_field(
				'github-oauth-token',
				'<label for="pressody_records-github-oauth-token">' . esc_html__( 'GitHub OAuth Token', 'pressody_records' ) . '</label>',
				[ $this, 'render_field_github_oauth_token' ],
				'pressody_records',
				'default'
		);

		add_settings_field(
				'pdretailer-compositions-root-endpoint',
				'<label for="pressody_records-pdretailer-compositions-root-endpoint">' . esc_html__( 'Solutions Repository Endpoint', 'pressody_records' ) . '</label>',
				[ $this, 'render_field_pdretailer_compositions_root_endpoint' ],
				'pressody_records',
				'pdretailer'
		);

		add_settings_field(
				'pdretailer-api-key',
				'<label for="pressody_records-pdretailer-api-key">' . esc_html__( 'Access API Key', 'pressody_records' ) . '</label>',
				[ $this, 'render_field_pdretailer_api_key' ],
				'pressody_records',
				'pdretailer'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array $value Settings values.
	 *
	 * @return array Sanitized and filtered settings values.
	 */
	public function sanitize_settings( array $value ): array {
		if ( ! empty( $value['vendor'] ) ) {
			$value['vendor'] = preg_replace( '/[^a-z0-9_\-\.]+/i', '', $value['vendor'] );
		}

		if ( ! empty( $value['github-oauth-token'] ) ) {
			$value['github-oauth-token'] = trim( $value['github-oauth-token'] );
		}

		if ( ! empty( $value['pdretailer-compositions-root-endpoint'] ) ) {
			$value['pdretailer-compositions-root-endpoint'] = esc_url( $value['pdretailer-compositions-root-endpoint'] );
		}

		if ( ! empty( $value['pdretailer-api-key'] ) ) {
			$value['pdretailer-api-key'] = trim( $value['pdretailer-api-key'] );
		}

		return (array) apply_filters( 'pressody_records/sanitize_settings', $value );
	}

	/**
	 * Display the screen.
	 *
	 * @since 0.1.0
	 */
	public function render_screen() {
		$packages_permalink     = esc_url( get_packages_permalink() );
		$parts_permalink     = esc_url( get_parts_permalink() );

		$tabs = [
				'repository' => [
						'name'       => esc_html__( 'Repository', 'pressody_records' ),
						'capability' => Capabilities::VIEW_PACKAGES,
				],
				'access'     => [
						'name'       => esc_html__( 'Access', 'pressody_records' ),
						'capability' => Capabilities::MANAGE_OPTIONS,
						'is_active'  => false,
				],
				'composer'   => [
						'name'       => esc_html__( 'Composer', 'pressody_records' ),
						'capability' => Capabilities::VIEW_PACKAGES,
				],
				'settings'   => [
						'name'       => esc_html__( 'Settings', 'pressody_records' ),
						'capability' => Capabilities::MANAGE_OPTIONS,
				],
				'system-status'   => [
						'name'       => esc_html__( 'System Status', 'pressody_records' ),
						'capability' => Capabilities::MANAGE_OPTIONS,
				],
		];

		// By default, the Repository tabs is active.
		$active_tab = 'repository';

		include $this->plugin->get_path( 'views/screen-settings.php' );
	}

	/**
	 * Display a field for defining the vendor.
	 *
	 * @since 0.1.0
	 */
	public function render_field_vendor() {
		$value = $this->get_setting( 'vendor', '' );
		?>
		<p>
			<input type="text" name="pressody_records[vendor]" id="pressody_records-vendor"
			       value="<?php echo esc_attr( $value ); ?>" placeholder="pressody-records"><br/>
			<span class="description">The default is <code>pressody-records</code><br>
			This is the general vendor that will be used when exposing all the packages for consumption.<br>
				<strong>For example:</strong> you have a managed package with the source on Packagist.org (say <a
						href="https://packagist.org/packages/yoast/wordpress-seo"><code>yoast/wordpress-seo</code></a>). You will expose it under a package name in the form <code>vendor/post_slug</code> (say <code>pressody-records/yoast-wordpress-seo</code>).</span>
		</p>
		<?php
	}

	/**
	 * Display a field for defining the GitHub OAuth Token.
	 *
	 * @since 0.5.0
	 */
	public function render_field_github_oauth_token() {
		$value = $this->get_setting( 'github-oauth-token', '' );
		?>
		<p>
			<input type="password" size="80" name="pressody_records[github-oauth-token]"
			       id="pressody_records-github-oauth-token" value="<?php echo esc_attr( $value ); ?>"><br/>
			<span class="description">GitHub has a rate limit of 60 requests/hour on their API for requests not using an OAuth Token.<br>
				Since most packages on Packagist.org have their source on GitHub, and you may be using actual GitHub repos as sources, <strong>you should definitely generate a token and save it here.</strong><br>
				Learn more about <strong>the steps to take <a
							href="https://getcomposer.org/doc/articles/authentication-for-private-packages.md#github-oauth">here</a>.</strong> <strong>Be careful about the permissions you grant on the generated token!</strong></span>
		</p>
		<?php
	}

	/**
	 * Display a field for defining the PD Retailer Compositions root endpoint.
	 *
	 * @since 0.10.0
	 */
	public function render_field_pdretailer_compositions_root_endpoint() {
		$value = $this->get_setting( 'pdretailer-compositions-root-endpoint', '' );
		?>
		<p>
			<input type="url" size="80" name="pressody_records[pdretailer-compositions-root-endpoint]"
			       id="pressody_records-pdretailer-compositions-root-endpoint"
			       value="<?php echo esc_attr( $value ); ?>"><br/>
			<span class="description">Provide here the PD Retailer Compositions root endpoint URL. We will append the following fragments:<br>
				<?php echo '<code>' . PDRetailer::PDRETAILER_COMPOSITIONS_ENDPOINT_VALIDATE_PDDETAILS_PARTIAL . '</code>, ' .
				           '<code>' . PDRetailer::PDRETAILER_COMPOSITIONS_ENDPOINT_UPDATE_PARTIAL . '</code>, '; ?></span>
		</p>
		<?php
	}

	/**
	 * Display a field for defining the PD Retailer API Key.
	 *
	 * @since 0.10.0
	 */
	public function render_field_pdretailer_api_key() {
		$value = $this->get_setting( 'pdretailer-api-key', '' );
		?>
		<p>
			<input type="text" size="80" name="pressody_records[pdretailer-api-key]"
			       id="pressody_records-pdretailer-api-key" value="<?php echo esc_attr( $value ); ?>"><br/>
			<span class="description">Provide here <strong>a valid PD Retailer API key</strong> for PD Records to use to access the endpoints above.</span>
		</p>
		<?php
	}

	/**
	 * Retrieve a setting.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key     Setting name.
	 * @param mixed  $default Optional. Default setting value.
	 *
	 * @return mixed
	 */
	protected function get_setting( string $key, $default = null ) {
		return get_setting( $key, $default );
	}
}
