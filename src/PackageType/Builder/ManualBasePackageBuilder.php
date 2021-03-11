<?php
/**
 * Manual uploaded package builder.
 *
 * @since   0.7.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType\Builder;

/**
 * Manual package builder class for packages with zips manually uploaded to the post.
 *
 * @since 0.7.0
 */
class ManualBasePackageBuilder extends BasePackageBuilder {

	/**
	 * Fill (missing) package details from the PackageManager if this is a managed package (via CPT).
	 *
	 * @since 0.7.0
	 *
	 * @param int   $post_id Optional. The package post ID to retrieve data for. Leave empty and provide $args to query.
	 * @param array $args    Optional. Args used to query for a managed package if the post ID failed to retrieve data.
	 *
	 * @return ExternalBasePackageBuilder
	 */
	public function from_manager( int $post_id = 0, array $args = [] ): BasePackageBuilder {
		$this->set_managed_post_id( $post_id );

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

		// Write the package data here, so the following logic has the data it needs.
		$this->from_package_data( $package_data );

		// If we have manually uploaded releases (in the database), add them.
		if ( ! empty( $package_data['manual_releases'] ) ) {
			$this->from_manual_releases( $package_data['manual_releases'] );
		}

		return $this;
	}

	public function from_manual_releases( array $releases ): BasePackageBuilder {

		$latest_release = reset( $releases );
		foreach ( $releases as $release ) {
			if ( empty( $release['version'] ) || empty( $release['source_url'] ) ) {
				continue;
			}

			$this->add_release( $release['version'], $release['source_url'] );

			if ( \version_compare( $latest_release['version'], $release['version'], '<' ) ) {
				$latest_release = $release;
			}
		}

		// Next, we want to extract the latest version zip (a theme or a plugin) and read certain details.
		// But only if the manager didn't supply them first.
		if ( empty( $this->package->get_description() )
		     || empty( $this->package->get_homepage() )
		     || empty( $this->package->get_authors() )
		     || empty( $this->package->get_license() )
		) {

			if ( ! empty( $this->releases[ $latest_release['version'] ] ) ) {
				$this->from_release_file( $this->releases[ $latest_release['version'] ] );
			}
		}

		return $this;
	}

	/**
	 * Attempt to prune the package releases by certain conditions (maybe constraints).
	 *
	 * @return $this
	 */
	public function prune_releases(): BasePackageBuilder {
		$manual_releases = $this->package_manager->get_post_package_manual_releases( $this->package->get_managed_post_id() );
		if ( empty( $manual_releases ) ) {
			return $this;
		}

		foreach ( $this->releases as $key => $release ) {

			if ( ! array_key_exists( $this->normalize_version( $release->get_version() ), $manual_releases ) ) {
				unset( $this->releases[ $key ] );
			}
		}

		return $this;
	}

}
