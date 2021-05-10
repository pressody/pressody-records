<?php
/**
 * Package release.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

/**
 * Package release class.
 *
 * @since 0.1.0
 */
class Release {
	/**
	 * The package this release belongs to.
	 *
	 * @since 0.1.0
	 *
	 * @var Package
	 */
	protected Package $package;

	/**
	 * The release Semver version.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * The release meta data.
	 *
	 * @since 0.9.0
	 *
	 * This is (Composer) data that is specific to this release.
	 * This data is likely to be cached so that, once published, a release doesn't change in behavior over time (like changing requirements).
	 * We aim to make published releases non-editable (of-course one can delete the cache and force a rebuild,
	 * but that is nuclear and you should be wearing protective equipment :) ).
	 * This way we can be sure that any changes made to a parent package are only applied to future releases,
	 * not existing ones. To do such a thing is dangerous since it would possibly break existing uses,
	 * and hinder our ability to change a package's behavior since we would always need to account for backwards compatibility.
	 *
	 * @var array
	 */
	protected array $meta;

	/**
	 * Create a release.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package The package this release belongs to.
	 * @param string  $version The release Semver version.
	 * @param array   $meta    Optional. The release meta data.
	 */
	public function __construct( Package $package, string $version, array $meta = [] ) {
		$this->package = $package;
		$this->version = $version;

		$this->meta = $this->fill_meta( $meta );
	}

	/**
	 * Fill a releases meta data from the package and the provided meta
	 *
	 * @since 0.9.0
	 *
	 * @param array $meta Optional. Meta data to take precedence over the general one extracted from the release's parent package.
	 * @return array The filled meta data.
	 */
	protected function fill_meta( array $meta = [] ): array {
		$package_to_meta_props = [
			'source_type'          => 'source_type',
			'source_name'          => 'source_name',
			'authors'              => 'authors',
			'description'          => 'description',
			'homepage'             => 'homepage',
			'license'              => 'license',
			'keywords'             => 'keywords',
			'requires_at_least_wp' => 'requires_at_least_wp',
			'tested_up_to_wp'      => 'tested_up_to_wp',
			'requires_php'         => 'requires_php',
			'required_packages'    => 'require_ltpackages',
			'composer_require'     => 'require',
		];

		foreach ( $package_to_meta_props as $package_prop => $meta_prop ) {
			if ( ! isset( $meta[ $meta_prop ] ) && isset( $this->package[ $package_prop ] ) ) {
				$meta[ $meta_prop ] = $this->package[ $package_prop ];
			}
		}

		// Make sure that the 'dist' meta entry is in place, even if it doesn't point to an actual zip.
		if ( empty( $meta['dist'] ) ) {
			$meta['dist'] = [
				'url' => '',
			];
		}

		return $meta;
	}

	/**
	 * Retrieve the URL to download the release.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Query parameters to add to the URL.
	 *
	 * @return string
	 */
	public function get_download_url( array $args = [] ): string {
		$url = sprintf(
			'/ltpackagist/%s/%s/%s',
			$this->get_package()->get_managed_post_id_hash(),
			$this->get_package()->get_slug(),
			$this->get_version()
		);

		return add_query_arg( $args, network_home_url( $url ) );
	}

	/**
	 * Retrieve the relative path to a release artifact.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_file_path(): string {
		// We will organize artifacts by their source package name.
		return sprintf(
			'%1$s/%2$s',
			$this->get_package()->get_source_name(),
			$this->get_file()
		);
	}

	/**
	 * Retrieve the name of the file.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_file(): string {
		return sprintf(
			'%1$s-%2$s.zip',
			$this->get_package()->get_slug(),
			$this->get_version()
		);
	}

	/**
	 * Retrieve the relative path to a release meta JSON file.
	 *
	 * @since 0.9.0
	 *
	 * @return string
	 */
	public function get_meta_file_path(): string {
		return sprintf(
			'%1$s/%2$s',
			$this->get_package()->get_source_name(),
			$this->get_meta_file()
		);
	}

	/**
	 * Retrieve the name of the meta JSON file.
	 *
	 * @since 0.9.0
	 *
	 * @return string
	 */
	public function get_meta_file(): string {
		return sprintf(
			'%1$s-%2$s.json',
			$this->get_package()->get_slug(),
			$this->get_version()
		);
	}

	/**
	 * Retrieve the package.
	 *
	 * @since 0.1.0
	 *
	 * @return Package
	 */
	public function get_package(): Package {
		return $this->package;
	}

	/**
	 * Retrieve the version number for the release.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Retrieve the release's meta data.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * Retrieve the release's meta entry with the provided key.
	 *
	 * @since 0.9.0
	 *
	 * @return mixed
	 */
	public function get_meta_entry( string $key ) {
		return $this->meta[ $key ] ?? null;
	}

	/**
	 * Retrieve the source URL for a release.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_source_url(): string {
		return $this->meta['dist']['url'] ?? '';
	}
}
