<?php

namespace Kraber\Http;

use Psr\Http\Message\StreamInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
class Stream implements StreamInterface
{
	private $stream;
	private array $meta = [];
	private bool $isSeekable = false;
	private bool $isReadable = false;
	private bool $isWritable = false;
	private static array $readWriteModes = [
		'read' => [
			'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
			'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
			'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a+' => true
		],
		'write' => [
			'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
			'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
			'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
			'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
		]
	];
	
	/**
	 * Stream constructor.
	 * @param resource|string $stream
	 * @param string $mode
	 * @throws InvalidArgumentException
	 */
	public function __construct($stream, $mode = 'r') {
		if (is_resource($stream)) {
			$this->stream = $stream;
		}
		elseif (is_string($stream)) {
			$error = false;
			set_error_handler(fn(int $errno, string $errstr, string $errfile, int $errline) => $error = true);
			$this->stream = fopen($stream, $mode);
			restore_error_handler();
		}
		
		if (empty($this->stream) || (isset($error) && $error === true)) {
			throw new InvalidArgumentException("Stream must be a valid resource.");
		}
		
		$this->meta = stream_get_meta_data($this->stream);
		$this->isSeekable = $this->getMetadata('seekable') ? true : false;
		$this->isReadable = isset(self::$readWriteModes['read'][$this->getMetadata('mode')]);
		$this->isWritable = isset(self::$readWriteModes['write'][$this->getMetadata('mode')]);
	}
	
	public function __destruct() {
		$this->close();
	}
	
	/**
	 * Reads all data from the stream into a string, from the beginning to end.
	 *
	 * This method MUST attempt to seek to the beginning of the stream before
	 * reading data and read the stream until the end is reached.
	 *
	 * Warning: This could attempt to load a large amount of data into memory.
	 *
	 * This method MUST NOT raise an exception in order to conform with PHP's
	 * string casting operations.
	 *
	 * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 * @return string
	 */
	public function __toString() {
		if (!$this->stream || !$this->isReadable()) return "";
		
		try {
			$this->rewind();
			return $this->getContents();
		}
		catch (Exception $e) {
			return "";
		}
	}
	
	/**
	 * Closes the stream and any underlying resources.
	 *
	 * @return void
	 */
	public function close() : void {
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
		
		$this->stream = null;
		$this->meta = [];
		$this->isSeekable = $this->isWritable = $this->isReadable = false;
	}
	
	/**
	 * Separates any underlying resources from the stream.
	 *
	 * After the stream has been detached, the stream is in an unusable state.
	 *
	 * @return resource|null Underlying PHP stream, if any
	 */
	public function detach() {
		$stream = $this->stream;
		
		$this->stream = null;
		$this->meta = [];
		$this->isSeekable = $this->isWritable = $this->isReadable = false;
		
		return $stream;
	}
	
	/**
	 * Get the size of the stream if known.
	 *
	 * @return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize() : ?int {
		if (!$this->stream) return null;
		
		$fstat = fstat($this->stream);
		return (is_array($fstat) && isset($fstat['size'])) ? $fstat['size'] : null;
	}
	
	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return int Position of the file pointer
	 * @throws \RuntimeException on error.
	 */
	public function tell() : int {
		if (!$this->stream) throw new RuntimeException("Stream is in an unusable state.");
		
		$offset = ftell($this->stream);
		if ($offset === false) {
			throw new RuntimeException("An error occurred while retrieving stream offset.");
		}
		
		return $offset;
	}
	
	/**
	 * Returns true if the stream is at the end of the stream.
	 *
	 * @return bool
	 */
	public function eof() : bool {
		return $this->stream ? feof($this->stream) : true;
	}
	
	/**
	 * Returns whether or not the stream is seekable.
	 *
	 * @return bool
	 */
	public function isSeekable() : bool {
		return $this->isSeekable;
	}
	
	/**
	 * Seek to a position in the stream.
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *     based on the seek offset. Valid values are identical to the built-in
	 *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *     offset bytes SEEK_CUR: Set position to current location plus offset
	 *     SEEK_END: Set position to end-of-stream plus offset.
	 * @throws \RuntimeException on failure.
	 */
	public function seek($offset, $whence = SEEK_SET) : void {
		if (!$this->isSeekable()) throw new RuntimeException("Stream is not seekable.");
		
		if (fseek($this->stream, $offset, $whence) === -1) {
			throw new RuntimeException("An error occurred while seeking stream.");
		}
	}
	
	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @see seek()
	 * @link http://www.php.net/manual/en/function.fseek.php
	 * @throws \RuntimeException on failure.
	 */
	public function rewind() : void {
		$this->seek(0);
	}
	
	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable() : bool {
		return $this->isWritable;
	}
	
	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 * @return int Returns the number of bytes written to the stream.
	 * @throws \RuntimeException on failure.
	 */
	public function write($string) : int {
		if (!$this->isWritable()) throw new RuntimeException("Stream is not writable.");
		
		$written = fwrite($this->stream, $string);
		if ($written === false) {
			throw new RuntimeException("An error occurred while writing stream.");
		}
		
		return $written;
	}
	
	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable() : bool {
		return $this->isReadable;
	}
	
	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *     them. Fewer than $length bytes may be returned if underlying stream
	 *     call returns fewer bytes.
	 * @return string Returns the data read from the stream, or an empty string
	 *     if no bytes are available.
	 * @throws \RuntimeException if an error occurs.
	 */
	public function read($length) : string {
		if (!$this->isReadable()) throw new RuntimeException("Stream is not readable.");
		
		$data = fread($this->stream, $length);
		if ($data === false) {
			throw new RuntimeException("An error occurred while writing stream.");
		}
		
		return $data;
	}
	
	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 * @throws \RuntimeException if unable to read or an error occurs while
	 *     reading.
	 */
	public function getContents() : string {
		if (!$this->stream) throw new RuntimeException("Stream is in an unusable state.");
		
		return stream_get_contents($this->stream);
	}
	
	/**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @link http://php.net/manual/en/function.stream-get-meta-data.php
	 * @param string $key Specific metadata to retrieve.
	 * @return array|mixed|null Returns an associative array if no key is
	 *     provided. Returns a specific key value if a key is provided and the
	 *     value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null) : array|string|null {
		if (!$this->stream) return null;
		
		return !isset($key) ? $this->meta : (isset($this->meta[$key]) ? $this->meta[$key] : null);
	}
}
