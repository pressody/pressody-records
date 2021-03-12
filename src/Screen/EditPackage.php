<?php
/**
 * Edit Package screen provider.
 *
 * @since   0.5.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Screen;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PostType\PackagePostType;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\Transformer\PackageTransformer;
use function PixelgradeLT\Records\get_packages_permalink;

/**
 * Edit Package screen provider class.
 *
 * @since 0.5.0
 */
class EditPackage extends AbstractHookProvider {

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected $package_manager;

	/**
	 * Packages repository.
	 *
	 * This is (should be) the repo that holds all the packages that we manage.
	 *
	 * @var PackageRepository
	 */
	protected $packages;

	/**
	 * Package Post Type.
	 *
	 * @var PackagePostType
	 */
	protected $package_post_type;

	/**
	 * Composer package transformer.
	 *
	 * @var PackageTransformer
	 */
	protected $composer_transformer;

	/**
	 * Constructor.
	 *
	 * @since 0.5.0
	 *
	 * @param PackageManager     $package_manager      Packages manager.
	 * @param PackageRepository  $packages             Packages repository.
	 * @param PackagePostType    $package_post_type    Packages post type.
	 * @param PackageTransformer $composer_transformer Package transformer.
	 */
	public function __construct(
			PackageManager $package_manager,
			PackageRepository $packages,
			PackagePostType $package_post_type,
			PackageTransformer $composer_transformer
	) {

		$this->package_manager      = $package_manager;
		$this->packages             = $packages;
		$this->package_post_type    = $package_post_type;
		$this->composer_transformer = $composer_transformer;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5.0
	 */
	public function register_hooks() {
		// Assets.
		add_action( 'load-post.php', [ $this, 'load_screen' ] );
		add_action( 'load-post-new.php', [ $this, 'load_screen' ] );

		// Logic.
		// Make sure that the post has a title.
		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'prevent_post_save_without_title' );

		// Change the post title placeholder.
		$this->add_filter( 'enter_title_here', 'change_title_placeholder', 10, 2 );

		// Add a description to the slug
		$this->add_filter( 'editable_slug', 'add_post_slug_description', 10, 2 );
		// Make sure that the slug and other metaboxes are never hidden.
		$this->add_filter( 'hidden_meta_boxes', 'prevent_hidden_metaboxes', 10, 2 );
		// Rearrange the core metaboxes.
		$this->add_action( 'add_meta_boxes_' . $this->package_manager::PACKAGE_POST_TYPE, 'add_package_current_state_meta_box', 10 );
		$this->add_action( 'add_meta_boxes_' . $this->package_manager::PACKAGE_POST_TYPE, 'adjust_core_metaboxes', 99 );

		// ADD CUSTOM POST META VIA CARBON FIELDS.
		$this->add_action( 'plugins_loaded', 'carbonfields_load' );
		$this->add_action( 'carbon_fields_register_fields', 'attach_post_meta_fields' );
		// Fill empty package details from source.
		$this->add_action( 'carbon_fields_post_meta_container_saved', 'fetch_external_packages_on_post_save', 5, 1 );
		$this->add_action( 'carbon_fields_post_meta_container_saved', 'fill_empty_package_config_details_on_post_save', 10, 2 );

		// Show edit post screen error messages.
		$this->add_action( 'edit_form_top', 'show_post_error_msgs' );

		// Add a message to the post publish metabox.
		$this->add_action( 'post_submitbox_start', 'show_publish_message' );
	}

	/**
	 * Set up the screen.
	 *
	 * @since 0.5.0
	 */
	public function load_screen() {
		$screen = get_current_screen();
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $screen->post_type ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_footer', [ $this, 'print_templates' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.5.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pixelgradelt_records-admin' );
		wp_enqueue_style( 'pixelgradelt_records-admin' );
	}

	/**
	 * Print Underscore.js templates.
	 *
	 * @since 0.5.0
	 */
	public function print_templates() {
		include $this->plugin->get_path( 'views/templates.php' );
	}

	/**
	 * Prevent the package from being published on certain occasions.
	 *
	 * Instead save as draft.
	 *
	 * @param int $post_id The ID of the post that's being saved.
	 */
	protected function prevent_post_save_without_title( int $post_id ) {
		$post = get_post( $post_id );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		     || ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		     || ! current_user_can( 'edit_post', $post_id )
		     || false !== wp_is_post_revision( $post_id )
		     || 'trash' == get_post_status( $post_id )
		     || isset( $post->post_status ) && 'auto-draft' == $post->post_status ) {
			return;
		}

		$package_title = isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : '';

		// A valid title is required, so don't let this get published without one
		if ( empty( $package_title ) ) {
			// unhook this function so it doesn't loop infinitely
			$this->remove_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'prevent_post_save_without_title' );

			$postdata = array(
					'ID'          => $post_id,
					'post_status' => 'draft',
			);
			wp_update_post( $postdata );

			// This way we avoid the "published" admin message.
			unset( $_POST['publish'] );
		}
	}

	protected function change_title_placeholder( string $placeholder, \WP_Post $post ): string {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) ) {
			return $placeholder;
		}

		return esc_html__( 'Add package title', 'pixelgradelt_records' );
	}

	protected function add_post_slug_description( string $post_name, $post ): string {
		// we want this only on the edit post screen.
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_current_screen()->id ) {
			return $post_name;
		}

		// Only on our post type.
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) ) {
			return $post_name;
		}
		// Just output it since there is no way to add it other way. ?>
		<p class="description">
			<?php _e( '<strong>The post slug is, at the same time, the Composer PROJECT NAME.</strong> It is best to use <strong>the exact plugin or theme slug!</strong><br>In the end this will be prefixed with the vendor name (like so: <code>vendor/slug</code>) to form the package name to be used in composer.json.<br>The slug/name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.', 'pixelgradelt_records' ); ?>
		</p>
		<style>
			input#post_name {
				width: 20%;
			}
		</style>
		<?php

		// We must return the post slug.
		return $post_name;
	}

	/**
	 * @param string[]   $hidden
	 * @param \WP_Screen $screen
	 *
	 * @return string[]
	 */
	protected function prevent_hidden_metaboxes( array $hidden, \WP_Screen $screen ): array {
		if ( ! empty( $hidden ) && is_array( $hidden ) &&
		     ! empty( $screen->id ) &&
		     $this->package_manager::PACKAGE_POST_TYPE === $screen->id &&
		     ! empty( $screen->post_type ) &&
		     $this->package_manager::PACKAGE_POST_TYPE === $screen->post_type
		) {
			// Prevent the slug metabox from being hidden.
			if ( false !== ( $key = array_search( 'slugdiv', $hidden ) ) ) {
				unset( $hidden[ $key ] );
			}

			// Prevent the package type metabox from being hidden.
			if ( false !== ( $key = array_search( 'tagsdiv-ltpackage_types', $hidden ) ) ) {
				unset( $hidden[ $key ] );
			}
		}

		return $hidden;
	}

	protected function adjust_core_metaboxes( \WP_Post $post ) {
		global $wp_meta_boxes;

		if ( empty( $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ] ) ) {
			return;
		}

		// We will move the slug metabox at the very top.
		if ( ! empty( $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['normal']['core']['slugdiv'] ) ) {
			$tmp = $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['normal']['core']['slugdiv'];
			unset( $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['normal']['core']['slugdiv'] );

			$wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['normal']['core'] = [ 'slugdiv' => $tmp ] + $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['normal']['core'];
		}

		// Since we are here, modify the package type title to be singular, rather than plural.
		if ( ! empty( $wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['side']['core']['tagsdiv-ltpackage_types'] ) ) {
			$wp_meta_boxes[ $this->package_manager::PACKAGE_POST_TYPE ]['side']['core']['tagsdiv-ltpackage_types']['title'] = esc_html__( 'Package Type', 'pixelgradelt_records' ) . '<span style="color: red; flex: auto">*</span>';
		}
	}

	protected function carbonfields_load() {
		Carbon_Fields::boot();
	}

	protected function attach_post_meta_fields() {
		// Register the metabox for managing the source details of the package.
		Container::make( 'post_meta', esc_html__( 'Source Configuration', 'pixelgradelt_records' ) )
		         ->where( 'post_type', '=', $this->package_manager::PACKAGE_POST_TYPE )
		         ->set_context( 'normal' )
		         ->set_priority( 'core' )
		         ->add_fields( [
				         Field::make( 'html', 'source_configuration_html', __( 'Section Description', 'pixelgradelt_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'First, configure details about <strong>where from should we get package/versions</strong> for this package.', 'pixelgradelt_records' ) ) ),

				         Field::make( 'select', 'package_source_type', __( 'Set the package source type', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Composer works with packages and repositories to find the core to use for the defined dependencies. We will strive to keep as close to that in terms of concepts. Learn more about it <a href="https://getcomposer.org/doc/05-repositories.md#repository" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->set_options( [
						              null             => esc_html__( 'Pick your package source, carefully..', 'pixelgradelt_records' ),
						              'packagist.org'  => esc_html__( 'A Packagist.org public repo', 'pixelgradelt_records' ),
						              'wpackagist.org' => esc_html__( 'A WPackagist.org repo (mirror of wordpress.org)', 'pixelgradelt_records' ),
						              'vcs'            => esc_html__( 'A VCS repo (git, SVN, fossil or hg)', 'pixelgradelt_records' ),
						              'local.plugin'   => esc_html__( 'A plugin installed on this WordPress installation', 'pixelgradelt_records' ),
						              'local.theme'    => esc_html__( 'A theme installed on this WordPress installation', 'pixelgradelt_records' ),
						              'local.manual'   => esc_html__( 'A local repo: package releases/versions are managed here, manually', 'pixelgradelt_records' ),
				              ] )
				              ->set_default_value( null )
				              ->set_required( true )
				              ->set_width( 50 ),

				         Field::make( 'text', 'package_source_name', __( 'Package Source Name', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Composer identifies a certain package (the package name) by its project name and vendor, resulting in a <code>vendor/projectname</code> package name. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#name" target="_blank">here</a>. Most often you will find the correct project name in the project\'s <code>composer.json</code> file, under the <code>"name"</code> JSON key.<br>The vendor and project name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.<br><strong>Provide the whole package name (e.g. <code>wp-media/wp-rocket<code>)!</strong>', 'pixelgradelt_records' ) )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => [ 'packagist.org', 'vcs', ],
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => 'IN',
						              ],
				              ] ),

				         Field::make( 'text', 'package_source_project_name', __( 'Package Source Project Name', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Composer identifies a certain package by its project name and vendor, resulting in a <code>vendor/name</code> identifier. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#name" target="_blank">here</a>.<br>The project name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.<br><strong>Provide only the project name (e.g. <code>akismet</code>), not the whole package name (e.g. <code>wpackagist-plugin/akismet</code>)!</strong>', 'pixelgradelt_records' ) )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => [ 'wpackagist.org', ],
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => 'IN',
						              ],
				              ] ),

				         Field::make( 'text', 'package_source_version_range', __( 'Package Source Version Range', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'A certain source can contain tens or even hundreds of historical versions/releases. <strong>It is wasteful to pull all those in</strong> (and cache them) if we are only interested in the latest major version, for example.<br>
 Specify a version range to <strong>limit the available versions/releases for this package.</strong> Most likely you will only lower-bound your range (e.g. <code>>2.0</code>), but that is up to you.<br>
 Learn more about Composer <a href="https://getcomposer.org/doc/articles/versions.md#writing-version-constraints" target="_blank">versions</a> or <a href="https://semver.mwl.be/?package=madewithlove%2Fhtaccess-cli&constraint=%3C1.2%20%7C%7C%20%3E1.6&stability=stable" target="_blank">play around</a> with version ranges.', 'pixelgradelt_records' ) )
				              ->set_width( 75 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'select', 'package_source_stability', __( 'Package Source Stability', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Limit the minimum stability required for versions. <code>Stable</code> is the most restrictive one, while <code>dev</code> the most all encompassing.<br><code>Stable</code> is the recommended (and default) one.', 'pixelgradelt_records' ) )
				              ->set_width( 25 )
				              ->set_options( [
						              'stable' => esc_html__( 'Stable', 'pixelgradelt_records' ),
						              'rc'     => esc_html__( 'RC', 'pixelgradelt_records' ),
						              'beta'   => esc_html__( 'Beta', 'pixelgradelt_records' ),
						              'alpha'  => esc_html__( 'Alpha', 'pixelgradelt_records' ),
						              'dev'    => esc_html__( 'Dev', 'pixelgradelt_records' ),
				              ] )
				              ->set_default_value( 'stable' )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => 'IN',
						              ],
				              ] ),

				         Field::make( 'text', 'package_vcs_url', __( 'Package VCS URL', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Just provide the full URL to your VCS repo (e.g. a Github repo URL like <code>https://github.com/pixelgradelt/satispress</code>). Learn more about it <a href="https://getcomposer.org/doc/05-repositories.md#vcs" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => 'vcs',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => '=',
						              ],
				              ] ),

				         Field::make( 'select', 'package_local_plugin_file', __( 'Choose one of the installed plugins', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Installed plugins that are already attached to a package are NOT part of the list of choices.', 'pixelgradelt_records' ) )
				              ->set_options( [ $this, 'get_available_installed_plugins_options' ] )
				              ->set_default_value( null )
				              ->set_required( true )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => 'local.plugin',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => '=',
						              ],
				              ] ),
				         Field::make( 'select', 'package_local_theme_slug', __( 'Choose one of the installed themes', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Installed themes that are already attached to a package are NOT part of the list of choices.', 'pixelgradelt_records' ) )
				              ->set_options( [ $this, 'get_available_installed_themes_options' ] )
				              ->set_default_value( null )
				              ->set_required( true )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'value'   => 'local.theme',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
								              'compare' => '=',
						              ],
				              ] ),

				         Field::make( 'complex', 'package_manual_releases', __( 'Package Releases', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'The manually uploaded package releases (zips).<br> <strong>These zip files will be cached</strong> just like external or installed sources. If you remove a certain release and update the post, the cache will keep up and auto-clean itself.<br><strong>If you upload a different zip to a previously published release, the cache will not auto-update itself</strong> (for performance reasons). In this case, first delete the release, hit "Update" for the post and them add a new release.<br>Also, bear in mind that <strong>we do not clean the Media Gallery of unused zip files.</strong> That is up to you, if you can\'t stand some mess.', 'pixelgradelt_records' ) )
				              ->set_classes( 'package-manual-releases' )
				              ->set_collapsed( true )
				              ->add_fields( [
						              Field::make( 'text', 'version', __( 'Version', 'pixelgradelt_records' ) )
						                   ->set_help_text( __( 'Semver-formatted version string. Bear in mind that we currently don\'t do any check regarding the version. It is up to you to <strong>make sure that the zip file matches the version specified.</strong>', 'pixelgradelt_records' ) )
						                   ->set_required( true )
						                   ->set_width( 25 ),
						              Field::make( 'file', 'file', __( 'Zip File', 'pixelgradelt_records' ) )
						                   ->set_type( 'zip' ) // The allowed mime-types (see wp_get_mime_types())
						                   ->set_value_type( 'id' ) // Change to 'url' to store the file/attachment URL instead of the attachment ID.
						                   ->set_required( true )
						                   ->set_width( 50 ),
				              ] )
				              ->set_header_template( '
								    <% if (version) { %>
								        Version: <%- version %>
								    <% } %>
								' )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
								              'value'   => 'local.manual',
								              'compare' => '=',
						              ],
				              ] ),


				         Field::make( 'separator', 'package_details_separator', '' )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'html', 'package_details_html', __( 'Section Description', 'pixelgradelt_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'Configure details about <strong>the package itself,</strong> as it will be exposed for consumption.<br><strong>Leave empty</strong> and we will try to figure them out on save; after that you can modify them however you like.', 'pixelgradelt_records' ) ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'textarea', 'package_details_description', __( 'Package Description', 'pixelgradelt_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'text', 'package_details_homepage', __( 'Package Homepage URL', 'pixelgradelt_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'text', 'package_details_license', __( 'Package License', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'The package license in a standard format (e.g. <code>GPL-3.0-or-later</code>). If there are multiple licenses, comma separate them. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#license" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'complex', 'package_details_authors', __( 'Package Authors', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'The package authors details. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#authors" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->add_fields( [
						              Field::make( 'text', 'name', __( 'Author Name', 'pixelgradelt_records' ) )->set_required( true )->set_width( 50 ),
						              Field::make( 'text', 'email', __( 'Author Email', 'pixelgradelt_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'homepage', __( 'Author Homepage', 'pixelgradelt_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'role', __( 'Author Role', 'pixelgradelt_records' ) )->set_width( 50 ),
				              ] )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),

		         ] );
	}

	public function add_package_current_state_meta_box() {
		$post_type    = $this->package_manager::PACKAGE_POST_TYPE;
		$container_id = $post_type . '_current_state_details';
		add_meta_box(
				$container_id,
				esc_html__( 'Current Package State Details', 'pixelgradelt_records' ),
				array( $this, 'display_package_current_state_meta_box' ),
				$this->package_manager::PACKAGE_POST_TYPE,
				'normal',
				'default'
		);

		add_filter( "postbox_classes_{$post_type}_{$container_id}", [
				$this,
				'add_package_current_state_box_classes',
		] );
	}

	/**
	 * Classes to add to the post meta box
	 */
	public function add_package_current_state_box_classes( $classes ) {
		$classes[] = 'carbon-box';

		return $classes;
	}

	/**
	 * Display Package Current State meta box
	 *
	 * @param \WP_Post $post
	 */
	public function display_package_current_state_meta_box( \WP_Post $post ) {
		$package_data = $this->package_manager->get_package_id_data( (int) $post->ID );
		if ( empty( $package_data ) || empty( $package_data['source_name'] ) || empty( $package_data['type'] ) ) {
			echo '<div class="cf-container"><div class="cf-field"><p>No current package details. Probably you need to do some configuring first.</p></div></div>';

			return;
		}

		$package = $this->packages->first_where( [
				'source_name' => $package_data['source_name'],
				'type'        => $package_data['type'],
		] );

		// Transform the package in the Composer format.
		$package = $this->composer_transformer->transform( $package );

		if ( empty( $package ) ) {
			echo '<div class="cf-container"><div class="cf-field"><p>No current package details. Probably you need to do some configuring first.</p></div></div>';

			return;
		}

		// Wrap it for spacing.
		echo '<div class="cf-container"><div class="cf-field">';
		echo '<p>This is the same info shown in the full package-details list available <a href="' . esc_url( admin_url( 'options-general.php?page=pixelgradelt_records#pixelgradelt_records-packages' ) ) . '">here</a>. The real source of truth is the packages JSON available <a href="' . esc_url( get_packages_permalink() ) . '">here</a>.</p>';
		require $this->plugin->get_path( 'views/package-details.php' );
		echo '</div></div>';
	}

	/**
	 * Attempt to fill empty package details from the package source.
	 *
	 * @param int                           $post_ID
	 * @param Container\Post_Meta_Container $meta_container
	 */
	protected function fill_empty_package_config_details_on_post_save( int $post_ID, Container\Post_Meta_Container $meta_container ) {

		$package_data = $this->package_manager->get_package_id_data( $post_ID );
		if ( empty( $package_data ) ) {
			return;
		}

		$package = $this->packages->first_where( [
				'source_name' => $package_data['source_name'],
				'type'        => $package_data['type'],
		] );
		if ( empty( $package ) ) {
			return;
		}

		// The package description.
		if ( empty( get_post_meta( $post_ID, '_package_details_description', true ) ) && $package->get_description() ) {
			update_post_meta( $post_ID, '_package_details_description', sanitize_text_field( $package->get_description() ) );
		}
		// The package homepage URL.
		if ( empty( get_post_meta( $post_ID, '_package_details_homepage', true ) ) && $package->get_homepage() ) {
			update_post_meta( $post_ID, '_package_details_homepage', esc_url_raw( $package->get_homepage() ) );
		}
		// The package license.
		if ( empty( get_post_meta( $post_ID, '_package_details_license', true ) ) && $package->get_license() ) {
			update_post_meta( $post_ID, '_package_details_license', sanitize_text_field( $package->get_license() ) );
		}
		// The package authors.
		if ( empty( $this->package_manager->get_post_package_authors( $post_ID, $meta_container->get_id() ) ) && $package->get_authors() ) {
			$this->package_manager->set_post_package_authors( $post_ID, $package->get_authors(), $meta_container->get_id() );
		}

		// The package keywords.
		$package_keywords = $this->package_manager->get_post_package_keywords( $post_ID );
		if ( empty( $package_keywords ) && $package->get_keywords() ) {
			$this->package_manager->set_post_package_keywords( $post_ID, $package->get_keywords() );
		}
	}

	/**
	 * Attempt to fetch external packages on post save.
	 *
	 * @param int $post_ID
	 *
	 * @throws \Exception
	 */
	protected function fetch_external_packages_on_post_save( int $post_ID ) {
		$packages = $this->package_manager->fetch_external_package_remote_releases( $post_ID );

		// We will save the packages (these are actually releases considering we tackle a single package) in the database.
		// For actually caching the zips, we will rely on PixelgradeLT\Records\PackageType\Builder\PackageBuilder::build() to do the work.
		if ( ! empty( $packages ) ) {
			update_post_meta( $post_ID, '_package_source_cached_release_packages', $packages );
		}
	}

	public function get_available_installed_plugins_options(): array {
		$options = [];

		$used_plugin_files = $this->package_manager->get_managed_installed_plugins( [ 'post__not_in' => [ get_the_ID(), ], ] );
		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			// Do not include plugins already attached to a package.
			if ( in_array( $plugin_file, $used_plugin_files ) ) {
				continue;
			}

			$options[ $plugin_file ] = sprintf( __( '%s (by %s) - %s', 'pixelgradelt_records' ), $plugin_data['Name'], $plugin_data['Author'], $this->get_slug_from_plugin_file( $plugin_file ) );
		}

		ksort( $options );

		// Prepend an empty option.
		$options = [ null => esc_html__( 'Pick your installed plugin, carefully..', 'pixelgradelt_records' ) ] + $options;

		return $options;
	}

	/**
	 * Retrieve a plugin slug.
	 *
	 * @since 0.5.0
	 *
	 * @param string $plugin_file Plugin slug or relative path to the main plugin
	 *                            file from the plugins directory.
	 *
	 * @return string
	 */
	protected function get_slug_from_plugin_file( string $plugin_file ): string {
		$slug = \dirname( $plugin_file );

		// Account for single file plugins.
		$slug = '.' === $slug ? basename( $plugin_file, '.php' ) : $slug;

		return $slug;
	}

	public function get_available_installed_themes_options(): array {
		$options = [];

		$used_theme_slugs = $this->package_manager->get_managed_installed_themes( [ 'post__not_in' => [ get_the_ID(), ], ] );

		foreach ( wp_get_themes() as $theme_slug => $theme_data ) {
			// Do not include themes already attached to a package.
			if ( in_array( $theme_slug, $used_theme_slugs ) ) {
				continue;
			}

			$options[ $theme_slug ] = sprintf( __( '%s (by %s) - %s', 'pixelgradelt_records' ), $theme_data->get( 'Name' ), $theme_data->get( 'Author' ), $theme_slug );
		}

		ksort( $options );

		// Prepend an empty option.
		$options = [ null => esc_html__( 'Pick your installed theme, carefully..', 'pixelgradelt_records' ) ] + $options;

		return $options;
	}

	/**
	 * Display error messages at the top of the post edit screen.
	 *
	 * Doing this prevents users from getting confused when their new posts aren't published.
	 *
	 * @param \WP_Post The current post object.
	 */
	protected function show_post_error_msgs( \WP_Post $post ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) || 'auto-draft' === get_post_status( $post ) ) {
			return;
		}

		// Display an error regarding that the package title is required.
		if ( empty( $post->post_title ) ) {
			printf(
					'<div class="error below-h2"><p>%s</p></div>',
					esc_html__( 'You MUST set a unique name (title) for creating a new package.', 'pixelgradelt_records' )
			);
		}

		// Display an error regarding that the package type is required.
		$package_type = wp_get_object_terms( $post->ID, $this->package_manager::PACKAGE_TYPE_TAXONOMY, array(
				'orderby' => 'term_id',
				'order'   => 'ASC',
		) );
		if ( is_wp_error( $package_type ) || empty( $package_type ) ) {
			$taxonomy_args = $this->package_post_type->get_package_type_taxonomy_args();
			printf(
					'<div class="error below-h2"><p>%s</p></div>',
					sprintf( esc_html__( 'You MUST choose a %s for creating a new package.', 'pixelgradelt_records' ), $taxonomy_args['labels']['singular_name'] )
			);
		}
	}

	/**
	 * Display a message above the post publish actions.
	 *
	 * @param \WP_Post The current post object.
	 */
	protected function show_publish_message( \WP_Post $post ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) ) {
			return;
		}

		printf(
				'<div class="message patience"><p>%s</p></div>',
				esc_html__( 'Please bear in mind that Publish/Update may take a minute or two since we do some heavy lifting behind the scenes. Patience is advised.', 'pixelgradelt_records' )
		);
	}
}
