<?php

namespace Kraber\Test\Http\Message;

use Kraber\Test\TestCase;
use Kraber\Http\Message\Response;
use InvalidArgumentException;

class ResponseTest extends TestCase
{
	public function testConstructorInitializesProperties() {
		$response = new Response();
		
		$this->assertIsInt($this->getPropertyValue($response, 'code'));
		$this->assertIsString($this->getPropertyValue($response, 'reasonPhrase'));
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getReasonPhrase());
	}
	
	public function testConstructorAndCustomReasonPhraseInitializesProperties() {
		$response = new Response(200, "Good");
		
		$this->assertIsInt($this->getPropertyValue($response, 'code'));
		$this->assertIsString($this->getPropertyValue($response, 'reasonPhrase'));
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("Good", $response->getReasonPhrase());
	}
	
	public function testConstructorThrowsExceptionOnInvalidHttpStatusCode() {
		$this->expectException(InvalidArgumentException::class);
		$response = new Response(42);
	}
	
	public function testWithStatus() {
		$response = new Response();
		
		$newResponse = $response->withStatus(404);
		$this->assertNotSame($newResponse, $response);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals(404, $newResponse->getStatusCode());
		$this->assertEquals("OK", $response->getReasonPhrase());
		$this->assertEquals("Not Found", $newResponse->getReasonPhrase());
	}
	
	public function testWithStatusAndCustomReasonPhrase() {
		$response = new Response();
		
		$newResponse = $response->withStatus(404, "Nope");
		$this->assertNotSame($newResponse, $response);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals(404, $newResponse->getStatusCode());
		$this->assertEquals("OK", $response->getReasonPhrase());
		$this->assertEquals("Nope", $newResponse->getReasonPhrase());
	}
	
	public function testWithStatusThrowsExceptionOnInvalidHttpStatusCode() {
		$response = new Response();
		
		$this->expectException(InvalidArgumentException::class);
		$response->withStatus(42);
	}
}
