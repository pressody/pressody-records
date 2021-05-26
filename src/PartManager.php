<?php
/**
 * Part manager.
 *
 * A part is a special kind of package.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

/**
 * Part manager class.
 *
 * Handles the logic related to configuring parts through a CPT.
 *
 * @since 0.9.0
 */
class PartManager extends PackageManager {

	const PACKAGE_POST_TYPE = 'ltpart';
	const PACKAGE_POST_TYPE_PLURAL = 'ltparts';

	const PACKAGE_TYPE_TAXONOMY = 'ltpart_types';
	const PACKAGE_TYPE_TAXONOMY_SINGULAR = 'ltpart_type';

	const PACKAGE_KEYWORD_TAXONOMY = 'ltpart_keywords';
	const PACKAGE_KEYWORD_TAXONOMY_SINGULAR = 'ltpart_keyword';

	/**
	 * @since 0.9.0
	 *
	 * @param array $args Optional. Any provided args will be merged and overwrite default ones.
	 *
	 * @return array
	 */
	public function get_package_post_type_args( array $args = [] ): array {
		$labels = [
			'name'                  => esc_html__( 'LT Parts', 'pixelgradelt_records' ),
			'singular_name'         => esc_html__( 'LT Part', 'pixelgradelt_records' ),
			'menu_name'             => esc_html_x( 'LT Parts', 'Admin Menu text', 'pixelgradelt_records' ),
			'add_new'               => esc_html_x( 'Add New', 'LT Part', 'pixelgradelt_records' ),
			'add_new_item'          => esc_html__( 'Add New LT Part', 'pixelgradelt_records' ),
			'new_item'              => esc_html__( 'New LT Part', 'pixelgradelt_records' ),
			'edit_item'             => esc_html__( 'Edit LT Part', 'pixelgradelt_records' ),
			'view_item'             => esc_html__( 'View LT Part', 'pixelgradelt_records' ),
			'all_items'             => esc_html__( 'All Parts', 'pixelgradelt_records' ),
			'search_items'          => esc_html__( 'Search Parts', 'pixelgradelt_records' ),
			'not_found'             => esc_html__( 'No parts found.', 'pixelgradelt_records' ),
			'not_found_in_trash'    => esc_html__( 'No parts found in Trash.', 'pixelgradelt_records' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this package', 'pixelgradelt_records' ),
			'filter_items_list'     => esc_html__( 'Filter parts list', 'pixelgradelt_records' ),
			'items_list_navigation' => esc_html__( 'Parts list navigation', 'pixelgradelt_records' ),
			'items_list'            => esc_html__( 'LT Parts list', 'pixelgradelt_records' ),
		];

		return array_merge( [
			'labels'             => $labels,
			'description'        => esc_html__( 'Composer (special) packages to be used as the basis for PixelgradeLT solutions offered to PixelgradeLT users.', 'pixelgradelt_records' ),
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'rest_base'          => self::PACKAGE_POST_TYPE_PLURAL,
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
		], $args );
	}

	/**
	 * @since 0.9.0
	 *
	 * @param array $args Optional. Any provided args will be merged and overwrite default ones.
	 *
	 * @return array
	 */
	public function get_package_type_taxonomy_args( array $args = [] ): array {
		$labels = [
			'name'                  => esc_html__( 'Part Types', 'pixelgradelt_records' ),
			'singular_name'         => esc_html__( 'Part Type', 'pixelgradelt_records' ),
			'add_new'               => esc_html_x( 'Add New', 'LT Part Type', 'pixelgradelt_records' ),
			'add_new_item'          => esc_html__( 'Add New Part Type', 'pixelgradelt_records' ),
			'update_item'           => esc_html__( 'Update Part Type', 'pixelgradelt_records' ),
			'new_item_name'         => esc_html__( 'New Part Type Name', 'pixelgradelt_records' ),
			'edit_item'             => esc_html__( 'Edit Part Type', 'pixelgradelt_records' ),
			'all_items'             => esc_html__( 'All Part Types', 'pixelgradelt_records' ),
			'search_items'          => esc_html__( 'Search Part Types', 'pixelgradelt_records' ),
			'parent_item'           => esc_html__( 'Parent Part Type', 'pixelgradelt_records' ),
			'parent_item_colon'     => esc_html__( 'Parent Part Type:', 'pixelgradelt_records' ),
			'not_found'             => esc_html__( 'No part types found.', 'pixelgradelt_records' ),
			'no_terms'              => esc_html__( 'No part types.', 'pixelgradelt_records' ),
			'items_list_navigation' => esc_html__( 'Part Types list navigation', 'pixelgradelt_records' ),
			'items_list'            => esc_html__( 'Part Types list', 'pixelgradelt_records' ),
			'back_to_items'         => esc_html__( '&larr; Go to Part Types', 'pixelgradelt_records' ),
		];

		return array_merge( [
			'labels'             => $labels,
			'show_ui'            => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'hierarchical'       => true,
			'capabilities'       => [
				'manage_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
				'edit_terms'   => Capabilities::MANAGE_PACKAGE_TYPES,
				'delete_terms' => Capabilities::MANAGE_PACKAGE_TYPES,
				'assign_terms' => 'edit_posts',
			],
		], $args );
	}

	/**
	 * @since 0.9.0
	 *
	 * @param array $args Optional. Any provided args will be merged and overwrite default ones.
	 *
	 * @return array
	 */
	public function get_package_keyword_taxonomy_args( array $args = [] ): array {
		$labels = [
			'name'                       => esc_html__( 'Part Keywords', 'pixelgradelt_records' ),
			'singular_name'              => esc_html__( 'Part Keyword', 'pixelgradelt_records' ),
			'add_new'                    => esc_html_x( 'Add New', 'LT Part Keyword', 'pixelgradelt_records' ),
			'add_new_item'               => esc_html__( 'Add New Part Keyword', 'pixelgradelt_records' ),
			'update_item'                => esc_html__( 'Update Part Keyword', 'pixelgradelt_records' ),
			'new_item_name'              => esc_html__( 'New Part Keyword Name', 'pixelgradelt_records' ),
			'edit_item'                  => esc_html__( 'Edit Part Keyword', 'pixelgradelt_records' ),
			'all_items'                  => esc_html__( 'All Part Keywords', 'pixelgradelt_records' ),
			'search_items'               => esc_html__( 'Search Part Keywords', 'pixelgradelt_records' ),
			'not_found'                  => esc_html__( 'No part keywords found.', 'pixelgradelt_records' ),
			'no_terms'                   => esc_html__( 'No part keywords.', 'pixelgradelt_records' ),
			'separate_items_with_commas' => esc_html__( 'Separate keywords with commas.', 'pixelgradelt_records' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used keywords.', 'pixelgradelt_records' ),
			'most_used'                  => esc_html__( 'Most used.', 'pixelgradelt_records' ),
			'items_list_navigation'      => esc_html__( 'Part Keywords list navigation', 'pixelgradelt_records' ),
			'items_list'                 => esc_html__( 'Part Keywords list', 'pixelgradelt_records' ),
			'back_to_items'              => esc_html__( '&larr; Go to Part Keywords', 'pixelgradelt_records' ),
		];

		return array_merge( [
			'labels'             => $labels,
			'show_ui'            => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'hierarchical'       => false,
		], $args );
	}

	/**
	 * Gather all the data about a managed package ID.
	 *
	 * @param int $post_ID The package post ID.
	 *
	 * @return array The package data we have available.
	 */
	public function get_package_id_data( int $post_ID ): array {
		$data = parent::get_package_id_data( $post_ID );

		// Parts have extra data.
		$data['required_parts'] = $this->get_post_package_required_parts( $post_ID );
		$data['replaced_parts'] = $this->get_post_package_replaced_parts( $post_ID );

		return $data;
	}

	/**
	 * Check if a given post ID should be handled by the manager.
	 *
	 * @param int $post_ID
	 *
	 * @return bool
	 */
	protected function check_post_id( int $post_ID ): bool {
		if ( empty( $post_ID ) ) {
			return false;
		}
		$post = get_post( $post_ID );
		// We will include both post types since parts can depend on both other parts and packages.
		if ( empty( $post ) || ! in_array( $post->post_type, [ self::PACKAGE_POST_TYPE, PackageManager::PACKAGE_POST_TYPE ] ) ) {
			return false;
		}

		return true;
	}

	public function get_post_package_required_parts( int $post_ID, string $pseudo_id_delimiter = ' #', string $container_id = '' ): array {
		$required_parts = carbon_get_post_meta( $post_ID, 'package_required_parts', $container_id );
		if ( empty( $required_parts ) || ! is_array( $required_parts ) ) {
			return [];
		}

		// Make sure only the fields we are interested in are left.
		$accepted_keys = array_fill_keys( [ 'pseudo_id', 'version_range', 'stability' ], '' );
		foreach ( $required_parts as $key => $required_part ) {
			$required_parts[ $key ] = array_replace( $accepted_keys, array_intersect_key( $required_part, $accepted_keys ) );

			if ( empty( $required_part['pseudo_id'] ) || false === strpos( $required_part['pseudo_id'], $pseudo_id_delimiter ) ) {
				unset( $required_parts[ $key ] );
				continue;
			}

			// We will now split the pseudo_id in its components (source_name and post_id with the delimiter in between).
			[ $source_name, $post_id ] = explode( $pseudo_id_delimiter, $required_part['pseudo_id'] );
			if ( empty( $post_id ) ) {
				unset( $required_parts[ $key ] );
				continue;
			}

			$required_parts[ $key ]['source_name']     = $source_name;
			$required_parts[ $key ]['managed_post_id'] = intval( $post_id );
		}

		return $required_parts;
	}

	public function set_post_package_required_parts( int $post_ID, array $required_parts, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_required_parts', $required_parts, $container_id );
	}

	public function get_post_package_replaced_parts( int $post_ID, string $pseudo_id_delimiter = ' #', string $container_id = '' ): array {
		$replaced_parts = carbon_get_post_meta( $post_ID, 'package_replaced_parts', $container_id );
		if ( empty( $replaced_parts ) || ! is_array( $replaced_parts ) ) {
			return [];
		}

		// Make sure only the fields we are interested in are left.
		$accepted_keys = array_fill_keys( [ 'pseudo_id', 'version_range', 'stability' ], '' );
		foreach ( $replaced_parts as $key => $replaced_part ) {
			$replaced_parts[ $key ] = array_replace( $accepted_keys, array_intersect_key( $replaced_part, $accepted_keys ) );

			if ( empty( $replaced_part['pseudo_id'] ) || false === strpos( $replaced_part['pseudo_id'], $pseudo_id_delimiter ) ) {
				unset( $replaced_parts[ $key ] );
				continue;
			}

			// We will now split the pseudo_id in its components (source_name and post_id with the delimiter in between).
			[ $source_name, $post_id ] = explode( $pseudo_id_delimiter, $replaced_part['pseudo_id'] );
			if ( empty( $post_id ) ) {
				unset( $replaced_parts[ $key ] );
				continue;
			}

			$replaced_parts[ $key ]['source_name']     = $source_name;
			$replaced_parts[ $key ]['managed_post_id'] = intval( $post_id );
		}

		return $replaced_parts;
	}

	public function set_post_package_replaced_parts( int $post_ID, array $replaced_parts, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_replaced_parts', $replaced_parts, $container_id );
	}
}
