<?php
/**
 * Release manager.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use PixelgradeLT\Records\Client\ComposerClient;
use PixelgradeLT\Records\Exception\FileNotFound;
use PixelgradeLT\Records\Exception\FileOperationFailed;
use PixelgradeLT\Records\Exception\InvalidReleaseSource;
use PixelgradeLT\Records\HTTP\Response;
use PixelgradeLT\Records\PackageType\BasePackage;
use PixelgradeLT\Records\PackageType\LocalBasePackage;
use PixelgradeLT\Records\Storage\Storage;
use Psr\Log\LoggerInterface;

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
	protected Archiver $archiver;

	/**
	 * Storage.
	 *
	 * @var Storage
	 */
	protected Storage $storage;

	/**
	 * Composer version parser.
	 *
	 * @var ComposerVersionParser
	 */
	protected ComposerVersionParser $composer_version_parser;

	/**
	 * External Composer repository client.
	 *
	 * @var ComposerClient
	 */
	protected ComposerClient $composer_client;

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
	 * @param Storage               $storage  Storage service.
	 * @param Archiver              $archiver Archiver.
	 * @param ComposerVersionParser $composer_version_parser
	 * @param ComposerClient        $composer_client
	 * @param LoggerInterface       $logger   Logger.
	 */
	public function __construct(
		Storage $storage,
		Archiver $archiver,
		ComposerVersionParser $composer_version_parser,
		ComposerClient $composer_client,
		LoggerInterface $logger
	) {

		$this->archiver                = $archiver;
		$this->storage                 = $storage;
		$this->composer_version_parser = $composer_version_parser;
		$this->composer_client         = $composer_client;
		$this->logger                  = $logger;
	}

	public function get_composer_version_parser(): ComposerVersionParser {
		return $this->composer_version_parser;
	}

	public function get_composer_client(): ComposerClient {
		return $this->composer_client;
	}

	/**
	 * Retrieve all stored releases for a package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package instance.
	 *
	 * @return Release[]
	 */
	public function all_stored_releases( Package $package ): array {
		$releases = [];

		foreach ( $this->storage->list_files( $package->get_source_name() ) as $filename ) {
			$version = trim( str_replace( $package->get_slug() . '-', '', basename( $filename, '.zip' ) ) );
			if ( empty( $version ) ) {
				continue;
			}
			try {
				// Check that the version is actually valid.
				$tmp_version = $this->composer_version_parser->normalize( $version );
			} catch ( \Exception $e ) {
				// If there was an exception it means that something is wrong with this version. So ignore it.
				continue;
			}

			// We have a valid .zip file.
			// Now we search for a .json file with the release meta data.
			// This is the data that will take precedence over everything else.
			// Missing meta data will get filled with parent package data.
			$meta           = [];
			$meta_file_path = trailingslashit( $package->get_source_name() ) . basename( $filename, '.zip' ) . '.json';
			if ( $this->storage->exists( $meta_file_path ) ) {
				try {
					$meta_file = new JsonFile( $this->storage->get_absolute_path( $meta_file_path ) );
					$meta      = array_merge( $meta, $meta_file->read() );
				} catch ( \Exception $e ) {
					$this->logger->error(
						'Error getting meta data from file {file} for package {package}: release version {version}.',
						[
							'exception' => $e,
							'package'   => $package->get_source_name(),
							'version'   => $version,
							'file'      => $meta_file_path,
						]
					);
				}
			}

			$releases[ $version ] = new Release( $package, $version, $meta );
		}

		return $releases;
	}

	/**
	 * Store a release in the storage.
	 *
	 * We will store the release zip artifact (archive) and meta data as JSON.
	 *
	 * @since 0.9.0
	 *
	 * @param Release $release Release instance.
	 *
	 * @throws InvalidReleaseSource If a source URL is not available or the
	 *                              version doesn't match the currently installed version.
	 * @throws FileOperationFailed  If the release artifact can't be moved to storage.
	 * @throws \Exception           If Composer couldn't download the archive file.
	 * @return Release
	 */
	public function store( Release $release ): Release {
		// We don't want to store if we are running tests.
		if ( is_running_unit_tests() ) {
			return $release;
		}

		if ( $this->is_stored( $release ) ) {
			return $this->transform_into_stored( $release );
		}

		$parent_package = $release->get_package();
		$source_url     = $release->get_source_url();

		if ( ! empty( $source_url ) ) {
			// Determine if we have a Composer repo source URL and thus use Composer to download since it has all the auth info required.
			if ( $parent_package instanceof BasePackage && $this->is_composer_repo_source_url( $source_url ) ) {
				$client = $this->get_composer_client();

				// Get the cached source package data since that is in the format Composer expects.
				$managed_post_id = $parent_package->get_managed_post_id();
				// Get the source version/release packages data (fetched from the external repo) we have stored.
				$source_cached_release_packages = get_post_meta( $managed_post_id, '_package_source_cached_release_packages', true );
				if ( ! empty( $source_cached_release_packages[ $parent_package->get_source_name() ][ $release->get_version() ] ) ) {
					$loader           = new ArrayLoader();
					$composer_package = $loader->load( $source_cached_release_packages[ $parent_package->get_source_name() ][ $release->get_version() ] );
					$filename         = $client->archivePackage( $composer_package );
				} else {
					throw InvalidReleaseSource::missingSourceCachedPackage( $release );
				}
			} else {
				// Otherwise, use the regular WordPress download logic.
				$filename = $this->archiver->archive_from_url( $release );
			}
		} elseif ( $parent_package instanceof LocalBasePackage && $parent_package->is_installed() && $parent_package->is_installed_release( $release ) ) {
			$filename = $this->archiver->archive_from_source( $parent_package, $release->get_version() );
		} else {
			throw InvalidReleaseSource::forRelease( $release );
		}

		if ( ! $this->storage->move( $filename, $release->get_file_path() ) ) {
			throw FileOperationFailed::unableToMoveReleaseArtifactToStorage( $filename, $release->get_file_path() );
		}

		// We have safely stored the release.
		// This means we should create a release with the authenticated source URL so the stored zip file is used as the source instead.
		return $this->transform_into_stored( $release );
	}

	/**
	 * Dump (write) a release's meta data to file.
	 *
	 * @since 0.9.0
	 *
	 * @param Release $release Release instance.
	 *
	 * @return Release
	 */
	public function dump_meta( Release $release ): Release {
		// We don't want to write if we are running tests.
		if ( is_running_unit_tests() ) {
			return $release;
		}

		try {
			$meta_file_path = $this->storage->get_absolute_path( $release->get_meta_file_path() );
			$meta_file      = new JsonFile( $meta_file_path );
			if ( ! $meta_file->exists() ) {
				@touch( $meta_file_path );
			}
			$meta_file->write( $release->get_meta() );
		} catch ( \UnexpectedValueException | \Exception $e ) {
			throw FileOperationFailed::unableToWriteReleaseMetaFileToStorage( $release->get_meta_file_path() );
		}

		return $release;
	}

	/**
	 * Given a release modify its (meta) data to use the fact that its has a stored artifact.
	 *
	 * @param Release $release
	 *
	 * @return Release
	 */
	public function transform_into_stored( Release $release ): Release {
		if ( ! $this->is_stored( $release ) ) {
			return $release;
		}

		// We need create a release with the authenticated source URL so the stored zip file is used as the source.
		$meta = $release->get_meta();

		// If we already have the right 'dist' details (maybe from some cache; JSON?), don't recalculate the checksum.
		// But only if the artifact file (.zip) wasn't modified in the mean time.
		if ( ! empty( $meta['dist']['url'] )
		     && ! empty( $meta['dist']['shasum'] )
		     && $meta['dist']['url'] === $release->get_download_url()
		     && ! empty( $meta['dist']['artifactmtime'] )
		     && filemtime( $this->storage->get_absolute_path( $release->get_file_path() ) ) === $meta['dist']['artifactmtime'] ) {

			return $release;
		}

		unset( $meta['dist'] );
		try {
			$meta['dist'] = [
				'type'          => 'zip',
				'url'           => $release->get_download_url(),
				'shasum'        => $this->checksum( 'sha1', $release ),
				'artifactmtime' => filemtime( $this->storage->get_absolute_path( $release->get_file_path() ) ),
			];
		} catch ( FileNotFound $e ) {
			$this->logger->error(
				'Package artifact could not be found for package {package}:{version}.',
				[
					'exception' => $e,
					'package'   => $release->get_package()->get_name(),
					'version'   => $release->get_version(),
				]
			);
		}

		return new Release( $release->get_package(), $release->get_version(), $meta );
	}

	/**
	 * Determine if a given package source URL is from an external Composer repo.
	 *
	 * This is used to determine the best way to download the artifacts.
	 *
	 * @since 0.5.0
	 *
	 * @param string $source_url The package source URL.
	 *
	 * @return bool
	 */
	protected function is_composer_repo_source_url( string $source_url ): bool {
		// An evolving list of fragments to identify external Composer repo sources.
		$fragments = [
			'github.com',
			'packagist.org',
			'bitbucket.org',
		];

		foreach ( $fragments as $fragment ) {
			if ( false !== strpos( $source_url, $fragment ) ) {
				return true;
			}
		}

		return false;
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
		if ( ! $this->is_stored( $release ) ) {
			return $release;
		}

		if ( ! $this->storage->delete( $release->get_file_path() ) ) {
			throw FileOperationFailed::unableToDeleteReleaseArtifactFromStorage( $release->get_file_path() );
		}

		if ( ! $this->storage->delete( $release->get_meta_file_path() ) ) {
			throw FileOperationFailed::unableToDeleteReleaseMetaFileFromStorage( $release->get_meta_file_path() );
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
	 *
	 * @return string
	 */
	public function checksum( string $algorithm, Release $release ): string {
		return $this->storage->checksum( $algorithm, $release->get_file_path() );
	}

	/**
	 * Whether an zip artifact is already stored for a given release.
	 *
	 * @param Release $release Release instance.
	 *
	 * @return bool
	 */
	public function is_stored( Release $release ): bool {
		return $this->storage->exists( $release->get_file_path() );
	}

	/**
	 * Retrieve the absolute file path for an artifact.
	 *
	 * @param Release $release Release instance.
	 *
	 * @return string|false The absolute file path or false if it doesn't exist.
	 */
	public function get_absolute_path( Release $release ) {
		if ( ! $this->is_stored( $release ) ) {
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
	 *
	 * @return Response
	 */
	public function send( Release $release ): Response {
		do_action( 'pixelgradelt_records_send_release', $release );

		return $this->storage->send( $release->get_file_path() );
	}
}
