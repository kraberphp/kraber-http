<?php

declare(strict_types=1);

namespace Kraber\Http\Factory;

use Psr\Http\Message\{
	UploadedFileFactoryInterface,
	UploadedFileInterface,
	StreamInterface
};
use Kraber\Http\Message\UploadedFile;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
	/**
	 * Create a new uploaded file.
	 *
	 * If a size is not provided it will be determined by checking the size of
	 * the file.
	 *
	 * @see http://php.net/manual/features.file-upload.post-method.php
	 * @see http://php.net/manual/features.file-upload.errors.php
	 *
	 * @param StreamInterface $stream Underlying stream representing the
	 *     uploaded file content.
	 * @param int $size in bytes
	 * @param int $error PHP file upload error
	 * @param string $clientFilename Filename as provided by the client, if any.
	 * @param string $clientMediaType Media type as provided by the client, if any.
	 *
	 * @return UploadedFileInterface
	 *
	 * @throws \InvalidArgumentException If the file resource is not readable.
	 */
	public function createUploadedFile(
		StreamInterface $stream,
		int $size = null,
		int $error = \UPLOAD_ERR_OK,
		string $clientFilename = null,
		string $clientMediaType = null
	) : UploadedFileInterface {
		if ($size === null) {
			$size = $stream->getSize();
		}
		
		return new UploadedFile($stream, $clientFilename, $clientMediaType, $size, $error);
	}
}
