<?php

namespace Kraber\Test\Http\Message;

use Kraber\Test\TestCase;
use Kraber\Http\Message\ServerRequest;
use InvalidArgumentException;

class ServerRequestTest extends TestCase
{
	public function testConstructorInitializesProperties() {
		$serverRequest = new ServerRequest();
		
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'serverParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'cookieParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'queryParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'uploadedFiles'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'parsedBody'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'attributes'));
	}
	
	public function testConstructorInitializesServerParams() {
		$_SERVER['foo'] = 'bar';
		$serverRequest = new ServerRequest();
		
		$this->assertEquals($_SERVER, $serverRequest->getServerParams());
	}
	
	public function testGetServerParamsIsImmutable() {
		$serverRequest = new ServerRequest();
		$_SERVER['foo'] = 'bar';
		
		$this->assertNotEquals($_SERVER, $serverRequest->getServerParams());
	}
	
	public function testWithCookieParams() {
		$cookies = ['foo' => 'bar'];
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withCookieParams($cookies);
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertEquals($cookies, $newServerRequest->getCookieParams());
	}
	
	public function testWithQueryParams() {
		$get = ['foo' => 'bar'];
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withQueryParams($get);
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertEquals($get, $newServerRequest->getQueryParams());
	}
}
