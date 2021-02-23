<?php
/**
 * Package manager.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\Client\ComposerClient;
use PixelgradeLT\Records\Storage\Storage;

/**
 * Package manager class.
 *
 * Handles the logic related to configuring packages through a CPT.
 *
 * @since 0.1.0
 */
class PackageManager {

	const PACKAGE_POST_TYPE = 'ltpackage';
	const PACKAGE_POST_TYPE_PLURAL = 'ltpackages';

	const PACKAGE_TYPE_TAXONOMY = 'ltpackage_types';
	const PACKAGE_TYPE_TAXONOMY_SINGULAR = 'ltpackage_type';

	/**
	 * We will automatically register these if they are not present.The slugs will be transformed into the package types defined by composer/installers
	 * @see \PixelgradeLT\Records\Transformer\ComposerPackageTransformer::WORDPRESS_TYPES
	 * @link https://packagist.org/packages/composer/installers
	 */
	const PACKAGE_TYPE_TERMS = [
		[
			'name'        => 'WordPress Plugin',
			'slug'        => 'plugin',
			'description' => 'A WordPress plugin package.',
		],
		[
			'name'        => 'WordPress Theme',
			'slug'        => 'theme',
			'description' => 'A WordPress theme package.',
		],
		[
			'name'        => 'WordPress Must-Use Plugin',
			'slug'        => 'muplugin',
			'description' => 'A WordPress Must-Use plugin package.',
		],
		[
			'name'        => 'WordPress Drop-in Plugin',
			'slug'        => 'dropin',
			'description' => 'A WordPress Drop-in plugin package.',
		],
	];

	const PACKAGE_KEYWORD_TAXONOMY = 'ltpackage_keywords';
	const PACKAGE_KEYWORD_TAXONOMY_SINGULAR = 'ltpackage_keyword';

	/**
	 * Archiver.
	 *
	 * @var Archiver
	 */
	protected $archiver;

	/**
	 * External Composer repository client.
	 *
	 * @var ComposerClient
	 */
	protected $composer_client;

	/**
	 * Storage.
	 *
	 * @var Storage
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Storage        $storage  Storage service.
	 * @param Archiver       $archiver Archiver.
	 * @param ComposerClient $composer_client
	 */
	public function __construct( Storage $storage, Archiver $archiver, ComposerClient $composer_client ) {
		$this->archiver = $archiver;
		$this->storage  = $storage;
		$this->composer_client  = $composer_client;
	}

	public function get_composer_client(): ComposerClient {
		return $this->composer_client;
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
			'post_type'        => PackageManager::PACKAGE_POST_TYPE,
			'fields'           => 'ids',
			'post_status'      => 'publish',
			'tax_query'        => [],
			'meta_query'       => [],
			'nopaging'         => true,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		];

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
		if ( empty( $post_ID ) ) {
			return $data;
		}
		$post = get_post( $post_ID );
		if ( empty( $post ) || PackageManager::PACKAGE_POST_TYPE !== $post->post_type ) {
			return $data;
		}

		$data['name']        = $this->get_post_package_name( $post_ID );
		$data['type']        = $this->get_post_package_type( $post_ID );
		$data['slug']        = $this->get_post_package_slug( $post_ID );
		$data['source_type'] = $this->get_post_package_source_type( $post_ID );
		$data['keywords']    = $this->get_post_package_keywords( $post_ID );

		switch ( $data['source_type'] ) {
			case 'packagist.org':
				// For packagist we expect a complete package name (in the form vendor/name).
				$data['source_name'] = get_post_meta( $post_ID, '_package_source_name', true );
				break;
			case 'wpackagist.org':
				// WPackagist.org used special vendor names.
				$vendor = 'wpackagist-plugin';
				if ( 'theme' === $data['type'] ) {
					$vendor = 'wpackagist-theme';
				}
				$data['source_name'] = $vendor . '/' . get_post_meta( $post_ID, '_package_source_project_name', true );
				break;
			case 'vcs':
				$data['source_name'] = 'vcs' . '/' . $data['slug'];
				$data['vcs_url']       = get_post_meta( $post_ID, '_package_vcs_url', true );
				break;
			case 'local.plugin':
				$data['source_name'] = 'local-plugin' . '/' . $data['slug'];

				$data['local_plugin_file'] = get_post_meta( $post_ID, '_package_local_plugin_file', true );

				// Determine if plugin is actually (still) installed.
				$data['local_installed'] = false;
				$installed_plugins = get_plugins();
				if ( in_array( $data['local_plugin_file'], array_keys( $installed_plugins ) ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.theme':
				$data['source_name'] = 'local-theme' . '/' . $data['slug'];
				$data['local_theme_slug'] = get_post_meta( $post_ID, '_package_local_theme_slug', true );

				// Determine if theme is actually (still) installed.
				$data['local_installed'] = false;
				$installed_themes = search_theme_directories();
				if ( is_array( $installed_themes ) && in_array( $data['local_theme_slug'], $installed_themes ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.manual':
				$data['source_name'] = 'local-manual' . '/' . $data['slug'];
				break;
			default:
				// Nothing
				break;
		}

		if ( in_array( $data['source_type'], [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
			$data['source_version_range'] = trim( get_post_meta( $post_ID, '_package_source_version_range', true ) );
			$data['source_stability'] = trim( get_post_meta( $post_ID, '_package_source_stability', true ) );
		}

		if ( in_array( $data['source_type'], [ 'local.plugin', 'local.theme', 'local.manual', ] ) ) {
			$data['details'] = [
				'description' => get_post_meta( $post_ID, '_package_details_description', true ),
				'homepage'    => get_post_meta( $post_ID, '_package_details_homepage', true ),
				'license'     => get_post_meta( $post_ID, '_package_details_license', true ),
				'authors'     => $this->get_post_package_authors( $post_ID ),
			];
		}

		return $data;
	}

	/**
	 * Identify a package post ID based on certain details about it and return all configured data about it.
	 *
	 * @param array  $args Array of package details to look for.
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

	public function get_managed_installed_plugins( array $query_args = [] ): array {
		$all_plugins_files = array_keys( get_plugins() );

		// Get all package posts that use installed plugins.
		$query = new \WP_Query( array_merge( [
			'post_type'  => static::PACKAGE_POST_TYPE,
			'fields' => 'ids',
			'post_status' => 'publish',
			'meta_query' => [
				[
					'key'   => '_package_source_type',
					'value' => 'local.plugin',
					'compare' => '=',
				],
			],
			'nopaging' => true,
			'no_found_rows' => true,
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
		$query = new \WP_Query( array_merge( [
			'post_type'  => PackageManager::PACKAGE_POST_TYPE,
			'fields' => 'ids',
			'post_status' => 'publish',
			'meta_query' => [
				[
					'key'   => '_package_source_type',
					'value' => 'local.theme',
					'compare' => '=',
				],
			],
			'nopaging' => true,
			'no_found_rows' => true,
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
		$package_type = wp_get_post_terms( $post_ID, PackageManager::PACKAGE_TYPE_TAXONOMY );
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
		$accepted_keys = array_fill_keys( ['name', 'email', 'homepage', 'role'], '');
		foreach ( $authors as $key => $author ) {
			$authors[ $key ] = array_replace( $accepted_keys, array_intersect_key( $author, $accepted_keys ) );
		}

		return $authors;
	}

	public function set_post_package_authors( int $post_ID, array $authors, string $container_id = '' ) {
		carbon_set_post_meta( $post_ID, 'package_details_authors', $authors, $container_id );
	}

	public function get_post_installed_package_slug( int $post_ID ): string {
		$package_slug = '';

		$package_type = $this->get_post_package_type( $post_ID );
		if ( empty( $package_type ) ) {
			return $package_slug;
		}

		$package_source_type = $this->get_post_package_source_type( $post_ID );
		if ( empty( $package_source_type ) ) {
			return $package_slug;
		}

		if ( 'plugin' === $package_type && 'local.plugin' === $package_source_type  ) {
			$package_slug = get_post_meta( $post_ID, '_package_local_plugin_file', true );
		} else if ( 'theme' === $package_type && 'local.theme' === $package_source_type ) {
			$package_slug = get_post_meta( $post_ID, '_package_local_theme_slug', true );
		}

		if ( ! is_string( $package_slug ) ) {
			return '';
		}

		return $package_slug;
	}
}
