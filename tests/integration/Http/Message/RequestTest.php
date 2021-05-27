<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\RequestIntegrationTest;
use Kraber\Http\Message\Request;

class RequestTest extends RequestIntegrationTest
{
	protected $skippedTests = [
		'testMethodIsExtendable' => "It's not extendable, only standards HTTP methods are supported."
	];

	public function createSubject()
	{
		return new Request('/', 'GET');
	}
}
