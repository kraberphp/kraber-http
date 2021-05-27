<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\RequestIntegrationTest;
use Kraber\Http\Message\Request;

class RequestTest extends RequestIntegrationTest
{
	protected $skippedTests = [
		'testMethodIsExtendable' => "Only standards HTTP methods are supported otherwise an exception is thrown."
	];

	public function createSubject()
	{
		return new Request('/', 'GET');
	}
}
