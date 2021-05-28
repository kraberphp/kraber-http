<?php

declare(strict_types=1);

namespace Kraber\Http\Factory;

use Psr\Http\Message\{
	StreamFactoryInterface,
	StreamInterface
};
use Kraber\Http\Message\Stream;

class StreamFactory implements StreamFactoryInterface
{
	/**
	 * Create a new stream from a string.
	 *
	 * The stream SHOULD be created with a temporary resource.
	 *
	 * @param string $content String content with which to populate the stream.
	 * @return StreamInterface
	 */
	public function createStream(string $content = '') : StreamInterface {
		$stream = new Stream("php://temp", "r+");
		$stream->write($content);
		
		return $stream;
	}
	
	/**
	 * Create a stream from an existing file.
	 *
	 * The file MUST be opened using the given mode, which may be any mode
	 * supported by the `fopen` function.
	 *
	 * The `$filename` MAY be any string supported by `fopen()`.
	 *
	 * @param string $filename Filename or stream URI to use as basis of stream.
	 * @param string $mode Mode with which to open the underlying filename/stream.
	 * @return StreamInterface
	 * @throws InvalidArgumentException If the mode is invalid.
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r') : StreamInterface {
		return new Stream($filename, $mode);
	}
	
	/**
	 * Create a new stream from an existing resource.
	 *
	 * The stream MUST be readable and may be writable.
	 *
	 * @param resource $resource PHP resource to use as basis of stream.
	 * @return StreamInterface
	 */
	public function createStreamFromResource($resource) : StreamInterface {
		return new Stream($resource);
	}
}
