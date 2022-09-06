<?php
/**
 * Part manager.
 *
 * A part is a special kind of package.
 *
 * @since   0.9.0
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

namespace Pressody\Records;

/**
 * Part manager class.
 *
 * Handles the logic related to configuring parts through a CPT.
 *
 * @since 0.9.0
 */
class PartManager extends PackageManager {

	const PACKAGE_POST_TYPE = 'pdpart';
	const PACKAGE_POST_TYPE_PLURAL = 'pdparts';

	const PACKAGE_TYPE_TAXONOMY = 'pdpart_types';
	const PACKAGE_TYPE_TAXONOMY_SINGULAR = 'pdpart_type';

	const PACKAGE_KEYWORD_TAXONOMY = 'pdpart_keywords';
	const PACKAGE_KEYWORD_TAXONOMY_SINGULAR = 'pdpart_keyword';

	/**
	 * Used to create the pseudo IDs saved as values for a part's required packages/parts.
	 * Don't change this without upgrading the data in the DB!
	 */
	const PSEUDO_ID_DELIMITER = ' #';

	/**
	 * @since 0.9.0
	 *
	 * @param array $args Optional. Any provided args will be merged and overwrite default ones.
	 *
	 * @return array
	 */
	public function get_package_post_type_args( array $args = [] ): array {
		$labels = [
			'name'                  => esc_html__( 'PD Parts', 'pressody_records' ),
			'singular_name'         => esc_html__( 'PD Part', 'pressody_records' ),
			'menu_name'             => esc_html_x( 'PD Parts', 'Admin Menu text', 'pressody_records' ),
			'add_new'               => esc_html_x( 'Add New', 'PD Part', 'pressody_records' ),
			'add_new_item'          => esc_html__( 'Add New PD Part', 'pressody_records' ),
			'new_item'              => esc_html__( 'New PD Part', 'pressody_records' ),
			'edit_item'             => esc_html__( 'Edit PD Part', 'pressody_records' ),
			'view_item'             => esc_html__( 'View PD Part', 'pressody_records' ),
			'all_items'             => esc_html__( 'All Parts', 'pressody_records' ),
			'search_items'          => esc_html__( 'Search Parts', 'pressody_records' ),
			'not_found'             => esc_html__( 'No parts found.', 'pressody_records' ),
			'not_found_in_trash'    => esc_html__( 'No parts found in Trash.', 'pressody_records' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this package', 'pressody_records' ),
			'filter_items_list'     => esc_html__( 'Filter parts list', 'pressody_records' ),
			'items_list_navigation' => esc_html__( 'Parts list navigation', 'pressody_records' ),
			'items_list'            => esc_html__( 'PD Parts list', 'pressody_records' ),
		];

		return array_merge( [
			'labels'             => $labels,
			'description'        => esc_html__( 'Composer (special) packages to be used as the basis for Pressody solutions offered to Pressody users.', 'pressody_records' ),
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'rest_base'          => self::PACKAGE_POST_TYPE_PLURAL,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-media-audio',
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
			'name'                  => esc_html__( 'Part Types', 'pressody_records' ),
			'singular_name'         => esc_html__( 'Part Type', 'pressody_records' ),
			'add_new'               => esc_html_x( 'Add New', 'PD Part Type', 'pressody_records' ),
			'add_new_item'          => esc_html__( 'Add New Part Type', 'pressody_records' ),
			'update_item'           => esc_html__( 'Update Part Type', 'pressody_records' ),
			'new_item_name'         => esc_html__( 'New Part Type Name', 'pressody_records' ),
			'edit_item'             => esc_html__( 'Edit Part Type', 'pressody_records' ),
			'all_items'             => esc_html__( 'All Part Types', 'pressody_records' ),
			'search_items'          => esc_html__( 'Search Part Types', 'pressody_records' ),
			'parent_item'           => esc_html__( 'Parent Part Type', 'pressody_records' ),
			'parent_item_colon'     => esc_html__( 'Parent Part Type:', 'pressody_records' ),
			'not_found'             => esc_html__( 'No part types found.', 'pressody_records' ),
			'no_terms'              => esc_html__( 'No part types.', 'pressody_records' ),
			'items_list_navigation' => esc_html__( 'Part Types list navigation', 'pressody_records' ),
			'items_list'            => esc_html__( 'Part Types list', 'pressody_records' ),
			'back_to_items'         => esc_html__( '&larr; Go to Part Types', 'pressody_records' ),
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
			'name'                       => esc_html__( 'Part Keywords', 'pressody_records' ),
			'singular_name'              => esc_html__( 'Part Keyword', 'pressody_records' ),
			'add_new'                    => esc_html_x( 'Add New', 'PD Part Keyword', 'pressody_records' ),
			'add_new_item'               => esc_html__( 'Add New Part Keyword', 'pressody_records' ),
			'update_item'                => esc_html__( 'Update Part Keyword', 'pressody_records' ),
			'new_item_name'              => esc_html__( 'New Part Keyword Name', 'pressody_records' ),
			'edit_item'                  => esc_html__( 'Edit Part Keyword', 'pressody_records' ),
			'all_items'                  => esc_html__( 'All Part Keywords', 'pressody_records' ),
			'search_items'               => esc_html__( 'Search Part Keywords', 'pressody_records' ),
			'not_found'                  => esc_html__( 'No part keywords found.', 'pressody_records' ),
			'no_terms'                   => esc_html__( 'No part keywords.', 'pressody_records' ),
			'separate_items_with_commas' => esc_html__( 'Separate keywords with commas.', 'pressody_records' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used keywords.', 'pressody_records' ),
			'most_used'                  => esc_html__( 'Most used.', 'pressody_records' ),
			'items_list_navigation'      => esc_html__( 'Part Keywords list navigation', 'pressody_records' ),
			'items_list'                 => esc_html__( 'Part Keywords list', 'pressody_records' ),
			'back_to_items'              => esc_html__( '&larr; Go to Part Keywords', 'pressody_records' ),
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

	public function get_post_package_required_parts( int $post_ID, string $container_id = '', string $pseudo_id_delimiter = '' ): array {
		$required_parts = carbon_get_post_meta( $post_ID, 'package_required_parts', $container_id );
		if ( empty( $required_parts ) || ! is_array( $required_parts ) ) {
			return [];
		}

		if ( empty( $pseudo_id_delimiter ) ) {
			$pseudo_id_delimiter = self::PSEUDO_ID_DELIMITER;
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

	public function get_post_package_replaced_parts( int $post_ID, string $container_id = '', string $pseudo_id_delimiter = '' ): array {
		$replaced_parts = carbon_get_post_meta( $post_ID, 'package_replaced_parts', $container_id );
		if ( empty( $replaced_parts ) || ! is_array( $replaced_parts ) ) {
			return [];
		}

		if ( empty( $pseudo_id_delimiter ) ) {
			$pseudo_id_delimiter = self::PSEUDO_ID_DELIMITER;
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
