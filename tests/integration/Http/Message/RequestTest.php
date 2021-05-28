<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\RequestIntegrationTest;
use Kraber\Http\Message\Request;

class RequestTest extends RequestIntegrationTest
{
	public function createSubject()
	{
		return new Request();
	}
}
