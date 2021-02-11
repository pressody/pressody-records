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

	const PACKAGE_TYPE_TAXONOMY = 'ltpackage_types';

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
		$this->add_action( 'init', 'register_post_type' );
		$this->add_action( 'init', 'register_taxonomy' );
		$this->add_action( 'init', 'register_meta' );
	}

	protected function register_post_type() {
		register_post_type( static::POST_TYPE, $this->get_args() );
	}

	protected function register_meta() {
		register_meta( 'post', 'isbn', array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		) );
	}

	protected function register_taxonomy() {
		$labels = [
			'name'                  => __( 'Types', 'pixelgradelt_records' ),
			'singular_name'         => __( 'Package Type', 'pixelgradelt_records' ),
			'add_new'               => _x( 'Add New', 'PixelgradeLT Package Type', 'pixelgradelt_records' ),
			'add_new_item'          => __( 'Add New Package Type', 'pixelgradelt_records' ),
			'update_item'           => __( 'Update Package Type', 'pixelgradelt_records' ),
			'new_item_name'         => __( 'New Package Type Name', 'pixelgradelt_records' ),
			'edit_item'             => __( 'Edit Package Type', 'pixelgradelt_records' ),
			'all_items'             => __( 'All Package Types', 'pixelgradelt_records' ),
			'search_items'          => __( 'Search Package Types', 'pixelgradelt_records' ),
			'not_found'             => __( 'No package types found.', 'pixelgradelt_records' ),
			'no_terms'              => __( 'No package types.', 'pixelgradelt_records' ),
			'items_list_navigation' => __( 'Package Types list navigation', 'pixelgradelt_records' ),
			'items_list'            => __( 'Package Types list', 'pixelgradelt_records' ),
			'back_to_items'         => __( '&larr; Go to Package Types', 'pixelgradelt_records' ),
		];

		register_taxonomy(
			static::PACKAGE_TYPE_TAXONOMY,
			[ static::POST_TYPE ],
			[
				'labels'       => $labels,
				'show_ui'      => true,
				'show_in_quick_edit' => true,
				'hierarchical' => true,
				'capabilities' => [
					'manage_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
					'edit_terms'   => Capabilities::MANAGE_PACKAGE_TYPES,
					'delete_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
					'assign_terms' => 'edit_posts',
				],
			]
		);

		foreach ( static::PACKAGE_TYPE_TERMS as $term ) {
			if ( ! term_exists( $term['name'], static::PACKAGE_TYPE_TAXONOMY ) ) {
				wp_insert_term( $term['name'], static::PACKAGE_TYPE_TAXONOMY, $term );
			}
		}
	}

	protected function get_args(): array {
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
			'hierarchical'      => false,
			'public'            => false,
			'publicly_queryable' => true,
			'has_archive'        => false,
			'rest_base'         => 'ltpackages',
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'map_meta_cap'       => true,
			'supports'           => [
				'title',
				'revisions',
				'custom-fields',
			],
		];
	}
}
