<?php

declare(strict_types=1);

namespace Kraber\Http\Factory;

use Psr\Http\Message\{
	RequestFactoryInterface,
	UriInterface,
	RequestInterface
};
use Kraber\Http\Message\Request;

class RequestFactory implements RequestFactoryInterface
{
	/**
	 * Create a new request.
	 *
	 * @param string $method The HTTP method associated with the request.
	 * @param UriInterface|string $uri The URI associated with the request. If
	 *     the value is a string, the factory MUST create a UriInterface
	 *     instance based on it.
	 *
	 * @return RequestInterface
	 */
	public function createRequest(string $method, $uri) : RequestInterface {
		return new Request($method, $uri);
	}
}
