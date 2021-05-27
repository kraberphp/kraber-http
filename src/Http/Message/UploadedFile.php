<?php

declare(strict_types=1);

namespace Kraber\Http\Message;

use Psr\Http\Message\{
	UploadedFileInterface,
	StreamInterface
};
use InvalidArgumentException;
use RuntimeException;

/**
 * Value object representing a file uploaded through an HTTP request.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 */
class UploadedFile implements UploadedFileInterface
{
	private ?StreamInterface $stream = null;
	private ?string $file = null;
	private ?string $clientFilename = null;
	private ?string $clientMediaType = null;
	private ?int $size = null;
	private int $error = UPLOAD_ERR_NO_FILE;
	private bool $moved = false;
	private static $errorStatus = [
		UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
		UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
		UPLOAD_ERR_NO_FILE => 'No file was uploaded',
		UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
		UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
		UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
	];
	
	public function __construct(null|string|StreamInterface $file = null, ?string $clientFilename = null, ?string $clientMediaType = null, ?int $size = null, int $error = UPLOAD_ERR_NO_FILE) {
		if (is_string($file)) $this->file = $file;
		elseif (is_subclass_of($file, StreamInterface::class)) $this->stream = $file;
		
		$this->clientFilename = $clientFilename;
		$this->clientMediaType = $clientMediaType;
		$this->size = $size;
		$this->error = $error;
	}
	
	private function ensureUploadedFileIsValid() : void {
		if ($this->moved === true) {
			throw new RuntimeException("Uploaded file has already been moved.");
		}
		
		if ($this->error !== UPLOAD_ERR_OK) {
			throw new RuntimeException("Uploaded file error: ".self::$errorStatus[$this->error]);
		}
	}
	
	/**
	 * Retrieve a stream representing the uploaded file.
	 *
	 * This method MUST return a StreamInterface instance, representing the
	 * uploaded file. The purpose of this method is to allow utilizing native PHP
	 * stream functionality to manipulate the file upload, such as
	 * stream_copy_to_stream() (though the result will need to be decorated in a
	 * native PHP stream wrapper to work with such functions).
	 *
	 * If the moveTo() method has been called previously, this method MUST raise
	 * an exception.
	 *
	 * @return StreamInterface Stream representation of the uploaded file.
	 * @throws \RuntimeException in cases when no stream is available or can be
	 *     created.
	 */
	public function getStream() : StreamInterface {
		$this->ensureUploadedFileIsValid();
		
		if ($this->stream !== null) {
			return $this->stream;
		}
		
		try {
			$stream = new Stream($this->file, "r");
		}
		catch (InvalidArgumentException $e) {
			throw new RuntimeException("Unable to create stream: ".$e->getMessage(), $e->getCode(), $e);
		}
		
		return $stream;
	}
	
	/**
	 * Move the uploaded file to a new location.
	 *
	 * Use this method as an alternative to move_uploaded_file(). This method is
	 * guaranteed to work in both SAPI and non-SAPI environments.
	 * Implementations must determine which environment they are in, and use the
	 * appropriate method (move_uploaded_file(), rename(), or a stream
	 * operation) to perform the operation.
	 *
	 * $targetPath may be an absolute path, or a relative path. If it is a
	 * relative path, resolution should be the same as used by PHP's rename()
	 * function.
	 *
	 * The original file or stream MUST be removed on completion.
	 *
	 * If this method is called more than once, any subsequent calls MUST raise
	 * an exception.
	 *
	 * When used in an SAPI environment where $_FILES is populated, when writing
	 * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
	 * used to ensure permissions and upload status are verified correctly.
	 *
	 * If you wish to move to a stream, use getStream(), as SAPI operations
	 * cannot guarantee writing to stream destinations.
	 *
	 * @see http://php.net/is_uploaded_file
	 * @see http://php.net/move_uploaded_file
	 * @param string $targetPath Path to which to move the uploaded file.
	 * @throws \InvalidArgumentException if the $targetPath specified is invalid.
	 * @throws \RuntimeException on any error during the move operation, or on
	 *     the second or subsequent call to the method.
	 */
	public function moveTo($targetPath) : void {
		$this->ensureUploadedFileIsValid();
		
		if (empty($targetPath) || !is_string($targetPath)) {
			throw new InvalidArgumentException('Invalid path provided.');
		}
		
		if (!empty($this->file)) {
			if (PHP_SAPI === 'cli') {
				$this->moved = rename($this->file, $targetPath);
			}
			else {
				$this->moved = move_uploaded_file($this->file, $targetPath);
			}
		}
		
		if ($this->moved === false) {
			throw new RuntimeException('Unable to move uploaded file to '.$targetPath);
		}
	}
	
	/**
	 * Retrieve the file size.
	 *
	 * Implementations SHOULD return the value stored in the "size" key of
	 * the file in the $_FILES array if available, as PHP calculates this based
	 * on the actual size transmitted.
	 *
	 * @return int|null The file size in bytes or null if unknown.
	 */
	public function getSize() : int|null {
		return $this->size;
	}
	
	/**
	 * Retrieve the error associated with the uploaded file.
	 *
	 * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
	 *
	 * If the file was uploaded successfully, this method MUST return
	 * UPLOAD_ERR_OK.
	 *
	 * Implementations SHOULD return the value stored in the "error" key of
	 * the file in the $_FILES array.
	 *
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 * @return int One of PHP's UPLOAD_ERR_XXX constants.
	 */
	public function getError() : int {
		return $this->error;
	}
	
	/**
	 * Retrieve the filename sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious filename with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "name" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The filename sent by the client or null if none
	 *     was provided.
	 */
	public function getClientFilename() : string|null {
		return $this->clientFilename;
	}
	
	/**
	 * Retrieve the media type sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious media type with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "type" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The media type sent by the client or null if none
	 *     was provided.
	 */
	public function getClientMediaType() : string|null {
		return $this->clientMediaType;
	}
}