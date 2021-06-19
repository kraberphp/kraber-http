<?php

declare(strict_types=1);

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\TestCase;
use Kraber\Http\Factory\ServerRequestFactory;

class ServerRequestFactoryTest extends TestCase
{
    public function testCreateServerRequest()
    {
        $serverRequestFactory = new ServerRequestFactory();
        $serverRequest = $serverRequestFactory->createServerRequest("GET", "/home", ['foo' => 'bar']);

        $this->assertEquals("GET", $serverRequest->getMethod());
        $this->assertEquals("/home", $serverRequest->getRequestTarget());
        $this->assertEquals("/home", (string) $serverRequest->getUri());
        $this->assertEquals(['foo' => 'bar'], $serverRequest->getServerParams());
        $this->assertNotEquals($_SERVER, $serverRequest->getServerParams());
    }
}
