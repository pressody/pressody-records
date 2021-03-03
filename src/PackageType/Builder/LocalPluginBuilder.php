<?php
/**
 * Local plugin builder.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType\Builder;

use PixelgradeLT\Records\Package;

use PixelgradeLT\Records\PackageType\LocalPlugin;
use function PixelgradeLT\Records\is_plugin_file;

/**
 * Local plugin builder class.
 *
 * A local plugin is a plugin that is installed in the current WordPress installation.
 *
 * @since 0.1.0
 */
final class LocalPluginBuilder extends LocalBasePackageBuilder {
	/**
	 * Set the plugin basename.
	 *
	 * @param string $basename Relative path from the main plugin directory.
	 *
	 * @throws \ReflectionException
	 * @return LocalPluginBuilder
	 */
	public function set_basename( string $basename ): self {
		return $this->set( 'basename', $basename );
	}

	/**
	 * Fill the basic plugin package details that we can deduce from a given plugin file string (not its contents).
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 *
	 * @throws \ReflectionException
	 * @return LocalPluginBuilder
	 */
	public function from_basename( string $plugin_file ): self {
		$slug = $this->get_slug_from_plugin_file( $plugin_file );

		// Account for single-file plugins.
		$directory = '.' === \dirname( $plugin_file ) ? '' : \dirname( $plugin_file );

		return $this
			->set_type( 'plugin' )
			->set_slug( $slug )
			->set_source_name( 'local-plugin' . '/' . $slug )
			->set_source_type( 'local.plugin' )
			->set_basename( $plugin_file )
			->set_directory( WP_PLUGIN_DIR . '/' . $directory )
			->set_installed( true );
	}

	/**
	 * Fill (missing) plugin package details from the source files (mainly the main plugin file header data).
	 *
	 * @see get_plugin_data()
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 * @param array  $plugin_data Optional. Array of plugin data.
	 *
	 * @throws \ReflectionException
	 * @return LocalPluginBuilder
	 */
	public function from_source( string $plugin_file, array $plugin_data = [] ): self {
		if ( empty( $plugin_data ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
		}

		/*
		 * Start filling info.
		 */

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( 'plugin' );
		}

		if ( empty( $this->package->get_basename() ) ) {
			$this->set_basename( $plugin_file );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $this->get_slug_from_plugin_file( $plugin_file ) );
		}

		if ( empty( $this->package->get_installed_version() ) ) {
			$this->set_installed_version( $plugin_data['Version'] );
		}

		return $this->from_header_data( $plugin_data );
	}

	/**
	 * Fill (missing) plugin package details from the plugin's readme file (generally intended for WordPress.org display).
	 *
	 * If the readme file is missing, nothing is done.
	 *
	 * @since 0.5.0
	 *
	 * @param string $plugin_file Relative path to the main plugin file.
	 * @param array  $readme_data Optional. Array of readme data.
	 *
	 * @throws \ReflectionException
	 * @return LocalPluginBuilder
	 */
	public function from_readme( string $plugin_file, array $readme_data = [] ): self {
		/*
		 * Start filling info.
		 */

		if ( empty( $this->package->get_type() ) ) {
			$this->set_type( 'plugin' );
		}

		if ( empty( $this->package->get_basename() ) ) {
			$this->set_basename( $plugin_file );
		}

		if ( empty( $this->package->get_slug() ) ) {
			$this->set_slug( $this->get_slug_from_plugin_file( $plugin_file ) );
		}

		if ( ! empty( $readme_data ) ) {
			return $this->from_readme_data( $readme_data );
		}

		// Account for single-file plugins.
		$directory = '.' === \dirname( $plugin_file ) ? '' : \dirname( $plugin_file );

		return parent::from_readme( trailingslashit( WP_PLUGIN_DIR ) . trailingslashit( $directory ) );
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
	public function with_package( Package $package ): BasePackageBuilder {
		parent::with_package( $package );

		if ( $package instanceof LocalPlugin ) {
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
