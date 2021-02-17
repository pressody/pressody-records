<?php
/**
 * Package interface.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use PixelgradeLT\Records\Exception\InvalidReleaseVersion;

/**
 * Package interface.
 *
 * @since 0.1.0
 */
interface Package {
	/**
	 * Retrieve the authors.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_authors(): array;

	/**
	 * Retrieve the description.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Retrieve the homepage URL.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_homepage(): string;

	/**
	 * Retrieve the pacakge license.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_license(): string;

	/**
	 * Retrieve the package directory.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_directory(): string;

	/**
	 * Retrieve the list of files in the package.
	 *
	 * @since 0.1.0
	 *
	 * @param array $excludes Optional. Array of file names to exclude.
	 * @return array
	 */
	public function get_files( array $excludes = [] ): array;

	/**
	 * Retrieve the name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Retrieve the path to a file in the package.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Optional. Path relative to the package root.
	 * @return string
	 */
	public function get_path( string $path = '' ): string;

	/**
	 * Retrieve the slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Retrieve the package type.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Whether the package is installed.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_installed(): bool;

	/**
	 * Whether the package has any releases.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function has_releases(): bool;

	/**
	 * Retrieve a release by version.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version string.
	 * @throws InvalidReleaseVersion If the version is invalid.
	 * @return Release
	 */
	public function get_release( string $version ): Release;

	/**
	 * Retrieve releases.
	 *
	 * @since 0.1.0
	 *
	 * @return Release[]
	 */
	public function get_releases(): array;

	/**
	 * Retrieve the installed version.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_installed_version(): string;
	/**
	 * Retrieve the installed release.
	 *
	 * @since 0.1.0
	 *
	 * @return Release
	 */
	public function get_installed_release(): Release;

	/**
	 * Whether a given release is the currently installed version.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release.
	 * @return bool
	 */
	public function is_installed_release( Release $release ): bool;

	/**
	 * Retrieve the version for the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_version(): string;

	/**
	 * Retrieve the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return Release
	 */
	public function get_latest_release(): Release;

	/**
	 * Retrieve a link to download the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_download_url(): string;

	/**
	 * Whether an update is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_update_available(): bool;
}
