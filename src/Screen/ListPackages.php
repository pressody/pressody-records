<?php
/**
 * List Packages (All Packages) screen provider.
 *
 * @since   0.5.0
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

namespace Pressody\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Repository\PackageRepository;
use Pressody\Records\Utils\ArrayHelpers;

/**
 * List Packages screen provider class.
 *
 * @since 0.5.0
 */
class ListPackages extends AbstractHookProvider {

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Packages repository.
	 *
	 * This is (should be) the repo that holds all the packages that we manage.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $packages;

	/**
	 * Constructor.
	 *
	 * @since 0.5.0
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

	/**
	 * Register hooks.
	 *
	 * @since 0.5.0
	 */
	public function register_hooks() {
		// Assets.
		add_action( 'load-edit.php', [ $this, 'load_screen' ] );

		// Logic.
		// Show a dropdown to filter the posts list by the custom taxonomy.
		$this->add_action( 'restrict_manage_posts', 'output_admin_list_filters' );

		// Add custom columns to post list.
		$this->add_action( 'manage_' . $this->package_manager::PACKAGE_POST_TYPE . '_posts_columns', 'add_custom_columns' );
		$this->add_action( 'manage_' . $this->package_manager::PACKAGE_POST_TYPE . '_posts_custom_column', 'populate_custom_columns', 10, 2 );
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
			'show_option_all' => sprintf( __( 'All %s', 'pressody_records' ), $taxonomy->label ),
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
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.5.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pressody_records-admin' );
		wp_enqueue_style( 'pressody_records-admin' );
	}

	protected function add_custom_columns( array $columns ): array {
		$screen = get_current_screen();
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $screen->post_type ) {
			return $columns;
		}

		// Insert after the title a column for package source details and columns for dependency details.
		$columns = ArrayHelpers::insertAfterKey( $columns, 'title',
			[
				'package_source'            => esc_html__( 'Package Source', 'pressody_records' ),
				'new_releases'              => esc_html__( 'New Releases', 'pressody_records' ),
				'package_required_packages' => esc_html__( 'Required Packages', 'pressody_records' ),
			]
		);

		return $columns;
	}

	protected function populate_custom_columns( string $column, int $post_id ): void {
		if ( ! in_array( $column, [ 'package_source', 'new_releases', 'package_required_packages', ] ) ) {
			return;
		}

		$output = '—';

		$package = $this->packages->first_where( [ 'managed_post_id' => $post_id ] );
		if ( empty( $package ) ) {
			echo $output;

			return;
		}
		$package_data = $this->package_manager->get_package_id_data( $post_id );
		if ( 'package_source' === $column ) {
			// Add details to the title regarding the package configured source.
			if ( ! empty( $package_data ) && ! empty( $package_data['source_type'] ) ) {
				switch ( $package_data['source_type'] ) {
					case 'packagist.org':
						$output = 'Packagist.org - ' . $package_data['source_name'];
						break;
					case 'wpackagist.org':
						$output = 'WPackagist.org - ' . $package_data['source_name'];
						break;
					case 'vcs':
						if ( false !== strpos( $package_data['vcs_url'], 'github.com' ) ) {
							$output = 'GitHub - ';
						} else {
							$output = 'VCS - ';
						}

						$output .= $package_data['source_name'];
						break;
					case 'local.plugin':
						$output = 'Local Plugin - ' . $package_data['slug'];
						break;
					case 'local.theme':
						$output = 'Local Theme - ' . $package_data['slug'];
						break;
					case 'local.manual':
						if ( PackageTypes::THEME === $package_data['type'] ) {
							$output = 'Manual Theme - ' . $package_data['slug'];
						} else {
							$output = 'Manual Plugin - ' . $package_data['slug'];
						}
						break;
					default:
						// Nothing
						break;
				}
			}
		}

		if ( 'new_releases' === $column ) {
			$seen_list = get_post_meta( $post_id, '_pressody_package_seen_releases', true );
			if ( empty( $seen_list ) ) {
				$seen_list = [];
			}

			$current_list = [];
			foreach ( $package->get_releases() as $release ) {
				$current_list[] = $release->get_version();
			}

			uasort(
				$current_list,
				function ( $a, $b ) {
					return version_compare( $b, $a );
				}
			);

			$not_seen_list = array_diff( $current_list, $seen_list );
			if ( ! empty( $not_seen_list ) ) {
				$output = '<span class="bubble wp-ui-highlight">' . implode( '</span><span class="bubble wp-ui-highlight">', $not_seen_list ) . '</span>';
			}
		}

		if ( 'package_required_packages' === $column && ! empty( $package_data['required_packages'] ) ) {
			$list = [];
			foreach ( $package_data['required_packages'] as $package_details ) {
				$item = $package_details['pseudo_id'] . ':' . $package_details['version_range'];
				if ( 'stable' !== $package_details['stability'] ) {
					$item .= '@' . $package_details['stability'];
				}

				if ( ! empty( $package_details['managed_post_id'] ) ) {
					$item = '<a class="package-list_link" href="' . esc_url( get_edit_post_link( $package_details['managed_post_id'] ) ) . '" title="Edit Required PD Package">' . get_the_title( $package_details['managed_post_id'] ) . ' (' . $item . ')</a>';
				}

				$list[] = $item;
			}

			$output = implode( '<br>' . PHP_EOL, $list );
		}

		echo $output;
	}
}
