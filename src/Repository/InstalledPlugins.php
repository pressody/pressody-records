<?php
/**
 * Installed plugins repository.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types = 1 );

namespace Pressody\Records\Repository;

use Pressody\Records\Package;
use Pressody\Records\PackageFactory;
use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\PackageType\PackageTypes;

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
	protected PackageFactory $factory;

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
	 * @return LocalPlugin|Package
	 */
	protected function build( string $plugin_file, array $plugin_data ): LocalPlugin {
		return $this->factory->create( PackageTypes::PLUGIN, 'local.plugin' )
			// Fill package details in a cascade.
			// First from just the plugin file.
			->from_basename( $plugin_file )
			// Then from the managed data, if this plugin is managed.
			->from_manager( 0, [ 'package_source_type' => 'local.plugin', 'local_plugin_file' => $plugin_file ] )
			// Then from the plugin source files, if there is anything left to fill.
			->from_source( $plugin_file, $plugin_data )
			->add_cached_releases()
			->build();
	}
}
