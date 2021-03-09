<?php
/**
 * Base package builder.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use PixelgradeLT\Records\Exception\PixelgradeltRecordsException;
use PixelgradeLT\Records\Logger;
use PixelgradeLT\Records\PackageManager;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;

/**
 * Base package builder class.
 *
 * The base package is the simplest package we handle.
 *
 * @since 0.1.0
 */
class BasePackageBuilder {
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
		// Make sure that we enforce any conditions or constraints on the releases list before we set them in the package.
		$this->prune_releases();

		uasort(
			$this->releases,
			function ( Release $a, Release $b ) {
				return version_compare( $b->get_version(), $a->get_version() );
			}
		);

		$this->set( 'releases', $this->releases );

		$this->cache_releases();

		return $this->package;
	}

	/**
	 * Set the name.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name Package name.
	 *
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
	 *
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
	 *
	 * @return $this
	 */
	public function set_slug( string $slug ): self {
		return $this->set( 'slug', $slug );
	}

	/**
	 * Set the authors.
	 *
	 * @since 0.1.0
	 *
	 * @param array $authors Authors.
	 *
	 * @return $this
	 */
	public function set_authors( array $authors ): self {
		return $this->set( 'authors', $this->normalize_authors( $authors ) );
	}

	protected function normalize_authors( array $authors ): array {
		$authors = array_map( function ( $author ) {
			if ( is_string( $author ) && ! empty( $author ) ) {
				return [ 'name' => trim( $author ) ];
			}

			if ( is_array( $author ) ) {
				// Make sure only the fields we are interested in are left.
				$accepted_keys = array_fill_keys( [ 'name', 'email', 'homepage', 'role' ], '' );
				$author = array_replace( $accepted_keys, array_intersect_key( $author, $accepted_keys ) );

				// Remove falsy author entries.
				$author = array_filter( $author );

				// We need the name not to be empty.
				if ( empty( $author['name'] ) ) {
					return false;
				}

				return $author;
			}

			// We have an invalid author.
			return false;

		}, $authors );

		// Filter out falsy authors.
		$authors = array_filter( $authors );

		// We don't keep the array keys.
		$authors = array_values( $authors );

		return $authors;
	}

	/**
	 * Set the description.
	 *
	 * @since 0.1.0
	 *
	 * @param string $description Description.
	 *
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
	 *
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
	 *
	 * @return array
	 */
	protected function normalize_keywords( $keywords ): array {
		$delimiter = ',';
		// If by any chance we are given an array, sanitize and return it.
		if ( is_array( $keywords ) ) {
			foreach ( $keywords as $key => $keyword ) {
				// Reject non-string or empty entries.
				if ( ! is_string( $keyword ) ) {
					unset( $keywords[ $key ] );
					continue;
				}

				$keywords[ $key ] = trim( \sanitize_text_field( $keyword ) );
			}

			// We don't keep the array keys.
			$keywords = array_values( $keywords );

			// We don't keep the falsy keywords.
			$keywords = array_filter( $keywords );

			// We don't keep duplicates.
			$keywords = array_unique( $keywords );

			// Sort the keywords alphabetically.
			sort( $keywords );

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
			return [ trim( \sanitize_text_field( $keywords ) ) ];
		}

		$keywords = explode( $delimiter, $keywords );
		foreach ( $keywords as $key => $keyword ) {
			$keywords[ $key ] = trim( \sanitize_text_field( $keyword ) );

			if ( empty( $keywords[ $key ] ) ) {
				unset( $keywords[ $key ] );
			}
		}

		// We don't keep the array keys.
		$keywords = array_values( $keywords );

		// We don't keep the falsy keywords.
		$keywords = array_filter( $keywords );

		// We don't keep duplicates.
		$keywords = array_unique( $keywords );

		// Sort the keywords alphabetically.
		sort( $keywords );

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

		$tmp_license = strtolower( $license );

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
	 * Set the requires at least WP version, but only if the given version string is valid.
	 *
	 * @since 0.5.0
	 *
	 * @param string $version Version.
	 * @return $this
	 */
	public function set_requires_at_least_wp( string $version ): self {
		if ( ! $this->check_version_validity( $version ) ) {
			return $this;
		}

		return $this->set( 'requires_at_least_wp', $version );
	}

	/**
	 * Set the tested up to WP version, but only if the given version string is valid.
	 *
	 * @since 0.5.0
	 *
	 * @param string $version Version.
	 * @return $this
	 */
	public function set_tested_up_to_wp( string $version ): self {
		if ( ! $this->check_version_validity( $version ) ) {
			return $this;
		}

		return $this->set( 'tested_up_to_wp', $version );
	}

	/**
	 * Set the the required PHP version, but only if the given version string is valid.
	 *
	 * @since 0.5.0
	 *
	 * @param string $version Version.
	 * @return $this
	 */
	public function set_requires_php( string $version ): self {
		if ( ! $this->check_version_validity( $version ) ) {
			return $this;
		}

		return $this->set( 'requires_php', $version );
	}

	/**
	 * Set if this package is managed by us.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_managed
	 *
	 * @return $this
	 */
	public function set_is_managed( bool $is_managed ): self {
		return $this->set( 'is_managed', $is_managed );
	}

	/**
	 * Fill (missing) package details from the PackageManager if this is a managed package (via CPT).
	 *
	 * @since 0.1.0
	 *
	 * @param int   $post_id Optional. The package post ID to retrieve data for. Leave empty and provide $args to query.
	 * @param array $args    Optional. Args used to query for a managed package if the post ID failed to retrieve data.
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
	 *
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
			// Make sure that the license is a single string, not an array of strings.
			// Packagist.org offers a list of license in case a project is dual or triple licensed.
			if ( is_array( $package_data['license'] ) ) {
				$package_data['license'] = reset( $package_data['license'] );
			}
			$this->set_license( $package_data['license'] );
		}

		if ( empty( $this->package->get_keywords() ) && ! empty( $package_data['keywords'] ) ) {
			$this->set_keywords( $package_data['keywords'] );
		}

		if ( empty( $this->package->get_requires_at_least_wp() ) && ! empty( $package_data['requires_at_least_wp'] ) ) {
			$this->set_requires_at_least_wp( $package_data['requires_at_least_wp'] );
		}

		if ( empty( $this->package->get_tested_up_to_wp() ) && ! empty( $package_data['tested_up_to_wp'] ) ) {
			$this->set_tested_up_to_wp( $package_data['tested_up_to_wp'] );
		}

		if ( empty( $this->package->get_requires_php() ) && ! empty( $package_data['requires_php'] ) ) {
			$this->set_requires_php( $package_data['requires_php'] );
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
					'homepage' => $header_data['AuthorURI'],
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

		if ( empty( $this->package->get_requires_at_least_wp() ) && ! empty( $header_data['Requires at least'] ) ) {
			$this->set_requires_at_least_wp( $header_data['Requires at least'] );
		}

		if ( empty( $this->package->get_tested_up_to_wp() ) && ! empty( $header_data['Tested up to'] ) ) {
			$this->set_tested_up_to_wp( $header_data['Tested up to'] );
		}

		if ( empty( $this->package->get_requires_php() ) && ! empty( $header_data['Requires PHP'] ) ) {
			$this->set_requires_php( $header_data['Requires PHP'] );
		}

		return $this;
	}

	/**
	 * Fill (missing) package details from the package's readme file (generally intended for WordPress.org display).
	 *
	 * If the readme file is missing, nothing is done.
	 *
	 * @since 0.5.0
	 *
	 * @param string $directory   The absolute path to the package files directory.
	 * @param array  $readme_data Optional. Array of readme data.
	 *
	 * @throws \ReflectionException
	 * @return LocalThemeBuilder
	 */
	public function from_readme( string $directory, array $readme_data = [] ): self {
		if ( empty( $readme_data ) ) {
			$readme_file = trailingslashit( $directory ) . 'readme.txt';
			if ( ! file_exists( $readme_file ) ) {
				// Try a readme.md.
				$readme_file = trailingslashit( $directory ) . 'readme.md';
			}
			if ( file_exists( $readme_file ) ) {
				$readme_data = $this->package_manager->get_wordpress_readme_parser()->parse_readme( $readme_file );
			}
		}

		return $this->from_readme_data( $readme_data );
	}

	/**
	 * Fill (missing) package details from readme data.
	 *
	 * @since 0.5.0
	 *
	 * @param array $readme_data The package (plugin or theme) readme data.
	 *
	 * @return $this
	 */
	public function from_readme_data( array $readme_data ): self {

		if ( empty( $this->package->get_name() ) && ! empty( $readme_data['name'] ) ) {
			$this->set_name( $readme_data['name'] );
		}

		if ( empty( $this->package->get_authors() ) && ! empty( $readme_data['contributors'] ) ) {
			$converted = array_map( function ( $contributor ) {
				return [ 'name' => $contributor ];
			}, $readme_data['contributors'] );

			$this->set_authors( $converted );
		}

		if ( empty( $this->package->get_description() ) && ! empty( $readme_data['short_description'] ) ) {
			$this->set_description( $readme_data['short_description'] );
		}

		if ( empty( $this->package->get_license() ) && ! empty( $readme_data['license'] ) ) {
			$this->set_license( $readme_data['license'] );
		}

		if ( empty( $this->package->get_keywords() ) && ! empty( $readme_data['tags'] ) ) {
			$this->set_keywords( $readme_data['tags'] );
		}

		if ( empty( $this->package->get_requires_at_least_wp() ) && ! empty( $readme_data['requires_at_least'] ) ) {
			$this->set_requires_at_least_wp( $readme_data['requires_at_least'] );
		}

		if ( empty( $this->package->get_tested_up_to_wp() ) && ! empty( $readme_data['tested_up_to'] ) ) {
			$this->set_tested_up_to_wp( $readme_data['tested_up_to'] );
		}

		if ( empty( $this->package->get_requires_php() ) && ! empty( $readme_data['requires_php'] ) ) {
			$this->set_requires_php( $readme_data['requires_php'] );
		}

		return $this;
	}

	/**
	 * Set properties from an existing package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 *
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
			->set_license( $package->get_license() )
			->set_requires_at_least_wp( $package->get_requires_at_least_wp() )
			->set_tested_up_to_wp( $package->get_tested_up_to_wp() )
			->set_requires_php( $package->get_requires_php() );

		foreach ( $package->get_releases() as $release ) {
			$this->add_release( $release->get_version(), $release->get_source_url() );
		}

		return $this;
	}

	/**
	 * Check a given package version string for validity.
	 *
	 * Validity means that the Composer version parser accepts the version string without throwing an exception.
	 *
	 * @since 0.5.0
	 *
	 * @param string $version
	 *
	 * @return bool
	 */
	protected function check_version_validity( string $version ): bool {
		try {
			$this->release_manager->get_composer_version_parser()->normalize( $version );
		} catch ( \Exception $e ) {
			// If there was an exception it means that something is wrong with this version.
			return false;
		}

		return true;
	}

	/**
	 * Normalize a given package version string.
	 *
	 * @since 0.5.0
	 *
	 * @param string $version
	 *
	 * @return string|null The normalized version string or null if invalid.
	 */
	protected function normalize_version( string $version ): ?string {
		try {
			$normalized_version = $this->release_manager->get_composer_version_parser()->normalize( $version );
		} catch ( \Exception $e ) {
			// If there was an exception it means that something is wrong with this version.

			$this->logger->error(
				'Error normalizing version: {version} for package {package}.',
				[
					'exception' => $e,
					'version'   => $version,
					'package'   => $this->package->get_name(),
				]
			);

			return null;
		}

		return $normalized_version;
	}

	/**
	 * Attempt to cache any releases not cached yet. But only for managed packages.
	 *
	 * We will also prune cached releases that are no longer present in the releases list.
	 *
	 * @return $this
	 */
	public function cache_releases(): BasePackageBuilder {
		if ( ! $this->package->is_managed() ) {
			return $this;
		}

		$versions = [];
		foreach ( $this->releases as $key => $release ) {

			try {
				$new_release = $this->release_manager->archive( $release );
				// Once the release file (zip) is successfully archived (cached), it is transformed so we need to overwrite.
				if ( $release !== $new_release ) {
					$this->package->set_release( $new_release );
				}

				$versions[] = $release->get_version();
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

		// Prune the cache from extra releases.
		if ( ! empty( $versions ) ) {
			$all_cached = $this->release_manager->all_cached( $this->package );
			foreach ( $all_cached as $cached_release ) {
				if ( ! in_array( $cached_release->get_version(), $versions ) ) {
					$this->release_manager->delete( $cached_release );
				}
			}
		}

		return $this;
	}

	/**
	 * Attempt to prune the releases by certain conditions (maybe constraints).
	 *
	 * @return $this
	 */
	public function prune_releases(): BasePackageBuilder {
		// Nothing right now.

		return $this;
	}

	/**
	 * Add cached releases to a package.
	 *
	 * @since 0.1.0
	 *
	 * @return $this
	 */
	public function add_cached_releases(): BasePackageBuilder {
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
	 *
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
	 *
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
	 * @throws \ReflectionException If no property exists by that name.
	 * @return $this
	 */
	protected function set( $name, $value ): self {
		$property = $this->class->getProperty( $name );
		$property->setAccessible( true );
		$property->setValue( $this->package, $value );

		return $this;
	}
}
