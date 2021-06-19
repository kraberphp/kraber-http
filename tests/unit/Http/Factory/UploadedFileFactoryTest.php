<?php

declare(strict_types=1);

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\TestCase;
use Kraber\Http\Message\Stream;
use Kraber\Http\Factory\UploadedFileFactory;

class UploadedFileFactoryTest extends TestCase
{
    public function testCreateUploadedFile()
    {
        $stream = new Stream("php://temp", "r+");
        $stream->write("Hello world !");
        $uploadedFileFactory = new UploadedFileFactory();
        $uploadedFile = $uploadedFileFactory->createUploadedFile(
            $stream,
            $stream->getSize(),
            UPLOAD_ERR_OK,
            "filename.txt",
            "text/plain"
        );

        $this->assertSame($stream, $uploadedFile->getStream());
        $this->assertEquals($stream->getSize(), $uploadedFile->getSize());
        $this->assertEquals("filename.txt", $uploadedFile->getClientFilename());
        $this->assertEquals("text/plain", $uploadedFile->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
    }
}
