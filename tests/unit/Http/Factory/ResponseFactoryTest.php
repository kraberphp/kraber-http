<?php

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\Unit\Http\TestCase;
use Kraber\Http\Factory\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
	public function testCreateResponse() {
		$responseFactory = new ResponseFactory();
		$response = $responseFactory->createResponse(200, "All good !");
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("All good !", $response->getReasonPhrase());
	}
}
