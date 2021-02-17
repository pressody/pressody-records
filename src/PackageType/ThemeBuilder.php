<?php
/**
 * Theme builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

/**
 * Theme builder class.
 *
 * @since 0.1.0
 */
final class ThemeBuilder extends PackageBuilder {
	/**
	 * Create a theme from source.
	 *
	 * @since 0.1.0
	 *
	 * @param string        $slug  Theme slug.
	 * @param \WP_Theme|null $theme Optional. Theme instance.
	 *
	 * @return ThemeBuilder
	 */
	public function from_source( string $slug, \WP_Theme $theme = null ): self {
		if ( null === $theme ) {
			$theme = wp_get_theme( $slug );
		}

		return $this
			->set_authors( [
				[
					'name' => $theme->get( 'Author' ),
					'homepage' => $theme->get( 'AuthorURI' ),
				]
			] )
			->set_description( $theme->get( 'Description' ) )
			->set_homepage( $theme->get( 'ThemeURI' ) )
			->set_keywords( $theme->get( 'Tags' ) )
			->set_license( $theme->get( 'License' ) )
			->set_directory( get_theme_root() . '/' . $slug )
			->set_name( $theme->get( 'Name' ) )
			->set_installed( true )
			->set_installed_version( $theme->get( 'Version' ) )
			->set_slug( $slug )
			->set_type( 'theme' )
			->add_cached_releases();
	}
}
