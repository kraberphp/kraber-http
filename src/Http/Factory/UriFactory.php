<?php

declare(strict_types=1);

namespace Kraber\Http\Factory;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Kraber\Http\Message\Uri;
use InvalidArgumentException;

class UriFactory implements UriFactoryInterface
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     * @return UriInterface
     * @throws InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = ''): UriInterface
    {
        if (parse_url($uri) === false) {
            throw new InvalidArgumentException("Invalid URI provided.");
        }
        
        return new Uri($uri);
    }
}
