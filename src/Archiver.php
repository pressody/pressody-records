<?php
/**
 * Archiver.
 *
 * Creates package artifacts in the system's temporary directory. Methods return
 * the absolute path to the artifact. Code making use of this class should move
 * or delete the artifacts as necessary.
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

use LogicException;
use PclZip;
use Pimple\ServiceIterator;
use Psr\Log\LoggerInterface;
use Pressody\Records\Exception\FileDownloadFailed;
use Pressody\Records\Exception\FileOperationFailed;
use Pressody\Records\Exception\InvalidPackageArtifact;
use Pressody\Records\Exception\PackageNotInstalled;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\Validator\ArtifactValidator;

/**
 * Archiver class.
 *
 * @since 0.1.0
 */
class Archiver {
	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Artifact validators.
	 *
	 * @var ServiceIterator
	 */
	protected $validators = [];

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register artifact validators.
	 *
	 * @since 0.1.0
	 *
	 * @param ServiceIterator $validators Artifact validators.
	 *
	 * @return $this
	 */
	public function register_validators( ServiceIterator $validators ): Archiver {
		$this->validators = $validators;

		return $this;
	}

	/**
	 * Create a package artifact from the installed source.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Installed package instance.
	 * @param string  $version Release version.
	 *
	 * @throws PackageNotInstalled If the package is not installed.
	 * @throws FileOperationFailed If a temporary working directory can't be created.
	 * @throws FileOperationFailed If zip creation fails.
	 * @return string Absolute path to the artifact.
	 */
	public function archive_from_source( Package $package, string $version ): string {
		if ( ! $package->is_installed() ) {
			throw PackageNotInstalled::unableToArchiveFromSource( $package );
		}

		$release     = $package->get_release( $version );
		$remove_path = \dirname( $package->get_directory() );
		$excludes    = $this->get_excluded_files( $package, $release );

		$files = $package->get_files( $excludes );

		if ( $package instanceof LocalPlugin && $package->is_single_file() ) {
			$remove_path = $package->get_directory();
		}

		$filename = $this->get_absolute_path_to_tmpdir( $release->get_file() );

		if ( ! wp_mkdir_p( \dirname( $filename ) ) ) {
			throw FileOperationFailed::unableToCreateTemporaryDirectory( $filename );
		}

		$zip = new PclZip( $filename );

		$contents = $zip->create(
			$files,
			PCLZIP_OPT_REMOVE_PATH,
			$remove_path
		);

		if ( 0 === $contents ) {
			throw FileOperationFailed::unableToCreateZipFile( $filename );
		}

		$this->logger->info(
			'Archived "{package}" version {version} from source.',
			[
				'package' => $package->get_name(),
				'version' => $version,
				'logCategory' => 'package_archiver',
			]
		);

		return $filename;
	}

	/**
	 * Get the list of files to exclude from a package artifact.
	 *
	 * @since 0.5.2
	 *
	 * @param Package $package Installed package instance.
	 * @param Release $release Release instance.
	 *
	 * @return string[] Array of files to exclude.
	 */
	protected function get_excluded_files( Package $package, Release $release ): array {
		$dist_ignore_path = $package->get_directory() . '/.distignore';

		if ( ( ! $package instanceof LocalPlugin || ! $package->is_single_file() ) && file_exists( $dist_ignore_path ) ) {
			$excludes = $this->get_dist_ignored_files( $dist_ignore_path );
		} else {
			$excludes = [
				'.DS_Store',
				'.git',
				'node_modules',
			];
		}

		return apply_filters( 'pressody_records/archive_excludes', $excludes, $release );
	}

	/**
	 * Get the list of files to exclude based on a .distignore file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $dist_ignore_path Path to the .distignore file. File must already exist.
	 *
	 * @return string[]
	 */
	private function get_dist_ignored_files( string $dist_ignore_path ): array {
		$ignored_files = array();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$maybe_ignored_files = explode( PHP_EOL, file_get_contents( $dist_ignore_path ) );

		foreach ( $maybe_ignored_files as $file ) {
			$file = trim( $file );

			if ( ! $file || 0 === strpos( $file, '#' ) ) {
				continue;
			}

			$ignored_files[] = $file;
		}

		return $ignored_files;
	}

	/**
	 * Create a package artifact from a URL.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release instance.
	 *
	 * @throws FileDownloadFailed  If the artifact can't be downloaded.
	 * @throws FileOperationFailed If a temporary working directory can't be created.
	 * @throws LogicException If a registered server doesn't implement the server interface.
	 * @throws InvalidPackageArtifact If downloaded artifact cannot be validated.
	 * @throws FileOperationFailed If a temporary artifact can't be renamed.
	 * @return string Absolute path to the artifact.
	 */
	public function archive_from_url( Release $release ): string {
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$filename = $this->get_absolute_path_to_tmpdir( $release->get_file() );
		$tmpfname = $this->download_url( $release->get_source_url() );

		if ( ! wp_mkdir_p( \dirname( $filename ) ) ) {
			throw FileOperationFailed::unableToCreateTemporaryDirectory( $filename );
		}

		foreach ( $this->validators as $validator ) {
			if ( ! $validator instanceof ArtifactValidator ) {
				throw new LogicException( 'Artifact validators must implement \Pressody\Records\Validator\ArtifactValidator.' );
			}

			if ( ! $validator->validate( $tmpfname, $release ) ) {
				throw new InvalidPackageArtifact( "Unable to validate {$tmpfname} as a zip archive." );
			}
		}

		if ( ! rename( $tmpfname, $filename ) ) {
			throw FileOperationFailed::unableToRenameTemporaryArtifact( $filename, $tmpfname );
		}

		$this->logger->info(
			'Archived "{package}" version {version} from URL.',
			[
				'package' => $release->get_package()->get_name(),
				'version' => $release->get_version(),
				'logCategory' => 'package_archiver',
			]
		);

		return $filename;
	}

	/**
	 * Given an URL download it to a temporary location and provide the path to the resulting file.
	 *
	 * @param string $url
	 *
	 * @throws FileOperationFailed
	 * @return string The temporary file path.
	 */
	public function download_url( string $url ): string {
		$url = apply_filters( 'pressody_records/package_download_url', $url );

		// Since this is a local URL to a file, we don't need to download, just to create a temporary copy.
		if ( $this->is_local_url( $url ) && $path = $this->local_url_to_path( $url ) ) {
			$url_filename = basename( $path );
			$tmpfname = wp_tempnam( $url_filename );
			if ( ! $tmpfname ) {
				$this->logger->error(
					'Could not create Temporary file for file {filename} from URL {url}.',
					[
						'filename' => $url_filename,
						'url'   => $url,
						'logCategory' => 'package_archiver',
					]
				);

				throw FileDownloadFailed::forUrl( $url );
			}

			if ( false === copy( $path, $tmpfname ) ) {
				$this->logger->error(
					'Could not copy file {path} to the Temporary file {tmpfname}.',
					[
						'path' => $path,
						'tmpfname'   => $tmpfname,
						'logCategory' => 'package_archiver',
					]
				);

				throw FileDownloadFailed::forUrl( $url );
			}

			// Return the path to the temporary file.
			return $tmpfname;
		}

		// Allow others to hook-in just before the download.
		do_action( 'pressody_records/download_url_before', $url );

		$tmpfname = download_url( $url );

		// Allow others to hook-in just after the download.
		do_action( 'pressody_records/download_url_after', $url );

		if ( is_wp_error( $tmpfname ) ) {
			$this->logger->error(
				'Download failed.',
				[
					'error' => $tmpfname,
					'url'   => $url,
					'logCategory' => 'package_archiver',
				]
			);

			throw FileDownloadFailed::forUrl( $url );
		}

		// Return the path to the temporary file.
		return $tmpfname;
	}

	/**
	 * Given an URL determine if it is a local one (has the same host as the WP install).
	 *
	 * @see wp_http_validate_url()
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function is_local_url( string $url ): bool {
		$original_url = $url;
		$url          = wp_kses_bad_protocol( $url, array( 'http', 'https' ) );
		if ( ! $url || strtolower( $url ) !== strtolower( $original_url ) ) {
			return false;
		}

		$parsed_url = parse_url( $url );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			return false;
		}

		$parsed_home = parse_url( get_option( 'home' ) );
		if ( isset( $parsed_home['host'] ) ) {
			return strtolower( $parsed_home['host'] ) === strtolower( $parsed_url['host'] );
		}

		return false;
	}

	/**
	 * Given a local file URL convert it to an absolute path.
	 *
	 * @see wp_http_validate_url()
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function local_url_to_path( string $url ): string {
		$parsed_url = parse_url( $url );

		if ( empty( $parsed_url['path'] ) ) {
			return '';
		}

		$file = ABSPATH . ltrim( $parsed_url['path'], '/' );
		if ( file_exists( $file ) ) {
			return $file;
		}

		return '';
	}

	/**
	 * Retrieve the absolute path to a file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Optional. Relative path.
	 *
	 * @return string
	 */
	protected function get_absolute_path_to_tmpdir( string $path = '' ): string {
		return \trailingslashit( \get_temp_dir() ) . 'pressody_records/' . ltrim( $path, '/' );
	}
}
