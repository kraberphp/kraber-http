<?php

namespace Kraber\Test\Http;

use Kraber\Http\Stream;
use InvalidArgumentException;
use RuntimeException;

class StreamTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructThrowsExceptionOnInvalidArgument()
	{
		$this->expectException(InvalidArgumentException::class);
		$stream = new Stream(true);
	}
	
	public function testConstructorInitializesProperties()
	{
		$handle = fopen('php://temp', 'r+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertTrue($stream->isReadable());
		$this->assertTrue($stream->isWritable());
		$this->assertTrue($stream->isSeekable());
		$this->assertEquals('php://temp', $stream->getMetadata('uri'));
		$this->assertIsArray($stream->getMetadata());
		$this->assertEquals(4, $stream->getSize());
		$this->assertFalse($stream->eof());
		$stream->close();
	}
	
	public function testStreamClosesHandleOnDestruct()
	{
		$handle = fopen('php://temp', 'r');
		$stream = new Stream($handle);
		unset($stream);
		$this->assertFalse(is_resource($handle));
	}
	
	public function testConvertsToString()
	{
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertEquals('data', (string) $stream);
		$this->assertEquals('data', (string) $stream);
		$stream->close();
	}
	
	public function testGetsContents()
	{
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertEquals('', $stream->getContents());
		$stream->seek(0);
		$this->assertEquals('data', $stream->getContents());
		$this->assertEquals('', $stream->getContents());
		$stream->close();
	}
	
	public function testChecksEof()
	{
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertFalse($stream->eof());
		$stream->read(4);
		$this->assertTrue($stream->eof());
		$stream->close();
	}
	
	public function testGetSize()
	{
		$size = filesize(__FILE__);
		$handle = fopen(__FILE__, 'r');
		$stream = new Stream($handle);
		$this->assertEquals($size, $stream->getSize());
		$stream->close();
	}
	
	public function testEnsuresSizeIsConsistent()
	{
		$h = fopen('php://temp', 'w+');
		$this->assertEquals(3, fwrite($h, 'foo'));
		$stream = new Stream($h);
		$this->assertEquals(3, $stream->getSize());
		$this->assertEquals(4, $stream->write('test'));
		$this->assertEquals(7, $stream->getSize());
		$this->assertEquals(7, $stream->getSize());
		$stream->close();
	}
	
	public function testProvidesStreamPosition()
	{
		$handle = fopen('php://temp', 'w+');
		$stream = new Stream($handle);
		$this->assertEquals(0, $stream->tell());
		$stream->write('foo');
		$this->assertEquals(3, $stream->tell());
		$stream->seek(1);
		$this->assertEquals(1, $stream->tell());
		$this->assertSame(ftell($handle), $stream->tell());
		$stream->close();
	}
	
	public function testKeepsPositionOfResource()
	{
		$h = fopen(__FILE__, 'r');
		fseek($h, 10);
		$stream = new Stream($h);
		$this->assertEquals(10, $stream->tell());
		$stream->close();
	}
	
	public function testCanDetachStream()
	{
		$r = fopen('php://temp', 'w+');
		$stream = new Stream($r);
		$this->assertTrue($stream->isReadable());
		$this->assertSame($r, $stream->detach());
		$this->assertNull($stream->detach());
		$this->assertFalse($stream->isReadable());
		//$this->assertSame('', $stream->read(10));
		$this->assertFalse($stream->isWritable());
		//$this->assertFalse($stream->write('foo'));
		$this->assertFalse($stream->isSeekable());
		//$this->assertFalse($stream->seek(10));
		//$this->assertFalse($stream->tell());
		$this->assertTrue($stream->eof());
		$this->assertNull($stream->getSize());
		$this->assertSame('', (string) $stream);
		//$this->assertSame('', $stream->getContents());
		$stream->close();
	}
}
