<?php

declare(strict_types=1);

namespace Kraber\Http\Message;

use Psr\Http\Message\StreamInterface;
use Throwable;
use InvalidArgumentException;
use RuntimeException;

class Stream implements StreamInterface
{
    /** @var resource|null The Stream.  */
    private $stream = null;

    /** @var array<string, mixed> Stream metadata. */
    private array $meta = [];

    /** @var bool Is seekable ? */
    private bool $isSeekable = false;

    /** @var bool Is readable ? */
    private bool $isReadable = false;

    /** @var bool Is writable ? */
    private bool $isWritable = false;

    /** @var bool[][] Stream modes allowing read or write operations. */
    private const READ_WRITE_MODES = [
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
     *
     * @param resource|string $stream
     * @param string $mode
     * @throws InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        if (!is_resource($stream)) {
            try {
                $handle = fopen($stream, $mode);
                if ($handle !== false) {
                    $this->stream = $handle;
                } else {
                    throw new RuntimeException("Unable to open provided stream '" . $stream . "'.");
                }
            } catch (Throwable $t) {
                throw new InvalidArgumentException($t->getMessage(), $t->getCode(), $t);
            }
        } else {
            $this->stream = $stream;
        }

        $this->meta = stream_get_meta_data($this->stream);
        $this->isSeekable = $this->getMetadata('seekable') ? true : false;
        $this->isReadable = isset(self::READ_WRITE_MODES['read'][$this->getMetadata('mode')]);
        $this->isWritable = isset(self::READ_WRITE_MODES['write'][$this->getMetadata('mode')]);
    }

    /**
     * Stream destructor.
     */
    public function __destruct()
    {
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
    public function __toString()
    {
        try {
            $offset = $this->tell();
            $this->rewind();
            $data = $this->getContents();
            $this->seek($offset);
        } catch (Throwable) {
            return "";
        }

        return $data;
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->clearInternalState();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $stream = $this->stream;

        $this->clearInternalState();
        return $stream;
    }

    /**
     * Clear interval state of the Stream.
     */
    private function clearInternalState(): void
    {
        $this->stream = null;
        $this->meta = [];
        $this->isSeekable = $this->isWritable = $this->isReadable = false;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return null;
        }

        try {
            $fstat = fstat($this->stream);
            return $fstat !== false ? ($fstat['size'] ?? null) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws RuntimeException on error.
     */
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new RuntimeException("Stream is in an unusable state.");
        }

        try {
            $offset = ftell($this->stream);
            if ($offset === false) {
                throw new RuntimeException("Unable to retrieves current pointer position.");
            }
        } catch (Throwable $t) {
            throw new RuntimeException($t->getMessage(), $t->getCode(), $t);
        }

        return $offset;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return $this->stream !== null ? feof($this->stream) : true;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
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
     * @throws RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new RuntimeException("Stream is in an unusable state.");
        }

        try {
            fseek($this->stream, $offset, $whence);
        } catch (Throwable $t) {
            throw new RuntimeException($t->getMessage(), $t->getCode(), $t);
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
     * @throws RuntimeException on failure.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->isWritable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws RuntimeException on failure.
     */
    public function write($string): int
    {
        if ($this->stream === null) {
            throw new RuntimeException("Stream is in an unusable state.");
        }

        try {
            $written = fwrite($this->stream, $string);
        } catch (Throwable $t) {
            throw new RuntimeException($t->getMessage(), $t->getCode(), $t);
        }

        return $written !== false ? $written : 0;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
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
     * @throws RuntimeException if an error occurs.
     */
    public function read($length): string
    {
        if ($this->stream === null) {
            throw new RuntimeException("Stream is in an unusable state.");
        }

        try {
            $data = fread($this->stream, $length);
            if ($data === false) {
                throw new RuntimeException("Unable to read stream.");
            }
        } catch (Throwable $t) {
            throw new RuntimeException($t->getMessage(), $t->getCode(), $t);
        }

        return $data;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new RuntimeException("Stream is in an unusable state.");
        }

        $data = stream_get_contents($this->stream);
        if ($data === false) {
            throw new RuntimeException("Unable to read stream.");
        }

        return $data;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return mixed Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null): mixed
    {
        return ($this->stream !== null && $key === null) ?
            $this->meta :
            ($this->stream !== null && isset($this->meta[$key]) ? $this->meta[$key] : null);
    }
}
