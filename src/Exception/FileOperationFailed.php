<?php
/**
 * Failed file operation exception.
 *
 * @package PixelgradeLT
 * @license GPL-2.0-or-later
 * @since 0.1.0
 */

declare ( strict_types = 1 );

namespace PixelgradeLT\Records\Exception;

use Throwable;

/**
 * Failed file operation exception class.
 *
 * @since 0.1.0
 */
class FileOperationFailed extends \RuntimeException implements PixelgradeltRecordsException {
	/**
	 * Create an exception for being unable to move a release artifact to storage.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filename    File name.
	 * @param string         $destination File path.
	 * @param int            $code        Optional. The Exception code.
	 * @param Throwable|null $previous    Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToMoveReleaseArtifactToStorage(
		string $filename,
		string $destination,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to move release artifact {$filename} to storage: {$destination}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to delete a release artifact from storage.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filepath    File path.
	 * @param int            $code        Optional. The Exception code.
	 * @param Throwable|null $previous    Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToDeleteReleaseArtifactFromStorage(
		string $filepath,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to delete release artifact {$filepath} from storage.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to write a release meta file to storage.
	 *
	 * @since 0.9.0.
	 *
	 * @param string         $filepath File path.
	 * @param int            $code        Optional. The Exception code.
	 * @param Throwable|null $previous    Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToWriteReleaseMetaFileToStorage(
		string $filepath,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to write release meta JSON file to storage: {$filepath}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to delete a release meta file from storage.
	 *
	 * @since 0.9.0.
	 *
	 * @param string         $filepath    File path.
	 * @param int            $code        Optional. The Exception code.
	 * @param Throwable|null $previous    Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToDeleteReleaseMetaFileFromStorage(
		string $filepath,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to delete release meta file {$filepath} from storage.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to delete a package directory from storage.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $directorypath    Directory path.
	 * @param int            $code        Optional. The Exception code.
	 * @param Throwable|null $previous    Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToDeletePackageDirectoryFromStorage(
		string $directorypath,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to delete package directory {$directorypath} from storage.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to create a temporary directory.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filename File name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToCreateTemporaryDirectory(
		string $filename,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$directory = \dirname( $filename );
		$message   = "Unable to create temporary directory: {$directory}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to create a zip file.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filename File name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToCreateZipFile(
		string $filename,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to create zip file for {$filename}.";

		return new static( $message, $code, $previous );
	}

	/**
	 * Create an exception for being unable to rename a temporary artifact.
	 *
	 * @since 0.1.0.
	 *
	 * @param string         $filename File name.
	 * @param string         $tmpfname Temporary file name.
	 * @param int            $code     Optional. The Exception code.
	 * @param Throwable|null $previous Optional. The previous throwable used for the exception chaining.
	 *
	 * @return FileOperationFailed
	 */
	public static function unableToRenameTemporaryArtifact(
		string $filename,
		string $tmpfname,
		int $code = 0,
		Throwable $previous = null
	): FileOperationFailed {
		$message = "Unable to rename temporary artifact {$tmpfname} to {$filename}.";

		return new static( $message, $code, $previous );
	}
}
