<?php

declare(strict_types=1);

namespace Kraber\Http\Message;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use InvalidArgumentException;

/**
 * Representation of an outgoing, client-side request.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * During construction, implementations MUST attempt to set the Host header from
 * a provided URI if no Host header is provided.
 *
 * Requests are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */
class Request extends AbstractMessage implements RequestInterface
{
    /** @var string Request HTTP method. */
    private string $method = "GET";

    /** @var UriInterface Request URI. */
    private UriInterface $uri;

    /** @var string Request target. */
    private string $requestTarget = "";

    /**
     * Request constructor.
     *
     * @param string $method
     * @param UriInterface|string|null $uri
     * @param array<string, array<string>> $headers
     * @param StreamInterface|null $body
     * @param string $version
     *
     * @throws InvalidArgumentException If invalid HTTP method is provided.
     */
    public function __construct(
        string $method = "GET",
        UriInterface|string|null $uri = null,
        array $headers = [],
        ?StreamInterface $body = null,
        string $version = "1.1"
    ) {
        parent::__construct($headers, $body, $version);

        $this->validateHttpMethod($method);
        if (is_string($uri) || is_null($uri)) {
            $uri = new Uri($uri ?? "/");
        }

        $this->uri = $uri;
        $this->method = $method;
        $this->updateHostHeaderFromUri();
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        if (!empty($this->requestTarget)) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath() ?? "/";
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param mixed $requestTarget
     *
     * @return static
     */
    public function withRequestTarget($requestTarget): static
    {
        $newRequest = clone $this;
        $newRequest->requestTarget = trim($requestTarget);

        return $newRequest;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return static
     * @throws InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException("Invalid argument provided. String expected.");
        }

        $this->validateHttpMethod($method);
        $newRequest = clone $this;
        $newRequest->method = $method;

        return $newRequest;
    }

    /**
     * Validate the format of an HTTP method.
     *
     * @param string $method
     *
     * @throws InvalidArgumentException for invalid HTTP methods.
     */
    private function validateHttpMethod(string $method): void
    {
        if (!preg_match('/^[A-Za-z]+$/', $method)) {
            throw new InvalidArgumentException("Invalid HTTP method provided, method must only be letters.");
        }
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $newRequest = clone $this;
        $newRequest->uri = $uri;

        if ($preserveHost === false || (!$newRequest->hasHeader('host') || empty($newRequest->getHeaderLine('host')))) {
            $newRequest->updateHostHeaderFromUri();
        }

        return $newRequest;
    }

    private function updateHostHeaderFromUri(): void
    {
        if (empty($this->uri->getHost())) {
            return;
        }

        $host = $this->uri->getHost();
        if ($this->uri->getPort() !== null) {
            $host .= ':' . $this->uri->getPort();
        }

        if (isset($this->headerNames['host'])) {
            $headerName = $this->headerNames['host'];
        } else {
            $this->headerNames['host'] = $headerName = 'Host';
        }

        $this->headers = [$headerName => [$host]] + $this->headers;
    }
}
