<?php

declare(strict_types=1);

namespace Kraber\Http\Factory;

use Psr\Http\Message\{
	UriFactoryInterface,
	UriInterface
};
use Kraber\Http\Message\Uri;

class UriFactory implements UriFactoryInterface
{
	/**
	 * Create a new URI.
	 *
	 * @param string $uri
	 *
	 * @return UriInterface
	 *
	 * @throws \InvalidArgumentException If the given URI cannot be parsed.
	 */
	public function createUri(string $uri = '') : UriInterface {
		return new Uri($uri);
	}
}