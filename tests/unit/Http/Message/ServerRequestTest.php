<?php

namespace Kraber\Test\Unit\Http\Message;

use Kraber\Test\Unit\Http\TestCase;
use Kraber\Http\Message\{
	ServerRequest,
	UploadedFile
};
use InvalidArgumentException;

class ServerRequestTest extends TestCase
{
	public function testConstructorInitializesProperties() {
		$serverRequest = new ServerRequest();
		
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'serverParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'cookieParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'queryParams'));
		$this->assertIsArray($this->getPropertyValue($serverRequest, 'uploadedFiles'));
		$this->assertNull($this->getPropertyValue($serverRequest, 'parsedBody'));
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
	
	public function testGetAttributeReturnDefaultValueIfAttributeIsUndefined() {
		$serverRequest = new ServerRequest();
		
		$this->assertEquals('foo', $serverRequest->getAttribute('myAttribute', 'foo'));
	}
	
	public function testWithAttribute() {
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withAttribute('alice', 'bob');
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertEquals(null, $serverRequest->getAttribute('alice'));
		$this->assertEquals('bob', $newServerRequest->getAttribute('alice'));
	}
	
	public function testWithoutAttribute() {
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withAttribute('alice', 'bob');
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertEquals(null, $serverRequest->getAttribute('alice'));
		$this->assertEquals('bob', $newServerRequest->getAttribute('alice'));
		
		$newServerRequest = $newServerRequest->withoutAttribute('alice');
		$this->assertEquals(null, $newServerRequest->getAttribute('alice'));
		$this->assertEquals([], $newServerRequest->getAttributes());
	}
	
	public function testWithoutAttributeReturnCurrentInstanceRatherThanACopy() {
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withoutAttribute('foo');

		$this->assertSame($newServerRequest, $serverRequest);
		$this->assertEquals([], $newServerRequest->getAttributes());
	}
	
	/**
	 * @dataProvider providerWithParsedBody
	 */
	public function testWithParsedBody($parsedBody) {
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withParsedBody($parsedBody);
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertEquals($parsedBody, $newServerRequest->getParsedBody());
	}
	
	public function providerWithParsedBody() {
		return [
			'null body' => [null],
			'array body' => [['foo' => 'bar']],
			'object body' => [new class() {}]
		];
	}
	
	public function testWithParsedBodyThrowsExceptionOnInvalidParsedBodyType() {
		$this->expectException(InvalidArgumentException::class);
		
		$serverRequest = new ServerRequest();
		$serverRequest->withParsedBody('This is not a parsed body.');
	}
	
	public function testWithUploadedFiles() {
		$uploadedFiles = [
			new UploadedFile("filename.txt")
		];
		
		$serverRequest = new ServerRequest();
		$newServerRequest = $serverRequest->withUploadedFiles($uploadedFiles);
		
		$this->assertNotSame($newServerRequest, $serverRequest);
		$this->assertSame($uploadedFiles, $newServerRequest->getUploadedFiles());
	}
	
	public function testWithUploadedFilesThrowsExceptionOnInvalidArgument() {
		$uploadedFiles = [
			new UploadedFile("filename.txt"),
			'foo'
		];
		
		$this->expectException(InvalidArgumentException::class);
		$serverRequest = new ServerRequest();
		$serverRequest->withUploadedFiles($uploadedFiles);
	}
}
