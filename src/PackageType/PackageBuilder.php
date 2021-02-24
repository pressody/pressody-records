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

use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\PackageManager;
use Psr\Log\LoggerInterface;
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
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Create a builder for installed packages.
	 *
	 * @since 0.1.0
	 *
	 * @param Package         $package         Package instance to build.
	 * @param PackageManager  $package_manager Packages manager.
	 * @param ReleaseManager  $release_manager Release manager.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		Package $package,
		PackageManager $package_manager,
		ReleaseManager $release_manager,
		LoggerInterface $logger
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
		$this->logger          = $logger;
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

		// Attempt to cache any releases not cached yet. But only for managed packages.
		foreach ( $this->releases as $key => $release ) {
			if ( $this->package->is_managed() ) {
				try {
					// Once the release is successfully archived (cached), it is transformed so we need to overwrite.
					$this->package->set_release( $this->release_manager->archive( $release ) );
				} catch ( PixelgradeltRecordsException $e ) {
					$this->logger->error(
						'Error archiving {package}.',
						[
							'exception' => $e,
							'package'   => $this->package->get_name(),
						]
					);
				}
			}
		}

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
	 * @param string $source_type Package source type.
	 *
	 * @return $this
	 */
	public function set_source_type( string $source_type ): self {
		return $this->set( 'source_type', $source_type );
	}

	/**
	 * Set the source name (in the form vendor/name).
	 *
	 * @since 0.1.0
	 *
	 * @param string $source_name Package source name.
	 *
	 * @return $this
	 */
	public function set_source_name( string $source_name ): self {
		return $this->set( 'source_name', $source_name );
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
	 * Set is this package is managed by us.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_managed
	 * @return $this
	 */
	public function set_is_managed( bool $is_managed ): self {
		return $this->set( 'is_managed', $is_managed );
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
	 * Fill (missing) package details from the PackageManager if this is a managed package (via CPT).
	 *
	 * @since 0.1.0
	 *
	 * @param int   $post_id Optional. The package post ID to retrieve data for. Leave empty and provide $args to query.
	 * @param array $args Optional. Args used to query for a managed package if the post ID failed to retrieve data.
	 *
	 * @return $this
	 */
	public function from_manager( int $post_id = 0, array $args = [] ): self {
		$package_data = $this->package_manager->get_package_id_data( $post_id );
		// If we couldn't fetch package data by the post ID, try via the args.
		if ( empty( $package_data ) ) {
			$package_data = $this->package_manager->get_managed_package_data_by( $args );
		}
		// No data, no play.
		if ( empty( $package_data ) ) {
			// Mark this package as not being managed by us, yet.
			$this->set_is_managed( false );

			return $this;
		}

		// Since we have data, it is a managed package.
		$this->set_is_managed( true );

		$this->from_package_data( $package_data );

		return $this;
	}

	/**
	 * Set properties from a package data array.
	 *
	 * @since 0.1.0
	 *
	 * @param array $package_data Package data.
	 * @return $this
	 */
	public function from_package_data( array $package_data ): self {
		if ( empty( $this->package->get_name() ) && ! empty( $package_data['name'] ) ) {
			$this->set_name( $package_data['name'] );
		}

		if ( empty( $this->package->get_slug() ) && ! empty( $package_data['slug'] ) ) {
			$this->set_slug( $package_data['slug'] );
		}

		if ( empty( $this->package->get_type() ) && ! empty( $package_data['type'] ) ) {
			$this->set_type( $package_data['type'] );
		}

		if ( empty( $this->package->get_source_type() ) && ! empty( $package_data['source_type'] ) ) {
			$this->set_source_type( $package_data['source_type'] );
		}

		if ( empty( $this->package->get_source_name() ) && ! empty( $package_data['source_name'] ) ) {
			$this->set_source_name( $package_data['source_name'] );
		}

		if ( empty( $this->package->get_authors() ) && ! empty( $package_data['authors'] ) ) {
			$this->set_authors( $package_data['authors'] );
		}

		if ( empty( $this->package->get_homepage() ) && ! empty( $package_data['homepage'] ) ) {
			$this->set_homepage( $package_data['homepage'] );
		}

		if ( empty( $this->package->get_description() ) && ! empty( $package_data['description'] ) ) {
			$this->set_description( $package_data['description'] );
		}

		if ( empty( $this->package->get_license() ) && ! empty( $package_data['license'] ) ) {
			$this->set_license( $package_data['license'] );
		}

		if ( empty( $this->package->get_keywords() ) && ! empty( $package_data['keywords'] ) ) {
			$this->set_keywords( $package_data['keywords'] );
		}

		return $this;
	}

	/**
	 * Fill (missing) package details from header data.
	 *
	 * @since 0.1.0
	 *
	 * @param array $header_data The package (plugin or theme) header data.
	 *
	 * @return $this
	 */
	public function from_header_data( array $header_data ): self {

		if ( empty( $this->package->get_name() ) && ! empty( $header_data['Name'] ) ) {
			$this->set_name( $header_data['Name'] );
		}

		// Treat both theme and plugin headers.
		if ( empty( $this->package->get_homepage() ) ) {
			if ( ! empty( $header_data['ThemeURI'] ) ) {
				$this->set_homepage( $header_data['ThemeURI'] );
			} else if ( ! empty( $header_data['PluginURI'] ) ) {
				$this->set_homepage( $header_data['PluginURI'] );
			}
		}

		if ( empty( $this->package->get_authors() ) && ! empty( $header_data['Author'] ) ) {
			$this->set_authors( [
				[
					'name'     => $header_data['Author'],
					'homepage' => $header_data[ 'AuthorURI'],
				],
			] );
		}

		if ( empty( $this->package->get_description() ) && ! empty( $header_data['Description'] ) ) {
			$this->set_description( $header_data['Description'] );
		}

		if ( empty( $this->package->get_license() ) && ! empty( $header_data['License'] ) ) {
			$this->set_license( $header_data['License'] );
		}

		if ( empty( $this->package->get_keywords() ) && ! empty( $header_data['Tags'] ) ) {
			$this->set_keywords( $header_data['Tags'] );
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
			->set_slug( $package->get_slug() )
			->set_type( $package->get_type() )
			->set_source_type( $package->get_source_type() )
			->set_source_name( $package->get_source_name() )
			->set_authors( $package->get_authors() )
			->set_homepage( $package->get_homepage() )
			->set_description( $package->get_description() )
			->set_keywords( $package->get_keywords() )
			->set_license( $package->get_license() );

		foreach ( $package->get_releases() as $release ) {
			$this->add_release( $release->get_version(), $release->get_source_url() );
		}

		return $this;
	}

	/**
	 * Add cached releases to a package.
	 *
	 * @since 0.1.0
	 *
	 * @return $this
	 */
	public function add_cached_releases(): PackageBuilder {
		$releases = $this->release_manager->all_cached( $this->package );

		foreach ( $releases as $release ) {
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
