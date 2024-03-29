<?php
/**
 * Htaccess class
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

namespace Pressody\Records;

/**
 * Interact with the .htaccess file.
 *
 * @since 0.1.0
 */
class Htaccess {
	/**
	 * The directory path where .htaccess is located.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $path = '';

	/**
	 * .htaccess rules.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	protected $rules = [];

	/**
	 * Constructor method.
	 *
	 * @since 0.1.0
	 *
	 * @param string|null $path Optional. Directory path where .htaccess is located. Default is empty string.
	 */
	public function __construct( string $path = null ) {
		if ( null === $path ) {
			$path = '';
		}

		$this->path = $path;
	}

	/**
	 * Add rules to .htaccess.
	 *
	 * @since 0.1.0
	 *
	 * @param array $rules List of rules to add.
	 */
	public function add_rules( array $rules ) {
		$this->rules = array_merge( $this->rules, $rules );
	}

	/**
	 * Retrieve the full path to the .htaccess file itself.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_file(): string {
		return $this->path . '.htaccess';
	}

	/**
	 * Retrieve the rules in the .htaccess file.
	 *
	 * Only contains the rules between the #Pressody Records delimiters.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_rules(): array {
		return (array) apply_filters( 'pressody_records/htaccess_rules', $this->rules );
	}

	/**
	 * Determine if the .htaccess file exists.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the file exists, false otherwise.
	 */
	public function file_exists(): bool {
		return file_exists( $this->get_file() );
	}

	/**
	 * Determine if the .htaccess file is writable.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_writable(): bool {
		$file = $this->get_file();

		return ( ! $this->file_exists() && is_writable( $this->path ) ) || is_writable( $file );
	}

	/**
	 * Save rules to the .htaccess file.
	 *
	 * @since 0.1.0
	 */
	public function save() {
		$rules = $this->get_rules();
		insert_with_markers( $this->get_file(), 'Pressody Records', $rules );
	}
}
