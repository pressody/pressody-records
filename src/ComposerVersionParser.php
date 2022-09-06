<?php
/**
 * Composer version parser.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
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

declare ( strict_types=1 );

namespace Pressody\Records;

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Composer version parser class.
 *
 * @since   0.1.0
 * @package Pressody
 */
final class ComposerVersionParser implements VersionParser {
	/**
	 * Version parser instance.
	 *
	 * @var \Composer\Semver\VersionParser
	 */
	private $parser;

	/**
	 * Initialize the version parser.
	 *
	 * @since 0.1.0
	 *
	 * @param \Composer\Semver\VersionParser $parser Version parser.
	 */
	public function __construct( \Composer\Semver\VersionParser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Normalizes a version string to be able to perform comparisons on it.
	 *
	 * @since 0.1.0
	 *
	 * @param string      $version      Version string.
	 * @param string|null $full_version Optional complete version string to give more context.
	 *
	 * @throws \UnexpectedValueException
	 * @return string Normalized version string.
	 */
	public function normalize( string $version, string $full_version = null ): string {
		return $this->parser->normalize( $version, $full_version );
	}

	/**
	 * Parses a constraint string into MultiConstraint and/or Constraint objects.
	 *
	 * @since 0.5.0
	 *
	 * @param string $constraints
	 *
	 * @throws \UnexpectedValueException Thrown when given an invalid constraint(s) string.
	 * @return ConstraintInterface
	 */
	public function parseConstraints( string $constraints ): ConstraintInterface {
		return $this->parser->parseConstraints( $constraints );
	}
}
