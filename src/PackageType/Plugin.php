<?php
/**
 * Plugin class
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\PackageType;

/**
 * Plugin package class.
 *
 * @since 0.1.0
 */
final class Plugin extends BasePackage {
	/**
	 * Plugin basename.
	 *
	 * Ex: plugin-name/plugin-name.php
	 *
	 * @var string
	 */
	protected $basename;

	/**
	 * Retrieve the relative path to the main plugin file from the plugins
	 * directory.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_basename(): string {
		return $this->basename;
	}

	/**
	 * Retrieve the list of files in the plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param array $excludes Optional. Array of file names to exclude.
	 * @return array
	 */
	public function get_files( array $excludes = [] ): array {
		// Single-file plugins should only include the main plugin file.
		if ( $this->is_single_file() ) {
			return [ $this->get_path( $this->get_basename() ) ];
		}

		return parent::get_files( $excludes );
	}

	/**
	 * Whether the plugin is a single-file plugin.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_single_file(): bool {
		return false === strpos( $this->get_basename(), '/' );
	}
}
