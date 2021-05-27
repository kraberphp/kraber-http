<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\UriIntegrationTest;
use Kraber\Http\Message\Uri;

class UriTest extends UriIntegrationTest
{
	public function createUri($uri)
	{
		return new Uri($uri);
	}
}
