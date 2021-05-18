<?php

namespace Kraber\Test\Http;

use Kraber\Http\Uri;

class UriTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @dataProvider providerGetScheme
	 */
	public function testGetScheme(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getScheme());
	}
	
	public function providerGetScheme() {
		return [
			'lowercase scheme' => [
				"https://www.example.tld",
				"https"
			],
			'uppercase scheme' => [
				"HTTPS://www.example.tld",
				"https"
			],
			'malformed scheme' => [
				"%fake://www.example.tld",
				""
			],
			'no scheme' => [
				"//www.example.tld",
				""
			],
		];
	}
	
	/**
	 * @dataProvider providerGetAuthority
	 */
	public function testGetAuthority(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getAuthority());
	}
	
	public function providerGetAuthority() {
		return [
			'simple uri' => [
				"https://www.example.tld",
				"www.example.tld"
			],
			'simple uri and standard port' => [
				"https://www.example.tld:443",
				"www.example.tld"
			],
			'simple uri and standard port without scheme' => [
				"//www.example.tld:443",
				"www.example.tld:443"
			],
			'simple uri and custom port' => [
				"https://www.example.tld:8080",
				"www.example.tld:8080"
			],
			
			'username included' => [
				"https://anonymous@www.example.tld",
				"anonymous@www.example.tld"
			],
			'username included and standard port' => [
				"https://anonymous@www.example.tld:443",
				"anonymous@www.example.tld"
			],
			'username included and standard port without scheme' => [
				"//anonymous@www.example.tld:443",
				"anonymous@www.example.tld:443"
			],
			'username included and custom port' => [
				"https://anonymous@www.example.tld:8080",
				"anonymous@www.example.tld:8080"
			],
			
			'username and password included' => [
				"https://anonymous:pass123@www.example.tld",
				"anonymous:pass123@www.example.tld"
			],
			'username and password included and standard port' => [
				"https://anonymous:pass123@www.example.tld:443",
				"anonymous:pass123@www.example.tld"
			],
			'username and password included and standard port without scheme' => [
				"//anonymous:pass123@www.example.tld:443",
				"anonymous:pass123@www.example.tld:443"
			],
			'username and password included and custom port' => [
				"https://anonymous:pass123@www.example.tld:8080",
				"anonymous:pass123@www.example.tld:8080"
			],
		];
	}
	
	/**
	 * @dataProvider providerGetUserInfo
	 */
	public function testGetUserInfo(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getUserInfo());
	}
	
	public function providerGetUserInfo() {
		return [
			'no user info' => [
				"https://www.example.tld",
				""
			],
			'username included' => [
				"https://anonymous@www.example.tld",
				"anonymous"
			],
			'username and password included' => [
				"https://anonymous:pass123@www.example.tld",
				"anonymous:pass123"
			],
		];
	}
	
	/**
	 * @dataProvider providerGetHost
	 */
	public function testGetHost(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getHost());
	}
	
	public function providerGetHost() {
		return [
			'no scheme' => [
				"//www.example.tld",
				"www.example.tld"
			],
			'no host' => [
				"https://anonymous:pass123",
				""
			],
			'domain only' => [
				"https://example.tld",
				"example.tld"
			],
			'subdomain' => [
				"https://www.example.tld",
				"www.example.tld"
			],
			'username included' => [
				"https://anonymous@www.example.tld",
				"www.example.tld"
			],
			'username and password included' => [
				"https://anonymous:pass123@www.example.tld",
				"www.example.tld"
			],
		];
	}
	
	/**
	 * @dataProvider providerGetPort
	 */
	public function testGetPort(string $uri, ?int $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getPort());
	}
	
	public function providerGetPort() {
		return [
			'scheme only' => [
				"https://www.example.tld",
				null
			],
			'no scheme and standard port' => [
				"//www.example.tld:443",
				443
			],
			'scheme and standard port' => [
				"https://www.example.tld:443",
				null
			],
			'scheme and custom port' => [
				"https://www.example.tld:8080",
				8080
			],
		];
	}
	
	/**
	 * @dataProvider providerGetPath
	 */
	public function testGetPath(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getPath());
	}
	
	public function providerGetPath() {
		return [
			'url and no trailing slash' => [
				"https://www.example.tld",
				""
			],
			'url and trailing slash' => [
				"https://www.example.tld/",
				"/"
			],
			'url and absolute path' => [
				"https://www.example.tld/privacy",
				"/privacy"
			],
			'url and absolute path with double slash' => [
				"https://www.example.tld//privacy",
				"/privacy"
			],
			'rootless path and no trailing slash' => [
				"account/profile",
				"account/profile"
			],
			'rootless path and trailing slash' => [
				"account/profile/",
				"account/profile/"
			],
			'url and absolute path not encoded' => [
				"https://www.example.tld/privacy and settings",
				"/privacy+and+settings"
			],
			'url and absolute path encoded' => [
				"https://www.example.tld/privacy+and+settings",
				"/privacy+and+settings"
			],
			'url and absolute path and slash encoded inside' => [
				"https://www.example.tld/privacy%2Fand%2Fsettings/account",
				"/privacy%2Fand%2Fsettings/account"
			],
		];
	}
	
	/**
	 * @dataProvider providerGetQuery
	 */
	public function testGetQuery(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getQuery());
	}
	
	public function providerGetQuery() {
		return [
			'empty query string' => [
				"https://www.example.tld?",
				""
			],
			
			'query string 1 arg' => [
				"https://www.example.tld?foo=bar",
				"foo=bar"
			],
			'query string 1 arg and no value' => [
				"https://www.example.tld?foo=",
				"foo="
			],
			'query string 1 arg not encoded' => [
				"https://www.example.tld?foo=bar alice",
				"foo=bar+alice"
			],
			'query string 1 arg encoded' => [
				"https://www.example.tld?foo=bar+alice",
				"foo=bar+alice"
			],
			
			'query string 2 args' => [
				"https://www.example.tld?foo=bar&alice=bob",
				"foo=bar&alice=bob"
			],
			'query string 2 args and no value' => [
				"https://www.example.tld?foo=&alice=",
				"foo=&alice="
			],
			'query string 2 args' => [
				"https://www.example.tld?foo&alice",
				"foo&alice"
			],
			'query string 2 args with double ampersand' => [
				"https://www.example.tld?foo&&alice",
				"foo&alice"
			],
			'query string 2 args not encoded' => [
				"https://www.example.tld?foo=bar stub&alice=bob stub",
				"foo=bar+stub&alice=bob+stub"
			],
			'query string 2 args encoded' => [
				"https://www.example.tld?foo=bar+stub&alice=bob+stub",
				"foo=bar+stub&alice=bob+stub"
			],
			'query string 2 args include ampersand encoded' => [
				"https://www.example.tld?foo=bar%26stub&alice=bob%26stub",
				"foo=bar%26stub&alice=bob%26stub"
			],
		];
	}
	
	/**
	 * @dataProvider providerGetFragment
	 */
	public function testGetFragment(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, $Uri->getFragment());
	}
	
	public function providerGetFragment() {
		return [
			'empty fragment' => [
				"https://www.example.tld#",
				""
			],
			'fragment' => [
				"https://www.example.tld#account-settings",
				"account-settings"
			],
			'fragment not encoded' => [
				"https://www.example.tld#account&settings",
				"account%26settings"
			],
			'fragment encoded' => [
				"https://www.example.tld#account%26settings",
				"account%26settings"
			],
		];
	}
	
	/**
	 * @dataProvider providerWithScheme
	 */
	public function testWithScheme(string $uri, string $newScheme, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withScheme($newScheme);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithScheme() {
		return [
			'http to https' => [
				"http://www.example.tld/",
				"https",
				"https://www.example.tld/"
			],
			'removing scheme from url with trailing slash' => [
				"https://www.example.tld/",
				"",
				"//www.example.tld/"
			],
			'removing scheme from url with no trailing slash' => [
				"https://www.example.tld",
				"",
				"//www.example.tld"
			],
			'http to https with userinfo' => [
				"http://username:pass123@www.example.tld/",
				"https",
				"https://username:pass123@www.example.tld/"
			],
			'http to https with standard port' => [
				"http://www.example.tld:443/",
				"https",
				"https://www.example.tld/"
			],
			'http to https with custom port' => [
				"http://www.example.tld:8080/",
				"https",
				"https://www.example.tld:8080/"
			],
			'http to https with query string' => [
				"http://www.example.tld/?foo=bar",
				"https",
				"https://www.example.tld/?foo=bar"
			],
			'http to https with fragment' => [
				"http://www.example.tld/#section-3.1",
				"https",
				"https://www.example.tld/#section-3.1"
			],
		];
	}
	
	/**
	 * @dataProvider providerWithUserInfo
	 */
	public function testWithUserInfo(string $uri, string $newUsername, string $newPassword, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withUserInfo($newUsername, $newPassword);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithUserInfo() {
		return [
			'new username and no password' => [
				"https://username@www.example.tld/",
				"anonymous",
				"",
				"https://anonymous@www.example.tld/"
			],
			'new username and password' => [
				"https://username:pass123@www.example.tld/",
				"anonymous",
				"321ssap",
				"https://anonymous:321ssap@www.example.tld/"
			],
			'removing userinfo' => [
				"https://username:pass123@www.example.tld/",
				"",
				"",
				"https://www.example.tld/"
			]
		];
	}
	
	/**
	 * @dataProvider providerWithHost
	 */
	public function testWithHost(string $uri, string $host, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withHost($host);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithHost() {
		return [
			'new host' => [
				"https://www.example.tld/",
				"fake.tld",
				"https://fake.tld/"
			],
			'host as ip' => [
				"https://www.example.tld/",
				"127.0.0.1",
				"https://127.0.0.1/"
			],
			'removing host' => [
				"//example.tld/test",
				"",
				"/test"
			],
		];
	}
	
	/**
	 * @dataProvider providerWithPort
	 */
	public function testWithPort(string $uri, ?int $port, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withPort($port);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithPort() {
		return [
			'standard port to custom' => [
				"https://www.example.tld:443/",
				8080,
				"https://www.example.tld:8080/",
			],
			'custom port to standard port' => [
				"https://www.example.tld:8080/",
				443,
				"https://www.example.tld/"
			],
			'removing custom port' => [
				"https://www.example.tld:8080/",
				null,
				"https://www.example.tld/"
			],
		];
	}
	
	/**
	 * @dataProvider providerWithPath
	 */
	public function testWithPath(string $uri, string $path, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withPath($path);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithPath() {
		return [
			'url removing path' => [
				"https://www.example.tld/test",
				"",
				"https://www.example.tld",
			],
			'url removing path with slash' => [
				"https://www.example.tld/test",
				"/",
				"https://www.example.tld/",
			],
			'url append path' => [
				"https://www.example.tld/",
				"/account/settings",
				"https://www.example.tld/account/settings",
			],
			'url append not encoded path' => [
				"https://www.example.tld/",
				"/my account/settings",
				"https://www.example.tld/my+account/settings",
			],
			'url append encoded path' => [
				"https://www.example.tld/",
				"/home%2Faccount/settings",
				"https://www.example.tld/home%2Faccount/settings",
			]
		];
	}
	
	/**
	 * @dataProvider providerWithQuery
	 */
	public function testWithQuery(string $uri, string $query, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withQuery($query);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithQuery() {
		return [
			'url removing query string' => [
				"https://www.example.tld/?foo=bar",
				"",
				"https://www.example.tld/",
			],
			'url replace query string' => [
				"https://www.example.tld/?foo=bar",
				"alice=bob",
				"https://www.example.tld/?alice=bob",
			],
			'url append query string not encoded' => [
				"https://www.example.tld/",
				"foo=bar alice",
				"https://www.example.tld/?foo=bar+alice",
			],
			'url append query string encoded' => [
				"https://www.example.tld/",
				"foo=bar+alice",
				"https://www.example.tld/?foo=bar+alice",
			],
		];
	}
	
	/**
	 * @dataProvider providerWithFragment
	 */
	public function testWithFragment(string $uri, string $fragment, string $expected) {
		$Uri = new Uri($uri);
		$Uri = $Uri->withFragment($fragment);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerWithFragment() {
		return [
			'url removing fragment' => [
				"https://www.example.tld/#section-3.1",
				"",
				"https://www.example.tld/",
			],
			'url append fragment encoded' => [
				"https://www.example.tld/",
				"section%2F3.1",
				"https://www.example.tld/#section%2F3.1",
			],
			'url append fragment not encoded' => [
				"https://www.example.tld/",
				"section/3.1",
				"https://www.example.tld/#section%2F3.1",
			],
			'url replace fragment' => [
				"https://www.example.tld/#test",
				"section-3.1",
				"https://www.example.tld/#section-3.1",
			],
		];
	}
	
	/**
	 * @dataProvider providerToString
	 */
	public function testToString(string $uri, string $expected) {
		$Uri = new Uri($uri);
		
		$this->assertSame($expected, (string) $Uri);
	}
	
	public function providerToString() {
		return [
			'standard port' => [
				"https://www.example.tld:443/",
				"https://www.example.tld/",
			],
			'custom port' => [
				"https://www.example.tld:8080/",
				"https://www.example.tld:8080/",
			],
			'standard port and no scheme' => [
				"//www.example.tld:443/",
				"//www.example.tld:443/",
			],
			'custom port and no scheme' => [
				"//www.example.tld:8080/",
				"//www.example.tld:8080/",
			],
			'double slash in path' => [
				"https://www.example.tld/foo//bar",
				"https://www.example.tld/foo/bar",
			],
			'double ampersand' => [
				"https://www.example.tld/?foo&&bar",
				"https://www.example.tld/?foo&bar",
			],
			'decoded path' => [
				"https://www.example.tld/my account",
				"https://www.example.tld/my+account",
			],
			'encoded path' => [
				"https://www.example.tld/my+account",
				"https://www.example.tld/my+account",
			],
			'decoded query string' => [
				"https://www.example.tld/?foo=hello world",
				"https://www.example.tld/?foo=hello+world",
			],
			'encoded query string' => [
				"https://www.example.tld/?foo=hello+world",
				"https://www.example.tld/?foo=hello+world",
			],
		];
	}
}