<?php
/**
 * Release manager.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\Exception\FileOperationFailed;
use PixelgradeLT\Records\Exception\InvalidReleaseSource;
use PixelgradeLT\Records\HTTP\Response;
use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\Storage\Storage;
use Composer\Semver\VersionParser;

/**
 * Release manager class.
 *
 * @since 0.1.0
 */
class ReleaseManager {
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
	 * Retrieve all cached releases for a package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package instance.
	 * @return Release[]
	 */
	public function all_cached( Package $package ): array {
		$releases = [];
		$version_parser = new VersionParser();

		foreach ( $this->storage->list_files( $package->get_source_name() ) as $filename ) {
			$version = trim( str_replace( $package->get_slug() . '-', '', basename( $filename, '.zip' ) ) );
			if ( empty( $version ) ) {
				continue;
			}
			try {
				// Check that the version is actually valid.
				$tmp_version = $version_parser->normalize( $version );
			} catch ( \Exception $e ) {
				// If there was an exception it means that something is wrong with this version. So ignore it.
				continue;
			}

			$releases[ $version ] = new Release( $package, $version );
		}

		return $releases;
	}

	/**
	 * Archive a release.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release instance.
	 * @throws InvalidReleaseSource If a source URL is not available or the
	 *                              version doesn't match the currently installed version.
	 * @throws FileOperationFailed  If the release artifact can't be moved to storage.
	 * @return Release
	 */
	public function archive( Release $release ): Release {
		if ( $this->exists( $release ) ) {
			return $release;
		}

		$package    = $release->get_package();
		$source_url = $release->get_source_url();

		if ( ! empty( $source_url ) ) {
			$filename = $this->archiver->archive_from_url( $release );
		} elseif ( $package instanceof LocalBasePackage && $package->is_installed() && $package->is_installed_release( $release ) ) {
			$filename = $this->archiver->archive_from_source( $package, $release->get_version() );
		} else {
			throw InvalidReleaseSource::forRelease( $release );
		}

		if ( ! $this->storage->move( $filename, $release->get_file_path() ) ) {
			throw FileOperationFailed::unableToMoveReleaseArtifactToStorage( $filename, $release->get_file_path() );
		}

		// We have safely cached the release. This means we should remove the source URL so from now on, so the cached zip is used instead.
		return new Release( $package, $release->get_version() );
	}

	/**
	 * Delete a release from storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release instance.
	 *
	 * @throws FileOperationFailed  If the release artifact can't be deleted from storage.
	 * @return Release
	 */
	public function delete( Release $release ): Release {
		if ( ! $this->exists( $release ) ) {
			return $release;
		}

		if ( ! $this->storage->delete( $release->get_file_path() ) ) {
			throw FileOperationFailed::unableToDeleteReleaseArtifactFromStorage( $release->get_file_path() );
		}

		return $release;
	}

	/**
	 * Retrieve a checksum for a release.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $algorithm Algorithm.
	 * @param Release $release   Release instance.
	 * @return string
	 */
	public function checksum( string $algorithm, Release $release ): string {
		return $this->storage->checksum( $algorithm, $release->get_file_path() );
	}

	/**
	 * Whether an artifact exists for a given release.
	 *
	 * @param Release $release Release instance.
	 * @return bool
	 */
	public function exists( Release $release ): bool {
		return $this->storage->exists( $release->get_file_path() );
	}

	/**
	 * Retrieve the absolute file path for an artifact.
	 *
	 * @param Release $release Release instance.
	 * @return string|false The absolute file path or false if it doesn't exist.
	 */
	public function get_absolute_path( Release $release ) {
		if ( ! $this->exists( $release ) ) {
			return false;
		}

		return $this->storage->get_absolute_path( $release->get_file_path() );
	}

	/**
	 * Send a download.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release instance.
	 * @return Response
	 */
	public function send( Release $release ): Response {
		do_action( 'pixelgradelt_records_send_release', $release );
		return $this->storage->send( $release->get_file_path() );
	}
}
