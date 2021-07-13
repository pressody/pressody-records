<?php
/**
 * List Parts screen provider.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\PackageTypes;
use PixelgradeLT\Records\PartManager;
use PixelgradeLT\Records\Utils\ArrayHelpers;

/**
 * List Parts screen provider class.
 *
 * @since 1.0.0
 */
class ListParts extends AbstractHookProvider {

	/**
	 * Part manager.
	 *
	 * @var PartManager
	 */
	protected PartManager $part_manager;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param PartManager $part_manager Parts manager.
	 */
	public function __construct(
		PartManager $part_manager
	) {
		$this->part_manager = $part_manager;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {
		// Assets.
		add_action( 'load-edit.php', [ $this, 'load_screen' ] );

		// Logic.
		// Show a dropdown to filter the posts list by the custom taxonomy.
		$this->add_action( 'restrict_manage_posts', 'output_admin_list_filters' );

		// Add custom columns to post list.
		$this->add_action( 'manage_' . $this->part_manager::PACKAGE_POST_TYPE . '_posts_columns', 'add_custom_columns' );
		$this->add_action( 'manage_' . $this->part_manager::PACKAGE_POST_TYPE . '_posts_custom_column', 'populate_custom_columns', 10, 2);
	}

	/**
	 * Output filters in the All Posts screen.
	 *
	 * @param string $post_type The current post type.
	 */
	protected function output_admin_list_filters( string $post_type ) {
		if ( $this->part_manager::PACKAGE_POST_TYPE !== $post_type ) {
			return;
		}

		$taxonomy = get_taxonomy( $this->part_manager::PACKAGE_TYPE_TAXONOMY );

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

	/**
	 * Set up the screen.
	 *
	 * @since 1.0.0
	 */
	public function load_screen() {
		$screen = get_current_screen();
		if ( $this->part_manager::PACKAGE_POST_TYPE !== $screen->post_type ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'pixelgradelt_records-admin' );
		wp_enqueue_style( 'pixelgradelt_records-admin' );
	}

	protected function add_custom_columns( array $columns ): array {
		$screen = get_current_screen();
		if ( $this->part_manager::PACKAGE_POST_TYPE !== $screen->post_type ) {
			return $columns;
		}

		// Insert after the title columns with dependency details.
		$columns = ArrayHelpers::insertAfterKey( $columns, 'title',
			[
				'package_required_packages' => esc_html__( 'Required Packages', 'pixelgradelt_records' ),
				'package_required_parts' => esc_html__( 'Required Parts', 'pixelgradelt_records' ),
			]
		);

		return $columns;
	}

	protected function populate_custom_columns( string $column, int $post_id ): void {
		if ( ! in_array( $column, [ 'package_required_packages', 'package_required_parts', ] ) ) {
			return;
		}

		$output = '—';

		$part_data = $this->part_manager->get_package_id_data( $post_id );
		if ( 'package_required_packages' === $column && ! empty( $part_data['required_packages'] ) ) {
			$list = [];
			foreach ( $part_data['required_packages'] as $package_details ) {
				$item = $package_details['pseudo_id'] . ':' . $package_details['version_range'];
				if ( 'stable' !== $package_details['stability'] ) {
					$item .= '@' . $package_details['stability'];
				}

				if ( ! empty( $package_details['managed_post_id'] ) ) {
					$item = '<a class="package-list_link" href="' . esc_url( get_edit_post_link( $package_details['managed_post_id'] ) ) . '" title="Edit Required LT Package">' . get_the_title( $package_details['managed_post_id'] ) . ' (' . $item . ')</a>';
				}

				$list[] = $item;
			}

			$output = implode( '<br>' . PHP_EOL, $list );
		}

		if ( 'package_required_parts' === $column && ! empty( $part_data['required_parts'] ) ) {
			$list = [];
			foreach ( $part_data['required_parts'] as $part_details ) {
				$item = $part_details['pseudo_id'] . ':' . $part_details['version_range'];
				if ( 'stable' !== $part_details['stability'] ) {
					$item .= '@' . $part_details['stability'];
				}

				if ( ! empty( $part_details['managed_post_id'] ) ) {
					$item = '<a class="package-list_link" href="' . esc_url( get_edit_post_link( $part_details['managed_post_id'] ) ) . '" title="Edit Required LT Part">' . get_the_title( $part_details['managed_post_id'] ) . ' (' . $item . ')</a>';
				}

				$list[] = $item;
			}

			$output = implode( '<br>' . PHP_EOL, $list );
		}

		echo $output;
	}
}
