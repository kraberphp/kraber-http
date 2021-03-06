<?php

declare(strict_types=1);

namespace Kraber\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

abstract class AbstractMessage implements MessageInterface
{
    /** @var array<string, array<string>> Message headers. */
    protected array $headers = [];

    /** @var array<string, string> Internal header names mapping case-insensitive => case-sensitive. */
    protected array $headerNames = [];

    /** @var StreamInterface Message body.  */
    protected StreamInterface $body;

    /** @var string Message version. */
    protected string $version = "1.1";

    /**
     * AbstractMessage constructor.
     *
     * @param array<string, array<string>> $headers
     * @param StreamInterface|null $body
     * @param string $version
     */
    public function __construct(
        array $headers = [],
        ?StreamInterface $body = null,
        string $version = "1.1"
    ) {
        $this->headers = $headers;
        $this->body = !empty($body) ? $body : new Stream("php://temp", "r+");
        $this->version = $version;

        $this->updateHeaderNames();
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version): static
    {
        $message = clone $this;
        $message->version = $version;

        return $message;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Normalize an header name.
     *
     * @param string $name
     * @return string Normalized header name.
     */
    protected function normalizedHeaderName(string $name): string
    {
        return trim(mb_strtolower($name, "utf-8"));
    }

    /**
     * Update internal mapping between case-insensitive name and case-sensitive name.
     */
    protected function updateHeaderNames(): void
    {
        $this->headerNames = array_combine(
            array_map(fn ($key) => $this->normalizedHeaderName($key), array_keys($this->headers)),
            array_keys($this->headers)
        );
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name): bool
    {
        return is_string($name) && isset($this->headerNames[$this->normalizedHeaderName($name)]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name): array
    {
        return $this->hasHeader($name) ?
            $this->headers[$this->headerNames[$this->normalizedHeaderName($name)]] :
            [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name): string
    {
        return $this->hasHeader($name) ?
            implode(",", $this->headers[$this->headerNames[$this->normalizedHeaderName($name)]]) :
            "";
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value): static
    {
        $this->validateHeaderNameAndHeaderValue($name, $value);

        $newMessage = clone $this;
        if (!$newMessage->hasHeader($name)) {
            $newMessage->headerNames[$newMessage->normalizedHeaderName($name)] = $name;
        }

        $newMessage->headers[$newMessage->headerNames[$newMessage->normalizedHeaderName($name)]] = is_array($value) ?
            array_values($value) :
            [$value];

        return $newMessage;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): static
    {
        $this->validateHeaderNameAndHeaderValue($name, $value);

        $normalizedName = $this->normalizedHeaderName($name);

        $newMessage = clone $this;
        if ($newMessage->hasHeader($name)) {
            $existingValues = $newMessage->headers[$newMessage->headerNames[$normalizedName]];
            $newMessage->headers[$newMessage->headerNames[$normalizedName]] = array_merge(
                $existingValues,
                is_array($value) ? array_values($value) : [$value]
            );
        } else {
            $newMessage->headers[$name] = is_array($value) ? array_values($value) : [$value];
            $newMessage->updateHeaderNames();
        }

        return $newMessage;
    }

    /**
     * Ensure withHeader/withAddedHeader arguments are valid.
     *
     * @param mixed $name Case-insensitive header field name to add.
     * @param mixed $value Header value(s).
     * @throws InvalidArgumentException for invalid header names or values.
     */
    private function validateHeaderNameAndHeaderValue($name, $value): void
    {
        if (!is_string($name) || empty($name)) {
            throw new InvalidArgumentException("Invalid header name provided, must be a string.");
        }

        if (!is_string($value) && !(is_array($value) && !empty($value))) {
            throw new InvalidArgumentException(
                "Invalid header value(s) provided must be a string or an array of string."
            );
        }
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): static
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $newMessage = clone $this;
        unset($newMessage->headers[$newMessage->headerNames[$this->normalizedHeaderName($name)]]);
        $newMessage->updateHeaderNames();

        return $newMessage;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): static
    {
        if (!$body->isSeekable()) {
            throw new InvalidArgumentException("StreamInterface must be seekable.");
        }

        $newMessage = clone $this;
        $newMessage->body = $body;

        return $newMessage;
    }
}
