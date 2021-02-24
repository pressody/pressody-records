<?php
/**
 * Base package.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

use PixelgradeLT\Records\Exception\InvalidReleaseVersion;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\Release;

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
	protected $name = '';

	/**
	 * Package type.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Package source type.
	 *
	 * @var string
	 */
	protected $source_type = '';

	/**
	 * Package source name (in the form vendor/name).
	 *
	 * @var string
	 */
	protected $source_name = '';

	/**
	 * Package slug.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Package authors.
	 *
	 * @var array
	 */
	protected $authors = [];

	/**
	 * Description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Package homepage URL.
	 *
	 * @var string
	 */
	protected $homepage = '';

	/**
	 * Package license.
	 *
	 * @var string
	 */
	protected $license = '';

	/**
	 * Package keywords.
	 *
	 * @var string[]
	 */
	protected $keywords = [];

	/**
	 * Is managed package?
	 *
	 * @var string
	 */
	protected $is_managed = false;

	/**
	 * Releases.
	 *
	 * @var Release[]
	 */
	protected $releases = [];

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
	 * Whether a property exists.
	 *
	 * Checks for an accessor method rather than the actual property.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Property name.
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
