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

declare ( strict_types=1 );

namespace Pressody\Records\Tests\Unit\PackageType\Builder;

use Composer\IO\NullIO;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Pressody\Records\Archiver;
use Pressody\Records\Package;
use Pressody\Records\PackageManager;
use Pressody\Records\PackageType\Builder\ExternalBasePackageBuilder;
use Pressody\Records\PackageType\Builder\BasePackageBuilder;
use Pressody\Records\PackageType\ExternalBasePackage;
use Pressody\Records\ReleaseManager;
use Pressody\Records\Tests\Unit\TestCase;
use Psr\Log\NullLogger;

class ExternalPackageBuilderTest extends TestCase {
	protected $builder = null;

	public function setUp(): void {
		parent::setUp();

		// Provide direct getters.
		$package = new class extends ExternalBasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
		};


		$package_manager = $this->getMockBuilder( PackageManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$release_manager = $this->getMockBuilder( ReleaseManager::class )
		                        ->disableOriginalConstructor()
		                        ->getMock();

		$logger = new NullIO();
		$archiver = new Archiver( new NullLogger() );

		$this->builder = new ExternalBasePackageBuilder( $package, $package_manager, $release_manager, $archiver, $logger );
	}

	public function test_extends_package_builder() {

		$this->assertInstanceOf( BasePackageBuilder::class, $this->builder );
	}

	public function test_implements_package_interface() {
		$package = $this->builder->build();

		$this->assertInstanceOf( Package::class, $package );
	}

	public function test_source_constraint() {
		$expected = $this->getMockBuilder( MultiConstraint::class )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$package = $this->builder->set_source_constraint( $expected )->build();

		$this->assertSame( $expected, $package->source_constraint );
	}

	public function test_with_package() {
		$expected = new class extends ExternalBasePackage {
			public function __get( $name ) {
				return $this->$name;
			}
			public function __set( $name, $value ) {
				$this->$name = $value;
			}
		};
		$expected->source_constraint = new MatchAllConstraint();

		$package = $this->builder->with_package( $expected )->build();

		$this->assertSame( $expected->source_constraint, $package->source_constraint );
	}
}
