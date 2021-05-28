<?php

namespace Kraber\Test\Http\Message;

use Kraber\Test\TestCase;
use Psr\Http\Message\StreamInterface;
use Kraber\Http\Message\{
	Stream,
	AbstractMessage
};
use InvalidArgumentException;

class AbstractMessageTest extends TestCase
{
	protected function getRawMessageImplementation(
		array $headers = [],
		?StreamInterface $body = null,
		string $version = "1.1"
	) : AbstractMessage {
		return new class ($headers, $body, $version) extends AbstractMessage {
			public function __construct($headers, $body, $version) {
				AbstractMessage::__construct($headers, $body, $version);
			}
		};
	}
	
	public function testConstructorInitializesProperties() {
		$message = $this->getRawMessageImplementation();
		
		$this->assertIsArray($this->getPropertyValue($message, 'headers'));
		$this->assertInstanceOf(StreamInterface::class, $this->getPropertyValue($message, 'body'));
		$this->assertIsString($this->getPropertyValue($message, 'version'));
	}
	
	public function testWithProtocolVersion() {
		$message = $this->getRawMessageImplementation();
		$newMessage = $message->withProtocolVersion("42.0");
		
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("42.0", $newMessage->getProtocolVersion());
	}
	
	public function testGetHeaders() {
		$headers = [
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		];
		$message = $this->getRawMessageImplementation($headers);
		
		$this->assertEquals($headers, $message->getHeaders());
	}
	
	public function testGetHeader() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		
		$this->assertEquals(['Mon, 23 May 2005 22:38:34 GMT'], $message->getHeader('Date'));
		$this->assertEquals(['Mon, 23 May 2005 22:38:34 GMT'], $message->getHeader('date'));
		$this->assertEquals([], $message->getHeader('Foo'));
		$this->assertEquals([], $message->getHeader('foo'));
	}
	
	public function testGetHeaderLine() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close'],
			'X-Custom-Header' => ['Foo', 'Bar']
		]);
		
		$this->assertEquals('Mon, 23 May 2005 22:38:34 GMT', $message->getHeaderLine('Date'));
		$this->assertEquals('Mon, 23 May 2005 22:38:34 GMT', $message->getHeaderLine('date'));
		$this->assertEquals('Foo,Bar', $message->getHeaderLine('X-Custom-Header'));
		$this->assertEquals('Foo,Bar', $message->getHeaderLine('x-custom-header'));
		$this->assertEquals("", $message->getHeaderLine('Foo'));
		$this->assertEquals("", $message->getHeaderLine('foo'));
	}
	
	public function testHasHeader() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		
		$this->assertTrue($message->hasHeader('Date'));
		$this->assertTrue($message->hasHeader('date'));
		$this->assertFalse($message->hasHeader('Foo'));
		$this->assertFalse($message->hasHeader('foo'));
	}
	
	public function testWithHeaderCanReplaceWithAString() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withHeader("Connection", "open");
		
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("open", $newMessage->getHeaderLine("Connection"));
		$this->assertEquals("open", $newMessage->getHeaderLine("connection"));
		
		unset($newMessage);
		
		$newMessage = $message->withHeader("connection", "open");
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("open", $newMessage->getHeaderLine("Connection"));
		$this->assertEquals("open", $newMessage->getHeaderLine("connection"));
	}
	
	public function testWithHeaderCanReplaceWithAnArray() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withHeader("ETag", ["3f80f-1c6-3e1cb03b", "3f80f-1d6-3e1cb03b"]);
		
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1c6-3e1cb03b,3f80f-1d6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1c6-3e1cb03b,3f80f-1d6-3e1cb03b", $newMessage->getHeaderLine("etag"));
		
		unset($newMessage);
		
		$newMessage = $message->withHeader("etag", ["3f80f-1c6-3e1cb03b", "3f80f-1d6-3e1cb03b"]);
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1c6-3e1cb03b,3f80f-1d6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1c6-3e1cb03b,3f80f-1d6-3e1cb03b", $newMessage->getHeaderLine("etag"));
	}
	
	public function testWithAddedHeaderCanAppendString() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withAddedHeader("ETag", "3f80f-1c6-3e1cb03b");
		
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("etag"));
		
		unset($newMessage);
		
		$newMessage = $message->withAddedHeader("etag", "3f80f-1c6-3e1cb03b");
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("etag"));
	}
	
	public function testWithAddedHeaderCanAppendArray() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withAddedHeader("ETag", ["3f80f-1c6-3e1cb03b"]);
		
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("etag"));
		
		unset($newMessage);
		
		$newMessage = $message->withAddedHeader("etag", ["3f80f-1c6-3e1cb03b"]);
		$this->assertNotSame($newMessage, $message);
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("ETag"));
		$this->assertEquals("3f80f-1b6-3e1cb03b,3f80f-1c6-3e1cb03b", $newMessage->getHeaderLine("etag"));
	}
	
	public function testWithAddedHeaderCanAppendStringAsNewHeaderName() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withAddedHeader("X-Custom-Header", "Foo");
		
		$this->assertNotSame($newMessage, $message);
		$this->assertTrue($newMessage->hasHeader("X-Custom-Header"));
		$this->assertTrue($newMessage->hasHeader("x-custom-header"));
		$this->assertEquals("Foo", $newMessage->getHeaderLine("X-Custom-Header"));
		$this->assertEquals("Foo", $newMessage->getHeaderLine("x-custom-header"));
	}
	
	public function testWithAddedHeaderCanAppendArrayAsNewHeaderName() {
		$message = $this->getRawMessageImplementation([
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		]);
		$newMessage = $message->withAddedHeader("X-Custom-Header", ["Foo", "Bar"]);
		
		$this->assertNotSame($newMessage, $message);
		$this->assertTrue($newMessage->hasHeader("X-Custom-Header"));
		$this->assertTrue($newMessage->hasHeader("x-custom-header"));
		$this->assertEquals("Foo,Bar", $newMessage->getHeaderLine("X-Custom-Header"));
		$this->assertEquals("Foo,Bar", $newMessage->getHeaderLine("x-custom-header"));
	}
	
	public function testWithoutHeaderCanRemoveExistingHeaderName() {
		$headers = [
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		];
		$message = $this->getRawMessageImplementation($headers);
		$newMessage = $message->withoutHeader("Last-Modified");
		$this->assertNotSame($newMessage, $message);
		$this->assertFalse($newMessage->hasHeader("Last-Modified"));
		
		unset($newMessage);
		$newMessage = $message->withoutHeader("last-modified");
		$this->assertNotSame($newMessage, $message);
		$this->assertFalse($newMessage->hasHeader("Last-Modified"));
	}
	
	public function testWithoutHeaderOnNonExistingHeaderNameKeepAllHeadersAndReturnSameObject() {
		$headers = [
			'Date' => ['Mon, 23 May 2005 22:38:34 GMT'],
			'Content-Type' => ['text/html; charset=UTF-8'],
			'Content-Length' => ['155'],
			'Last-Modified' => ['Wed, 08 Jan 2003 23:11:55 GMT'],
			'Server' => ['Apache/1.3.3.7 (Unix) (Red-Hat/Linux)'],
			'ETag' => ["3f80f-1b6-3e1cb03b"],
			'Accept-Ranges' => ['bytes'],
			'Connection' => ['close']
		];
		$message = $this->getRawMessageImplementation($headers);
		$newMessage = $message->withoutHeader("X-Custom-Header");
		$this->assertSame($newMessage, $message);
		$this->assertFalse($newMessage->hasHeader("X-Custom-Header"));
		$this->assertSame($headers, $newMessage->getHeaders());
	}
	
	public function testWithBody() {
		$message = $this->getRawMessageImplementation([], new Stream("php://memory", "r+"));
		$newMessage = $message->withBody(new Stream("php://memory", "r+"));
		$this->assertNotSame($newMessage, $message);
		$this->assertNotSame($newMessage->getBody(), $message->getBody());
		$newMessage->getBody()->write("Foo");
		$this->assertEquals("Foo", (string) $newMessage->getBody());
		$this->assertEquals("", (string) $message->getBody());
	}
	
	public function testWithBodyThrowsExceptionOnNonSeekableStream() {
		$message = $this->getRawMessageImplementation([], new Stream("php://memory", "r+"));
		$notSeekableStream = new Stream("php://memory", "r+");
		$this->setPropertyValue($notSeekableStream, 'isSeekable', false);
		
		$this->expectException(InvalidArgumentException::class);
		$message->withBody($notSeekableStream);
	}
}
