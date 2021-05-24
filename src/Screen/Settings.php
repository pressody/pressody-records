<?php
/**
 * Settings screen provider.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Authentication\ApiKey\ApiKey;
use PixelgradeLT\Records\Authentication\ApiKey\ApiKeyRepository;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Provider\HealthCheck;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageTransformer;

use function PixelgradeLT\Records\get_packages_permalink;
use function PixelgradeLT\Records\get_parts_permalink;
use function PixelgradeLT\Records\preload_rest_data;

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
				esc_html__( 'PixelgradeLT Records', 'pixelgradelt_records' ),
				esc_html__( 'LT Records', 'pixelgradelt_records' ),
				Capabilities::MANAGE_OPTIONS,
				'pixelgradelt_records',
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
		wp_enqueue_script( 'pixelgradelt_records-admin' );
		wp_enqueue_style( 'pixelgradelt_records-admin' );
		wp_enqueue_script( 'pixelgradelt_records-access' );
		wp_enqueue_script( 'pixelgradelt_records-repository' );

		wp_localize_script(
				'pixelgradelt_records-access',
				'_pixelgradeltRecordsAccessData',
				[
						'editedUserId' => get_current_user_id(),
				]
		);

		wp_localize_script(
				'pixelgradelt_records-repository',
				'_pixelgradeltRecordsRepositoryData',
				[
						'addNewPackageUrl' => admin_url('post-new.php?post_type=ltpackage'),
				]
		);

		$preload_paths = [
				'/pixelgradelt_records/v1/packages',
		];

		if ( current_user_can( Capabilities::MANAGE_OPTIONS ) ) {
			$preload_paths = array_merge(
					$preload_paths,
					[
							'/pixelgradelt_records/v1/apikeys?user=' . get_current_user_id(),
							'/pixelgradelt_records/v1/plugins?_fields=slug,name,type',
							'/pixelgradelt_records/v1/themes?_fields=slug,name,type',
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
		register_setting( 'pixelgradelt_records', 'pixelgradelt_records', [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Add settings sections.
	 *
	 * @since 0.1.0
	 */
	public function add_sections() {
		add_settings_section(
				'default',
				esc_html__( 'General', 'pixelgradelt_records' ),
				'__return_null',
				'pixelgradelt_records'
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
				'<label for="pixelgradelt_records-vendor">' . esc_html__( 'Vendor', 'pixelgradelt_records' ) . '</label>',
				[ $this, 'render_field_vendor' ],
				'pixelgradelt_records',
				'default'
		);

		add_settings_field(
				'github-oauth-token',
				'<label for="pixelgradelt_records-github-oauth-token">' . esc_html__( 'Github OAuth Token', 'pixelgradelt_records' ) . '</label>',
				[ $this, 'render_field_github_oauth_token' ],
				'pixelgradelt_records',
				'default'
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

		return (array) apply_filters( 'pixelgradelt_records_sanitize_settings', $value );
	}

	/**
	 * Display the screen.
	 *
	 * @since 0.1.0
	 */
	public function render_screen() {
		$packages_permalink     = esc_url( get_packages_permalink() );
		$parts_permalink     = esc_url( get_parts_permalink() );
		$system_checks = [];

		$tabs = [
				'repository' => [
						'name'       => esc_html__( 'Repository', 'pixelgradelt_records' ),
						'capability' => Capabilities::VIEW_PACKAGES,
				],
				'access'     => [
						'name'       => esc_html__( 'Access', 'pixelgradelt_records' ),
						'capability' => Capabilities::MANAGE_OPTIONS,
						'is_active'  => false,
				],
				'composer'   => [
						'name'       => esc_html__( 'Composer', 'pixelgradelt_records' ),
						'capability' => Capabilities::VIEW_PACKAGES,
				],
				'settings'   => [
						'name'       => esc_html__( 'Settings', 'pixelgradelt_records' ),
						'capability' => Capabilities::MANAGE_OPTIONS,
				],
		];

		$active_tab = 'repository';

		include $this->plugin->get_path( 'views/screen-settings.php' );
	}

	/**
	 * Display the access section description.
	 *
	 * @since 0.1.0
	 */
	public function render_section_access_description() {

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
			<input type="text" name="pixelgradelt_records[vendor]" id="pixelgradelt_records-vendor"
			       value="<?php echo esc_attr( $value ); ?>" placeholder="pixelgradel-records"><br/>
			<span class="description">The default is <code>pixelgradelt-records</code><br>
			This is the general vendor that will be used when exposing all the packages for consumption.<br>
				<strong>For example:</strong> you have a managed package with the source on Packagist.org (say <a
						href="https://packagist.org/packages/yoast/wordpress-seo"><code>yoast/wordpress-seo</code></a>). You will expose it under a package name in the form <code>vendor/post_slug</code> (say <code>pixelgradelt-records/yoast-wordpress-seo</code>).</span>
		</p>
		<?php
	}

	/**
	 * Display a field for defining the Github OAuth Token.
	 *
	 * @since 0.5.0
	 */
	public function render_field_github_oauth_token() {
		$value = $this->get_setting( 'github-oauth-token', '' );
		?>
		<p>
			<input type="password" size="80" name="pixelgradelt_records[github-oauth-token]"
			       id="pixelgradelt_records-github-oauth-token" value="<?php echo esc_attr( $value ); ?>"><br/>
			<span class="description">Github has a rate limit of 60 requests/hour on their API for requests not using an OAuth Token.<br>
				Since most packages on Packagist.org have their source on Github, and you may be using actual Github repos as sources, <strong>you should definitely generate a token and save it here.</strong><br>
				Learn more about <strong>the steps to take <a
							href="https://getcomposer.org/doc/articles/authentication-for-private-packages.md#github-oauth">here</a>.</strong> <strong>Be careful about the permissions you grant on the generated token!</strong></span>
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
		$option = get_option( 'pixelgradelt_records' );

		return $option[ $key ] ?? $default;
	}
}
