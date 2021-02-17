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
	 * Retrieve all managed packages.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Package type.
	 * @return Package[]
	 */
	public function all( string $type = 'all' ): array {

	}

	public function get_managed_installed_plugins( array $query_args = [] ): array {
		$all_plugins_files = array_keys( get_plugins() );

		// Get all package posts that use installed plugins.
		$query = new \WP_Query( array_merge( [
			'post_type'  => static::PACKAGE_POST_TYPE,
			'fields' => 'ids',
			'meta_query' => [
				[
					'key'   => '_package_source_type',
					'value' => 'local.plugin',
					'compare' => '=',
				],
			],
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
			'meta_query' => [
				[
					'key'   => '_package_source_type',
					'value' => 'local.theme',
					'compare' => '=',
				],
			],
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

	public function get_post_package_type( int $post_ID ): string {
		/** @var \WP_Error|\WP_Term[] $package_type */
		$package_type = wp_get_post_terms( $post_ID, PackageManager::PACKAGE_TYPE_TAXONOMY );
		if ( is_wp_error( $package_type ) || empty( $package_type ) ) {
			return '';
		}
		$package_type = reset( $package_type );

		return $package_type->slug;
	}

	public function get_post_installed_package_slug( int $post_ID ): string {
		$package_slug = '';

		$package_type = $this->get_post_package_type( $post_ID );
		if ( empty( $package_type ) ) {
			return $package_slug;
		}

		$package_source_type = get_post_meta( $post_ID, '_package_source_type', true );
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

	static function get_stored_package_data( int $post_ID ): array {
		$data = [];

		return $data;
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
