<?php
/**
 * Manage Plugins screen provider.
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

namespace Pressody\Records\Screen;

use Cedaro\WP\Plugin\AbstractHookProvider;
use Pressody\Records\Capabilities;
use Pressody\Records\Repository\PackageRepository;

/**
 * Manage Plugins screen provider class.
 *
 * @since 0.1.0
 */
class ManagePlugins extends AbstractHookProvider {
	/**
	 * Whitelisted packages repository.
	 *
	 * @var PackageRepository
	 */
	protected $repository;

	/**
	 * Create the Manage Plugins screen provider.
	 *
	 * @param PackageRepository $repository Whitelisted packages repository.
	 */
	public function __construct( PackageRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 */
	public function register_hooks() {
		if ( is_multisite() ) {
			add_filter( 'manage_plugins-network_columns', [ $this, 'register_columns' ] );
		} else {
			add_filter( 'manage_plugins_columns', [ $this, 'register_columns' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'manage_plugins_custom_column', [ $this, 'display_columns' ], 10, 2 );
	}

	/**
	 * Enqueue assets for the screen.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix Screen hook id.
	 */
	public function enqueue_assets( string $hook_suffix ) {
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script( 'pressody_records-admin' );
		wp_enqueue_style( 'pressody_records-admin' );
	}

	/**
	 * Register admin columns.
	 *
	 * @since 0.1.0
	 *
	 * @param array $columns List of admin columns.
	 * @return array
	 */
	public function register_columns( array $columns ): array {
		if ( current_user_can( Capabilities::MANAGE_OPTIONS ) ) {
			$columns['pressody_records'] = 'PD Package Source';
		}

		return $columns;
	}

	/**
	 * Display admin columns.
	 *
	 * @since 0.1.0
	 *
	 * @throws \Exception If package type not known.
	 *
	 * @param string $column_name Column identifier.
	 * @param string $plugin_file Plugin file basename.
	 */
	public function display_columns( string $column_name, string $plugin_file ) {
		if ( 'pressody_records' !== $column_name ) {
			return;
		}

		$output = '<span>';
		if ( $this->repository->contains( [ 'slug' => $plugin_file ] ) ) {
			$output .= '<span class="dashicons dashicons-yes-alt wp-ui-text-highlight"></span>';
		} else {
			$output .= '&nbsp;';
		}
		$output .= '</span>';

		echo $output;
	}
}
