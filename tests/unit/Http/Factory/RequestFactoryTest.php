<?php

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\Unit\Http\TestCase;
use Kraber\Http\Factory\RequestFactory;

class RequestFactoryTest extends TestCase
{
	public function testCreateRequest() {
		$requestFactory = new RequestFactory();
		$request = $requestFactory->createRequest("GET", "https://www.example.com/");
		
		$this->assertEquals("GET", $request->getMethod());
		$this->assertEquals("https://www.example.com/", (string) $request->getUri());
	}
}
