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

use PixelgradeLT\Records\Exception\FileOperationFailed;
use PixelgradeLT\Records\Exception\InvalidReleaseSource;
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
	 * @param Storage  $storage  Storage service.
	 * @param Archiver $archiver Archiver.
	 */
	public function __construct( Storage $storage, Archiver $archiver ) {
		$this->archiver = $archiver;
		$this->storage  = $storage;
	}

	/**
	 * Retrieve managed packages.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Package type.
	 * @return Package[]
	 */
	public function all( string $type = 'all' ): array {
		$items = [];

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$package = $this->build( $plugin_file, $plugin_data );
			$items[] = $package;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Get post ids for managed packages.
	 *
	 * @param string $types            Optional. Package types. Default is to query for all package types.
	 * @param array  $extra_query_args Optional. Query args.
	 *
	 * @return int[] Post ids.
	 */
	public function get_package_ids( string $types = 'all', array $extra_query_args = [] ): array {
		$query_args = [
			'post_type'  => static::PACKAGE_POST_TYPE,
			'fields' => 'ids',
			'post_status' => 'publish',
			'nopaging' => true,
			'no_found_rows' => true,
			'suppress_filters' => true,
		];

		if ( 'all' !== $types && ! empty( $types ) ) {
			if ( is_string( $types ) ) {
				$types = [ $types ];
			}

			$types = array_filter( array_values( $types ), 'is_string' );

			$query_args['tax_query'] = [
				[
					'taxonomy' => static::PACKAGE_TYPE_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $types,
					'operator' => 'IN',
				],
			];
		}

		$query = new \WP_Query( array_merge( $query_args, $extra_query_args ) );
		$package_ids = $query->get_posts();
		if ( empty( $package_ids ) ) {
			return [];
		}

		return $package_ids;
	}

	/**
	 * Identify a package post ID based on certain details about it.
	 *
	 * @param string $source_type The package source type.
	 * @param array  $args Array of package details to look for.
	 *
	 * @return int|false The post ID or false if not found.
	 */
	public function get_package_id_by( string $source_type, array $args ) {
		$query_args = [
			'post_type'  => PackageManager::PACKAGE_POST_TYPE,
			'fields' => 'ids',
			'post_status' => 'any',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'   => '_package_source_type',
					'value' => $source_type,
					'compare' => '=',
				],
			],
			'posts_per_page' => 1, // We only want one package.
			'nopaging' => true,
			'no_found_rows' => true,
			'suppress_filters' => true,
		];

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
				'key'   => '_package_local_plugin_file',
				'value' => $args['local_plugin_file'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['local_theme_slug'] ) ) {
			if ( is_string( $args['local_theme_slug'] ) ) {
				$args['local_theme_slug'] = [ $args['local_theme_slug'] ];
			}

			$query_args['meta_query'][] = [
				'key'   => '_package_local_theme_slug',
				'value' => $args['local_theme_slug'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['package_source_name'] ) ) {
			if ( is_string( $args['package_source_name'] ) ) {
				$args['package_source_name'] = [ $args['package_source_name'] ];
			}

			$query_args['meta_query'][] = [
				'key'   => '_package_source_name',
				'value' => $args['package_source_name'],
				'compare' => 'IN',
			];
		}

		if ( ! empty( $args['package_source_project_name'] ) ) {
			if ( is_string( $args['package_source_project_name'] ) ) {
				$args['package_source_project_name'] = [ $args['package_source_project_name'] ];
			}

			$query_args['meta_query'][] = [
				'key'   => '_package_source_project_name',
				'value' => $args['package_source_project_name'],
				'compare' => 'IN',
			];
		}

		$query = new \WP_Query( $query_args );
		$post_ids = $query->get_posts();

		if ( empty( $post_ids ) ) {
			return false;
		}

		return reset( $post_ids );
	}

	/**
	 * Gather all the data about a managed package ID.
	 *
	 * @param int $post_ID The package post ID.
	 *
	 * @return array The package data we have available.
	 */
	public function get_package_id_data( int $post_ID ): array {
		$data = [
			'name'        => $this->get_post_package_name( $post_ID ),
			'type'        => $this->get_post_package_type( $post_ID ),
			'source_type' => $this->get_post_package_source_type( $post_ID ),
			'slug'        => $this->get_post_package_slug( $post_ID ),
			'keywords'    => $this->get_post_package_keywords( $post_ID ),
		];

		switch ( $data['source_type'] ) {
			case 'packagist.org':
				$data['package_source_name'] = get_post_meta( $post_ID, '_package_source_name', true );
				break;
			case 'wpackagist.org':
				// WPackagist.org used special vendor names.
				$vendor = 'wpackagist-plugin';
				if ( 'theme' === $data['type'] ) {
					$vendor = 'wpackagist-theme';
				}
				$data['package_source_name'] = $vendor . '/' . get_post_meta( $post_ID, '_package_source_project_name', true );
				break;
			case 'vcs':
				$data['vcs_url']       = get_post_meta( $post_ID, '_package_vcs_url', true );
				break;
			case 'local.plugin':
				$data['local_plugin_file'] = get_post_meta( $post_ID, '_package_local_plugin_file', true );

				// Determine if plugin is actually (still) installed.
				$data['local_installed'] = false;
				$installed_plugins = get_plugins();
				if ( in_array( $data['local_plugin_file'], array_keys( $installed_plugins ) ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.theme':
				$data['local_theme_slug'] = get_post_meta( $post_ID, '_package_local_theme_slug', true );

				// Determine if theme is actually (still) installed.
				$data['local_installed'] = false;
				$installed_themes = search_theme_directories();
				if ( is_array( $installed_themes ) && in_array( $data['local_theme_slug'], $installed_themes ) ) {
					$data['local_installed'] = true;
				}
				break;
			case 'local.manual':
				// To be determined.
				break;
			default:
				// Nothing
				break;
		}

		if ( in_array( $data['source_type'], [ 'packagist.org', 'wpackagist.org', 'vcs', ] ) ) {
			$data['source_version_range'] = get_post_meta( $post_ID, '_package_source_version_range', true );
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
	 * @param string $source_type The package source type.
	 * @param array  $args Array of package details to look for.
	 *
	 * @return array The found package data.
	 */
	public function get_managed_package_data( string $source_type, array $args ): array {
		$found_package_id = $this->get_package_id_by( $source_type, $args );
		if ( empty( $found_package_id ) ) {
			return [];
		}

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
		return get_post_meta( $post_ID, '_package_source_type', true );
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

		return $package_slug;
	}

//	/**
//	 * Archive a release.
//	 *
//	 * @since 0.1.0
//	 *
//	 * @param Release $release Release instance.
//	 * @throws InvalidReleaseSource If a source URL is not available or the
//	 *                              version doesn't match the currently installed version.
//	 * @throws FileOperationFailed  If the release artifact can't be moved to storage.
//	 * @return Release
//	 */
//	public function archive( Release $release ): Release {
//		if ( $this->exists( $release ) ) {
//			return $release;
//		}
//
//		$package    = $release->get_package();
//		$source_url = $release->get_source_url();
//
//		if ( ! empty( $source_url ) ) {
//			$filename = $this->archiver->archive_from_url( $release );
//		} elseif ( $package->is_installed() && $package->is_installed_release( $release ) ) {
//			$filename = $this->archiver->archive_from_source( $package, $release->get_version() );
//		} else {
//			throw InvalidReleaseSource::forRelease( $release );
//		}
//
//		if ( ! $this->storage->move( $filename, $release->get_file_path() ) ) {
//			throw FileOperationFailed::unableToMoveReleaseArtifactToStorage( $filename, $release->get_file_path() );
//		}
//
//		return $release;
//	}
}
