<?php
/**
 * List Parts screen provider.
 *
 * @since   1.0.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\PartManager;
use Pressody\Records\Repository\PackageRepository;
use Pressody\Records\Utils\ArrayHelpers;

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
	 * @since 1.0.0
	 *
	 * @param PartManager       $part_manager Parts manager.
	 * @param PackageRepository $packages     Packages repository.
	 */
	public function __construct(
		PartManager $part_manager,
		PackageRepository $packages
	) {
		$this->part_manager = $part_manager;
		$this->packages     = $packages;
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
		$this->add_action( 'manage_' . $this->part_manager::PACKAGE_POST_TYPE . '_posts_custom_column', 'populate_custom_columns', 10, 2 );
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
		wp_enqueue_script( 'pressody_records-admin' );
		wp_enqueue_style( 'pressody_records-admin' );
	}

	protected function add_custom_columns( array $columns ): array {
		$screen = get_current_screen();
		if ( $this->part_manager::PACKAGE_POST_TYPE !== $screen->post_type ) {
			return $columns;
		}

		// Insert after the title columns with dependency details.
		$columns = ArrayHelpers::insertAfterKey( $columns, 'title',
			[
				'package_source'            => esc_html__( 'Part Plugin Source', 'pressody_records' ),
				'new_releases'              => esc_html__( 'New Releases', 'pressody_records' ),
				'package_required_packages' => esc_html__( 'Required Packages', 'pressody_records' ),
				'package_required_parts'    => esc_html__( 'Required Parts', 'pressody_records' ),
			]
		);

		return $columns;
	}

	protected function populate_custom_columns( string $column, int $post_id ): void {
		if ( ! in_array( $column, [
			'package_source',
			'new_releases',
			'package_required_packages',
			'package_required_parts',
		] ) ) {
			return;
		}

		$output = '—';

		$package = $this->packages->first_where( [ 'managed_post_id' => $post_id ] );
		if ( empty( $package ) ) {
			echo $output;

			return;
		}

		$part_data = $this->part_manager->get_package_id_data( $post_id );
		if ( 'package_source' === $column ) {
			// Add details to the title regarding the package configured source.
			if ( ! empty( $part_data ) && ! empty( $part_data['source_type'] ) ) {
				switch ( $part_data['source_type'] ) {
					case 'packagist.org':
						$output = 'Packagist.org - ' . $part_data['source_name'];
						break;
					case 'wpackagist.org':
						$output = 'WPackagist.org - ' . $part_data['source_name'];
						break;
					case 'vcs':
						if ( false !== strpos( $part_data['vcs_url'], 'github.com' ) ) {
							$output = 'Github - ';
						} else {
							$output = 'VCS - ';
						}

						$output .= $part_data['source_name'];
						break;
					case 'local.plugin':
						$output = 'Local Plugin - ' . $part_data['slug'];
						break;
					case 'local.theme':
						$output = 'Local Theme - ' . $part_data['slug'];
						break;
					case 'local.manual':
						if ( PackageTypes::THEME === $part_data['type'] ) {
							$output = 'Manual Theme - ' . $part_data['slug'];
						} else {
							$output = 'Manual Plugin - ' . $part_data['slug'];
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

		if ( 'package_required_packages' === $column && ! empty( $part_data['required_packages'] ) ) {
			$list = [];
			foreach ( $part_data['required_packages'] as $package_details ) {
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

		if ( 'package_required_parts' === $column && ! empty( $part_data['required_parts'] ) ) {
			$list = [];
			foreach ( $part_data['required_parts'] as $part_details ) {
				$item = $part_details['pseudo_id'] . ':' . $part_details['version_range'];
				if ( 'stable' !== $part_details['stability'] ) {
					$item .= '@' . $part_details['stability'];
				}

				if ( ! empty( $part_details['managed_post_id'] ) ) {
					$item = '<a class="package-list_link" href="' . esc_url( get_edit_post_link( $part_details['managed_post_id'] ) ) . '" title="Edit Required PD Part">' . get_the_title( $part_details['managed_post_id'] ) . ' (' . $item . ')</a>';
				}

				$list[] = $item;
			}

			$output = implode( '<br>' . PHP_EOL, $list );
		}

		echo $output;
	}
}
