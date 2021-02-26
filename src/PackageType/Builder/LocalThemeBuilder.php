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
			->set_directory( get_theme_root() . '/' . $slug )
			->set_installed( true )
			->set_source_name( 'local-theme' . '/' . $slug )
			->set_source_type( 'local.theme' )
			->set_slug( $slug )
			->set_type( 'theme' );
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
			$this->set_directory( get_theme_root() . '/' . $slug );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $slug );
		}

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( 'theme' );
		}

		if ( empty( $this->package->get_installed_version() ) ) {
			$this->set_installed_version( $theme->get( 'Version' ) );
		}

		$theme_data = [
			'Name' => $theme->get( 'Name' ),
			'ThemeURI' => $theme->get( 'ThemeURI' ),
			'Author' => $theme->get( 'Author' ),
			'AuthorURI' => $theme->get( 'AuthorURI' ),
			'Description' => $theme->get( 'Description' ),
			'License' => $theme->get( 'License' ),
			'Tags' => $theme->get( 'Tags' ),
		];

		return $this->from_header_data( $theme_data );
	}
}
