<?php
declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Tests\Unit\Validator;

use PixelgradeLT\Records\Exception\InvalidPackageArtifact;
use PixelgradeLT\Records\Release;
use PixelgradeLT\Records\Tests\Unit\TestCase;
use PixelgradeLT\Records\Validator\HiddenDirectoryValidator;

class HiddenDirectoryValidatorTest extends TestCase {
	public function setUp(): void {
		parent::setUp();

		$this->directory = \PixelgradeLT\Records\TESTS_DIR . '/Fixture/wp-content/uploads/pixelgradelt-records/packages/validate';

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
