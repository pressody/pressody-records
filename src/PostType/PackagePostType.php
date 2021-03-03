<?php
/**
 * The Package custom post type.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PostType;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Capabilities;
use PixelgradeLT\Records\Repository\PackageRepository;
use PixelgradeLT\Records\PackageManager;

/**
 * The Package custom post type provider: provides the interface for and stores the information about each managed package.
 *
 * @since 0.1.0
 */
class PackagePostType extends AbstractHookProvider {

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected $package_manager;

	/**
	 * Packages repository.
	 *
	 * @var PackageRepository
	 */
	protected $packages;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageManager    $package_manager Packages manager.
	 * @param PackageRepository $packages        Packages repository.
	 */
	public function __construct(
			PackageManager $package_manager,
			PackageRepository $packages
	) {
		$this->package_manager = $package_manager;
		$this->packages        = $packages;
	}

	public function register_hooks() {
		/*
		 * HANDLE THE CUSTOM POST TYPE LOGIC.
		 */
		$this->add_action( 'init', 'register_post_type' );
		// Make sure that the post has a title.
		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'prevent_post_save_without_title' );
		// Change the post title placeholder.
		$this->add_filter( 'enter_title_here', 'change_title_placeholder', 10, 2 );
		// Add a description to the slug
		$this->add_filter( 'editable_slug', 'add_post_slug_description', 10, 2 );
		// Make sure that the slug and other metaboxes are never hidden.
		$this->add_filter( 'hidden_meta_boxes', 'prevent_hidden_metaboxes', 10, 2 );
		// Rearrange the core metaboxes.
		$this->add_action( 'add_meta_boxes_' . $this->package_manager::PACKAGE_POST_TYPE, 'adjust_core_metaboxes', 99 );

		// We want extra header fields included in the plugin and theme data.
		$this->add_filter( 'extra_plugin_headers', 'add_extra_installed_headers' );
		$this->add_filter( 'extra_theme_headers', 'add_extra_installed_headers' );

		/*
		 * HANDLE THE CUSTOM TAXONOMY LOGIC.
		 */
		$this->add_action( 'init', 'register_taxonomy' );
		// Handle the custom taxonomy logic on the post edit screen.
		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'save_package_type_meta_box' );
		// Show a dropdown to filter the posts list by the custom taxonomy.
		$this->add_action( 'restrict_manage_posts', 'output_admin_list_filters' );

		/*
		 * Show edit post screen error messages.
		 */
		$this->add_action( 'edit_form_top', 'show_post_error_msgs' );

		/*
		 * ADD CUSTOM POST META VIA CARBON FIELDS
		 */
		$this->add_action( 'after_setup_theme', 'carbonfields_load' );
		$this->add_action( 'carbon_fields_register_fields', 'attach_post_meta_fields' );
		// Fill empty package details from source.
		$this->add_action( 'carbon_fields_post_meta_container_saved', 'fetch_external_packages_on_post_save', 5, 1 );
		$this->add_action( 'carbon_fields_post_meta_container_saved', 'fill_empty_package_config_details_on_post_save', 10, 2 );
	}

	protected function register_post_type() {
		register_post_type( $this->package_manager::PACKAGE_POST_TYPE, $this->get_post_type_args() );
	}

	protected function get_post_type_args(): array {
		$labels = [
				'name'                  => __( 'PixelgradeLT Packages', 'pixelgradelt_records' ),
				'singular_name'         => __( 'PixelgradeLT Package', 'pixelgradelt_records' ),
				'menu_name'             => _x( 'LT Packages', 'Admin Menu text', 'pixelgradelt_records' ),
				'add_new'               => _x( 'Add New', 'PixelgradeLT Package', 'pixelgradelt_records' ),
				'add_new_item'          => __( 'Add New PixelgradeLT Package', 'pixelgradelt_records' ),
				'new_item'              => __( 'New PixelgradeLT Package', 'pixelgradelt_records' ),
				'edit_item'             => __( 'Edit PixelgradeLT Package', 'pixelgradelt_records' ),
				'view_item'             => __( 'View PixelgradeLT Package', 'pixelgradelt_records' ),
				'all_items'             => __( 'All Packages', 'pixelgradelt_records' ),
				'search_items'          => __( 'Search Packages', 'pixelgradelt_records' ),
				'not_found'             => __( 'No packages found.', 'pixelgradelt_records' ),
				'not_found_in_trash'    => __( 'No packages found in Trash.', 'pixelgradelt_records' ),
				'uploaded_to_this_item' => __( 'Uploaded to this package', 'pixelgradelt_records' ),
				'filter_items_list'     => __( 'Filter packages list', 'pixelgradelt_records' ),
				'items_list_navigation' => __( 'Packages list navigation', 'pixelgradelt_records' ),
				'items_list'            => __( 'PixelgradeLT Packages list', 'pixelgradelt_records' ),
		];

		return [
				'labels'             => $labels,
				'description'        => __( 'Composer packages to be used in the PixelgradeLT modules delivered to PixelgradeLT users.', 'pixelgradelt_records' ),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => true,
				'has_archive'        => false,
				'rest_base'          => $this->package_manager::PACKAGE_POST_TYPE_PLURAL,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => true,
				'map_meta_cap'       => true,
				'supports'           => [
						'title',
						'revisions',
						'custom-fields',
				],
		];
	}

	protected function register_taxonomy() {

		register_taxonomy(
				$this->package_manager::PACKAGE_TYPE_TAXONOMY,
				[ $this->package_manager::PACKAGE_POST_TYPE ],
				$this->get_package_type_taxonomy_args()
		);

		foreach ( $this->package_manager::PACKAGE_TYPE_TERMS as $term ) {
			if ( ! term_exists( $term['name'], $this->package_manager::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term['name'], $this->package_manager::PACKAGE_TYPE_TAXONOMY, $term );
			}
		}

		register_taxonomy(
				$this->package_manager::PACKAGE_KEYWORD_TAXONOMY,
				[ $this->package_manager::PACKAGE_POST_TYPE ],
				$this->get_package_keyword_taxonomy_args()
		);
	}

	protected function get_package_type_taxonomy_args(): array {
		$labels = [
				'name'                  => __( 'Package Types', 'pixelgradelt_records' ),
				'singular_name'         => __( 'Package Type', 'pixelgradelt_records' ),
				'add_new'               => _x( 'Add New', 'PixelgradeLT Package Type', 'pixelgradelt_records' ),
				'add_new_item'          => __( 'Add New Package Type', 'pixelgradelt_records' ),
				'update_item'           => __( 'Update Package Type', 'pixelgradelt_records' ),
				'new_item_name'         => __( 'New Package Type Name', 'pixelgradelt_records' ),
				'edit_item'             => __( 'Edit Package Type', 'pixelgradelt_records' ),
				'all_items'             => __( 'All Package Types', 'pixelgradelt_records' ),
				'search_items'          => __( 'Search Package Types', 'pixelgradelt_records' ),
				'parent_item'           => __( 'Parent Package Type', 'pixelgradelt_records' ),
				'parent_item_colon'     => __( 'Parent Package Type:', 'pixelgradelt_records' ),
				'not_found'             => __( 'No package types found.', 'pixelgradelt_records' ),
				'no_terms'              => __( 'No package types.', 'pixelgradelt_records' ),
				'items_list_navigation' => __( 'Package Types list navigation', 'pixelgradelt_records' ),
				'items_list'            => __( 'Package Types list', 'pixelgradelt_records' ),
				'back_to_items'         => __( '&larr; Go to Package Types', 'pixelgradelt_records' ),
		];

		return [
				'labels'             => $labels,
				'show_ui'            => true,
				'show_in_quick_edit' => true,
				'show_admin_column'  => true,
				'hierarchical'       => false,
				'meta_box_cb'        => [ $this, 'package_type_meta_box' ],
				'capabilities'       => [
						'manage_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
						'edit_terms'   => Capabilities::MANAGE_PACKAGE_TYPES,
						'delete_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
						'assign_terms' => 'edit_posts',
				],
		];
	}

	protected function get_package_keyword_taxonomy_args(): array {
		$labels = [
				'name'                       => __( 'Package Keywords', 'pixelgradelt_records' ),
				'singular_name'              => __( 'Package Keyword', 'pixelgradelt_records' ),
				'add_new'                    => _x( 'Add New', 'PixelgradeLT Package Keyword', 'pixelgradelt_records' ),
				'add_new_item'               => __( 'Add New Package Keyword', 'pixelgradelt_records' ),
				'update_item'                => __( 'Update Package Keyword', 'pixelgradelt_records' ),
				'new_item_name'              => __( 'New Package Keyword Name', 'pixelgradelt_records' ),
				'edit_item'                  => __( 'Edit Package Keyword', 'pixelgradelt_records' ),
				'all_items'                  => __( 'All Package Keywords', 'pixelgradelt_records' ),
				'search_items'               => __( 'Search Package Keywords', 'pixelgradelt_records' ),
				'not_found'                  => __( 'No package tags found.', 'pixelgradelt_records' ),
				'no_terms'                   => __( 'No package tags.', 'pixelgradelt_records' ),
				'separate_items_with_commas' => __( 'Separate keywords with commas.', 'pixelgradelt_records' ),
				'choose_from_most_used'      => __( 'Choose from the most used keywords.', 'pixelgradelt_records' ),
				'most_used'                  => __( 'Most used.', 'pixelgradelt_records' ),
				'items_list_navigation'      => __( 'Package Keywords list navigation', 'pixelgradelt_records' ),
				'items_list'                 => __( 'Package Keywords list', 'pixelgradelt_records' ),
				'back_to_items'              => __( '&larr; Go to Package Keywords', 'pixelgradelt_records' ),
		];

		return [
				'labels'             => $labels,
				'show_ui'            => true,
				'show_in_quick_edit' => true,
				'show_admin_column'  => true,
				'hierarchical'       => false,
		];
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

	/**
	 * Display Package Type meta box
	 *
	 * @param \WP_Post $post
	 */
	public function package_type_meta_box( \WP_Post $post ) {
		$terms = get_terms( $this->package_manager::PACKAGE_TYPE_TAXONOMY, array(
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
		) );

		$package_type      = wp_get_object_terms( $post->ID, $this->package_manager::PACKAGE_TYPE_TAXONOMY, array(
				'orderby' => 'term_id',
				'order'   => 'ASC',
		) );
		$package_type_name = '';

		if ( ! is_wp_error( $package_type ) ) {
			if ( isset( $package_type[0] ) && isset( $package_type[0]->name ) ) {
				$package_type_name = $package_type[0]->name;
			}
		}

		foreach ( $terms as $term ) { ?>
			<label title="<?php esc_attr_e( $term->name ); ?>">
				<input type="radio"
				       name="<?php esc_attr_e( $this->package_manager::PACKAGE_TYPE_TAXONOMY_SINGULAR ); ?>"
				       value="<?php esc_attr_e( $term->name ); ?>" <?php checked( $term->name, $package_type_name ); ?>>
				<span><?php esc_html_e( $term->name ); ?></span>
			</label><br>
			<?php
		}
	}

	/**
	 * Save the package type box results.
	 *
	 * @param int $post_id The ID of the post that's being saved.
	 */
	protected function save_package_type_meta_box( int $post_id ) {
		$post = get_post( $post_id );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		     || ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		     || ! current_user_can( 'edit_post', $post_id )
		     || false !== wp_is_post_revision( $post_id )
		     || 'trash' == get_post_status( $post_id )
		     || isset( $post->post_status ) && 'auto-draft' == $post->post_status ) {
			return;
		}

		$package_type = isset( $_POST[ $this->package_manager::PACKAGE_TYPE_TAXONOMY_SINGULAR ] ) ? sanitize_text_field( $_POST[ $this->package_manager::PACKAGE_TYPE_TAXONOMY_SINGULAR ] ) : '';

		// A valid rating is required, so don't let this get published without one
		if ( empty( $package_type ) ) {
			// unhook this function so it doesn't loop infinitely
			$this->remove_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'save_package_type_meta_box' );

			$postdata = array(
					'ID'          => $post_id,
					'post_status' => 'draft',
			);
			wp_update_post( $postdata );
		} else {
			$term = get_term_by( 'name', $package_type, $this->package_manager::PACKAGE_TYPE_TAXONOMY );
			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, $term->term_id, $this->package_manager::PACKAGE_TYPE_TAXONOMY, false );
			}
		}
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
			$taxonomy_args = $this->get_package_type_taxonomy_args();
			printf(
					'<div class="error below-h2"><p>%s</p></div>',
					sprintf( esc_html__( 'You MUST choose a %s for creating a new package.', 'pixelgradelt_records' ), $taxonomy_args['labels']['singular_name'] )
			);
		}
	}

	/**
	 * Output filters in the All Posts screen.
	 *
	 * @param string $post_type The current post type.
	 */
	protected function output_admin_list_filters( string $post_type ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $post_type ) {
			return;
		}

		$taxonomy = get_taxonomy( $this->package_manager::PACKAGE_TYPE_TAXONOMY );

		wp_dropdown_categories( array(
				'show_option_all' => sprintf( __( 'All %s', 'pixelgradelt_records' ), $taxonomy->label ),
				'orderby'         => 'term_id',
				'order'           => 'ASC',
				'hide_empty'      => false,
				'hide_if_empty'   => true,
				'selected'        => filter_input( INPUT_GET, $taxonomy->query_var, FILTER_SANITIZE_STRING ),
				'hierarchical'    => false,
				'name'            => $taxonomy->query_var,
				'taxonomy'        => $taxonomy->name,
				'value_field'     => 'slug',
		) );
	}

	protected function carbonfields_load() {
		Carbon_Fields::boot();
	}

	protected function attach_post_meta_fields() {
		// Register the metabox for managing the source details of the package.
		Container::make( 'post_meta', 'Source Configuration' )
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
				              ->set_help_text( __( 'Composer identifies a certain package (the package name) by its project name and vendor, resulting in a <code>vendor/projectname</code> package name. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#name" target="_blank">here</a>.<br>The vendor and project name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.<br><strong>Provide the whole package name (e.g. <code>wp-media/wp-rocket<code>)!</strong>', 'pixelgradelt_records' ) )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'packagist.org', ],
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => 'IN',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
						              ],
				              ] ),

				         Field::make( 'text', 'package_source_project_name', __( 'Package Source Project Name', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Composer identifies a certain package by its project name and vendor, resulting in a <code>vendor/name</code> identifier. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#name" target="_blank">here</a>.<br>The project name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.<br><strong>Provide only the project name (e.g. <code>akismet</code>), not the whole package name (e.g. <code>wpackagist-plugin/akismet</code>)!</strong>', 'pixelgradelt_records' ) )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'wpackagist.org', ],
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => 'IN',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
						              ],
				              ] ),

				         Field::make( 'text', 'package_source_version_range', __( 'Package Source Version Range', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'A certain source can contain tens or even hundreds of historical versions/releases. <strong>It is wasteful to pull all those in</strong> (and cache them) if we are only interested in the latest major version, for example.<br>
 Specify a version range to <strong>limit the available versions/releases for this package.</strong> Most likely you will only lower-bound your range (e.g. <code>>2.0</code>), but that is up to you.<br>
 Learn more about Composer <a href="https://getcomposer.org/doc/05-repositories.md#repository" target="_blank">versions</a> or <a href="https://semver.mwl.be/?package=madewithlove%2Fhtaccess-cli&constraint=%3C1.2%20%7C%7C%20%3E1.6&stability=stable" target="_blank">play around</a> with version ranges.', 'pixelgradelt_records' ) )
				              ->set_width( 75 )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => 'IN',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
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
								              'value'   => [ 'packagist.org', 'wpackagist.org', 'vcs' ],
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => 'IN',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
						              ],
				              ] ),

				         Field::make( 'text', 'package_vcs_url', __( 'Package VCS URL', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'Just provide the full URL to your VCS repo (e.g. a Github repo URL like <code>https://github.com/pixelgradelt/satispress</code>). Learn more about it <a href="https://getcomposer.org/doc/05-repositories.md#vcs" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->set_conditional_logic( [
						              'relation' => 'AND', // Optional, defaults to "AND"
						              [
								              'field'   => 'package_source_type',
								              'value'   => 'vcs',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => '=',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
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
								              'value'   => 'local.plugin',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => '=',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
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
								              'value'   => 'local.theme',
							              // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
								              'compare' => '=',
							              // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
						              ],
				              ] ),


				         Field::make( 'separator', 'package_details_separator', '' ),
				         Field::make( 'html', 'package_details_html', __( 'Section Description', 'pixelgradelt_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'Configure details about <strong>the package itself,</strong> as it will be exposed for consumption.<br><strong>Leave empty</strong> and we will try to figure them out on save; after that you can modify them however you like.', 'pixelgradelt_records' ) ) ),
				         Field::make( 'textarea', 'package_details_description', __( 'Package Description', 'pixelgradelt_records' ) ),
				         Field::make( 'text', 'package_details_homepage', __( 'Package Homepage URL', 'pixelgradelt_records' ) ),
				         Field::make( 'text', 'package_details_license', __( 'Package License', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'The package license in a standard format (e.g. <code>GPL-3.0-or-later</code>). If there are multiple licenses, comma separate them. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#license" target="_blank">here</a>.', 'pixelgradelt_records' ) ),
				         Field::make( 'complex', 'package_details_authors', __( 'Package Authors', 'pixelgradelt_records' ) )
				              ->set_help_text( __( 'The package authors details. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#authors" target="_blank">here</a>.', 'pixelgradelt_records' ) )
				              ->add_fields( array(
						              Field::make( 'text', 'name', __( 'Author Name', 'pixelgradelt_records' ) )->set_required( true )->set_width( 50 ),
						              Field::make( 'text', 'email', __( 'Author Email', 'pixelgradelt_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'homepage', __( 'Author Homepage', 'pixelgradelt_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'role', __( 'Author Role', 'pixelgradelt_records' ) )->set_width( 50 ),
				              ) ),

		         ] );
	}

	protected function add_extra_installed_headers( array $extra_headers ): array {
		$extra_headers = $extra_headers + [ 'License', 'Tags', 'Requires at least', 'Tested up to', 'Requires PHP', 'Stable tag' ];

		return array_unique( $extra_headers );
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

		$package = $this->packages->first_where( [ 'source_name' => $package_data['source_name'], 'type' => $package_data['type'], ] );
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
	 * @since 0.1.0
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
}
