<?php
/**
 * Settings screen provider.
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
use PixelgradeLT\Records\Provider\HealthCheck;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageTransformer;
use WP_Theme;

use function PixelgradeLT\Records\get_packages_permalink;

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
	protected $api_keys;

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer
	 */
	protected $composer_transformer;

	/**
	 * Package repository.
	 *
	 * @var PackageRepository
	 */
	protected $packages;

	/**
	 * Create the setting screen.
	 *
	 * @param PackageRepository  $packages            Package repository.
	 * @param ApiKeyRepository   $api_keys            API Key repository.
	 * @param PackageTransformer $composer_transformer Package transformer.
	 */
	public function __construct( PackageRepository $packages, ApiKeyRepository $api_keys, PackageTransformer $composer_transformer ) {
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
			esc_html__( 'PixelgradeLT Records', 'pixelgradelt_records' ),
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
		wp_enqueue_script( 'pixelgradelt_records-package-settings' );

		$api_keys = $this->api_keys->find_for_user( wp_get_current_user() );

		$items = array_map(
			function( ApiKey $api_key ) {
					$data                   = $api_key->to_array();
					$data['user_edit_link'] = esc_url( get_edit_user_link( $api_key->get_user()->ID ) );

					return $data;
			},
			$api_keys
		);

		wp_enqueue_script( 'pixelgradelt_records-api-keys' );
		wp_localize_script(
			'pixelgradelt_records-api-keys',
			'_pixelgradelt_recordsApiKeysData',
			[
				'items'  => $items,
				'userId' => get_current_user_id(),
			]
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 0.1.0
	 */
	public function register_settings() {
		register_setting( 'pixelgradelt_records', 'pixelgradelt_records', [ $this, 'sanitize_settings' ] );
		register_setting( 'pixelgradelt_records', 'pixelgradelt_records_themes', [ $this, 'sanitize_theme_settings' ] );
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

		add_settings_section(
			'access',
			esc_html__( 'Access', 'pixelgradelt_records' ),
			[ $this, 'render_section_access_description' ],
			'pixelgradelt_records'
		);

		add_settings_section(
			'themes',
			esc_html__( 'Themes', 'pixelgradelt_records' ),
			[ $this, 'render_section_themes_description' ],
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
			'themes',
			esc_html__( 'Themes', 'pixelgradelt_records' ),
			[ $this, 'render_field_themes' ],
			'pixelgradelt_records',
			'themes'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array $value Settings values.
	 * @return array Sanitized and filtered settings values.
	 */
	public function sanitize_settings( array $value ): array {
		if ( ! empty( $value['vendor'] ) ) {
			$value['vendor'] = preg_replace( '/[^a-z0-9_\-\.]+/i', '', $value['vendor'] );
		}

		return (array) apply_filters( 'pixelgradelt_records_sanitize_settings', $value );
	}

	/**
	 * Sanitize list of themes.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Setting value.
	 * @return array
	 */
	public function sanitize_theme_settings( $value ): array {
		return array_filter( array_unique( (array) $value ) );
	}

	/**
	 * Display the screen.
	 *
	 * @since 0.1.0
	 */
	public function render_screen() {
		$permalink = esc_url( get_packages_permalink() );
		$packages  = array_map( [ $this->composer_transformer, 'transform' ], $this->packages->all() );
		include $this->plugin->get_path( 'views/screen-settings.php' );
		include $this->plugin->get_path( 'views/templates.php' );
	}

	/**
	 * Display the access section description.
	 *
	 * @since 0.1.0
	 */
	public function render_section_access_description() {
		printf(
			'<p>%s</p>',
			esc_html__( 'API Keys are used to access your PixelgradeLT Records repository and download packages. Your personal API keys appear below or you can create keys for other users by editing their accounts.', 'pixelgradelt_records' )
		);

		printf(
			'<p>%s</p>',
			/* translators: %s: <code>pixelgradelt_records</code> */
			sprintf( esc_html__( 'The password for all API Keys is %s.', 'pixelgradelt_records' ), '<code>pixelgradelt_records</code>' )
		);

		echo '<div id="pixelgradelt_records-api-key-manager"></div>';

		printf(
			'<p><a href="https://github.com/pixelgradelt/pixelgradelt-records/blob/develop/docs/security.md" target="_blank" rel="noopener noreferer"><em>%s</em></a></p>',
			esc_html__( 'Read more about securing your PixelgradeLT Records repository.', 'pixelgradelt_records' )
		);
	}

	/**
	 * Display the themes section description.
	 *
	 * @since 0.1.0
	 */
	public function render_section_themes_description() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Choose themes to make available in your PixelgradeLT Records repository.', 'pixelgradelt_records' )
		);
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
			<input type="text" name="pixelgradelt_records[vendor]" id="pixelgradelt_records-vendor" value="<?php echo esc_attr( $value ); ?>"><br />
			<span class="description">Default is <code>pixelgradelt_records</code></span>
		</p>
		<?php
	}

	/**
	 * Display the themes list field.
	 *
	 * @since 0.1.0
	 */
	public function render_field_themes() {
		$value = get_option( 'pixelgradelt_records_themes', [] );

		foreach ( wp_get_themes() as $slug => $theme ) {
			printf(
				'<label><input type="checkbox" name="pixelgradelt_records_themes[]" value="%1$s"%2$s> %3$s</label><br />',
				esc_attr( $slug ),
				checked( \in_array( $slug, $value, true ), true, false ),
				esc_html( $theme->get( 'Name' ) )
			);
		}
	}

	/**
	 * Retrieve a setting.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key     Setting name.
	 * @param mixed  $default Optional. Default setting value.
	 * @return mixed
	 */
	protected function get_setting( string $key, $default = null ) {
		$option = get_option( 'pixelgradelt_records' );

		return $option[ $key ] ?? $default;
	}
}
