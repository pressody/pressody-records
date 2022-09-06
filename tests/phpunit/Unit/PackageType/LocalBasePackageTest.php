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

use Pressody\Records\Exception\PackageNotInstalled;
use Pressody\Records\Package;
use Pressody\Records\PackageType\BasePackage;
use Pressody\Records\PackageType\LocalBasePackage;
use Pressody\Records\Tests\Unit\TestCase;

class LocalBasePackageTest extends TestCase {
	protected $package = null;

	public function setUp(): void {
		parent::setUp();

		$this->package = new class extends LocalBasePackage {
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

	public function test_directory() {
		$expected = __DIR__ . '/';
		$this->package->directory = $expected;

		$this->assertSame( $expected, $this->package->get_directory() );
	}

	public function test_is_installed() {
		$this->assertFalse( $this->package->is_installed() );

		$this->package->is_installed = true;
		$this->assertTrue( $this->package->is_installed() );
	}

	public function test_installed_version() {
		$expected = '1.0.0';
		$this->package->is_installed = true;
		$this->package->installed_version = $expected;

		$this->assertSame( $expected, $this->package->get_installed_version() );
	}

	public function test_get_installed_version_throws_exception_when_plugin_not_installed() {
		$this->expectException( PackageNotInstalled::class );

		$this->package->installed_version = '1.0.0';
		$this->package->get_installed_version();
	}
}
