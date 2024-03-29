<?php
/**
 * Base package.
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

namespace Pressody\Records\PackageType;

use Pressody\Records\Exception\InvalidPackage;
use Pressody\Records\Exception\InvalidReleaseVersion;
use Pressody\Records\Package;
use Pressody\Records\Release;

/**
 * Base package class.
 *
 * @since 0.1.0
 */
class BasePackage implements \ArrayAccess, Package {
	/**
	 * Package name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Package type.
	 *
	 * @see PackageTypes
	 *
	 * @var string
	 */
	protected string $type = '';

	/**
	 * Package source type.
	 *
	 * @var string
	 */
	protected string $source_type = '';

	/**
	 * Package source name (in the form vendor/name).
	 *
	 * @var string
	 */
	protected string $source_name = '';

	/**
	 * Package slug.
	 *
	 * @var string
	 */
	protected string $slug = '';

	/**
	 * Package authors, each, potentially, having: `name`, `email`, `homepage`, `role`.
	 *
	 * @var array
	 */
	protected array $authors = [];

	/**
	 * Description.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Package homepage URL.
	 *
	 * @var string
	 */
	protected string $homepage = '';

	/**
	 * Package license.
	 *
	 * @var string
	 */
	protected string $license = '';

	/**
	 * Package keywords.
	 *
	 * @var string[]
	 */
	protected array $keywords = [];

	/**
	 * Package requires at least WordPress version (from the package headers/readme).
	 *
	 * @var string
	 */
	protected string $requires_at_least_wp = '';

	/**
	 * Package tested up to WordPress version (from the package headers/readme).
	 *
	 * @var string
	 */
	protected string $tested_up_to_wp = '';

	/**
	 * Package requires PHP version (from the package headers/readme).
	 *
	 * @var string
	 */
	protected string $requires_php = '';

	/**
	 * Is managed package?
	 *
	 * @var bool
	 */
	protected bool $is_managed = false;

	/**
	 * Managed package post ID if this is a managed package.
	 *
	 * @var int
	 */
	protected int $managed_post_id = 0;

	/**
	 * Managed package post ID string hash, if this is a managed package.
	 *
	 * @var string
	 */
	protected string $managed_post_id_hash = '';

	/**
	 * Managed package visibility.
	 *
	 * @var string
	 */
	protected string $visibility = '';

	/**
	 * Managed packages required by this package.
	 *
	 * @var array
	 */
	protected array $required_packages = [];

	/**
	 * Managed packages replaced by this package.
	 *
	 * @var array
	 */
	protected array $replaced_packages = [];

	/**
	 * A Composer config `require` entry.
	 *
	 * This will be merged with the required packages and other hard-coded packages to generate the final require config.
	 *
	 * @var array
	 */
	protected array $composer_require = [];

	/**
	 * Releases.
	 *
	 * @var Release[]
	 */
	protected array $releases = [];

	/**
	 * Magic setter.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 */
	public function __set( string $name, $value ) {
		// Don't allow undefined properties to be set.
	}

	/**
	 * Retrieve the name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Retrieve the package type.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Retrieve the package source type.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_source_type(): string {
		return $this->source_type;
	}

	/**
	 * Retrieve the package source name (in the form vendor/name).
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_source_name(): string {
		return $this->source_name;
	}

	/**
	 * Retrieve the slug.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Retrieve the authors.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_authors(): array {
		return $this->authors;
	}

	/**
	 * Retrieve the description.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Retrieve the homepage URL.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_homepage(): string {
		return $this->homepage;
	}

	/**
	 * Retrieve the license.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_license(): string {
		return $this->license;
	}

	/**
	 * Retrieve the keywords.
	 *
	 * @since 0.1.0
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return $this->keywords;
	}

	/**
	 * Retrieve the requires at least WP version.
	 *
	 * @since 0.5.0
	 *
	 * @return string
	 */
	public function get_requires_at_least_wp(): string {
		return $this->requires_at_least_wp;
	}

	/**
	 * Retrieve the tested up to WP version.
	 *
	 * @since 0.5.0
	 *
	 * @return string
	 */
	public function get_tested_up_to_wp(): string {
		return $this->tested_up_to_wp;
	}

	/**
	 * Retrieve the required PHP version.
	 *
	 * @since 0.5.0
	 *
	 * @return string
	 */
	public function get_requires_php(): string {
		return $this->requires_php;
	}

	/**
	 * Whether the package is managed by us.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_managed(): bool {
		return $this->is_managed;
	}

	/**
	 * Alias for self::is_managed().
	 *
	 * @since 0.5.0
	 *
	 * @return bool
	 */
	public function get_is_managed(): bool {
		return $this->is_managed();
	}

	/**
	 * Retrieve the managed post ID.
	 *
	 * @since 0.5.0
	 *
	 * @return int
	 */
	public function get_managed_post_id(): int {
		return $this->managed_post_id;
	}

	/**
	 * Retrieve the managed post ID string hash.
	 *
	 * @since 0.9.0
	 *
	 * @return string
	 */
	public function get_managed_post_id_hash(): string {
		return $this->managed_post_id_hash;
	}

	/**
	 * Get the visibility status of the package (public, draft, private).
	 *
	 * @since 0.9.0
	 *
	 * @return string The visibility status of the package. One of: public, draft, private.
	 */
	public function get_visibility(): string {
		return $this->visibility;
	}

	/**
	 * Retrieve the managed required packages.
	 *
	 * @since 0.8.0
	 *
	 * @return array
	 */
	public function get_required_packages(): array {
		return $this->required_packages;
	}

	/**
	 * Whether the package has any managed required packages.
	 *
	 * @since 0.8.0
	 *
	 * @return bool
	 */
	public function has_required_packages(): bool {
		return ! empty( $this->required_packages );
	}

	/**
	 * Retrieve the managed replaced packages.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public function get_replaced_packages(): array {
		return $this->replaced_packages;
	}

	/**
	 * Whether the package has any managed replaced packages.
	 *
	 * @since 0.9.0
	 *
	 * @return bool
	 */
	public function has_replaced_packages(): bool {
		return ! empty( $this->replaced_packages );
	}

	/**
	 * Retrieve the Composer config `require` entry.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public function get_composer_require(): array {
		return $this->composer_require;
	}

	/**
	 * Check if the package has a source constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function has_source_constraint(): bool {
		return false;
	}

	/**
	 * Whether the package has any releases.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function has_releases(): bool {
		return ! empty( $this->releases );
	}

	/**
	 * Retrieve a release by version.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version string.
	 *
	 * @throws InvalidReleaseVersion If the version is invalid.
	 * @return Release
	 */
	public function get_release( string $version ): Release {
		if ( ! isset( $this->releases[ $version ] ) ) {
			throw InvalidReleaseVersion::fromVersion( $version, $this->get_name() );
		}

		return $this->releases[ $version ];
	}

	/**
	 * Set a release by version.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release
	 */
	public function set_release( Release $release ) {
		$this->releases[ $release->get_version() ] = $release;
	}

	/**
	 * Retrieve releases.
	 *
	 * @since 0.1.0
	 *
	 * @return Release[]
	 */
	public function get_releases(): array {
		return $this->releases;
	}

	/**
	 * Retrieve the version for the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_version(): string {
		return $this->get_latest_release()->get_version();
	}

	/**
	 * Retrieve the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return Release
	 */
	public function get_latest_release(): Release {
		if ( $this->has_releases() ) {
			return reset( $this->releases );
		}

		throw InvalidReleaseVersion::hasNoReleases( $this->get_name() );
	}

	/**
	 * Retrieve a link to download the latest release.
	 *
	 * @since 0.1.0
	 *
	 * @throws InvalidReleaseVersion If the package doesn't have any releases.
	 * @return string
	 */
	public function get_latest_download_url(): string {
		$url = $this->get_latest_release()->get_download_url();
		$url = substr( $url, 0, strrpos( $url, '/' ) );

		return $url . '/latest';
	}

	/**
	 * Retrieve the relative path for the package store directory.
	 *
	 * This will be used by the storage logic to organize the package releases and such.
	 *
	 * @since 0.9.0
	 *
	 * @throws InvalidPackage If the package doesn't the needed details.
	 * @return string
	 */
	public function get_store_dir(): string {
		if ( empty( $this->type ) || empty( $this->slug ) ) {
			throw InvalidPackage::missingDetailsForStoreDir( $this );
		}

		return \path_join( $this->type, $this->slug );
	}

	/**
	 * Whether a property exists.
	 *
	 * Checks for an accessor method rather than the actual property.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Property name.
	 *
	 * @return bool
	 */
	public function offsetExists( $name ): bool {
		return method_exists( $this, "get_{$name}" );
	}

	/**
	 * Retrieve a property value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 */
	public function offsetGet( $name ) {
		$method = "get_{$name}";

		if ( ! method_exists( $this, $method ) ) {
			return null;
		}

		return $this->$method();
	}

	/**
	 * Set a property value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name  Property name.
	 * @param array  $value Property value.
	 */
	public function offsetSet( $name, $value ) {
		// Prevent properties from being modified.
	}

	/**
	 * Unset a property.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Property name.
	 */
	public function offsetUnset( $name ) {
		// Prevent properties from being modified.
	}
}
