<?php
/**
 * VersionParser interface
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

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Version parser interface.
 *
 * @since 0.1.0
 *@package Pressody
 */
interface VersionParser {
	/**
	 * Normalizes a version string to be able to perform comparisons on it.
	 *
	 * @since 0.1.0
	 *
	 * @throws \UnexpectedValueException Thrown when given an invalid version string.
	 *
	 * @param string $version      Version string.
	 * @param string $full_version Optional complete version string to give more context.
	 * @return string Normalized version string.
	 */
	public function normalize( string $version, string $full_version = null ): string;

	/**
	 * Parses a constraint string into MultiConstraint and/or Constraint objects.
	 *
	 * @param string $constraints
	 *
	 * @throws \UnexpectedValueException Thrown when given an invalid constraint(s) string.
	 * @return ConstraintInterface
	 */
	public function parseConstraints( string $constraints ): ConstraintInterface;
}
