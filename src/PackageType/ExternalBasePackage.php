<?php
/**
 * External base package.
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
