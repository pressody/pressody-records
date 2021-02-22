<?php
/**
 * Package builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

use PixelgradeLT\Records\PackageManager;
use ReflectionClass;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;

/**
 * Package builder class.
 *
 * @since 0.1.0
 */
class PackageBuilder {
	/**
	 * Reflection class instance.
	 *
	 * @var ReflectionClass
	 */
	protected $class;

	/**
	 * Package instance.
	 *
	 * @var Package
	 */
	protected $package;

	/**
	 * Package releases.
	 *
	 * @var Release[]
	 */
	protected $releases = [];

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected $release_manager;

	/**
	 * Create a builder for installed packages.
	 *
	 * @since 0.1.0
	 *
	 * @param Package        $package         Package instance to build.
	 * @param PackageManager $package_manager Packages manager.
	 * @param ReleaseManager $release_manager Release manager.
	 */
	public function __construct(
		Package $package,
		PackageManager $package_manager,
		ReleaseManager $release_manager
	) {
		$this->package = $package;
		try {
			$this->class = new ReflectionClass( $package );
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( \ReflectionException $e ) {
			// noop.
		}

		$this->package_manager = $package_manager;
		$this->release_manager = $release_manager;
	}

	/**
	 * Finalize the package build.
	 *
	 * @since 0.1.0
	 *
	 * @return Package
	 */
	public function build(): Package {
		uasort(
			$this->releases,
			function( Release $a, Release $b ) {
				return version_compare( $b->get_version(), $a->get_version() );
			}
		);

		$this->set( 'releases', $this->releases );

		return $this->package;
	}

	/**
	 * Set the name.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Package name.
	 * @return $this
	 */
	public function set_name( string $name ): self {
		return $this->set( 'name', $name );
	}

	/**
	 * Set the slug.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Slug.
	 * @return $this
	 */
	public function set_slug( string $slug ): self {
		return $this->set( 'slug', $slug );
	}

	/**
	 * Set the type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Package type.
	 * @return $this
	 */
	public function set_type( string $type ): self {
		return $this->set( 'type', $type );
	}

	/**
	 * Set the source type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $source_type Package type.
	 *
	 * @return $this
	 */
	public function set_source_type( string $source_type ): self {
		return $this->set( 'source_type', $source_type );
	}

	/**
	 * Set the authors.
	 *
	 * @since 0.1.0
	 *
	 * @param array $authors Authors.
	 * @return $this
	 */
	public function set_authors( array $authors ): self {
		return $this->set( 'authors', $authors );
	}

	/**
	 * Set the description.
	 *
	 * @since 0.1.0
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function set_description( string $description ): self {
		return $this->set( 'description', $description );
	}

	/**
	 * Set the keywords.
	 *
	 * @since 0.1.0
	 *
	 * @param string|string[] $keywords
	 * @return $this
	 */
	public function set_keywords( $keywords ): self {
		return $this->set( 'keywords', $this->normalize_keywords( $keywords ) );
	}

	/**
	 * Normalize a given set of keywords.
	 *
	 * @since 0.1.0
	 *
	 * @param string|string[] $keywords
	 * @return array
	 */
	protected function normalize_keywords( $keywords ): array {
		$delimiter = ',';
		// If by any chance we are given an array, sanitize and return it.
		if ( is_array( $keywords ) ) {
			foreach ( $keywords as $key => $keyword ) {
				// Reject non-string or empty entries.
				if ( ! is_string( $keyword ) || empty( $keyword ) ) {
					unset( $keywords[ $key ] );
					continue;
				}

				$keywords[ $key ] = trim( sanitize_text_field( $keyword ) );
			}

			return $keywords;
		}

		// Anything else we coerce to a string.
		if ( ! is_string( $keywords ) ) {
			$keywords = (string) $keywords;
		}

		// Make sure we trim it.
		$keywords = trim( $keywords );

		// Bail on empty string.
		if ( empty( $keywords ) ) {
			return [];
		}

		// Return the whole string as an element if the delimiter is missing.
		if ( false === strpos( $keywords, $delimiter ) ) {
			return [ trim( sanitize_text_field( $keywords ) ) ];
		}

		$keywords = explode( $delimiter, $keywords );
		foreach ( $keywords as $key => $keyword ) {
			$keywords[ $key ] = trim( sanitize_text_field( $keyword ) );

			if ( empty( $keywords[ $key ] ) ) {
				unset( $keywords[ $key ] );
			}
		}

		return $keywords;
	}

	/**
	 * Set the homepage URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url URL.
	 *
	 * @return $this
	 */
	public function set_homepage( string $url ): self {
		return $this->set( 'homepage', $url );
	}

	/**
	 * Set the license.
	 *
	 * @since 0.1.0
	 *
	 * @param string $license
	 *
	 * @return $this
	 */
	public function set_license( string $license ): self {
		return $this->set( 'license', $this->normalize_license( $license ) );
	}

	/**
	 * We want to try and normalize the license to the SPDX format.
	 *
	 * @link https://spdx.org/licenses/
	 *
	 * @param string $license
	 *
	 * @return string
	 */
	protected function normalize_license( string $license ): string {
		$license = trim( $license );

		$tmp_license = strtolower( $license);

		if ( empty( $tmp_license ) ) {
			// Default to the WordPress license.
			return 'GPL-2.0-or-later';
		}

		// Handle the `GPL-2.0-or-later` license.
		if ( preg_match( '#(GNU\s*-?)?(General Public License|GPL)(\s*[-_v]*\s*)(2[.-]?0?\s*-?)(or\s*-?later|\+)#i', $tmp_license ) ) {
			return 'GPL-2.0-or-later';
		}

		// Handle the `GPL-2.0-only` license.
		if ( preg_match( '#(GNU\s*-?)?(General Public License|GPL)(\s*[-_v]*\s*)(2[.-]?0?\s*-?)(only)?#i', $tmp_license ) ) {
			return 'GPL-2.0-only';
		}

		// Handle the `GPL-3.0-or-later` license.
		if ( preg_match( '#(GNU\s*-?)?(General Public License|GPL)(\s*[-_v]*\s*)(3[.-]?0?\s*-?)(or\s*-?later|\+)#i', $tmp_license ) ) {
			return 'GPL-3.0-or-later';
		}

		// Handle the `GPL-3.0-only` license.
		if ( preg_match( '#(GNU\s*-?)?(General Public License|GPL)(\s*[-_v]*\s*)(3[.-]?0?\s*-?)(only)?#i', $tmp_license ) ) {
			return 'GPL-3.0-only';
		}

		// Handle the `MIT` license.
		if ( preg_match( '#(The\s*)?(MIT\s*)(License)?#i', $tmp_license ) ) {
			return 'MIT';
		}

		return $license;
	}

	/**
	 * Set a package's directory.
	 *
	 * @since 0.1.0
	 *
	 * @param string $directory Absolute path to the package directory.
	 * @return $this
	 */
	public function set_directory( string $directory ): self {
		return $this->set( 'directory', rtrim( $directory, '/' ) . '/' );
	}

	/**
	 * Set whether the package is installed.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_installed Whether the package is installed.
	 * @return $this
	 */
	public function set_installed( bool $is_installed ): self {
		return $this->set( 'is_installed', $is_installed );
	}

	/**
	 * Set the installed version.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version.
	 * @return $this
	 */
	public function set_installed_version( string $version ): self {
		return $this->set( 'installed_version', $version );
	}

	/**
	 * Fill (missing) package details from the PackageManager if this is a managed package (via CPT).
	 *
	 * @since 0.1.0
	 *
	 * @param int   $post_id Optional. The package post ID to retrieve data for. Leave empty and provide $args to query.
	 * @param array $args Optional. Args used to query for a managed package if the post ID failed to retrieve data.
	 *
	 * @return PluginBuilder
	 */
	public function from_manager( int $post_id = 0, array $args = [] ): self {
		$package_data = $this->package_manager->get_package_id_data( $post_id );
		// If we couldn't fetch package data by the post ID, try via the args.
		if ( empty( $package_data ) ) {
			$package_data = $this->package_manager->get_managed_package_data_by( $args );
		}
		// No data, no play.
		if ( empty( $package_data ) ) {
			return $this;
		}

		if ( ! empty( $package_data['name'] ) ) {
			$this->set_name( $package_data['name'] );
		}

		if ( ! empty( $package_data['type'] ) ) {
			$this->set_type( $package_data['type'] );
		}

		if ( ! empty( $package_data['source_type'] ) ) {
			$this->set_source_type( $package_data['source_type'] );
		}

		if ( ! empty( $package_data['slug'] ) ) {
			$this->set_slug( $package_data['slug'] );
		}

		if ( ! empty( $package_data['keywords'] ) ) {
			$this->set_keywords( $package_data['keywords'] );
		}

		if ( ! empty( $package_data['details'] ) ) {
			$this
				->set_authors( $package_data['details']['authors'] )
				->set_homepage( $package_data['details']['homepage'] )
				->set_description( $package_data['details']['description'] )
				->set_license( $package_data['details']['license'] );
		}

		if ( ! empty( $package_data['local_installed'] ) ) {
			$this->set_installed( $package_data['local_installed'] );
		}

		return $this;
	}

	/**
	 * Set properties from an existing package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 * @return $this
	 */
	public function with_package( Package $package ): self {
		$this
			->set_name( $package->get_name() )
			->set_type( $package->get_type() )
			->set_source_type( $package->get_source_type() )
			->set_slug( $package->get_slug() )
			->set_authors( $package->get_authors() )
			->set_homepage( $package->get_homepage() )
			->set_description( $package->get_description() )
			->set_keywords( $package->get_keywords() )
			->set_license( $package->get_license() );

		if ( in_array( $package->get_source_type(), [ 'local.theme', 'local.plugin'] ) && $package->is_installed() ) {
			$this
				->set_directory( $package->get_directory() )
				->set_installed_version( $package->get_installed_version() )
				->set_installed( $package->is_installed() );
		}

		foreach ( $package->get_releases() as $release ) {
			$this->add_release( $release->get_version(), $release->get_source_url() );
		}

		return $this;
	}

	/**
	 * Add a release.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version    Version.
	 * @param string $source_url Optional. Release source URL.
	 * @return $this
	 */
	public function add_release( string $version, string $source_url = '' ): self {
		$this->releases[ $version ] = new Release( $this->package, $version, $source_url );
		return $this;
	}

	/**
	 * Remove a release.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version.
	 * @return $this
	 */
	public function remove_release( string $version ): self {
		unset( $this->releases[ $version ] );
		return $this;
	}

	/**
	 * Add cached releases to a package.
	 *
	 * This must be called after setting the installed state and version for
	 * the package.
	 *
	 * @todo Rename this?
	 *
	 * @since 0.1.0
	 *
	 * @return $this
	 */
	public function add_cached_releases(): self {
		$releases = $this->release_manager->all( $this->package );

		if ( $this->package->is_installed() ) {
			// Add the installed version in case it hasn't been cached yet.
			$installed_version = $this->package->get_installed_version();
			if ( ! isset( $releases[ $installed_version ] ) ) {
				$releases[ $installed_version ] = new Release( $this->package, $installed_version );
			}

			// Add a pending update if one is available.
			$update = $this->get_package_update( $this->package );
			if ( $update instanceof Release ) {
				$releases[ $update->get_version() ] = $update;
			}
		}

		foreach ( $releases as $release ) {
			$this->add_release( $release->get_version(), $release->get_source_url() );
		}

		return $this;
	}

	/**
	 * Retrieve a release for a pending theme or plugin update.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package instance.
	 * @return null|Release
	 */
	protected function get_package_update( Package $package ): ?Release {
		$release = null;

		if ( $package instanceof LocalPlugin ) {
			$updates = get_site_transient( 'update_plugins' );
			if ( ! empty( $updates->response[ $package->get_basename() ]->package ) ) {
				$update  = $updates->response[ $package->get_basename() ];
				$release = new Release( $package, $update->new_version, (string) $update->package );
			}
		} elseif ( $package instanceof LocalTheme ) {
			$updates = get_site_transient( 'update_themes' );
			if ( ! empty( $updates->response[ $package->get_slug() ]['package'] ) ) {
				$update  = $updates->response[ $package->get_slug() ];
				$release = new Release( $package, $update['new_version'], (string) $update['package'] );
			}
		}

		return $release;
	}

	/**
	 * Set a property on the package instance.
	 *
	 * Uses the reflection API to assign values to protected properties of the
	 * package instance to make the returned instance immutable.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return $this
	 * @throws \ReflectionException If no property exists by that name.
	 */
	protected function set( $name, $value ): self {
		$property = $this->class->getProperty( $name );
		$property->setAccessible( true );
		$property->setValue( $this->package, $value );
		return $this;
	}
}
