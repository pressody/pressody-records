<?php
/**
 * Composer version parser.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package PixelgradeLT
 */

declare ( strict_types=1 );

namespace PixelgradeLT\Records;

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * Composer version parser class.
 *
 * @since   0.1.0
 * @package PixelgradeLT
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
	 * @return string Normalized version string.
	 */
	public function normalize( string $version, string $full_version = null ): string {
		return $this->parser->normalize( $version, $full_version );
	}

	/**
	 * Parses a constraint string into MultiConstraint and/or Constraint objects.
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
