<?php
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

namespace Pressody\Records\Tests\Unit\PackageType;

use Composer\Semver\Constraint\MatchAllConstraint;
use Pressody\Records\Exception\PackageNotInstalled;
use Pressody\Records\Package;
use Pressody\Records\PackageType\BasePackage;
use Pressody\Records\PackageType\ExternalBasePackage;
use Pressody\Records\Tests\Unit\TestCase;

class ExternalBasePackageTest extends TestCase {
	protected $package = null;

	public function setUp(): void {
		parent::setUp();

		$this->package = new class extends ExternalBasePackage {
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
	}

	public function test_implements_package_interface() {
		$this->assertInstanceOf( Package::class, $this->package );
	}

	public function test_extends_base_package() {
		$this->assertInstanceOf( BasePackage::class, $this->package );
	}

	public function test_source_constraint() {
		$expected = new MatchAllConstraint();
		$this->package->source_constraint = $expected;

		$this->assertSame( $expected, $this->package->get_source_constraint() );
		$this->assertTrue( $this->package->has_source_constraint() );
	}
}
