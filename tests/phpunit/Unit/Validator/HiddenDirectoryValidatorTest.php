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

namespace Pressody\Records\Tests\Unit\Validator;

use Pressody\Records\Exception\InvalidPackageArtifact;
use Pressody\Records\Release;
use Pressody\Records\Tests\Unit\TestCase;
use Pressody\Records\Validator\HiddenDirectoryValidator;

class HiddenDirectoryValidatorTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		$this->directory = \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pressody-records/packages/validate';

		$this->release = $this->getMockBuilder( Release::class )
			->disableOriginalConstructor()
			->getMock();

		$this->validator = new HiddenDirectoryValidator();
	}

	public function test_artifact_is_valid_zip() {
		$filename = $this->directory . '/valid-zip.zip';
		$result = $this->validator->validate( $filename, $this->release );
		$this->assertTrue( $result );
	}

	public function test_validator_throws_exception_for_invalid_artifact() {
		$this->expectException( InvalidPackageArtifact::class );
		$filename = $this->directory . '/invalid-osx-zip.zip';
		$this->validator->validate( $filename, $this->release );
	}
}
