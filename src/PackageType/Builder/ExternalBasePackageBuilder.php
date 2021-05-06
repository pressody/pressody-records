<?php
/**
 * External package builder.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use PixelgradeLT\Records\Package;

/**
 * External package builder class for packages with a source like Packagist.org, WPackagist.org, or a VCS url.
 *
 * @since 0.1.0
 */
class ExternalBasePackageBuilder extends BasePackageBuilder {

	/**
	 * Set the package constraint for available releases by.
	 *
	 * @since 0.1.0
	 *
	 * @param ConstraintInterface $source_constraint
	 *
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
	 * @param array $args    Optional. Args used to query for a managed package if the post ID failed to retrieve data.
	 *
	 * @return ExternalBasePackageBuilder
	 */
	public function from_manager( int $post_id = 0, array $args = [] ): BasePackageBuilder {
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
		$this->set_managed_post_id( $post_id );
		$this->set_visibility( $this->package_manager->get_package_visibility( $this->package ) );

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

		// Write the package current data here, so the following logic has the data it needs.
		$this->from_package_data( $package_data );

		// If we have cached external releases (in the database, not from a zip files point of view), add them.
		if ( ! empty( $package_data['source_cached_release_packages'] ) ) {
			$this->from_cached_release_packages( $package_data['source_cached_release_packages'] );
		}

		return $this;
	}

	public function from_cached_release_packages( array $cached_release_packages ): BasePackageBuilder {
		// Determine the latest version.
		$latest_version_package = reset( $cached_release_packages );
		foreach ( $cached_release_packages as $release_package ) {
			if ( empty( $release_package['version'] ) || empty( $release_package['dist']['url'] ) ) {
				continue;
			}

			if ( \version_compare( $latest_version_package['version'], $release_package['version'], '<' ) ) {
				$latest_version_package = $release_package;
			}
		}

		// We will use the latest version's release package to fill package details that may be missing.
		// We will first try and use the version package data itself.
		$this->from_package_data( $latest_version_package );

		// Next, we want to extract the latest version's release zip file (a theme or a plugin zip) and read certain details.
		// But only if the manager didn't supply them first.
		if ( empty( $this->package->get_description() )
		     || empty( $this->package->get_homepage() )
		     || empty( $this->package->get_authors() )
		     || empty( $this->package->get_license() )
		) {

			if ( ! empty( $this->releases[ $latest_version_package['version'] ] ) ) {
				$this->from_release_file( $this->releases[ $latest_version_package['version'] ] );
			}
		}

		foreach ( $cached_release_packages as $release_package ) {
			if ( empty( $release_package['version'] ) || empty( $release_package['dist']['url'] ) ) {
				continue;
			}

			// Pick-up all the data we need from the source cached release package.
			$release_meta = [];

			if ( ! empty( $release_package['source'] ) ) {
				$release_meta['source'] = $release_package['source'];
			}

			$release_meta['dist'] = $release_package['dist'];

			if ( ! empty( $release_package['require'] ) ) {
				$release_meta['require'] = $release_package['require'];
			}

			if ( ! empty( $release_package['time'] ) ) {
				$release_meta['time'] = $release_package['time'];
			}
			// We don't need more data since we prefer to use the details provided by the parent package (like description, authors).

			$this->add_release( $release_package['version'], $release_meta );
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
	public function with_package( Package $package ): BasePackageBuilder {
		parent::with_package( $package );

		if ( $package->has_source_constraint() ) {
			$this->set_source_constraint( $package->get_source_constraint() );
		}

		return $this;
	}

	/**
	 * Attempt to prune the package releases by certain conditions (maybe constraints).
	 *
	 * @return $this
	 */
	public function prune_releases(): BasePackageBuilder {
		/** @var ConstraintInterface $constraint */
		$constraint = $this->package->get_source_constraint();
		foreach ( $this->releases as $key => $release ) {
			if ( ! $constraint->matches( new Constraint( '==', $this->normalize_version( $release->get_version() ) ) ) ) {
				unset( $this->releases[ $key ] );
			}
		}

		return $this;
	}
}
