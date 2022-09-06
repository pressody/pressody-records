<?php
/**
 * Local base package.
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

namespace Pressody\Records\PackageType;

use Pressody\Records\Exception\PackageNotInstalled;
use Pressody\Records\Release;

/**
 * Local base package class for locally installed/managed packages (themes and plugins).
 *
 * @since 0.1.0
 */
class LocalBasePackage extends BasePackage {

	/**
	 * Absolute path to the package install directory.
	 *
	 * In the case of a installed theme or plugin,
	 * this is the absolute path to the wp-content/themes/slug or wp-content/plugins/slug directories.
	 *
	 * @var string
	 */
	protected string $directory;

	/**
	 * Whether the package is installed in the current WordPress installation.
	 *
	 * @var bool
	 */
	protected bool $is_installed = false;

	/**
	 * The currently installed version.
	 *
	 * @var string
	 */
	protected string $installed_version = '';

	/**
	 * Retrieve the package installed directory.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_directory(): string {
		return $this->directory;
	}

	/**
	 * Retrieve the list of files in the package.
	 *
	 * @since 0.1.0
	 *
	 * @param array $excludes Optional. Array of file names to exclude.
	 * @throws PackageNotInstalled If the package is not installed.
	 * @return array
	 */
	public function get_files( array $excludes = [] ): array {
		if ( ! $this->is_installed() ) {
			throw PackageNotInstalled::forInvalidMethodCall( __FUNCTION__, $this );
		}

		$directory = $this->get_directory();
		$files     = scandir( $directory, SCANDIR_SORT_NONE );
		$files     = array_values( array_diff( $files, $excludes, [ '.', '..' ] ) );

		return array_map(
			function( $file ) {
					return $this->get_path( $file );
			},
			$files
		);
	}

	/**
	 * Retrieve the path to a file in the package.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Optional. Path relative to the package root.
	 * @return string
	 */
	public function get_path( string $path = '' ): string {
		return $this->directory . ltrim( $path, '/' );
	}

	/**
	 * Whether the package is installed.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_installed(): bool {
		return $this->is_installed;
	}

	/**
	 * Retrieve the installed version.
	 *
	 * @since 0.1.0
	 *
	 * @throws PackageNotInstalled If the package is not installed.
	 * @return string
	 */
	public function get_installed_version():? string {
		if ( ! $this->is_installed() ) {
			throw PackageNotInstalled::forInvalidMethodCall( __FUNCTION__, $this );
		}

		return $this->installed_version;
	}

	/**
	 * Retrieve the installed release.
	 *
	 * @since 0.1.0
	 *
	 * @throws PackageNotInstalled If the package is not installed.
	 * @return Release
	 */
	public function get_installed_release(): Release {
		if ( ! $this->is_installed() ) {
			throw PackageNotInstalled::forInvalidMethodCall( __FUNCTION__, $this );
		}

		if ( null === $this->get_installed_version() || '' === $this->get_installed_version() ) {
			throw PackageNotInstalled::forInvalidInstalledVersion( __FUNCTION__, $this );
		}


		return $this->get_release( $this->get_installed_version() );
	}

	/**
	 * Whether a given release is the currently installed version.
	 *
	 * @since 0.1.0
	 *
	 * @param Release $release Release.
	 * @return bool
	 */
	public function is_installed_release( Release $release ): bool {
		if ( ! $this->is_installed() ) {
			return false;
		}

		return version_compare( $release->get_version(), $this->get_installed_version(), '=' );
	}

	/**
	 * Whether an update is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_update_available(): bool {
		return $this->is_installed() && version_compare( $this->get_installed_version(), $this->get_latest_version(), '<' );
	}
}
