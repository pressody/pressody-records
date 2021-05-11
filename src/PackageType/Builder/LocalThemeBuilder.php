<?php
/**
 * Local theme builder.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use PixelgradeLT\Records\PackageType\PackageTypes;

/**
 * Local theme builder class.
 *
 * A local theme is a theme that is installed in the current WordPress installation.
 *
 * @since 0.1.0
 */
final class LocalThemeBuilder extends LocalBasePackageBuilder {

	/**
	 * Fill the basic theme package details that we can deduce from a given theme slug.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Theme slug.
	 *
	 * @return LocalThemeBuilder
	 */
	public function from_slug( string $slug ): self {

		return $this
			->set_type( PackageTypes::THEME )
			->set_slug( $slug )
			->set_source_name( 'local-theme' . '/' . $slug )
			->set_source_type( 'local.theme' )
			->set_directory( get_theme_root( $slug ) . '/' . $slug )
			->set_installed( true );
	}

	/**
	 * Fill (missing) theme package details from source.
	 *
	 * @since 0.1.0
	 *
	 * @param string         $slug  Theme slug.
	 * @param \WP_Theme|null $theme Optional. Theme instance.
	 *
	 * @return LocalThemeBuilder
	 */
	public function from_source( string $slug, \WP_Theme $theme = null ): self {
		if ( null === $theme ) {
			$theme = wp_get_theme( $slug );
		}

		/*
		 * Start filling info.
		 */

		if ( empty( $this->package->get_directory() ) ) {
			$this->set_directory( get_theme_root( $slug ) . '/' . $slug );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $slug );
		}

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( PackageTypes::THEME );
		}

		if ( empty( $this->package->get_installed_version() ) ) {
			$this->set_installed_version( $theme->get( 'Version' ) );
		}

		$theme_data = [
			'Name'              => $theme->get( 'Name' ),
			'ThemeURI'          => $theme->get( 'ThemeURI' ),
			'Author'            => $theme->get( 'Author' ),
			'AuthorURI'         => $theme->get( 'AuthorURI' ),
			'Description'       => $theme->get( 'Description' ),
			'License'           => $theme->get( 'License' ),
			'Tags'              => $theme->get( 'Tags' ),
			'Requires at least' => $theme->get( 'Requires at least' ),
			'Tested up to'      => $theme->get( 'Tested up to' ),
			'Requires PHP'      => $theme->get( 'Requires PHP' ),
			'Stable tag'        => $theme->get( 'Stable tag' ),
		];

		return $this->from_header_data( $theme_data );
	}

	/**
	 * Fill (missing) theme package details from the theme's readme file (generally intended for WordPress.org display).
	 *
	 * If the readme file is missing, nothing is done.
	 *
	 * @since 0.5.0
	 *
	 * @param string $slug        Theme slug.
	 * @param array  $readme_data Optional. Array of readme data.
	 *
	 * @return LocalThemeBuilder
	 */
	public function from_readme( string $slug, array $readme_data = [] ): self {
		/*
		 * Start filling info.
		 */

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( PackageTypes::THEME );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $slug );
		}

		if ( ! empty( $readme_data ) ) {
			return $this->from_readme_data( $readme_data );
		}

		$directory_path = $this->package->get_directory();
		if ( empty( $directory_path ) ) {
			$directory_path = get_theme_root( $slug ) . '/' . $slug;
		}

		return parent::from_readme( $directory_path );
	}
}
