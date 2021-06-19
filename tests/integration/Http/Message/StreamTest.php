<?php

declare(strict_types=1);

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\StreamIntegrationTest;
use Kraber\Http\Message\Stream;

class StreamTest extends StreamIntegrationTest
{
    public function createStream($data)
    {
        return new Stream($data);
    }
}
