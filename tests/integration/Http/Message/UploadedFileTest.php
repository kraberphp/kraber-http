<?php

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\UploadedFileIntegrationTest;
use Kraber\Http\Message\UploadedFile;

class UploadedFileTest extends UploadedFileIntegrationTest
{
	public function createSubject()
	{
		return new UploadedFile(tempnam(sys_get_temp_dir(), 'foo'), null, UPLOAD_ERR_OK, "filename.txt", "text/plain");
	}
}
