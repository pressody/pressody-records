<?php
/**
 * Package manager.
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

namespace Pressody\Records;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Env\Env;
use Pressody\Records\Authentication\ApiKey\Server;
use Pressody\Records\Client\ComposerClient;
use Pressody\Records\PackageType\PackageTypes;
use Pressody\Records\Queue\QueueInterface;
use Psr\Log\LoggerInterface;

/**
 * Package manager class.
 *
 * Handles the logic related to configuring packages through a CPT.
 *
 * @since 0.1.0
 */
class PackageManager extends AbstractHookProvider {

	const PACKAGE_POST_TYPE = 'pdpackage';
	const PACKAGE_POST_TYPE_PLURAL = 'pdpackages';

	const PACKAGE_TYPE_TAXONOMY = 'pdpackage_types';
	const PACKAGE_TYPE_TAXONOMY_SINGULAR = 'pdpackage_type';

	const PACKAGE_KEYWORD_TAXONOMY = 'pdpackage_keywords';
	const PACKAGE_KEYWORD_TAXONOMY_SINGULAR = 'pdpackage_keyword';

	/**
	 * Used to create the pseudo IDs saved as values for a package's required packages.
	 * Don't change this without upgrading the data in the DB!
	 */
	const PSEUDO_ID_DELIMITER = ' #';

	/**
	 * External Composer repository client.
	 *
	 * @var ComposerClient
	 */
	protected ComposerClient $composer_client;

	/**
	 * Composer version parser.
	 *
	 * @var ComposerVersionParser
	 */
	protected ComposerVersionParser $composer_version_parser;

	/**
	 * WordPress readme parser.
	 *
	 * @var WordPressReadmeParser
	 */
	protected WordPressReadmeParser $wordpress_readme_parser;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Hasher.
	 *
	 * @var HasherInterface
	 */
	protected HasherInterface $hasher;

	/**
	 * Queue.
	 *
	 * @since 0.15.0
	 *
	 * @var QueueInterface
	 */
	protected QueueInterface $queue;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param ComposerClient        $composer_client
	 * @param ComposerVersionParser $composer_version_parser
	 * @param WordPressReadmeParser $wordpress_readme_parser
	 * @param LoggerInterface       $logger Logger.
	 * @param HasherInterface       $hasher Hasher.
	 * @param QueueInterface        $queue  Queue.
	 */
	public function __construct(
		ComposerClient $composer_client,
		ComposerVersionParser $composer_version_parser,
		WordPressReadmeParser $wordpress_readme_parser,
		LoggerInterface $logger,
		HasherInterface $hasher,
		QueueInterface $queue
	) {

		$this->composer_client         = $composer_client;
		$this->composer_version_parser = $composer_version_parser;
		$this->wordpress_readme_parser = $wordpress_readme_parser;
		$this->logger                  = $logger;
		$this->hasher                  = $hasher;
		$this->queue                   = $queue;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.15.0
	 */
	public function register_hooks() {
		$this->add_action( 'init', 'schedule_recurring_events' );

		// We want to refresh external source packages twice a day: at midday (noon) and midnight.
		add_action( 'pressody_records/midnight', [ $this, 'schedule_external_packages_refresh' ] );
		add_action( 'pressody_records/midday', [ $this, 'schedule_external_packages_refresh' ] );

		// Hook the actual logic to refresh external source packages.
		add_action( 'pressody_records/external_package_refresh', [ $this, 'post_fetch_external_packages' ], 10, 1 );
	}

	/**
	 * Maybe schedule the recurring actions/events, if it is not already scheduled.
	 *
	 * @since 0.15.0
	 */
	protected function schedule_recurring_events() {
		if ( ! $this->queue->get_next( 'pressody_records/midnight' ) ) {
			$this->queue->schedule_recurring( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'pressody_records/midnight', [], 'plt_rec' );
		}

		if ( ! $this->queue->get_next( 'pressody_records/midday' ) ) {
			$this->queue->schedule_recurring( strtotime( 'tomorrow' ) + 12 * HOUR_IN_SECONDS, DAY_IN_SECONDS, 'pressody_records/midday', [], 'plt_rec' );
		}

		if ( ! $this->queue->get_next( 'pressody_records/hourly' ) ) {
			$this->queue->schedule_recurring( (int) floor( ( time() + HOUR_IN_SECONDS ) / HOUR_IN_SECONDS ), HOUR_IN_SECONDS, 'pressody_records/hourly', [], 'plt_rec' );
		}
	}

	/**
	 * Schedule one-time tasks to refresh each package with external sources.
	 *
	 * Rather than doing all the processing synchronously,
	 * we favor this async approach to avoid timeout or single points of failure.
	 *
	 * @since 0.15.0
	 */
	public function schedule_external_packages_refresh() {
		// Get all package post IDs with external sources.
		$post_ids = $this->get_package_ids_by( [
			'package_source_type' => [
				'packagist.org',
				'wpackagist.org',
				'vcs',
			],
		] );

		foreach ( $post_ids as $post_id ) {
			// Schedule 30 seconds into the future.
			$this->queue->schedule_single(
				time() + 30,
				'pressody_records/external_package_refresh',
				[ 'post_id' => $post_id ],
				'plt_rec_package_refresh'
			);
		}
	}

	/**
	 * Attempt to fetch external packages and save them in the post meta to be used for release caching and such.
	 *
	 * @since 0.15.0
	 *
	 * @param int $post_id
	 */
	public function post_fetch_external_packages( int $post_id ) {

		$packages = $this->fetch_external_package_remote_releases( $post_id );

		// We will save the packages (these are actually releases considering we tackle a single package) in the database.
		// For actually caching the zips, we will rely on Pressody\Records\PackageType\Builder\PackageBuilder::build() to do the work.
		if ( ! empty( $packages ) ) {
			update_post_meta( $post_id, '_package_source_cached_release_packages', $packages );

			$this->logger->info(
				'Updated the source cached release packages for "{package_name}" (post ID #{post_id}).',
				[
					'package_name' => $this->get_post_package_name( $post_id ),
					'post_id' => $post_id,
					'logCategory' => 'package_manager',
				]
			);
		}
	}

	/**
	 * @since 0.9.0
	 *
	 * @param array $args Optional. Any provided args will be merged and overwrite default ones.
	 *
	 * @return array
	 */
	public function get_package_post_type_args( array $args = [] ): array {
		$labels = [
			'name'                  => esc_html__( 'PD Packages', 'pressody_records' ),
			'singular_name'         => esc_html__( 'PD Package', 'pressody_records' ),
			'menu_name'             => esc_html_x( 'PD Packages', 'Admin Menu text', 'pressody_records' ),
			'add_new'               => esc_html_x( 'Add New', 'PD Package', 'pressody_records' ),
			'add_new_item'          => esc_html__( 'Add New PD Package', 'pressody_records' ),
			'new_item'              => esc_html__( 'New PD Package', 'pressody_records' ),
			'edit_item'             => esc_html__( 'Edit PD Package', 'pressody_records' ),
			'view_item'             => esc_html__( 'View PD Package', 'pressody_records' ),
			'all_items'             => esc_html__( 'All Packages', 'pressody_records' ),
			'search_items'          => esc_html__( 'Search Packages', 'pressody_records' ),
			'not_found'             => esc_html__( 'No packages found.', 'pressody_records' ),
			'not_found_in_trash'    => esc_html__( 'No packages found in Trash.', 'pressody_records' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this package', 'pressody_records' ),
			'filter_items_list'     => esc_html__( 'Filter packages list', 'pressody_records' ),
			'items_list_navigation' => esc_html__( 'Packages list navigation', 'pressody_records' ),
			'items_list'            => esc_html__( 'PD Packages list', 'pressody_records' ),
		];

		return array_merge( [
			'labels'             => $labels,
			'description'        => esc_html__( 'Composer packages to be used in the Pressody parts delivered to Pressody users.', 'pressody_records' ),
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'rest_base'          => static::PACKAGE_POST_TYPE_PLURAL,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-format-audio',
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
			'name'                  => esc_html__( 'Package Types', 'pressody_records' ),
			'singular_name'         => esc_html__( 'Package Type', 'pressody_records' ),
			'add_new'               => esc_html_x( 'Add New', 'PD Package Type', 'pressody_records' ),
			'add_new_item'          => esc_html__( 'Add New Package Type', 'pressody_records' ),
			'update_item'           => esc_html__( 'Update Package Type', 'pressody_records' ),
			'new_item_name'         => esc_html__( 'New Package Type Name', 'pressody_records' ),
			'edit_item'             => esc_html__( 'Edit Package Type', 'pressody_records' ),
			'all_items'             => esc_html__( 'All Package Types', 'pressody_records' ),
			'search_items'          => esc_html__( 'Search Package Types', 'pressody_records' ),
			'parent_item'           => esc_html__( 'Parent Package Type', 'pressody_records' ),
			'parent_item_colon'     => esc_html__( 'Parent Package Type:', 'pressody_records' ),
			'not_found'             => esc_html__( 'No package types found.', 'pressody_records' ),
			'no_terms'              => esc_html__( 'No package types.', 'pressody_records' ),
			'items_list_navigation' => esc_html__( 'Package Types list navigation', 'pressody_records' ),
			'items_list'            => esc_html__( 'Package Types list', 'pressody_records' ),
			'back_to_items'         => esc_html__( '&larr; Go to Package Types', 'pressody_records' ),
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
			'name'                       => esc_html__( 'Package Keywords', 'pressody_records' ),
			'singular_name'              => esc_html__( 'Package Keyword', 'pressody_records' ),
			'add_new'                    => esc_html_x( 'Add New', 'PD Package Keyword', 'pressody_records' ),
			'add_new_item'               => esc_html__( 'Add New Package Keyword', 'pressody_records' ),
			'update_item'                => esc_html__( 'Update Package Keyword', 'pressody_records' ),
			'new_item_name'              => esc_html__( 'New Package Keyword Name', 'pressody_records' ),
			'edit_item'                  => esc_html__( 'Edit Package Keyword', 'pressody_records' ),
			'all_items'                  => esc_html__( 'All Package Keywords', 'pressody_records' ),
			'search_items'               => esc_html__( 'Search Package Keywords', 'pressody_records' ),
			'not_found'                  => esc_html__( 'No package keywords found.', 'pressody_records' ),
			'no_terms'                   => esc_html__( 'No package keywords.', 'pressody_records' ),
			'separate_items_with_commas' => esc_html__( 'Separate keywords with commas.', 'pressody_records' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used keywords.', 'pressody_records' ),
			'most_used'                  => esc_html__( 'Most used.', 'pressody_records' ),
			'items_list_navigation'      => esc_html__( 'Package Keywords list navigation', 'pressody_records' ),
			'items_list'                 => esc_html__( 'Package Keywords list', 'pressody_records' ),
			'back_to_items'              => esc_html__( '&larr; Go to Package Keywords', 'pressody_records' ),
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
	 * Get post ids for managed packages by type.
	 *
	 * @param string $types Optional. Package types. Default is to query for all package types.
	 *
	 * @return int[] Package post ids.
	 */
	public function get_package_type_ids( string $types = 'all' ): array {

		return $this->get_package_ids_by( [
			'package_type' => $types,
		] );
	}

	/**
	 * Identify packages post IDs based on certain details.
	 *
	 * @param array $args Array of package details to look for.
	 *
	 * @return int[] The package post IDs list.
	 */
	public function get_package_ids_by( array $args ): array {
		$query_args = [
			'post_type'        => static::PACKAGE_POST_TYPE,
			'fields'           => 'ids',
			'post_status'      => [ 'publish', 'draft', 'private', ],
			'tax_query'        => [],
			'meta_query'       => [],
			'nopaging'         => true,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		];

		// This allows us to query for specific package post types.
		if ( ! empty( $args['post_type'] ) ) {
			$query_args['post_type'] = $args['post_type'];
		}

		if ( ! empty( $args['package_type'] ) && 'all' !== $args['package_type'] ) {
			if ( is_string( $args['package_type'] ) ) {
				$args['package_type'] = [ $args['package_type'] ];
			}

			$args['package_type'] = array_filter( array_values( $args['package_type'] ), 'is_string' );

			$query_args['tax_query'][] = [
				'taxonomy' => static::PACKAGE_TYPE_TAXONOMY,
				'field'    => 'slug',
				'terms'    => $args['package_type'],
				'operator' => 'IN',
			];
		}

		if ( ! empty( $args['post_ids'] ) ) {
			if ( ! is_array( $args['post_ids'] ) ) {
				$args['post_ids'] = [ intval( $args['post_ids'] ) ];
			}

			$query_args['post__in'] = $args['post_ids'];
		}

		if ( ! empty( $args['exclude_post_ids'] ) ) {
			if ( ! is_array( $args['exclude_post_ids'] ) ) {
				$args['exclude_post_ids'] = [ intval( $args['exclude_post_ids'] ) ];
			}

			$query_args['post__not_in'] = $args['exclude_post_ids'];
		}

		if ( ! empty( $args['slug'] ) ) {
			if ( is_string( $args['slug'] ) ) {
				$args['slug'] = [ $args['slug'] ];
			}

			$query_args['post_name__in'] = $args['slug'];
		}

		if ( ! empty( $args['post_status'] ) ) {
			$query_args['post_status'] = $args['post_status'];
		}

		if ( ! empty( $args['local_plugin_file'] ) ) {
			if ( is_string( $args['local_plugin_file'] ) ) {
				$args['local_plugin_file'] = [ $args['local_plugin_file'] ];
			}

			$query_args['meta_query'][] = [
				'key'     => '_package_local_plugin_file',
				'value'   => $args['local_plugin_file'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['local_theme_slug'] ) ) {
			if ( is_string( $args['local_theme_slug'] ) ) {
				$args['local_theme_slug'] = [ $args['local_theme_slug'] ];
			}

			$query_args['meta_query'][] = [
				'key'     => '_package_local_theme_slug',
				'value'   => $args['local_theme_slug'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['package_source_type'] ) ) {
			if ( is_string( $args['package_source_type'] ) ) {
				$args['package_source_type'] = [ $args['package_source_type'] ];
			}

			$query_args['meta_query'][] = [
				'key'     => '_package_source_type',
				'value'   => $args['package_source_type'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['package_source_name'] ) ) {
			if ( is_string( $args['package_source_name'] ) ) {
				$args['package_source_name'] = [ $args['package_source_name'] ];
			}

			$query_args['meta_query'][] = [
				'key'     => '_package_source_name',
				'value'   => $args['package_source_name'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['package_source_project_name'] ) ) {
			if ( is_string( $args['package_source_project_name'] ) ) {
				$args['package_source_project_name'] = [ $args['package_source_project_name'] ];
			}

			$query_args['meta_query'][] = [
				'key'     => '_package_source_project_name',
				'value'   => $args['package_source_project_name'],
				'compare' => 'IN',
			];
		}

		$query       = new \WP_Query( $query_args );
		$package_ids = $query->get_posts();

		if ( empty( $package_ids ) ) {
			return [];
		}

		return $package_ids;
	}

	/**
	 * Gather all the data about a managed package ID.
	 *
	 * @param int $post_ID The package post ID.
	 *
	 * @return array The package data we have available.
	 */
	public function get_package_id_data( int $post_ID ): array {
		$data = [];

		// First, some checking.
		if ( ! $this->check_post_id( $post_ID ) ) {
			return $data;
		}

		$data['is_managed']      = true;
		$data['managed_post_id'] = $post_ID;

		$data['name']        = $this->get_post_package_name( $post_ID );
		$data['type']        = $this->get_post_package_type( $post_ID );
		$data['slug']        = $this->get_post_package_slug( $post_ID );
		$data['source_type'] = $this->get_post_package_source_type( $post_ID );
		$data['source_name'] = $this->get_post_package_source_name( $post_ID );
		$data['keywords']    = $this->get_post_package_keywords( $post_ID );
		$data['description'] = get_post_meta( $post_ID, '_package_details_description', true );
		$data['homepage']    = get_post_meta( $post_ID, '_package_details_homepage', true );
		$data['license']     = get_post_meta( $post_ID, '_package_details_license', true );
		$data['authors']     = $this->get_post_package_authors( $post_ID );

		$data['source_cached_release_packages'] = [];
		$data['manual_releases']                = [];

		switch ( $data['source_type'] ) {
			case 'packagist.org':
			case 'wpackagist.org':
				break;
			case 'vcs':
				$data['vcs_url'] = get_post_meta( $post_ID, '_package_vcs_url', true );
				break;
			case 'local.plugin':
				$data['local_plugin_file'] = get_post_meta( $post_ID, '_package_local_plugin_file', true );

				// Determine if plugin is actually (still) installed.
				$data['local_installed'] = false;
				$installed_plugins       = get_plugins();
				if ( in_array( $data['local_plugin_file'], array_keys( $installed_plugins ) ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.theme':
				$data['local_theme_slug'] = get_post_meta( $post_ID, '_package_local_theme_slug', true );

				// Determine if theme is actually (still) installed.
				$data['local_installed'] = false;
				$installed_themes        = search_theme_directories();
				if ( is_array( $installed_themes ) && in_array( $data['local_theme_slug'], array_keys( $installed_themes ) ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.manual':
				$data['manual_releases'] = $this->get_post_package_manual_releases( $post_ID );
				break;
			default:
				// Nothing
				break;
		}

		if ( in_array( $data['source_type'], [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
			$data['source_version_range'] = trim( get_post_meta( $post_ID, '_package_source_version_range', true ) );
			$data['source_stability']     = trim( get_post_meta( $post_ID, '_package_source_stability', true ) );

			// Get the source version/release packages data (fetched from the external repo) we have stored.
			$source_cached_release_packages = get_post_meta( $post_ID, '_package_source_cached_release_packages', true );
			if ( ! empty( $source_cached_release_packages[ $data['source_name'] ] ) ) {
				$data['source_cached_release_packages'] = $source_cached_release_packages[ $data['source_name'] ];
			}
		}

		$data['required_packages'] = $this->get_post_package_required_packages( $post_ID );
		$data['replaced_packages'] = $this->get_post_package_replaced_packages( $post_ID );
		$data['composer_require']  = $this->get_post_package_composer_require( $post_ID );

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
		if ( empty( $post ) || static::PACKAGE_POST_TYPE !== $post->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Identify a package post ID based on certain details about it and return all configured data about it.
	 *
	 * @param array $args Array of package details to look for.
	 *
	 * @return array The found package data.
	 */
	public function get_managed_package_data_by( array $args ): array {
		$found_package_id = $this->get_package_ids_by( $args );
		if ( empty( $found_package_id ) ) {
			return [];
		}

		// Make sure we only tackle the first package found.
		$found_package_id = reset( $found_package_id );

		return $this->get_package_id_data( $found_package_id );
	}

	/**
	 * Given an external (packagist.org, wpackagist.org, or vcs) managed package post ID, fetch the remote releases data.
	 *
	 * @param int $post_ID
	 *
	 * @return array
	 */
	public function fetch_external_package_remote_releases( int $post_ID ): array {
		$releases = [];

		$post = get_post( $post_ID );
		if ( empty( $post ) || ! in_array( $post->post_status, [ 'publish', 'draft', 'private', ] ) ) {
			return [];
		}

		$package_data = $this->get_package_id_data( $post_ID );
		if ( empty( $package_data['source_type'] ) || ! in_array( $package_data['source_type'], [
				'packagist.org',
				'wpackagist.org',
				'vcs',
			] ) ) {
			return [];
		}

		$client = $this->get_composer_client();

		$version_range = ! empty( $package_data['source_version_range'] ) ? $package_data['source_version_range'] : '*';
		$stability     = ! empty( $package_data['source_stability'] ) ? $package_data['source_stability'] : 'stable';

		try {
			switch ( $package_data['source_type'] ) {
				case 'packagist.org':
					// Pass along a Satis configuration to get packages.
					$releases = $client->getPackages( [
						// The packagist.org repository is available by default.
						'require'                       => [
							$package_data['source_name'] => $version_range,
						],
						'minimum-stability-per-package' => [
							$package_data['source_name'] => $stability,
						],
					] );
					break;
				case 'wpackagist.org':
					// Pass along a Satis configuration to get packages.
					$releases = $client->getPackages( [
						'repositories'                  => [
							[
								// Disable the default packagist.org repo so we don't mistakenly fetch from there.
								'packagist.org' => false,
							],
							[
								'type' => 'composer',
								'url'  => 'https://wpackagist.org',
								'only' => [
									'wpackagist-plugin/*',
									'wpackagist-theme/*',
								],
							],
						],
						'require'                       => [
							$package_data['source_name'] => $version_range,
						],
						'minimum-stability-per-package' => [
							$package_data['source_name'] => $stability,
						],
					] );
					break;
				case 'vcs':
					if ( empty( $package_data['vcs_url'] ) ) {
						break;
					}
					// Pass along a Satis configuration to get packages.
					$releases = $client->getPackages( [
						'repositories'                  => [
							[
								// Disable the default packagist.org repo so we don't mistakenly fetch from there.
								'packagist.org' => false,
							],
							[
								'type' => 'vcs',
								'url'  => $package_data['vcs_url'],
							],
						],
						'require'                       => [
							$package_data['source_name'] => $version_range,
						],
						'minimum-stability-per-package' => [
							$package_data['source_name'] => $stability,
						],
					] );
					break;
				default:
					break;
			}
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Error fetching external packages with the Composer Client for package "{package}" (source type {source_type}).',
				[
					'exception'   => $e,
					'package'     => $package_data['source_name'],
					'source_type' => $package_data['source_type'],
					'logCategory' => 'package_manager',
				]
			);
		}

		if ( ! empty( $releases ) ) {
			$releases = $client->standardizePackagesForJson( $releases, $stability );
		}

		return $releases;
	}

	public function get_managed_installed_plugins( array $query_args = [] ): array {
		$all_plugins_files = array_keys( get_plugins() );

		// Get all package posts that use installed plugins.
		$query       = new \WP_Query( array_merge( [
			'post_type'        => static::PACKAGE_POST_TYPE,
			'fields'           => 'ids',
			'post_status'      => [ 'publish', 'draft', 'private', ],
			'meta_query'       => [
				[
					'key'     => '_package_source_type',
					'value'   => 'local.plugin',
					'compare' => '=',
				],
			],
			'nopaging'         => true,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		], $query_args ) );
		$package_ids = $query->get_posts();

		// Go through all posts and gather all the plugin_file values.
		$used_plugin_files = [];
		foreach ( $package_ids as $package_id ) {
			$plugin_file = get_post_meta( $package_id, '_package_local_plugin_file', true );
			if ( ! empty( $plugin_file ) && in_array( $plugin_file, $all_plugins_files ) ) {
				$used_plugin_files[] = $plugin_file;
			}
		}

		return $used_plugin_files;
	}

	public function get_managed_installed_themes( array $query_args = [] ): array {
		$all_theme_slugs = array_keys( wp_get_themes() );

		// Get all package posts that use installed themes.
		$query       = new \WP_Query( array_merge( [
			'post_type'        => static::PACKAGE_POST_TYPE,
			'fields'           => 'ids',
			'post_status'      => [ 'publish', 'draft', 'private', ],
			'meta_query'       => [
				[
					'key'     => '_package_source_type',
					'value'   => 'local.theme',
					'compare' => '=',
				],
			],
			'nopaging'         => true,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		], $query_args ) );
		$package_ids = $query->get_posts();

		// Go through all posts and gather all the theme_slug values.
		$used_theme_slugs = [];
		foreach ( $package_ids as $package_id ) {
			$theme_slug = get_post_meta( $package_id, '_package_local_theme_slug', true );
			if ( ! empty( $theme_slug ) && in_array( $theme_slug, $all_theme_slugs ) ) {
				$used_theme_slugs[] = $theme_slug;
			}
		}

		return $used_theme_slugs;
	}

	public function get_post_package_name( int $post_ID ): string {
		$post = get_post( $post_ID );
		if ( empty( $post ) ) {
			return '';
		}

		return $post->post_title;
	}

	public function get_post_package_type( int $post_ID ): string {
		/** @var \WP_Error|\WP_Term[] $package_type */
		$package_type = wp_get_post_terms( $post_ID, static::PACKAGE_TYPE_TAXONOMY );
		if ( is_wp_error( $package_type ) || empty( $package_type ) ) {
			return '';
		}
		$package_type = reset( $package_type );

		return $package_type->slug;
	}

	public function get_post_package_source_type( int $post_ID ): string {
		$source_type = get_post_meta( $post_ID, '_package_source_type', true );
		if ( ! is_string( $source_type ) ) {
			return '';
		}

		return $source_type;
	}

	public function get_post_package_source_name( int $post_ID ): string {
		$source_type = $this->get_post_package_source_type( $post_ID );
		switch ( $source_type ) {
			case 'packagist.org':
				// For packagist we expect a complete package name (in the form vendor/name).
				return get_post_meta( $post_ID, '_package_source_name', true );
			case 'wpackagist.org':
				// WPackagist.org used special vendor names.
				$vendor = 'wpackagist-plugin';
				if ( PackageTypes::THEME === $this->get_post_package_type( $post_ID ) ) {
					$vendor = 'wpackagist-theme';
				}

				return $vendor . '/' . get_post_meta( $post_ID, '_package_source_project_name', true );
			case 'vcs':
				// Since we need to use the package name from the project's composer.json file,
				// we expect to be given the right package name. Usually it's the same as the repo name (username/project-name).
				return get_post_meta( $post_ID, '_package_source_name', true );
			case 'local.plugin':
				return 'local-plugin' . '/' . $this->get_post_package_slug( $post_ID );
			case 'local.theme':
				return 'local-theme' . '/' . $this->get_post_package_slug( $post_ID );
			case 'local.manual':
				return 'local-manual' . '/' . $this->get_post_package_slug( $post_ID );
			default:
				// Nothing
				break;
		}

		return '';
	}

	public function get_post_package_slug( int $post_ID ): string {
		$post = get_post( $post_ID );
		if ( empty( $post ) ) {
			return '';
		}

		return $post->post_name;
	}

	public function get_post_package_keywords( int $post_ID ): array {
		$keywords = wp_get_post_terms( $post_ID, static::PACKAGE_KEYWORD_TAXONOMY );
		if ( is_wp_error( $keywords ) || empty( $keywords ) ) {
			return [];
		}

		// We need to return the keywords slugs, not the WP_Term list.
		$keywords = array_map( function ( $term ) {
			if ( $term instanceof \WP_Term ) {
				$term = $term->slug;
			}

			return $term;
		}, $keywords );

		return $keywords;
	}

	public function set_post_package_keywords( int $post_ID, array $keywords ): bool {
		$result = wp_set_post_terms( $post_ID, $keywords, static::PACKAGE_KEYWORD_TAXONOMY );
		if ( false === $result || is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

	public function get_post_package_authors( int $post_ID, string $container_id = '' ): array {
		$authors = carbon_get_post_meta( $post_ID, 'package_details_authors', $container_id );

		// Make sure only the fields we are interested in are left.
		$accepted_keys = array_fill_keys( [ 'name', 'email', 'homepage', 'role' ], '' );
		foreach ( $authors as $key => $author ) {
			$authors[ $key ] = array_replace( $accepted_keys, array_intersect_key( $author, $accepted_keys ) );
		}

		return $authors;
	}

	public function set_post_package_authors( int $post_ID, array $authors, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_details_authors', $authors, $container_id );
	}

	public function get_post_package_manual_releases( int $post_ID, string $container_id = '' ): array {
		$published_releases = carbon_get_post_meta( $post_ID, 'package_manual_releases', $container_id );

		$releases = [];
		foreach ( $published_releases as $release_data ) {
			$release_data['version'] = trim( $release_data['version'] );
			if ( empty( $release_data['version'] ) || empty( $release_data['file'] ) ) {
				continue;
			}

			// Try to normalize the version string.
			try {
				$normalized_version = $this->normalize_version( $release_data['version'] );
			} catch ( \UnexpectedValueException $e ) {
				// This means that something is wrong with the version string. Skip it.
				$this->logger->error(
					'Invalid version string {version} for manually uploaded release in post ID {post_id}.',
					[
						'exception' => $e,
						'version'   => $release_data['version'],
						'post_id'   => $post_ID,
						'logCategory' => 'package_manager',
					]
				);
				continue;
			}

			// The file may be a direct URL to a zip file (like when switching from external sources to a manually managed ones).
			if ( ! is_numeric( $release_data['file'] ) ) {
				$url = $release_data['file'];
			} else {
				// Get the attachment URL.
				$url = wp_get_attachment_url( $release_data['file'] );
			}

			if ( empty( $url ) ) {
				continue;
			}

			$releases[ $normalized_version ] = [
				'version'            => $release_data['version'],
				'normalized_version' => $normalized_version,
				'meta'               => [
					'dist' => [
						'url' => $url,
					],
				],
			];
		}

		uasort(
			$releases,
			function ( $a, $b ) {
				return version_compare( $b['normalized_version'], $a['normalized_version'] );
			}
		);

		return $releases;
	}

	public function get_post_package_required_packages( int $post_ID, string $container_id = '', string $pseudo_id_delimiter = '' ): array {
		$required_packages = carbon_get_post_meta( $post_ID, 'package_required_packages', $container_id );
		if ( empty( $required_packages ) || ! is_array( $required_packages ) ) {
			return [];
		}

		if ( empty( $pseudo_id_delimiter ) ) {
			$pseudo_id_delimiter = self::PSEUDO_ID_DELIMITER;
		}

		// Make sure only the fields we are interested in are left.
		$accepted_keys = array_fill_keys( [ 'pseudo_id', 'version_range', 'stability' ], '' );
		foreach ( $required_packages as $key => $required_package ) {
			$required_packages[ $key ] = array_replace( $accepted_keys, array_intersect_key( $required_package, $accepted_keys ) );

			if ( empty( $required_package['pseudo_id'] ) || false === strpos( $required_package['pseudo_id'], $pseudo_id_delimiter ) ) {
				unset( $required_packages[ $key ] );
				continue;
			}

			// We will now split the pseudo_id in its components (source_name and post_id with the delimiter in between).
			[ $source_name, $post_id ] = explode( $pseudo_id_delimiter, $required_package['pseudo_id'] );
			if ( empty( $post_id ) ) {
				unset( $required_packages[ $key ] );
				continue;
			}

			$required_packages[ $key ]['source_name']     = $source_name;
			$required_packages[ $key ]['managed_post_id'] = intval( $post_id );
		}

		return $required_packages;
	}

	public function set_post_package_required_packages( int $post_ID, array $required_packages, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_required_packages', $required_packages, $container_id );
	}

	public function get_post_package_replaced_packages( int $post_ID, string $container_id = '', string $pseudo_id_delimiter = '' ): array {
		$replaced_packages = carbon_get_post_meta( $post_ID, 'package_replaced_packages', $container_id );
		if ( empty( $replaced_packages ) || ! is_array( $replaced_packages ) ) {
			return [];
		}

		if ( empty( $pseudo_id_delimiter ) ) {
			$pseudo_id_delimiter = self::PSEUDO_ID_DELIMITER;
		}

		// Make sure only the fields we are interested in are left.
		$accepted_keys = array_fill_keys( [ 'pseudo_id', 'version_range', 'stability' ], '' );
		foreach ( $replaced_packages as $key => $replaced_package ) {
			$replaced_packages[ $key ] = array_replace( $accepted_keys, array_intersect_key( $replaced_package, $accepted_keys ) );

			if ( empty( $replaced_package['pseudo_id'] ) || false === strpos( $replaced_package['pseudo_id'], $pseudo_id_delimiter ) ) {
				unset( $replaced_packages[ $key ] );
				continue;
			}

			// We will now split the pseudo_id in its components (source_name and post_id with the delimiter in between).
			[ $source_name, $post_id ] = explode( $pseudo_id_delimiter, $replaced_package['pseudo_id'] );
			if ( empty( $post_id ) ) {
				unset( $replaced_packages[ $key ] );
				continue;
			}

			$replaced_packages[ $key ]['source_name']     = $source_name;
			$replaced_packages[ $key ]['managed_post_id'] = intval( $post_id );
		}

		return $replaced_packages;
	}

	public function set_post_package_replaced_packages( int $post_ID, array $replaced_packages, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_replaced_packages', $replaced_packages, $container_id );
	}

	public function get_post_package_composer_require( int $post_ID, string $container_id = '', string $pseudo_id_delimiter = '' ): array {
		if ( empty( $pseudo_id_delimiter ) ) {
			$pseudo_id_delimiter = self::PSEUDO_ID_DELIMITER;
		}

		// We don't currently allow defining a per-package Composer require list.
		return [];
	}

	public function set_post_package_composer_require( int $post_ID, array $composer_require, string $container_id = '' ) {
		// Nothing right now.
		//		carbon_set_post_meta( $post_ID, 'package_composer_require', $composer_require, $container_id );
	}

	/**
	 * Given a package, dry-run a composer require of it (including its required packages) and see if all goes well.
	 *
	 * @param Package $package
	 *
	 * @return \Exception|bool
	 */
	public function dry_run_package_require( Package $package ) {
		$client = $this->get_composer_client();

		try {
			$client->getPackages( [
				'repositories'                  => [
					[
						// Our very own Composer repo.
						'type'    => 'composer',
						'url'     => get_packages_permalink( [ 'base' => true ] ),
						'options' => [
							'ssl'  => [
								'verify_peer' => ! is_debug_mode(),
							],
							'http' => [
								'header' => ! empty( Env::get( 'PDRECORDS_PHP_AUTH_USER' ) ) ? [
									'Authorization: Basic ' . base64_encode( Env::get( 'PDRECORDS_PHP_AUTH_USER' ) . ':' . Server::AUTH_PWD ),
								] : [],
							],
						],
					],
					[
						// We want the packagist.org repo since we are checking for package dependencies also.
						'type' => 'composer',
						'url'  => 'https://repo.packagist.org',
					],
				],
				'require-dependencies'          => true,
				'only-best-candidates'          => true,
				'require'                       => [
					// Any package version.
					$package->get_name() => '*',
				],
				'minimum-stability-per-package' => [
					// The loosest stability.
					$package->get_name() => 'dev',
				],
				// Since we are just simulating, it doesn't make sense to check the platform requirements (like PHP version, PHP extensions, etc).
				'ignore-platform-reqs'          => true,
			] );
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Error during Composer require dry-run for package "{package}" (type {type}).' . PHP_EOL . $e->getMessage(),
				[
					'exception' => $e,
					'package'   => $package->get_name(),
					'type'      => $package->get_type(),
					'logCategory' => 'package_manager',
				]
			);

			return $e;
		}

		return true;
	}

	/**
	 * Whether a managed package is public (published).
	 *
	 * @since 0.9.0
	 *
	 * @param Package $package
	 *
	 * @return bool
	 */
	public function is_package_public( Package $package ): bool {
		if ( ! $package->is_managed() || ! $package->get_managed_post_id() ) {
			return true;
		}

		if ( 'publish' === \get_post_status( $package->get_managed_post_id() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the visibility status of a package (public, draft, private).
	 *
	 * @since 0.9.0
	 *
	 * @param Package $package
	 *
	 * @return string The visibility status of the package. One of: public, draft, private.
	 */
	public function get_package_visibility( Package $package ): string {
		if ( ! $package->is_managed() || ! $package->get_managed_post_id() ) {
			return 'public';
		}

		switch ( \get_post_status( $package->get_managed_post_id() ) ) {
			case 'publish':
				return 'public';
			case 'draft':
				return 'draft';
			case 'private':
			default:
				return 'private';
		}
	}

	public function hash_encode_id( int $id ): string {
		return $this->hasher->encode( $id );
	}

	public function hash_decode_id( string $hash ): int {
		return $this->hasher->decode( $hash );
	}

	/**
	 * Normalize a version string according to Composer logic.
	 *
	 * @since 0.9.0
	 *
	 * @param string $version
	 *
	 * @throws \UnexpectedValueException
	 * @return string
	 */
	public function normalize_version( string $version ): string {
		return $this->composer_version_parser->normalize( $version );
	}

	public function get_composer_client(): ComposerClient {
		return $this->composer_client;
	}

	public function get_composer_version_parser(): ComposerVersionParser {
		return $this->composer_version_parser;
	}

	public function get_wordpress_readme_parser(): WordPressReadmeParser {
		return $this->wordpress_readme_parser;
	}
}
