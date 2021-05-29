<?php

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\TestCase;
use Kraber\Http\Factory\StreamFactory;

class StreamFactoryTest extends TestCase
{
	public function testCreateStream() {
		$streamFactory = new StreamFactory();
		$stream = $streamFactory->createStream("Hello world !");
		
		$stream->seek(0);
		$this->assertEquals("Hello world !", $stream->getContents());
	}
	
	public function testCreateStreamFromFile() {
		$streamFactory = new StreamFactory();
		$stream = $streamFactory->createStreamFromFile("php://temp", "r+");
		$stream->write("Hello world !");
		
		$stream->seek(0);
		$this->assertEquals("Hello world !", $stream->getContents());
	}
	
	public function testCreateStreamFromResource() {
		$streamFactory = new StreamFactory();
		$handle = fopen("php://temp", "r+");
		fwrite($handle, "Hello world !");
		$stream = $streamFactory->createStreamFromResource($handle);
		
		$stream->seek(0);
		$this->assertEquals("Hello world !", $stream->getContents());
		$stream->write(" And more.");
		
		$stream->seek(0);
		$this->assertEquals("Hello world ! And more.", $stream->getContents());
	}
}
