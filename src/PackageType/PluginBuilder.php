<?php
/**
 * Plugin builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

use PixelgradeLT\Records\Package;

use function PixelgradeLT\Records\is_plugin_file;

/**
 * Plugin builder class.
 *
 * @since 0.1.0
 */
final class PluginBuilder extends PackageBuilder {
	/**
	 * Set the plugin basename.
	 *
	 * @param string $basename Relative path from the main plugin directory.
	 *
	 * @return PluginBuilder
	 * @throws \ReflectionException
	 */
	public function set_basename( string $basename ): self {
		return $this->set( 'basename', $basename );
	}

	/**
	 * Fill the basic plugin package details that we can deduce from a given plugin file string.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 */
	public function from_file( string $plugin_file ): self {
		$slug = $this->get_slug_from_plugin_file( $plugin_file );

		// Account for single-file plugins.
		$directory = '.' === \dirname( $plugin_file ) ? '' : \dirname( $plugin_file );

		return $this
			->set_basename( $plugin_file )
			->set_directory( WP_PLUGIN_DIR . '/' . $directory )
			->set_installed( true )
			->set_slug( $slug )
			->set_type( 'plugin' );
	}

	/**
	 * Fill missing plugin package details from the PackageManager if this is a managed plugin (via CPT).
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 */
	public function from_manager( string $plugin_file ): self {
		$slug = $this->get_slug_from_plugin_file( $plugin_file );

		// Account for single-file plugins.
		$directory = '.' === \dirname( $plugin_file ) ? '' : \dirname( $plugin_file );

		if ( empty( $plugin_data ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
		}

		// If we don't have 'Tags', attempt to get them from the plugin's readme.txt.
		if ( empty( $plugin_data['Tags'] ) ) {
			$plugin_data['Tags'] = $this->get_tags_from_readme( $directory );
		}

		return $this
			->set_authors( [
				[
					'name' => $plugin_data['AuthorName'],
					'homepage' => $plugin_data['AuthorURI'],
				]
			] )
			->set_homepage( $plugin_data['PluginURI'] )
			->set_description( $plugin_data['Description'] )
			->set_keywords( $plugin_data['Tags'] )
			->set_license( $plugin_data['License'] )
			->set_name( $plugin_data['Name'] )
			->set_installed_version( $plugin_data['Version'] );
	}

	/**
	 * Fill missing plugin package details from source.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 * @param array  $plugin_data Optional. Array of plugin data.
	 *
	 * @return PluginBuilder
	 * @throws \ReflectionException
	 */
	public function from_source( string $plugin_file, array $plugin_data = [] ): self {
		$slug = $this->get_slug_from_plugin_file( $plugin_file );

		// Account for single-file plugins.
		$directory = '.' === \dirname( $plugin_file ) ? '' : \dirname( $plugin_file );

		if ( empty( $plugin_data ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
		}

		// If we don't have 'Tags', attempt to get them from the plugin's readme.txt.
		if ( empty( $plugin_data['Tags'] ) ) {
			$plugin_data['Tags'] = $this->get_tags_from_readme( $directory );
		}

		return $this
			->set_authors( [
				[
					'name' => $plugin_data['AuthorName'],
					'homepage' => $plugin_data['AuthorURI'],
				]
			] )
			->set_homepage( $plugin_data['PluginURI'] )
			->set_description( $plugin_data['Description'] )
			->set_keywords( $plugin_data['Tags'] )
			->set_license( $plugin_data['License'] )
			->set_name( $plugin_data['Name'] )
			->set_installed_version( $plugin_data['Version'] );
	}

	/**
	 * Attempt to extract plugin tags from its readme.txt or readme.md.
	 *
	 * @param string $directory
	 *
	 * @return string[]
	 */
	protected function get_tags_from_readme( string $directory ): array {
		$tags = [];

		$readme_file = trailingslashit( WP_PLUGIN_DIR ) . trailingslashit( $directory ) . 'readme.txt';
		if ( ! file_exists( $readme_file ) ) {
			// Try a readme.md.
			$readme_file = trailingslashit( WP_PLUGIN_DIR ) . trailingslashit( $directory ) . 'readme.md';
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

		return $tags;
	}

	/**
	 * Set properties from an existing package.
	 *
	 * @since 0.1.0
	 *
	 * @param Package $package Package.
	 *
	 * @throws \ReflectionException
	 * @return $this
	 */
	public function with_package( Package $package ): PackageBuilder {
		parent::with_package( $package );

		if ( $package instanceof Plugin ) {
			$this->set_basename( $package->get_basename() );
		}

		return $this;
	}

	/**
	 * Retrieve a plugin slug.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Plugin slug or relative path to the main plugin
	 *                            file from the plugins directory.
	 * @return string
	 */
	protected function get_slug_from_plugin_file( $plugin_file ): string {
		if ( ! is_plugin_file( $plugin_file ) ) {
			return $plugin_file;
		}

		$slug = \dirname( $plugin_file );

		// Account for single file plugins.
		$slug = '.' === $slug ? basename( $plugin_file, '.php' ) : $slug;

		return $slug;
	}
}
