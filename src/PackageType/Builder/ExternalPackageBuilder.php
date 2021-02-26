<?php
/**
 * External package builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use PixelgradeLT\Records\Package;

/**
 * External package builder class for packages with a source like Packagist.org, WPackagist.org, or a VCS url.
 *
 * @since 0.1.0
 */
class ExternalPackageBuilder extends PackageBuilder {

	/**
	 * Set the package constraint for available releases by.
	 *
	 * @since 0.1.0
	 *
	 * @param ConstraintInterface $source_constraint
	 * @return $this
	 */
	public function set_source_constraint( ConstraintInterface $source_constraint ): self {
		return $this->set( 'source_constraint', $source_constraint );
	}

	/**
	 * Fill (missing) package details from the PackageManager if this is a managed package (via CPT).
	 *
	 * @since 0.1.0
	 *
	 * @param int   $post_id Optional. The package post ID to retrieve data for. Leave empty and provide $args to query.
	 * @param array $args Optional. Args used to query for a managed package if the post ID failed to retrieve data.
	 *
	 * @return ExternalPackageBuilder
	 */
	public function from_manager( int $post_id = 0, array $args = [] ): PackageBuilder {
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

		if ( ! $this->package->has_source_constraint() && ! empty( $package_data['source_version_range'] ) && '*' !== $package_data['source_version_range'] ) {
			// Merge the version range with the stability so we have a full constraint string.
			$version_range = $package_data['source_version_range'];
			if ( ! empty( $package_data['source_stability'] ) || 'stable' !== $package_data['source_stability'] ) {
				$version_range .= '@' . $package_data['source_stability'];
			}

			try {
				$this->set_source_constraint( $this->package_manager->get_composer_version_parser()->parseConstraints( $version_range ) );
			} catch ( \Exception $e ) {
				$this->logger->error(
					'Error parsing source constraint for {package}.',
					[
						'exception' => $e,
						'package'   => $this->package->get_name(),
					]
				);
			}
		}

		// Write the package data here, so the following logic has the data it needs.
		$this->from_package_data( $package_data );

		// If we have cached external releases (in the database, not from a zip files point of view), add them.
		if ( ! empty( $package_data['source_cached_release_packages'] ) ) {
			$this->from_cached_release_packages( $package_data['source_cached_release_packages'] );
		}

		return $this;
	}

	public function from_cached_release_packages( array $cached_release_packages ): PackageBuilder {

		$latest_version_package = reset( $cached_release_packages );
		foreach ( $cached_release_packages as $package ) {
			if ( empty( $package['version'] )
			     || empty( $package['dist']['url'] )
			) {
				continue;
			}

			$this->add_release( $package['version'], $package['dist']['url'] );

			if ( version_compare( $latest_version_package['version'], $package['version'], '<' ) ) {
				$latest_version_package = $package;
			}
		}

		// We will use the latest version package to fill package details that may be missing.
		// We will first try and use the version package data itself.
		$this->from_package_data( $latest_version_package );

		// Next, we want to extract the latest version zip (a theme or a plugin) and read certain details.
		// But only if the manager didn't supply them first.
		if ( empty( $this->package->get_description() )
		     || empty( $this->package->get_homepage() )
		     || empty( $this->package->get_authors() )
		     || empty( $this->package->get_license() )
		) {
			/** @var \WP_Filesystem_Base $wp_filesystem */
			global $wp_filesystem;
			include_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			$release         = $this->releases[ $latest_version_package['version'] ];
			$source_filename = $this->release_manager->get_absolute_path( $release );
			$delete_source_after = false;
			if ( empty( $source_filename ) ) {
				// This release is not cached, so we need to download.
				$source_filename = \download_url( $release->get_source_url() );
				$delete_source_after = true;
				if ( \is_wp_error( $source_filename ) ) {
					$this->logger->error(
						'Download failed.',
						[
							'error' => $source_filename,
							'url'   => $release->get_source_url(),
						]
					);

					$source_filename = false;
				}
			}

			if ( ! empty( $source_filename ) && file_exists( $source_filename ) ) {
				$tempdir = \trailingslashit( get_temp_dir() ) . 'lt_tmp_' . \wp_generate_password( 6, false );
				if ( true === unzip_file( $source_filename, $tempdir ) ) {
					// First we must make sure that we are looking into the package directory
					// (themes and plugins usually have their directory included in the zip).
					$package_source_dir = $tempdir;
					$package_source_files = array_keys( $wp_filesystem->dirlist( $package_source_dir ) );
					if ( 1 == count( $package_source_files ) && $wp_filesystem->is_dir( trailingslashit( $package_source_dir ) . trailingslashit( $package_source_files[0] ) ) ) {
						// Only one folder? Then we want its contents.
						$package_source_dir = trailingslashit( $package_source_dir ) . trailingslashit( $package_source_files[0] );
					}

					// Depending on the package type (plugin or theme), extract the data accordingly.
					$tmp_package_data = [];
					if ( 'theme' === $this->package->get_type() ) {
						$tmp_package_data = get_file_data(
							\trailingslashit( $package_source_dir ) . 'style.css',
							array(
								'Name'        => 'Theme Name',
								'ThemeURI'    => 'Theme URI',
								'Description' => 'Description',
								'Author'      => 'Author',
								'AuthorURI'   => 'Author URI',
								'License'     => 'License',
								'Tags'        => 'Tags',
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
						if ( empty( $tmp_package_data['Tags'] ) ) {
							$tmp_package_data['Tags'] = $this->get_tags_from_readme( $package_source_dir );
						}

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

					// Cleanup the temporary directory, recursively.
					$wp_filesystem->delete( $tempdir, true );
				}

				// If we have been instructed to delete the source file, do so.
				if ( true === $delete_source_after ) {
					$wp_filesystem->delete( $source_filename );
				}
			}
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
	public function with_package( Package $package ): PackageBuilder {
		parent::with_package( $package );

		if ( $package->has_source_constraint() ) {
			$this->set_source_constraint( $package->get_source_constraint() );
		}

		return $this;
	}

	/**
	 * Attempt to extract plugin tags from its readme.txt or readme.md.
	 *
	 * @param string $directory The absolute path to the plugin directory.
	 *
	 * @return string[]
	 */
	protected function get_tags_from_readme( string $directory ): array {
		$tags = [];

		$readme_file = trailingslashit( $directory ) . 'readme.txt';
		if ( ! file_exists( $readme_file ) ) {
			// Try a readme.md.
			$readme_file = trailingslashit( $directory ) . 'readme.md';
		}
		if ( file_exists( $readme_file ) ) {
			$file_contents = file_get_contents( $readme_file );

			if ( preg_match( '|Tags:(.*)|i', $file_contents, $_tags ) ) {
				$tags = preg_split( '|,[\s]*?|', trim( $_tags[1] ) );
				foreach ( array_keys( $tags ) as $t ) {
					$tags[ $t ] = trim( strip_tags( $tags[ $t ] ) );
				}
			}
		}

		$tags = array_unique( array_values( $tags ) );
		sort( $tags );

		return $tags;
	}

	/**
	 * Attempt to prune the releases by certain conditions (maybe constraints).
	 *
	 * @return $this
	 */
	public function prune_releases(): PackageBuilder {
		/** @var ConstraintInterface $constraint */
		$constraint = $this->package->get_source_constraint();
		foreach ( $this->releases as $key => $release ) {
			if ( ! $constraint->matches( new Constraint('==', $release->get_version() ) ) ) {
				unset( $this->releases[ $key ] );
			}
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
}
