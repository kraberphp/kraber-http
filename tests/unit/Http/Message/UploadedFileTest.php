<?php

namespace Kraber\Test\Unit\Http\Message;

use Kraber\Test\TestCase;
use Kraber\Http\Message\{
	UploadedFile,
	Stream
};
use org\bovigo\vfs\vfsStream;
use RuntimeException;
use InvalidArgumentException;

class UploadedFileTest extends TestCase
{
	private $vfsRoot;
	private $vfsFile;
	
	public function setUp(): void
	{
		parent::setUp();
		$this->vfsRoot = vfsStream::setup();
		$this->vfsFile = vfsStream::newFile("filename.txt")->at($this->vfsRoot)->withContent("Hello World !");
		$this->vfsFileUnreadable = vfsStream::newFile("unreadable_filename.txt", 007)->at($this->vfsRoot)->withContent("Hello World !");
	}
	
	private function getValidUploadedFile() : UploadedFile{
		return new UploadedFile(
			$this->vfsFile->url(),
			strlen($this->vfsFile->getContent()),
			UPLOAD_ERR_OK,
			$this->vfsFile->getName(),
			"text/plain",
		);
	}
	
	private function getUnreadableUploadedFile() : UploadedFile{
		return new UploadedFile(
			$this->vfsFileUnreadable->url(),
			strlen($this->vfsFileUnreadable->getContent()),
			UPLOAD_ERR_OK,
			$this->vfsFileUnreadable->getName(),
			"text/plain"
		);
	}
	
	private function getErrorUploadedFile() : UploadedFile{
		return new UploadedFile(
			$this->vfsFile->url(),
			strlen($this->vfsFile->getContent()),
			UPLOAD_ERR_INI_SIZE,
			$this->vfsFile->getName(),
			"text/plain"
		);
	}
	
	public function testConstructorInitializesProperties() {
		$uploadedFile = new UploadedFile("filename.txt");
		
		$this->assertIsString($this->getPropertyValue($uploadedFile, 'file'));
		$this->assertNull($this->getPropertyValue($uploadedFile, 'stream'));
		$this->assertNull($this->getPropertyValue($uploadedFile, 'size'));
		$this->assertIsInt($this->getPropertyValue($uploadedFile, 'error'));
		$this->assertSame(UPLOAD_ERR_OK, $this->getPropertyValue($uploadedFile, 'error'));
		$this->assertNull($this->getPropertyValue($uploadedFile, 'clientFilename'));
		$this->assertNull($this->getPropertyValue($uploadedFile, 'clientMediaType'));
	}
	
	public function testConstructorThrowsExceptionOnInvalidFileArgument() {
		$this->expectException(InvalidArgumentException::class);
		$uploadedFile = new UploadedFile(
			42,
			strlen($this->vfsFile->getContent()),
			UPLOAD_ERR_OK,
			$this->vfsFile->getName(),
			"text/plain",
		);
	}
	
	public function testConstructorThrowsExceptionOnInvalidErrorArgument() {
		$this->expectException(InvalidArgumentException::class);
		$uploadedFile = new UploadedFile(
			$this->vfsFile->url(),
			strlen($this->vfsFile->getContent()),
			42,
			$this->vfsFile->getName(),
			"text/plain"
		);
	}
	
	public function testConstructorWithResourceAsFile() {
		$handle = fopen("php://temp", "r+");
		fwrite($handle, "Hello World !");
		
		$uploadedFile = new UploadedFile(
			$handle,
			ftell($handle),
			UPLOAD_ERR_OK,
			"filename.txt",
			"text/plain"
		);
		
		$stream = $uploadedFile->getStream();
		$this->assertEquals("Hello World !", (string) $stream);
	}
	
	public function testGetSize() {
		$uploadedFile = $this->getValidUploadedFile();
		
		$this->assertSame(strlen($this->vfsFile->getContent()), $uploadedFile->getSize());
	}
	
	public function testGetError() {
		$uploadedFile = $this->getValidUploadedFile();
		
		$this->assertSame(UPLOAD_ERR_OK, $uploadedFile->getError());
	}
	
	public function testGetClientFilename() {
		$uploadedFile = $this->getValidUploadedFile();
		
		$this->assertSame($this->vfsFile->getName(), $uploadedFile->getClientFilename());
	}
	
	public function testGetClientMediaType() {
		$uploadedFile = $this->getValidUploadedFile();
		
		$this->assertSame("text/plain", $uploadedFile->getClientMediaType());
	}
	
	public function testGetStream() {
		$uploadedFile = $this->getValidUploadedFile();
		$stream = $uploadedFile->getStream();
		
		$this->assertSame($uploadedFile->getSize(), $stream->getSize());
		$this->assertSame(file_get_contents($this->vfsFile->url()), (string) $stream);
	}
	
	public function testGetStreamThrowsExceptionUnableToReadFile() {
		$uploadedFile = $this->getUnreadableUploadedFile();
		$this->expectException(RuntimeException::class);
		$uploadedFile->getStream();
	}
	
	public function testGetStreamThrowsExceptionWhenAnErrorCodeIsPresent() {
		$uploadedFile = $this->getErrorUploadedFile();
		$this->expectException(RuntimeException::class);
		$uploadedFile->getStream();
	}
	
	public function testMoveTo() {
		$dst = vfsStream::newFile("new_filename.txt")->at($this->vfsRoot);
		$uploadedFile = $this->getValidUploadedFile();
		$srcContent = (string) $uploadedFile->getStream();
		$uploadedFile->moveTo($dst->url());
		
		$this->assertSame($srcContent, file_get_contents($dst->url()));
	}
	
	public function testMoveToUsingStream() {
		$src = new Stream("php://temp", "r+");
		$src->write("Hello World !");
		$dst = vfsStream::newFile("new_filename.txt")->at($this->vfsRoot);
		
		$this->assertEquals("Hello World !", (string) $src);
		$this->assertEquals("", $dst->getContent());
		
		$uploadedFile = new UploadedFile(
			$src,
			$src->getSize(),
			UPLOAD_ERR_OK,
			null,
			"text/plain",
		);
		$uploadedFile->moveTo($dst->url());
		
		$this->assertEquals("Hello World !", $dst->getContent());
	}
	
	public function testMoveToUsingStreamThrowExceptionOnWriteFailure() {
		$src = new Stream("php://temp", "r+");
		$src->write("Hello World !");
		
		$uploadedFile = new UploadedFile(
			$src,
			$src->getSize(),
			UPLOAD_ERR_OK,
			null,
			"text/plain",
		);
		
		$this->expectException(RuntimeException::class);
		$uploadedFile->moveTo("php://input");
	}
	
	public function testMoveToUsingStreamThrowsExceptionOnInvalidTarget() {
		$src = new Stream("php://temp", "r+");
		$src->write("Hello World !");
		$dst = vfsStream::newFile("new_filename.txt", 007)->at($this->vfsRoot);
		
		$this->assertEquals("Hello World !", (string) $src);
		$this->assertEquals("", $dst->getContent());
		
		$this->expectException(RuntimeException::class);
		$uploadedFile = new UploadedFile(
			$src,
			$src->getSize(),
			UPLOAD_ERR_OK,
			null,
			"text/plain"
		);
		$uploadedFile->moveTo($dst->url());
	}
	
	public function testMoveToThrowsExceptionOnAlreadyMovedFile() {
		$dst = vfsStream::newFile("new_filename.txt")->at($this->vfsRoot);
		$uploadedFile = $this->getValidUploadedFile();
		$srcContent = (string) $uploadedFile->getStream();
		$uploadedFile->moveTo($dst->url());
		
		$this->assertSame($srcContent, file_get_contents($dst->url()));
		
		$this->expectException(RuntimeException::class);
		$uploadedFile->moveTo($dst->url());
	}
	
	public function testMoveToThrowsExceptionOnInvalidArgument() {
		$uploadedFile = $this->getValidUploadedFile();
		$this->expectException(InvalidArgumentException::class);
		$uploadedFile->moveTo(null);
	}
	
	public function testMoveToThrowsExceptionOnUnreadableFile() {
		$dst = vfsStream::newFile("new_filename.txt")->at($this->vfsRoot);
		$uploadedFile = $this->getValidUploadedFile();
		$this->setPropertyValue($uploadedFile, 'file', null);
		$this->expectException(RuntimeException::class);
		$uploadedFile->moveTo($dst->url());
	}
}
