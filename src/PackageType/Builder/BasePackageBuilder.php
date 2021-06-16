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

use PixelgradeLT\Records\Archiver;
use PixelgradeLT\Records\PackageManager;
use PixelgradeLT\Records\PackageType\PackageTypes;
use PixelgradeLT\Records\Utils\ArrayHelpers;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\ReleaseManager;
use function PixelgradeLT\Records\get_composer_vendor;

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
	protected ReflectionClass $class;

	/**
	 * Package instance.
	 *
	 * @var Package
	 */
	protected Package $package;

	/**
	 * Package releases.
	 *
	 * @var Release[]
	 */
	protected array $releases = [];

	/**
	 * Package manager.
	 *
	 * @var PackageManager
	 */
	protected PackageManager $package_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	protected ReleaseManager $release_manager;

	/**
	 * Archiver.
	 *
	 * @var Archiver
	 */
	protected Archiver $archiver;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Create a builder for installed packages.
	 *
	 * @since 0.1.0
	 *
	 * @param Package         $package         Package instance to build.
	 * @param PackageManager  $package_manager Packages manager.
	 * @param ReleaseManager  $release_manager Release manager.
	 * @param Archiver        $archiver        Archiver.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		Package $package,
		PackageManager $package_manager,
		ReleaseManager $release_manager,
		Archiver $archiver,
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
		$this->archiver        = $archiver;
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

		$this->store_releases();

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
				$author        = array_replace( $accepted_keys, array_intersect_key( $author, $accepted_keys ) );

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
	 *
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
	 *
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
	 *
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
	 * Set the managed post ID if this package is managed by us.
	 *
	 * @since 0.5.0
	 *
	 * @param int $managed_post_id
	 *
	 * @return $this
	 */
	public function set_managed_post_id( int $managed_post_id ): self {
		return $this->set( 'managed_post_id', $managed_post_id );
	}

	/**
	 * Set the managed post ID string hash.
	 *
	 * @since 0.9.0
	 *
	 * @param string $managed_post_id_hash
	 *
	 * @return $this
	 */
	public function set_managed_post_id_hash( string $managed_post_id_hash ): self {
		return $this->set( 'managed_post_id_hash', $managed_post_id_hash );
	}

	/**
	 * Set the package visibility.
	 *
	 * @since 0.9.0
	 *
	 * @param string $visibility
	 *
	 * @return $this
	 */
	public function set_visibility( string $visibility ): self {
		return $this->set( 'visibility', $visibility );
	}

	/**
	 * Set the managed required packages if this package is managed by us.
	 *
	 * @since 0.8.0
	 *
	 * @param array $required_packages
	 *
	 * @return $this
	 */
	public function set_required_packages( array $required_packages ): self {
		return $this->set( 'required_packages', $this->normalize_dependency_packages( $required_packages ) );
	}

	/**
	 * Set the managed replaced packages if this package is managed by us.
	 *
	 * @since 0.9.0
	 *
	 * @param array $replaced_packages
	 *
	 * @return $this
	 */
	public function set_replaced_packages( array $replaced_packages ): self {
		return $this->set( 'replaced_packages', $this->normalize_dependency_packages( $replaced_packages ) );
	}

	/**
	 * Set the (Composer) require list if this package is managed by us.
	 *
	 * This will be merged with the required packages and other hard-coded packages to generate the final require config.
	 *
	 * @since 0.9.0
	 *
	 * @param array $composer_require
	 *
	 * @return $this
	 */
	public function set_composer_require( array $composer_require ): self {
		return $this->set( 'composer_require', $composer_require );
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
		$this->set_managed_post_id( $post_id );
		$this->set_managed_post_id_hash( $this->package_manager->hash_encode_id( $post_id ) );

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
		$this->set_visibility( $this->package_manager->get_package_visibility( $this->package ) );

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

		if ( isset( $package_data['is_managed'] ) ) {
			$this->set_is_managed( $package_data['is_managed'] );
		}

		if ( empty( $this->package->get_managed_post_id() ) && ! empty( $package_data['managed_post_id'] ) ) {
			$this->set_managed_post_id( $package_data['managed_post_id'] );
			$this->set_managed_post_id_hash( $this->package_manager->hash_encode_id( $package_data['managed_post_id'] ) );
		}

		if ( empty( $this->package->get_managed_post_id_hash() ) && ! empty( $package_data['managed_post_id_hash'] ) ) {
			$this->set_managed_post_id_hash( $package_data['managed_post_id_hash'] );
		}

		if ( empty( $this->package->get_visibility() ) && ! empty( $package_data['visibility'] ) ) {
			$this->set_visibility( $package_data['visibility'] );
		}

		if ( ! empty( $package_data['required_packages'] ) ) {
			// We need to normalize before the merge since we need the keys to be in the same format.
			// A bit inefficient, I know.
			$package_data['required_packages'] = $this->normalize_dependency_packages( $package_data['required_packages'] );
			// We will merge the required packages into the existing ones.
			$this->set_required_packages(
				ArrayHelpers::array_merge_recursive_distinct(
					$this->package->get_required_packages(),
					$package_data['required_packages']
				)
			);
		}

		if ( ! empty( $package_data['replaced_packages'] ) ) {
			// We need to normalize before the merge since we need the keys to be in the same format.
			// A bit inefficient, I know.
			$package_data['replaced_packages'] = $this->normalize_dependency_packages( $package_data['replaced_packages'] );
			// We will merge the replaced packages into the existing ones.
			$this->set_replaced_packages(
				ArrayHelpers::array_merge_recursive_distinct(
					$this->package->get_replaced_packages(),
					$package_data['replaced_packages']
				)
			);
		}

		if ( empty( $this->package->get_composer_require() ) && ! empty( $package_data['composer_require'] ) ) {
			$this->set_composer_require( $package_data['composer_require'] );
		}

		return $this;
	}

	/**
	 * Make sure that the managed dependency packages are in a format expected by BasePackage.
	 *
	 * @since 0.8.0
	 *
	 * @param array $packages
	 *
	 * @return array
	 */
	protected function normalize_dependency_packages( array $packages ): array {
		if ( empty( $packages ) ) {
			return [];
		}

		$normalized = [];
		// The pseudo_id is completely unique to a package since it encloses the source_name (source_type or vendor and package name/slug),
		// and the post ID. Totally unique.
		// We will rely on this uniqueness to make sure that only one dependent package remains of each entity.
		// Subsequent dependent package data referring to the same managed package post will overwrite previous ones.
		foreach ( $packages as $package ) {
			if ( empty( $package['pseudo_id'] )
			     || empty( $package['source_name'] )
			     || empty( $package['managed_post_id'] )
			) {
				$this->logger->error(
					'Invalid dependent package details for package "{package}".',
					[
						'package'          => $this->package->get_name(),
						'required_package' => $package,
					]
				);

				continue;
			}

			$normalized[ $package['pseudo_id'] ] = [
				'composer_package_name' => ! empty( $package['composer_package_name'] ) ? $package['composer_package_name'] : false,
				'version_range'         => ! empty( $package['version_range'] ) ? $package['version_range'] : '*',
				'stability'             => ! empty( $package['stability'] ) ? $package['stability'] : 'stable',
				'source_name'           => $package['source_name'],
				'managed_post_id'       => $package['managed_post_id'],
				'pseudo_id'             => $package['pseudo_id'],
			];

			if ( ! empty( $package['composer_package_name'] ) ) {
				continue;
			}

			$package_data = $this->package_manager->get_package_id_data( $package['managed_post_id'] );
			if ( empty( $package_data ) ) {
				// Something is wrong. We will not include this required package.
				$this->logger->error(
					'Error getting managed dependent package data with post ID #{managed_post_id} for package "{package}".',
					[
						'managed_post_id' => $package['managed_post_id'],
						'package'         => $this->package->get_name(),
					]
				);

				unset( $normalized[ $package['pseudo_id'] ] );
				continue;
			}

			/**
			 * Construct the Composer-like package name (the same way @see ComposerPackageTransformer::transform() does it).
			 */
			$vendor = get_composer_vendor();
			$name   = $this->normalize_package_name( $package_data['slug'] );

			$normalized[ $package['pseudo_id'] ]['composer_package_name'] = $vendor . '/' . $name;
		}

		return $normalized;
	}

	/**
	 * Normalize a package name for packages.json.
	 *
	 * @since 0.8.0
	 *
	 * @link  https://github.com/composer/composer/blob/79af9d45afb6bcaac8b73ae6a8ae24414ddf8b4b/src/Composer/Package/Loader/ValidatingArrayLoader.php#L339-L369
	 *
	 * @param string $name Package name.
	 *
	 * @return string
	 */
	protected function normalize_package_name( $name ): string {
		$name = strtolower( $name );

		return preg_replace( '/[^a-z0-9_\-\.]+/i', '', $name );
	}

	/**
	 * Fill (missing) package details from a given release file (zip).
	 *
	 * @since 0.7.0
	 *
	 * @param Release $release
	 *
	 * @return $this
	 */
	public function from_release_file( Release $release ): self {
		global $wp_filesystem;
		include_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$source_filename     = $this->release_manager->get_absolute_path( $release );
		$delete_source_after = false;
		if ( empty( $source_filename ) ) {
			// This release is not cached, so we need to download.
			try {
				$source_filename = $this->archiver->download_url( $release->get_source_url() );
			} catch ( \Exception $e ) {
				// Something went wrong with the download. Bail.
				return $this;
			}

			// Since the download succeeded, we need to clean-up after us.
			$delete_source_after = true;
		}

		if ( ! empty( $source_filename ) && file_exists( $source_filename ) ) {
			$tempdir = \trailingslashit( get_temp_dir() ) . 'lt_tmp_' . \wp_generate_password( 6, false );
			if ( true === unzip_file( $source_filename, $tempdir ) ) {
				// First we must make sure that we are looking into the package directory
				// (themes and plugins usually have their directory included in the zip).
				$package_source_dir   = $tempdir;
				$package_source_files = array_keys( $wp_filesystem->dirlist( $package_source_dir ) );
				if ( 1 == count( $package_source_files ) && $wp_filesystem->is_dir( trailingslashit( $package_source_dir ) . trailingslashit( $package_source_files[0] ) ) ) {
					// Only one folder? Then we want its contents.
					$package_source_dir = trailingslashit( $package_source_dir ) . trailingslashit( $package_source_files[0] );
				}

				// Depending on the package type (plugin or theme), extract the data accordingly.
				$tmp_package_data = [];
				if ( PackageTypes::THEME === $this->package->get_type() ) {
					$tmp_package_data = get_file_data(
						\trailingslashit( $package_source_dir ) . 'style.css',
						array(
							'Name'              => 'Theme Name',
							'ThemeURI'          => 'Theme URI',
							'Description'       => 'Description',
							'Author'            => 'Author',
							'AuthorURI'         => 'Author URI',
							'License'           => 'License',
							'Tags'              => 'Tags',
							'Requires at least' => 'Requires at least',
							'Tested up to'      => 'Tested up to',
							'Requires PHP'      => 'Requires PHP',
							'Stable tag'        => 'Stable tag',
						)
					);
				} else {
					// Assume it's a plugin, of some sort.
					$files = glob( \trailingslashit( $package_source_dir ) . '*.php' );
					if ( $files ) {
						foreach ( $files as $file ) {
							$info = \get_plugin_data( $file, false, false );
							if ( ! empty( $info['Name'] ) ) {
								$tmp_package_data = $info;
								break;
							}
						}
					}
				}

				if ( ! empty( $tmp_package_data ) ) {
					// Make sure that we don't end up doing the heavy lifting above on every request
					// due to missing information in the package headers.
					// Just fill missing header data with some default data.
					if ( empty( $tmp_package_data['Description'] ) ) {
						$tmp_package_data['Description'] = 'Just another package';
					}
					if ( empty( $tmp_package_data['ThemeURI'] ) && empty( $tmp_package_data['PluginURI'] ) ) {
						$tmp_package_data['PluginURI'] = 'https://pixelgradelt.com';
					}
					if ( empty( $tmp_package_data['License'] ) ) {
						$tmp_package_data['License'] = 'GPL-2.0-or-later';
					}
					if ( empty( $tmp_package_data['Author'] ) ) {
						$tmp_package_data['Author'] = 'Pixelgrade';
					}
					if ( empty( $tmp_package_data['AuthorURI'] ) ) {
						$tmp_package_data['AuthorURI'] = 'https://pixelgradelt.com';
					}

					// Now fill any missing package data from the headers data.
					$this->from_header_data( $tmp_package_data );
				}

				// Now, go a second time and extract info from the readme and fill anything missing.
				$this->from_readme( $package_source_dir );

				// Cleanup the temporary directory, recursively.
				$wp_filesystem->delete( $tempdir, true );
			}

			// If we have been instructed to delete the source file, do so.
			if ( true === $delete_source_after ) {
				$wp_filesystem->delete( $source_filename );
			}
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
			->set_requires_php( $package->get_requires_php() )
			->set_is_managed( $package->is_managed() )
			->set_managed_post_id( $package->get_managed_post_id() )
			->set_managed_post_id_hash( $package->get_managed_post_id_hash() )
			->set_visibility( $package->get_visibility() )
			->set_required_packages( $package->get_required_packages() )
			->set_replaced_packages( $package->get_replaced_packages() )
			->set_composer_require( $package->get_composer_require() );

		foreach ( $package->get_releases() as $release ) {
			$this->add_release( $release->get_version(), $release->get_meta() );
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
		if ( '' === trim( $version ) ) {
			return false;
		}

		try {
			$normalized = $this->package_manager->normalize_version( $version );
		} catch ( \Exception $e ) {
			// If there was an exception it means that something is wrong with this version.
			return false;
		}

		if ( '' === $normalized ) {
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
			$normalized_version = $this->package_manager->normalize_version( $version );
		} catch ( \Exception $e ) {
			// If there was an exception it means that something is wrong with this version.
			$this->logger->error(
				'Error normalizing version: {version} for package "{package}".',
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
	 * Attempt to store any releases not stored yet. But only for managed packages.
	 *
	 * We will also prune stored releases that are no longer present in the releases list.
	 *
	 * @return $this
	 */
	public function store_releases(): BasePackageBuilder {
		if ( ! $this->package->is_managed() || ! $this->package_manager->is_package_public( $this->package ) ) {
			return $this;
		}

		$versions = [];
		foreach ( $this->releases as $key => $release ) {

			try {
				$new_release = $this->release_manager->store( $release );
				// Once the release file (.zip) is successfully archived (cached), it is transformed so we need to overwrite.
				if ( $release !== $new_release ) {
					$this->package->set_release( $new_release );
				}

				// Now dump/cache the release meta data in a JSON file (if the meta data has changed).
				$this->release_manager->dump_meta( $new_release );

				$versions[] = $new_release->get_version();
			} catch ( \Exception $e ) {
				$this->logger->error(
					'Error storing package "{package}".',
					[
						'exception' => $e,
						'package'   => $this->package->get_name(),
					]
				);
			}
		}

		// Prune the storage from extra releases.
		if ( ! empty( $versions ) ) {
			$all_cached = $this->release_manager->all_stored_releases( $this->package );
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
		$releases = $this->release_manager->all_stored_releases( $this->package );

		foreach ( $releases as $release ) {
			$this->add_release( $release->get_version(), $release->get_meta() );
		}

		return $this;
	}

	/**
	 * Add a release.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version.
	 * @param array  $meta    Optional. Release meta data.
	 *
	 * @return $this
	 */
	public function add_release( string $version, array $meta = [] ): self {
		$this->releases[ $version ] = new Release( $this->package, $version, $meta );

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
	 * @return $this
	 */
	protected function set( string $name, $value ): self {
		try {
			$property = $this->class->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( $this->package, $value );
		} catch ( \ReflectionException $e ) {
			// Nothing right now. We should really make sure that we are setting properties that exist.
		}

		return $this;
	}
}
