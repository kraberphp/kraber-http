<?php

declare(strict_types=1);

namespace Kraber\Test\Unit\Http\Message;

use Kraber\Test\TestCase;
use Psr\Http\Message\UriInterface;
use Kraber\Http\Message\Uri;
use Kraber\Http\Message\Request;
use InvalidArgumentException;

class RequestTest extends TestCase
{
    public function testConstructorInitializesProperties()
    {
        $request = new Request();

        $this->assertInstanceOf(UriInterface::class, $this->getPropertyValue($request, 'uri'));
        $this->assertIsString($this->getPropertyValue($request, 'method'));
        $this->assertEquals("/", $request->getRequestTarget());
        $this->assertEquals((string) new Uri("/"), (string) $request->getUri());
        $this->assertEquals("GET", $request->getMethod());
    }

    public function testConstructorThrowsExceptionOnInvalidHttpMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new Request("1337", "/");
    }

    public function testWithRequestTarget()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withRequestTarget("/foo");

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("/test", $request->getRequestTarget());
        $this->assertEquals("www.example.tld", $request->getHeaderLine("host"));
        $this->assertEquals("/foo", $newRequest->getRequestTarget());
        $this->assertEquals("www.example.tld", $newRequest->getHeaderLine("host"));
    }

    public function testGetRequestTarget()
    {
        $request = new Request("GET", "https://www.example.tld/test?bar=foo");
        $this->assertEquals("/test?bar=foo", $request->getRequestTarget());
        $this->assertEquals("www.example.tld", $request->getHeaderLine("host"));
    }

    public function testWithUriPreserveHost()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withUri(new Uri("https://www.sample.tld/foo"), true);

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("/test", $request->getRequestTarget());
        $this->assertEquals("www.example.tld", $request->getHeaderLine("host"));
        $this->assertEquals("/foo", $newRequest->getRequestTarget());
        $this->assertEquals("www.example.tld", $newRequest->getHeaderLine("host"));
    }

    public function testWithUriCanReplaceHostHeader()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withUri(new Uri("https://www.sample.tld/foo"), false);

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("/test", $request->getRequestTarget());
        $this->assertEquals("www.example.tld", $request->getHeaderLine("host"));
        $this->assertEquals("/foo", $newRequest->getRequestTarget());
        $this->assertEquals("www.sample.tld", $newRequest->getHeaderLine("host"));
    }

    public function testWithUriUsingCustomPortCanReplaceHostHeaderAndKeepCustomPort()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withUri(new Uri("https://www.sample.tld:8080/foo"), false);

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("/test", $request->getRequestTarget());
        $this->assertEquals("www.example.tld", $request->getHeaderLine("host"));
        $this->assertEquals("/foo", $newRequest->getRequestTarget());
        $this->assertEquals("www.sample.tld:8080", $newRequest->getHeaderLine("host"));
    }

    public function testWithMethod()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withMethod("post");

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("post", $newRequest->getMethod());
    }

    public function testWithMethodPreserveMethodCase()
    {
        $request = new Request("GET", "https://www.example.tld/test");
        $newRequest = $request->withMethod("poSt");

        $this->assertNotSame($newRequest, $request);
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("poSt", $newRequest->getMethod());
    }

    public function testWithMethodThrowsExceptionOnInvalidHttpMethod()
    {
        $request = new Request("GET", "https://www.example.tld/test");

        $this->expectException(InvalidArgumentException::class);
        $request->withMethod("1337");
    }
}
