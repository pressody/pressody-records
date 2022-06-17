<?php
/**
 * External base package.
 *
 * @package Pressody
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace Pressody\Records\PackageType;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MatchAllConstraint;

/**
 * External base package class for remote packages (themes and plugins).
 *
 * @since 0.1.0
 */
class ExternalBasePackage extends BasePackage {

	/**
	 * The constraint for available releases by.
	 *
	 * @var ConstraintInterface|null
	 */
	protected ?ConstraintInterface $source_constraint = null;

	/**
	 * Retrieve the package source constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return ConstraintInterface
	 */
	public function get_source_constraint(): ConstraintInterface {
		if ( null === $this->source_constraint ) {
			$this->source_constraint = new MatchAllConstraint();
		}

		return $this->source_constraint;
	}

	/**
	 * Check if the package has a source constraint.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function has_source_constraint(): bool {
		if ( null === $this->source_constraint ) {
			return false;
		}

		return true;
	}
}
