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
use PixelgradeLT\Records\PackageType\PackageTypes;
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
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageManager $package_manager Packages manager.
	 */
	public function __construct(
			PackageManager $package_manager
	) {
		$this->package_manager = $package_manager;
	}

	public function register_hooks() {
		/*
		 * HANDLE THE CUSTOM POST TYPE LOGIC.
		 */
		$this->add_action( 'init', 'register_post_type' );

		// We want extra header fields included in the plugin and theme data.
		$this->add_filter( 'extra_plugin_headers', 'add_extra_installed_headers' );
		$this->add_filter( 'extra_theme_headers', 'add_extra_installed_headers' );

		/*
		 * HANDLE THE CUSTOM TAXONOMY LOGIC.
		 */
		$this->add_action( 'init', 'register_taxonomy' );

		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'save_package_type_meta_box' );
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
				'publicly_queryable' => false,
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

	protected function add_extra_installed_headers( array $extra_headers ): array {
		$extra_headers = $extra_headers + [
						'License',
						'Tags',
						'Requires at least',
						'Tested up to',
						'Requires PHP',
						'Stable tag',
				];

		return array_unique( $extra_headers );
	}

	protected function register_taxonomy() {

		register_taxonomy(
				$this->package_manager::PACKAGE_TYPE_TAXONOMY,
				[ $this->package_manager::PACKAGE_POST_TYPE ],
				$this->get_package_type_taxonomy_args()
		);

		// Force the registration of needed terms matching the PACKAGE TYPES.
		foreach ( PackageTypes::DETAILS as $term_slug => $term_details ) {
			if ( ! term_exists( $term_slug, $this->package_manager::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term_details['name'], $this->package_manager::PACKAGE_TYPE_TAXONOMY, [
						'slug'        => $term_slug,
						'description' => $term_details['description'],
				] );
			}
		}

		register_taxonomy(
				$this->package_manager::PACKAGE_KEYWORD_TAXONOMY,
				[ $this->package_manager::PACKAGE_POST_TYPE ],
				$this->get_package_keyword_taxonomy_args()
		);
	}

	public function get_package_type_taxonomy_args(): array {
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
				'hierarchical'       => true,
				'meta_box_cb'        => [ $this, 'package_type_meta_box' ],
				'capabilities'       => [
						'manage_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
						'edit_terms'   => Capabilities::MANAGE_PACKAGE_TYPES,
						'delete_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
						'assign_terms' => 'edit_posts',
				],
		];
	}

	public function get_package_keyword_taxonomy_args(): array {
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
}
