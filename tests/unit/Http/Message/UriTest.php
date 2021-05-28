<?php

namespace Kraber\Test\Unit\Http\Message;

use Kraber\Test\Unit\Http\TestCase;
use Kraber\Http\Message\Uri;
use InvalidArgumentException;

class UriTest extends TestCase {
	public function testConstructorInitializesProperties() {
		$uri = new Uri("https://www.example.tld");
		
		$this->assertIsArray($this->getPropertyValue($uri, 'components'));
	}
	
	/**
	 * @dataProvider providerGetScheme
	 */
	public function testGetScheme(string $uri, string $expected) {
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getScheme());
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getAuthority());
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getUserInfo());
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getHost());
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getPort());
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getPath());
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
				"//privacy"
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
				"/privacy%20and%20settings"
			],
			'url and absolute path encoded' => [
				"https://www.example.tld/privacy%20and%20settings",
				"/privacy%20and%20settings"
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getQuery());
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
				"foo=bar%20alice"
			],
			'query string 1 arg encoded' => [
				"https://www.example.tld?foo=bar%20alice",
				"foo=bar%20alice"
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
				"foo&&alice"
			],
			'query string 2 args not encoded' => [
				"https://www.example.tld?foo=bar stub&alice=bob stub",
				"foo=bar%20stub&alice=bob%20stub"
			],
			'query string 2 args encoded' => [
				"https://www.example.tld?foo=bar%20stub&alice=bob%20stub",
				"foo=bar%20stub&alice=bob%20stub"
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, $target->getFragment());
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
				"account&settings"
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
		$target = new Uri($uri);
		$newTarget = $target->withScheme($newScheme);
		
		$this->assertNotSame($target, $newTarget);
		$this->assertEquals($expected, (string) $newTarget);
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
		$target = new Uri($uri);
		$newTarget = $target->withUserInfo($newUsername, $newPassword);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
		$target = new Uri($uri);
		$newTarget = $target->withHost($host);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
		$target = new Uri($uri);
		$newTarget = $target->withPort($port);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
	
	public function testWithPortThrowsExceptionOnInvalidPortRange() {
		$target = new Uri("/");
		
		$this->expectException(InvalidArgumentException::class);
		$target->withPort(-1);
	}
	
	/**
	 * @dataProvider providerWithPath
	 */
	public function testWithPath(string $uri, string $path, string $expected) {
		$target = new Uri($uri);
		$newTarget = $target->withPath($path);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
				"https://www.example.tld/my%20account/settings",
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
		$target = new Uri($uri);
		$newTarget = $target->withQuery($query);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
				"https://www.example.tld/?foo=bar%20alice",
			],
			'url append query string encoded' => [
				"https://www.example.tld/",
				"foo=bar%20alice",
				"https://www.example.tld/?foo=bar%20alice",
			],
		];
	}
	
	/**
	 * @dataProvider providerWithFragment
	 */
	public function testWithFragment(string $uri, string $fragment, string $expected) {
		$target = new Uri($uri);
		$newTarget = $target->withFragment($fragment);
		
		$this->assertNotSame($newTarget, $target);
		$this->assertEquals($expected, (string) $newTarget);
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
				"https://www.example.tld/#section/3.1",
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
		$target = new Uri($uri);
		
		$this->assertEquals($expected, (string) $target);
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
				"https://www.example.tld/foo//bar",
			],
			'double ampersand' => [
				"https://www.example.tld/?foo&&bar",
				"https://www.example.tld/?foo&&bar",
			],
			'decoded path' => [
				"https://www.example.tld/my account",
				"https://www.example.tld/my%20account",
			],
			'encoded path' => [
				"https://www.example.tld/my%20account",
				"https://www.example.tld/my%20account",
			],
			'decoded query string' => [
				"https://www.example.tld/?foo=hello world",
				"https://www.example.tld/?foo=hello%20world",
			],
			'encoded query string' => [
				"https://www.example.tld/?foo=hello%20world",
				"https://www.example.tld/?foo=hello%20world",
			],
		];
	}
	
	public function testUriWithDoubleSlashAndHostRemovedKeepOnlyOneSlash() {
		$uri = new Uri("//example.com:8080//test");
		$uri = $uri->withHost("");
		
		$this->assertEquals("/test", (string) $uri);
	}
	
	public function testPathIsPrependedWithSlashIfHostComponentIsRemoved() {
		$uri = (new Uri())
			->withHost("example.tld")
			->withPath("account");
		
		$this->assertEquals("//example.tld/account", (string) $uri);
		
		$uri = $uri->withHost("");
		$this->assertEquals("/account", (string) $uri);
	}
	
	public function testWithHostThrowsExceptionOnInvalidArgument() {
		$uri = new Uri("https://example.com:8080/account/");
		$this->expectException(InvalidArgumentException::class);
		$uri->withHost(42);
	}
	
	public function testWithHostEmptyArgumentRemoveHost() {
		$uri = new Uri("//example.com:8080/test");
		$uri = $uri->withHost("");
		
		$this->assertEquals("/test", (string) $uri);
	}
	
	public function testWithPortThrowsExceptionOnInvalidArgument() {
		$uri = new Uri("https://example.com:8080/account/");
		$this->expectException(InvalidArgumentException::class);
		$uri->withPort("42");
	}
	
	public function testWithPortEmptyArgumentRemovePort() {
		$uri = new Uri("https://example.com:8080/");
		$uri = $uri->withPort();
		
		$this->assertEquals("https://example.com/", (string) $uri);
	}
	
	public function testWithPathThrowsExceptionOnInvalidArgument() {
		$uri = new Uri("https://example.com/account/");
		$this->expectException(InvalidArgumentException::class);
		$uri->withPath(42);
	}
	
	public function testWithPathEmptyArgumentRemovePath() {
		$uri = new Uri("https://example.com/account/");
		$uri = $uri->withPath("");
		
		$this->assertEquals("https://example.com", (string) $uri);
	}
	
	public function testWithQueryThrowsExceptionOnInvalidArgument() {
		$uri = new Uri("https://example.com/");
		$this->expectException(InvalidArgumentException::class);
		$uri->withQuery(42);
	}
	
	public function testWithQueryEmptyArgumentRemoveQuery() {
		$uri = new Uri("https://example.com/?bar=foo");
		$uri = $uri->withQuery("");
		
		$this->assertEquals("https://example.com/", (string) $uri);
	}
	
	public function testWithFragmentThrowsExceptionOnInvalidArgument() {
		$uri = new Uri("https://example.com/");
		$this->expectException(InvalidArgumentException::class);
		$uri->withFragment(42);
	}
	
	public function testWithFragmentEmptyArgumentRemoveFragment() {
		$uri = new Uri("https://example.com/#section");
		$uri = $uri->withFragment("");
		
		$this->assertEquals("https://example.com/", (string) $uri);
	}
}
