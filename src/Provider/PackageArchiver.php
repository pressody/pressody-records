<?php
/**
 * Package archiver.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use PixelgradeLT\Records\Exception\FileOperationFailed;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\Storage\Storage;
use Psr\Log\LoggerInterface;
use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Repository\PackageRepository;

/**
 * Package archiver class.
 *
 * @since 0.1.0
 */
class PackageArchiver extends AbstractHookProvider {
	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Managed packages repository.
	 *
	 * @var PackageRepository
	 */
	protected $packages;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected $package_manager;

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
	 * @param PackageRepository $packages                      Managed packages repository.
	 * @param ReleaseManager    $release_manager               Release manager.
	 * @param PackageManager    $package_manager               Packages manager.
	 * @param Storage           $storage                       Storage service.
	 * @param LoggerInterface   $logger                        Logger.
	 */
	public function __construct(
		PackageRepository $packages,
		ReleaseManager $release_manager,
		PackageManager $package_manager,
		Storage $storage,
		LoggerInterface $logger
	) {
		$this->packages                      = $packages;
		$this->release_manager               = $release_manager;
		$this->package_manager               = $package_manager;
		$this->storage                       = $storage;
		$this->logger                        = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_action( 'save_post', [ $this, 'archive_on_ltpackage_post_save' ], 999, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'archive_updates' ], 9999 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'archive_updates' ], 9999 );
		add_filter( 'upgrader_post_install', [ $this, 'archive_on_upgrade' ], 10, 3 );

		add_action( 'before_delete_post', [ $this, 'clean_on_ltpackage_post_delete' ], 10, 2 );
	}

	/**
	 * Archive packages when an PixelgradeLT package CPT is saved.
	 *
	 * Archiving packages when they're configured helps ensure a checksum can
	 * be included in packages.json.
	 *
	 * @since 0.1.0
	 *
	 * @param int      $post_ID Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public function archive_on_ltpackage_post_save( int $post_ID, \WP_Post $post, bool $update ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$package_data = $this->package_manager->get_package_id_data( $post_ID );
		if ( empty( $package_data ) ) {
			return;
		}

		$package = $this->packages->first_where( [ 'source_name' => $package_data['source_name'], 'type' => $package_data['type'], ] );
		if ( empty( $package ) ) {
			return;
		}

		$this->archive_package( $package );
	}

	/**
	 * Archive updates as they become available.
	 *
	 * @since 0.1.0
	 *
	 * @param object $value Update transient value.
	 * @return object
	 */
	public function archive_updates( $value ) {
		if ( empty( $value->response ) ) {
			return $value;
		}

		$type = 'pre_set_site_transient_update_plugins' === current_filter() ? 'plugin' : 'theme';

		// The $id will be a theme slug or the plugin file.
		foreach ( $value->response as $slug => $update_data ) {
			// Plugin data is stored as an object. Coerce to an array.
			$update_data = (array) $update_data;

			// Bail if a URL isn't available.
			if ( empty( $update_data['package'] ) ) {
				continue;
			}

			$args = ['slug' => $slug, 'type' => $type, 'source_type' => 'local.' . $type ];
			// Bail if the package isn't whitelisted.
			if ( ! $this->packages->contains( $args ) ) {
				continue;
			}

			$package = $this->packages->first_where( $args );

			$release = new Release(
				$package,
				$update_data['new_version'],
				(string) $update_data['package']
			);

			try {
				$package->set_release( $this->release_manager->archive( $release ) );
			} catch ( PixelgradeltRecordsException $e ) {
				$this->logger->error(
					'Error archiving "{package}" - release "{release}".',
					[
						'exception' => $e,
						'package'   => $package->get_name(),
						'release'   => $release->get_version(),
					]
				);
			}
		}

		return $value;
	}

	/**
	 * Archive a list of packages.
	 *
	 * @since 0.1.0
	 *
	 * @param Package[]  $packages Array of packages.
	 */
	protected function archive_packages( array $packages ) {
		foreach ( $packages as $package ) {
			$this->archive_package( $package );
		}
	}

	/**
	 * Archive a package when upgrading through the admin panel UI.
	 *
	 * @since 0.1.0
	 *
	 * @param bool  $response   Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result     Installation result data.
	 */
	public function archive_on_upgrade( bool $response, array $hook_extra, array $result ): bool {
		$type = $hook_extra['type'] ?? '';
		$source_type = 'local.' . $type;
		$slug = $result['destination_name'] ?? '';
		$args = compact( 'slug', 'type', 'source_type' );

		if ( $package = $this->packages->first_where( $args ) ) {
			$this->archive_package( $package );
		}

		return $response;
	}

	/**
	 * Archive a package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 */
	protected function archive_package( Package $package ) {
		foreach ( $package->get_releases() as $release ) {
			try {
				$new_release = $this->release_manager->archive( $release );
				// Once the release file (zip) is successfully archived (cached), it is transformed so we need to overwrite.
				if ( $release !== $new_release ) {
					$package->set_release( $new_release );
				}
			} catch ( PixelgradeltRecordsException $e ) {
				$this->logger->error(
					'Error archiving "{package}" - release "{release}".',
					[
						'exception' => $e,
						'package'   => $package->get_name(),
						'release'   => $release->get_version(),
					]
				);
			}
		}
	}

	/**
	 * Clean a package by deleting all stored zips.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 */
	protected function clean_package( Package $package ) {
		try {
			// Delete each release zip.
			foreach ( $package->get_releases() as $release ) {
				$this->release_manager->delete( $release );
			}

			// Delete the (empty) package directory.
			$package_storage_dir_absolute_path = $this->storage->get_absolute_path( $package->get_slug() );
			if ( ! \rmdir( $package_storage_dir_absolute_path ) ) {
				throw FileOperationFailed::unableToDeletePackageDirectoryFromStorage( $package_storage_dir_absolute_path );
			}
		} catch ( PixelgradeltRecordsException $e ) {
			$this->logger->warning(
				'Could not clean {package} storage before delete. Manual cleanup may be needed.',
				[
					'exception' => $e,
					'package'   => $package->get_name(),
				]
			);
		}
	}

	/**
	 * Clean packages before a ltpackage post is deleted from the database.
	 *
	 * @param int     $post_ID Post ID.
	 * @param \WP_Post $post   Post object.
	 */
	public function clean_on_ltpackage_post_delete( int $post_ID, \WP_Post $post ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $post->post_type ) {
			return;
		}

		$package_data = $this->package_manager->get_package_id_data( $post_ID );
		if ( empty( $package_data ) ) {
			return;
		}

		$package = $this->packages->first_where( [ 'source_name' => $package_data['source_name'], 'type' => $package_data['type'], ] );
		if ( empty( $package ) ) {
			return;
		}

		$this->clean_package( $package );
	}
}
