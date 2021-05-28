<?php

namespace Kraber\Test\Unit\Http\Factory;

use Kraber\Test\Unit\Http\TestCase;
use Kraber\Http\Factory\UriFactory;

class UriFactoryTest extends TestCase
{
	public function testCreateUri() {
		$url = "https://www.example.tld/?foo=bar";
		$uriFactory = new UriFactory();
		$uri = $uriFactory->createUri($url);
		
		$this->assertEquals($url, (string) $uri);
	}
}
