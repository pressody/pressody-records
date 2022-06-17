<?php
declare ( strict_types = 1 );

namespace Pressody\Records\Tests\Unit\Validator;

use Pressody\Records\Exception\InvalidPackageArtifact;
use Pressody\Records\Release;
use Pressody\Records\Tests\Unit\TestCase;
use Pressody\Records\Validator\ZipValidator;

class ZipValidatorTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		$this->directory = \Pressody\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pressody-records/packages/validate';

		$this->release = $this->getMockBuilder( Release::class )
			->disableOriginalConstructor()
			->getMock();

		$this->validator = new ZipValidator();
	}

	public function test_artifact_is_valid_zip() {
		$filename = $this->directory . '/valid-zip.zip';
		$result = $this->validator->validate( $filename, $this->release );
		$this->assertTrue( $result );
	}

	public function test_validator_throws_exception_for_invalid_artifact() {
		$this->expectException( InvalidPackageArtifact::class );
		$filename = $this->directory . '/invalid-zip.zip';
		$this->validator->validate( $filename, $this->release );
	}
}
