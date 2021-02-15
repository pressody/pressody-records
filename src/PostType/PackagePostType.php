<?php
/**
 * The Package custom post type.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PostType;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Capabilities;

/**
 * The Package custom post type provider: handle the information about each managed package.
 *
 * @since 0.1.0
 */
class PackagePostType extends AbstractHookProvider {
	const POST_TYPE = 'ltpackage';
	const POST_TYPE_PLURAL = 'ltpackages';

	const PACKAGE_TYPE_TAXONOMY = 'ltpackage_types';
	const PACKAGE_TYPE_TAXONOMY_SINGULAR = 'ltpackage_type';

	// We will automatically register these if they are not present.
	// The important part is the slug that must match the package types defined by composer/installers
	// @see https://packagist.org/packages/composer/installers
	const PACKAGE_TYPE_TERMS = [
		[
			'name'        => 'WordPress Plugin',
			'slug'        => 'wordpress-plugin',
			'description' => 'A WordPress plugin package.',
		],
		[
			'name'        => 'WordPress Theme',
			'slug'        => 'wordpress-theme',
			'description' => 'A WordPress theme package.',
		],
		[
			'name'        => 'WordPress Must-Use Plugin',
			'slug'        => 'wordpress-muplugin',
			'description' => 'A WordPress Must-Use plugin package.',
		],
		[
			'name'        => 'WordPress Drop-in Plugin',
			'slug'        => 'wordpress-dropin',
			'description' => 'A WordPress Drop-in plugin package.',
		],
	];

	public function register_hooks() {
		/*
		 * HANDLE THE CUSTOM POST TYPE LOGIC.
		 */
		$this->add_action( 'init', 'register_post_type' );
		// Make sure that the post has a title.
		$this->add_action( 'save_post_' . static::POST_TYPE, 'prevent_post_save_without_title' );
		// Change the post title placeholder.
		$this->add_filter( 'enter_title_here', 'change_title_placeholder', 10, 2 );
		// Add a description to the slug
		$this->add_filter( 'editable_slug', 'add_post_slug_description' );
		// Make sure that the slug and other metaboxes are never hidden.
		$this->add_filter( 'hidden_meta_boxes', 'prevent_hidden_metaboxes', 10, 2 );
		// Rearrange the core metaboxes.
		$this->add_action( 'add_meta_boxes_' . static::POST_TYPE, 'adjust_core_metaboxes', 99 );

		$this->add_action( 'init', 'register_meta' );

		/*
		 * HANDLE THE CUSTOM TAXONOMY LOGIC.
		 */
		$this->add_action( 'init', 'register_taxonomy' );
		// Handle the custom taxonomy logic on the post edit screen.
		$this->add_action( 'save_post_' . static::POST_TYPE, 'save_package_type_meta_box' );
		// Show a dropdown to filter the posts list by the custom taxonomy.
		$this->add_action( 'restrict_manage_posts', 'output_admin_list_filters' );

		$this->add_action( 'edit_form_top', 'show_post_error_msgs' );
	}

	protected function register_post_type() {
		register_post_type( static::POST_TYPE, $this->get_post_type_args() );
	}

	protected function register_meta() {
		register_meta( 'post', 'isbn', array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		) );
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
			'rest_base'          => static::POST_TYPE_PLURAL,
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
			static::PACKAGE_TYPE_TAXONOMY,
			[ static::POST_TYPE ],
			$this->get_taxonomy_args()
		);

		foreach ( static::PACKAGE_TYPE_TERMS as $term ) {
			if ( ! term_exists( $term['name'], static::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term['name'], static::PACKAGE_TYPE_TAXONOMY, $term );
			}
		}
	}

	protected function get_taxonomy_args(): array {
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
			$this->remove_action( 'save_post_' . static::POST_TYPE, 'prevent_post_save_without_title' );

			$postdata = array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			);
			wp_update_post( $postdata );

			// This way we avoid the "published" admin message.
			unset( $_POST['publish'] );
		}
	}

	protected function change_title_placeholder( string $placeholder, $post ) {
		if ( static::POST_TYPE !== get_post_type( $post ) || 'auto-draft' === get_post_status( $post ) ) {
			return $placeholder;
		}

		return esc_html__( 'Add package title', 'pixelgradelt_records' );
	}

	protected function add_post_slug_description() {
		// Just output it since there is no way to add it other way. ?>
		<p class="description">
			<?php _e( '<strong>The slug is the PACKAGE NAME,</strong> excluding the vendor name. It is best to use <strong>the exact plugin or theme slug!</strong><br>In the end this will be prefixed with the vendor name (like so: <code>vendor/slug</code>) to form the identifier to be used in composer.json.', 'pixelgradelt_records' ); ?>
		</p>
		<style>
			input#post_name {
				width: 25%;
			}
		</style>
		<?php
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
			 static::POST_TYPE === $screen->id &&
		     ! empty( $screen->post_type ) &&
		     static::POST_TYPE === $screen->post_type
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

		if ( empty( $wp_meta_boxes[ static::POST_TYPE ] ) ) {
			return;
		}

		// We will move the slug metabox at the very top.
		if ( ! empty( $wp_meta_boxes[ static::POST_TYPE ]['normal']['core']['slugdiv'] ) ) {
			$tmp = $wp_meta_boxes[ static::POST_TYPE ]['normal']['core']['slugdiv'];
			unset( $wp_meta_boxes[ static::POST_TYPE ]['normal']['core']['slugdiv'] );

			$wp_meta_boxes[ static::POST_TYPE ]['normal']['core'] = [ 'slugdiv' => $tmp ] + $wp_meta_boxes[ static::POST_TYPE ]['normal']['core'];
		}

		// Since we are here, modify the package type title to be singular, rather than plural.
		if ( ! empty( $wp_meta_boxes[ static::POST_TYPE ]['side']['core']['tagsdiv-ltpackage_types'] ) ) {
			$wp_meta_boxes[ static::POST_TYPE ]['side']['core']['tagsdiv-ltpackage_types'] = esc_html__( 'Package Type', 'pixelgradelt_records' );
		}
	}

	/**
	 * Display Package Type meta box
	 *
	 * @param \WP_Post $post
	 */
	public function package_type_meta_box( \WP_Post $post ) {
		$terms = get_terms( static::PACKAGE_TYPE_TAXONOMY, array( 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );

		$package_type = wp_get_object_terms( $post->ID, static::PACKAGE_TYPE_TAXONOMY, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		$package_type_name   = '';

		if ( ! is_wp_error( $package_type ) ) {
			if ( isset( $package_type[0] ) && isset( $package_type[0]->name ) ) {
				$package_type_name = $package_type[0]->name;
			}
		}

		foreach ( $terms as $term ) { ?>
			<label title="<?php esc_attr_e( $term->name ); ?>">
				<input type="radio" name="<?php esc_attr_e( static::PACKAGE_TYPE_TAXONOMY_SINGULAR ); ?>"
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

		$package_type = isset( $_POST[ static::PACKAGE_TYPE_TAXONOMY_SINGULAR ] ) ? sanitize_text_field( $_POST[ static::PACKAGE_TYPE_TAXONOMY_SINGULAR ] ) : '';

		// A valid rating is required, so don't let this get published without one
		if ( empty( $package_type ) ) {
			// unhook this function so it doesn't loop infinitely
			$this->remove_action( 'save_post_' . static::POST_TYPE, 'save_package_type_meta_box' );

			$postdata = array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			);
			wp_update_post( $postdata );
		} else {
			$term = get_term_by( 'name', $package_type, static::PACKAGE_TYPE_TAXONOMY );
			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, $term->term_id, static::PACKAGE_TYPE_TAXONOMY, false );
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
		if ( static::POST_TYPE !== get_post_type( $post ) || 'auto-draft' === get_post_status( $post ) ) {
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
		$package_type = wp_get_object_terms( $post->ID, static::PACKAGE_TYPE_TAXONOMY, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		if ( is_wp_error( $package_type ) || empty( $package_type ) ) {
			$taxonomy_args = $this->get_taxonomy_args();
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
		if ( static::POST_TYPE !== $post_type ) {
			return;
		}

		$taxonomy = get_taxonomy( static::PACKAGE_TYPE_TAXONOMY );

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
}
