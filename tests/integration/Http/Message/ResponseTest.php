<?php

declare(strict_types=1);

namespace Kraber\Test\Integration\Http\Message;

use Http\Psr7Test\ResponseIntegrationTest;
use Kraber\Http\Message\Response;

class ResponseTest extends ResponseIntegrationTest
{
    public function createSubject()
    {
        return new Response();
    }
}
