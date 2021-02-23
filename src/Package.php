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
 * This is different from Composer\Package\PackageInterface. This is an interface for our internal use.
 *
 * @since 0.1.0
 */
interface Package {
	/**
	 * Retrieve the name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Retrieve the package type.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Retrieve the package source type.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_source_type(): string;

	/**
	 * Retrieve the package source name (in the form vendor/name).
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_source_name(): string;

	/**
	 * Retrieve the slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_slug(): string;

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
	 * Whether the package is managed by us.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_managed(): bool;

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
}
