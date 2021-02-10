<?php
/**
 * Installed plugins repository.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Repository;

use PixelgradeLT\Records\Package;
use PixelgradeLT\Records\PackageFactory;
use PixelgradeLT\Records\PackageType\Plugin;

/**
 * Installed plugins repository class.
 *
 * @since 0.1.0
 */
class InstalledPlugins extends AbstractRepository implements PackageRepository {
	/**
	 * Package factory.
	 *
	 * @var PackageFactory
	 */
	protected $factory;

	/**
	 * Create a repository.
	 *
	 * @since 0.1.0
	 *
	 * @param PackageFactory $factory Package factory.
	 */
	public function __construct( PackageFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Retrieve all installed plugins.
	 *
	 * @since 0.1.0
	 *
	 * @return Package[]
	 */
	public function all(): array {
		$items = [];

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$package = $this->build( $plugin_file, $plugin_data );
			$items[] = $package;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Build a plugin.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_file Relative path to a plugin file.
	 * @param array  $plugin_data Plugin data.
	 *
	 * @throws \ReflectionException
	 * @return Plugin|Package
	 */
	protected function build( string $plugin_file, array $plugin_data ): Plugin {
		return $this->factory->create( 'plugin' )
			->from_source( $plugin_file, $plugin_data )
			->build();
	}
}
