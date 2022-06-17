<?php
/**
 * Local package builder.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

declare ( strict_types=1 );

namespace Pressody\Records\PackageType\Builder;

use Pressody\Records\Package;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\LocalTheme;
use Pressody\Records\Release;

/**
 * Local package builder class for locally installed themes and plugins.
 *
 * @since 0.1.0
 */
class LocalBasePackageBuilder extends BasePackageBuilder {

	/**
	 * Set a package's directory.
	 *
	 * @since 0.1.0
	 *
	 * @param string $directory Absolute path to the package directory.
	 *
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
	 *
	 * @return $this
	 */
	public function set_installed( bool $is_installed ): self {
		return $this->set( 'is_installed', $is_installed );
	}

	/**
	 * Set the installed version, but only if the given version string is valid.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version Version.
	 *
	 * @return $this
	 */
	public function set_installed_version( string $version ): self {
		if ( ! $this->check_version_validity( $version ) ) {
			return $this;
		}

		return $this->set( 'installed_version', $version );
	}

	/**
	 * Set properties from a package data array.
	 *
	 * @since 0.1.0
	 *
	 * @param array $package_data Package data.
	 *
	 * @return BasePackageBuilder
	 */
	public function from_package_data( array $package_data ): BasePackageBuilder {
		if ( ! empty( $package_data['local_installed'] ) ) {
			$this->set_installed( $package_data['local_installed'] );
		}

		return parent::from_package_data( $package_data );
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
	public function with_package( Package $package ): BasePackageBuilder {
		parent::with_package( $package );

		if ( $package->is_installed() ) {
			$this
				->set_directory( $package->get_directory() )
				->set_installed_version( $package->get_installed_version() )
				->set_installed( $package->is_installed() );
		}

		return $this;
	}

	/**
	 * Add cached releases to a package.
	 *
	 * This must be called after setting the installed state and version for
	 * the package.
	 *
	 * @since 0.1.0
	 *
	 * @return $this
	 */
	public function add_cached_releases(): BasePackageBuilder {
		$releases = $this->release_manager->all_stored_releases( $this->package );

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
			$this->add_release( $release->get_version(), $release->get_meta() );
		}

		return $this;
	}

	/**
	 * Retrieve a release for a pending theme or plugin update.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package instance.
	 *
	 * @return null|Release
	 */
	protected function get_package_update( Package $package ): ?Release {
		$release = null;

		if ( $package instanceof LocalPlugin ) {
			$updates = get_site_transient( 'update_plugins' );
			if ( ! empty( $updates->response[ $package->get_basename() ]->package ) ) {
				$update  = $updates->response[ $package->get_basename() ];
				$release = new Release( $package, $update->new_version, [
					'dist' => [
						'url' => (string) $update->package,
					],
				] );
			}
		} elseif ( $package instanceof LocalTheme ) {
			$updates = get_site_transient( 'update_themes' );
			if ( ! empty( $updates->response[ $package->get_slug() ]['package'] ) ) {
				$update  = $updates->response[ $package->get_slug() ];
				$release = new Release( $package, $update['new_version'], [
					'dist' => [
						'url' => (string) $update['package'],
					],
				] );
			}
		}

		return $release;
	}
}
