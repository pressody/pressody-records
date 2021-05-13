<?php
/**
 * Package archiver.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\Provider;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Composer\Util\Filesystem;
use PixelgradeLT\Records\Exception\FileOperationFailed;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\Storage\Storage;
use Psr\Log\LoggerInterface;
use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;
use PixelgradeLT\Records\Repository\PackageRepository;
use function PixelgradeLT\Records\is_debug_mode;
use function PixelgradeLT\Records\is_dev_url;

/**
 * Package archiver class.
 *
 * @since 0.1.0
 */
class PackageArchiver extends AbstractHookProvider {

	/**
	 * Managed packages repository.
	 *
	 * @var PackageRepository
	 */
	protected PackageRepository $packages;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected ReleaseManager $release_manager;

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Storage.
	 *
	 * @var Storage
	 */
	protected Storage $storage;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageRepository $packages        Managed packages repository.
	 * @param ReleaseManager    $release_manager Release manager.
	 * @param PackageManager    $package_manager Packages manager.
	 * @param Storage           $storage         Storage service.
	 * @param LoggerInterface   $logger          Logger.
	 */
	public function __construct(
		PackageRepository $packages,
		ReleaseManager $release_manager,
		PackageManager $package_manager,
		Storage $storage,
		LoggerInterface $logger
	) {
		$this->packages        = $packages;
		$this->release_manager = $release_manager;
		$this->package_manager = $package_manager;
		$this->storage         = $storage;
		$this->logger          = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_action( 'save_post', [ $this, 'archive_on_post_save' ], 999, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'archive_updates' ], 9999 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'archive_updates' ], 9999 );
		add_filter( 'upgrader_post_install', [ $this, 'archive_on_upgrade' ], 10, 3 );

		add_action( 'wp_trash_post', [ $this, 'clean_on_post_trash' ], 10, 1 );

		$this->add_action( 'pixelgradelt_records_download_url_before', 'hook_before_download_url' );
		$this->add_action( 'pixelgradelt_records_download_url_after', 'remove_hooks_after_download_url' );
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
	public function archive_on_post_save( int $post_ID, \WP_Post $post, bool $update ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$package_data = $this->package_manager->get_package_id_data( $post_ID );
		if ( empty( $package_data ) ) {
			return;
		}

		$package = $this->packages->first_where( [
			'slug' => $package_data['slug'],
			'type' => $package_data['type'],
		] );
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
	 *
	 * @return object
	 */
	public function archive_updates( object $value ): object {
		if ( empty( $value->response ) ) {
			return $value;
		}

		$type = 'pre_set_site_transient_update_plugins' === current_filter() ? 'plugin' : 'theme';

		// The $id will be a theme slug or the plugin file.
		foreach ( $value->response as $id => $update_data ) {
			// Plugin data is stored as an object. Coerce to an array.
			$update_data = (array) $update_data;

			// Bail if a URL isn't available.
			if ( empty( $update_data['package'] ) ) {
				continue;
			}

			$args = [
				'type'        => $type,
				'source_type' => 'local.' . $type,
				'is_managed'  => true,
			];
			if ( 'theme' === $type ) {
				$args['slug'] = $id;
			} else {
				$args['basename'] = $id;
			}

			// Bail if the package isn't whitelisted.
			if ( ! $this->packages->contains( $args ) ) {
				continue;
			}

			$package = $this->packages->first_where( $args );

			$release = new Release(
				$package,
				$update_data['new_version'],
				[
					'dist' => [
						'url' => (string) $update_data['package'],
					],
				]
			);

			try {
				$package->set_release( $this->release_manager->store( $release ) );
			} catch ( \Exception $e ) {
				$this->logger->error(
					'Error archiving package "{package}" - release "{release}".',
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
	 * @param Package[] $packages Array of packages.
	 */
	protected function archive_packages( array $packages ) {
		foreach ( $packages as $package ) {
			$this->archive_package( $package );
		}
	}

	/**
	 * Archive a package when upgrading through the admin panel UI.
	 *
	 * @since 0.6.0
	 *
	 * @param bool|\WP_Error $result     Installation result.
	 * @param array          $hook_extra Extra arguments passed to hooked filters.
	 * @param array          $data       Installation result data.
	 *
	 * @return bool|\WP_Error
	 */
	public function archive_on_upgrade( $result, array $hook_extra, array $data ): bool {
		$type        = $hook_extra['type'] ?? '';
		$source_type = 'local.' . $type;
		$slug        = $data['destination_name'] ?? '';
		$args        = compact( 'slug', 'type', 'source_type' );

		if ( $package = $this->packages->first_where( $args ) ) {
			$this->archive_package( $package );
		}

		return $result;
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
				$new_release = $this->release_manager->store( $release );
				// Once the release file (zip) is successfully archived (cached), it is transformed so we need to overwrite.
				if ( $release !== $new_release ) {
					$package->set_release( $new_release );
				}
			} catch ( \Exception $e ) {
				$this->logger->error(
					'Error archiving package "{package}" - release "{release}".',
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
			/** @var \WP_Filesystem_Base $wp_filesystem */
			global $wp_filesystem;
			include_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			// Delete the package directory and its contents.
			$package_storage_dir_absolute_path = $this->storage->get_absolute_path( $package->get_store_dir() );
			if ( ! $wp_filesystem->is_dir( $package_storage_dir_absolute_path ) || ! $wp_filesystem->delete( $package_storage_dir_absolute_path, true ) ) {
				throw FileOperationFailed::unableToDeletePackageDirectoryFromStorage( $package_storage_dir_absolute_path );
			}

			// Attempt to delete the package's vendor directory, if it is now empty.
			$package_vendor_storage_dir_absolute_path = dirname( $package_storage_dir_absolute_path );
			if ( $wp_filesystem->is_dir( $package_vendor_storage_dir_absolute_path ) && ! $wp_filesystem->dirlist( $package_vendor_storage_dir_absolute_path ) ) {
				$wp_filesystem->delete( $package_vendor_storage_dir_absolute_path );
			}
		} catch ( PixelgradeltRecordsException $e ) {
			$this->logger->warning(
				'Could not clean package "{package}" storage before delete. Manual cleanup may be needed.',
				[
					'exception' => $e,
					'package'   => $package->get_name(),
				]
			);
		}
	}

	/**
	 * Clean package artifacts before a ltpackage post is moved to trash.
	 *
	 * @param int $post_ID Post ID.
	 */
	public function clean_on_post_trash( int $post_ID ) {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post_ID ) ) {
			return;
		}

		$package_data = $this->package_manager->get_package_id_data( $post_ID );
		if ( empty( $package_data ) ) {
			return;
		}

		$package = $this->packages->first_where( [
			'slug' => $package_data['slug'],
			'type' => $package_data['type'],
		] );
		if ( empty( $package ) ) {
			return;
		}

		$this->clean_package( $package );
	}

	protected function hook_before_download_url() {
		$this->add_filter( 'http_request_args', 'maybe_relax_wp_http_settings', 10, 2 );
	}

	protected function remove_hooks_after_download_url() {
		$this->remove_filter( 'http_request_args', 'maybe_relax_wp_http_settings', 10, 2 );
	}

	/**
	 * Filters the arguments used in an HTTP request.
	 *
	 * @param array  $parsed_args An array of HTTP request arguments.
	 * @param string $url         The request URL.
	 *
	 * @return array
	 */
	protected function maybe_relax_wp_http_settings( $parsed_args, $url ): array {
		// Increase the timeout so there is plenty of time to download.
		$parsed_args['timeout'] = 300;

		// If we are in a local/development environment, relax further.
		if ( is_debug_mode() && is_dev_url( $url ) ) {
			// Skip SSL verification since we may be using self-signed certificates.
			$parsed_args['sslverify'] = false;
		}

		return $parsed_args;
	}
}
