<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Kraber\Http\Message\ServerRequest;

class ServerRequestTest extends ServerRequestIntegrationTest
{
	public function createSubject()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		
		return new ServerRequest();
	}
}
