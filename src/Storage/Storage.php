<?php
/**
 * Storage interface.
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

namespace Pressody\Records\Storage;

use Pressody\Records\HTTP\Response;

/**
 * Storage interface.
 *
 * @since 0.1.0
 */
interface Storage {
	/**
	 * Retrieve the hash value of the contents of a file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $algorithm Algorithm.
	 * @param string $file      Relative file path.
	 * @return string
	 */
	public function checksum( string $algorithm, string $file ): string;

	/**
	 * Delete a file.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Relative file path.
	 * @return bool
	 */
	public function delete( string $file ): bool;

	/**
	 * Whether a file exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Relative file path.
	 * @return bool
	 */
	public function exists( string $file ): bool;

	/**
	 * List (.zip) files.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Relative path.
	 * @return array Array of relative file paths.
	 */
	public function list_files( string $path ): array;

	/**
	 * Move a file.
	 *
	 * @param string $source      Absolute path to a file on the local file system.
	 * @param string $destination Relative destination path; includes the file name.
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool;

	/**
	 * Send a file for client download.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Relative file path.
	 * @return Response
	 */
	public function send( string $file ): Response;

	/**
	 * Given a relative path return its absolute path in the storage.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	public function get_absolute_path( string $path = '' ): string;
}
