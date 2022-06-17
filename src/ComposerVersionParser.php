<?php
/**
 * Composer version parser.
 *
 * @since   0.1.0
 * @license GPL-2.0-or-later
 * @package Pressody
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
