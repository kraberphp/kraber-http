<?php

namespace Kraber\Http\Message;

use \Psr\Http\Message\{
	StreamInterface,
	MessageInterface
};
use InvalidArgumentException;

abstract class AbstractMessage implements MessageInterface
{
	protected array $headers = [];
	protected StreamInterface $body;
	protected string $version = "1.1";
	
	public function __construct(
		array $headers = [],
		?StreamInterface $body = null,
		string $version = "1.1"
	) {
		$this->headers = $headers;
		$this->body = !empty($body) ? $body : new Stream(fopen("php://memory", "r+"));
		$this->version = $version;
	}
	
	/**
	 * Retrieves the HTTP protocol version as a string.
	 *
	 * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
	 *
	 * @return string HTTP protocol version.
	 */
	public function getProtocolVersion() : string {
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
	public function withProtocolVersion($version) : static {
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
	public function getHeaders() : array {
		return $this->headers;
	}
	
	protected function normalizedHeaderName(string $name) : string {
		return trim(mb_strtolower($name, 'utf-8'));
	}
	
	protected function getNormalizedHeaders() : array {
		return array_combine(
			array_map(fn ($key) => $this->normalizedHeaderName($key), array_keys($this->headers)),
			array_values($this->headers)
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
	public function hasHeader($name) : bool {
		return isset($this->getNormalizedHeaders()[$this->normalizedHeaderName($name)]);
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
	public function getHeader($name) : array {
		$name = $this->normalizedHeaderName($name);
		$headers = $this->getNormalizedHeaders();
		
		return isset($headers[$name]) ? $headers[$name] : [];
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
	public function getHeaderLine($name) : string {
		$name = $this->normalizedHeaderName($name);
		$headers = $this->getNormalizedHeaders();
		
		return isset($headers[$name]) ? implode(",", $headers[$name]) : "";
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
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withHeader($name, $value) : static {
		$normalizedName = $this->normalizedHeaderName($name);
		if (!$this->hasHeader($normalizedName)) {
			throw new InvalidArgumentException("Provided header name doesn't exists. Use withAddedHeader method to append specified header instead.");
		}
		
		$headerNameMap = array_combine(array_keys($this->getNormalizedHeaders()), array_keys($this->getNormalizedHeaders()));
		$newMessage = clone $this;
		$newMessage->headers[$headerNameMap[$normalizedName]] = is_array($value) ? $value : [$value];
		
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
	 * @throws \InvalidArgumentException for invalid header names or values.
	 */
	public function withAddedHeader($name, $value) : static {
		$normalizedName = $this->normalizedHeaderName($name);
		$headerNameMap = array_combine(array_keys($this->getNormalizedHeaders()), array_keys($this->headers));
		
		$newMessage = clone $this;
		if (isset($headerNameMap[$normalizedName]) && isset($newMessage->headers[$headerNameMap[$normalizedName]])) {
			$existingValues = $newMessage->headers[$headerNameMap[$normalizedName]];
			$newMessage->headers[$headerNameMap[$normalizedName]] = array_merge($existingValues, is_array($value) ? $value : [$value]);
		}
		else {
			$newMessage->headers[$name] = is_array($value) ? $value : [$value];
		}
		
		return $newMessage;
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
	public function withoutHeader($name) : static {
		$normalizedName = $this->normalizedHeaderName($name);
		$headerNameMap = array_combine(array_keys($this->getNormalizedHeaders()), array_keys($this->headers));
		
		$newMessage = clone $this;
		if (isset($headerNameMap[$normalizedName]) && isset($newMessage->headers[$headerNameMap[$normalizedName]])) {
			unset($newMessage->headers[$headerNameMap[$normalizedName]]);
		}
		
		return $newMessage;
	}
	
	/**
	 * Gets the body of the message.
	 *
	 * @return StreamInterface Returns the body as a stream.
	 */
	public function getBody() : StreamInterface {
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
	 * @throws \InvalidArgumentException When the body is not valid.
	 */
	public function withBody(StreamInterface $body) : static {
		if (!$body->isSeekable()) {
			throw new InvalidArgumentException("Provided StreamInterface is not seekable.");
		}
		
		$newMessage = clone $this;
		$newMessage->body = $body;
		
		return $newMessage;
	}
}