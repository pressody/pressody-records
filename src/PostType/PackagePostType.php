<?php
/**
 * The Package custom post type.
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

namespace Pressody\Records\PostType;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pixelgrade\WPPostNotes\PostNotes;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\PackageManager;

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
	protected PackageManager $package_manager;

	/**
	 * Post notes functionality.
	 *
	 * @var PostNotes
	 */
	protected PostNotes $post_notes;

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

		$this->post_notes = new PostNotes( $this->package_manager::PACKAGE_POST_TYPE );
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
		$this->add_action( 'init', 'register_package_type_taxonomy', 12 );
		$this->add_action( 'init', 'insert_package_type_taxonomy_terms', 14 );
		$this->add_action( 'init', 'register_package_keyword_taxonomy', 16 );

		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'save_package_type_meta_box' );
	}

	/**
	 * Register the custom package post type as defined by the package manager.
	 *
	 * @since 0.1.0
	 */
	protected function register_post_type() {
		register_post_type( $this->package_manager::PACKAGE_POST_TYPE, $this->package_manager->get_package_post_type_args() );
	}

	/**
	 * Register the taxonomy for the package type as defined by the package manager.
	 *
	 * @since 0.9.0
	 */
	protected function register_package_type_taxonomy() {
		if ( taxonomy_exists( $this->package_manager::PACKAGE_TYPE_TAXONOMY ) ) {
			register_taxonomy_for_object_type(
					$this->package_manager::PACKAGE_TYPE_TAXONOMY,
					$this->package_manager::PACKAGE_POST_TYPE
			);
		} else {
			register_taxonomy(
					$this->package_manager::PACKAGE_TYPE_TAXONOMY,
					[ $this->package_manager::PACKAGE_POST_TYPE ],
					$this->package_manager->get_package_type_taxonomy_args( [
							'meta_box_cb' => [
									$this,
									'package_type_meta_box',
							],
					] )
			);
		}
	}

	/**
	 * Insert the terms for the package type taxonomy defined by the package manager.
	 *
	 * @since 0.9.0
	 */
	protected function insert_package_type_taxonomy_terms() {
		// Force the insertion of needed terms matching the PACKAGE TYPES.
		foreach ( PackageTypes::DETAILS as $term_slug => $term_details ) {
			if ( ! term_exists( $term_slug, $this->package_manager::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term_details['name'], $this->package_manager::PACKAGE_TYPE_TAXONOMY, [
						'slug'        => $term_slug,
						'description' => $term_details['description'],
				] );
			}
		}
	}

	/**
	 * Register the taxonomy for the package keyword as defined by the package manager.
	 *
	 * @since 0.9.0
	 */
	protected function register_package_keyword_taxonomy() {
		if ( taxonomy_exists( $this->package_manager::PACKAGE_KEYWORD_TAXONOMY ) ) {
			register_taxonomy_for_object_type(
					$this->package_manager::PACKAGE_KEYWORD_TAXONOMY,
					$this->package_manager::PACKAGE_POST_TYPE
			);
		} else {
			register_taxonomy(
					$this->package_manager::PACKAGE_KEYWORD_TAXONOMY,
					[ $this->package_manager::PACKAGE_POST_TYPE ],
					$this->package_manager->get_package_keyword_taxonomy_args()
			);
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
		$current_package_type = '';

		if ( ! is_wp_error( $package_type ) ) {
			if ( isset( $package_type[0] ) && isset( $package_type[0]->slug ) ) {
				$current_package_type = $package_type[0]->slug;
			}
		}

		foreach ( $terms as $term ) { ?>
			<label title="<?php esc_attr_e( $term->name ); ?>">
				<input type="radio"
				       name="<?php esc_attr_e( $this->package_manager::PACKAGE_TYPE_TAXONOMY_SINGULAR ); ?>"
				       value="<?php esc_attr_e( $term->slug ); ?>" <?php checked( $term->slug, $current_package_type ); ?>>
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

		// A valid type is required, so don't let this get published without one
		if ( empty( $package_type ) ) {
			// unhook this function so it doesn't loop infinitely
			$this->remove_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'save_package_type_meta_box' );

			$postdata = array(
					'ID'          => $post_id,
					'post_status' => 'draft',
			);
			wp_update_post( $postdata );
		} else {
			$term = get_term_by( 'slug', $package_type, $this->package_manager::PACKAGE_TYPE_TAXONOMY );
			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, $term->term_id, $this->package_manager::PACKAGE_TYPE_TAXONOMY, false );
			}
		}
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
}
