<?php
/**
 * Local theme builder.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records\PackageType;

/**
 * Local theme builder class.
 *
 * A local theme is a theme that is installed in the current WordPress installation.
 *
 * @since 0.1.0
 */
final class LocalThemeBuilder extends LocalPackageBuilder {

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
			->set_directory( get_theme_root() . '/' . $slug )
			->set_installed( true )
			->set_slug( $slug )
			->set_type( 'theme' );
	}

	/**
	 * Fill (missing) plugin package details from source.
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
			$this->set_directory( get_theme_root() . '/' . $slug );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $slug );
		}

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( 'theme' );
		}

		if ( empty( $this->package->get_authors() ) ) {
			$this->set_authors( [
				[
					'name'     => $theme->get( 'Author' ),
					'homepage' => $theme->get( 'AuthorURI' ),
				],
			] );
		}

		if ( empty( $this->package->get_homepage() ) ) {
			$this->set_homepage( $theme->get( 'ThemeURI' ) );
		}

		if ( empty( $this->package->get_description() ) ) {
			$this->set_description( $theme->get( 'Description' ) );
		}

		if ( empty( $this->package->get_keywords() ) ) {
			$this->set_keywords( $theme->get( 'Tags' ) );
		}

		if ( empty( $this->package->get_license() ) ) {
			$this->set_license( $theme->get( 'License' ) );
		}

		if ( empty( $this->package->get_name() ) ) {
			$this->set_name( $theme->get( 'Name' ) );
		}

		if ( empty( $this->package->get_installed_version() ) ) {
			$this->set_installed_version( $theme->get( 'Version' ) );
		}

		return $this;
	}
}
