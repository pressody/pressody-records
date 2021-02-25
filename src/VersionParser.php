<?php
/**
 * VersionParser interface
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records;

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Version parser interface.
 *
 * @since 0.1.0
 *@package PixelgradeLT
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
