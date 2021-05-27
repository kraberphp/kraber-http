<?php

declare(strict_types=1);

namespace Kraber\Http\Message;

use InvalidArgumentException;

class Uri implements \Psr\Http\Message\UriInterface
{
	/**
	 * Parsed URL components
	 * @var array $components
	 */
	private array $components = [];
	
	/**
	 * @see https://datatracker.ietf.org/doc/html/rfc3986#section-2.2
	 */
	private const UNRESERVED_CHARACTERS = 'a-zA-Z0-9_-.~';
	private const RESERVED_CHARACTERS = ":/?#[]@!$&'()*+,;=";
	
	public function __construct(string $uri = "") {
		$components = parse_url($uri);
		
		if (is_array($components)) {
			$this->components = $components;
		}
	}
	
	/**
	 * Retrieve the scheme component of the URI.
	 *
	 * If no scheme is present, this method MUST return an empty string.
	 *
	 * The value returned MUST be normalized to lowercase, per RFC 3986
	 * Section 3.1.
	 *
	 * The trailing ":" character is not part of the scheme and MUST NOT be
	 * added.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.1
	 * @return string The URI scheme.
	 */
	public function getScheme() : string {
		return mb_strtolower(($this->components['scheme'] ?? ""), "utf-8");
	}
	
	/**
	 * Retrieve the authority component of the URI.
	 *
	 * If no authority information is present, this method MUST return an empty
	 * string.
	 *
	 * The authority syntax of the URI is:
	 *
	 * <pre>
	 * [user-info@]host[:port]
	 * </pre>
	 *
	 * If the port component is not set or is the standard port for the current
	 * scheme, it SHOULD NOT be included.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.2
	 * @return string The URI authority, in "[user-info@]host[:port]" format.
	 */
	public function getAuthority() : string {
		list($userInfo, $host, $port) = [$this->getUserInfo(), $this->getHost(), $this->getPort()];
		
		$authority = "";
		if ($userInfo !== "") $authority = $userInfo."@";
		if ($host !== "") $authority .= $host;
		if ($port !== null) $authority .= ":".$port;
		
		return $authority;
	}
	
	/**
	 * Retrieve the user information component of the URI.
	 *
	 * If no user information is present, this method MUST return an empty
	 * string.
	 *
	 * If a user is present in the URI, this will return that value;
	 * additionally, if the password is also present, it will be appended to the
	 * user value, with a colon (":") separating the values.
	 *
	 * The trailing "@" character is not part of the user information and MUST
	 * NOT be added.
	 *
	 * @return string The URI user information, in "username[:password]" format.
	 */
	public function getUserInfo() : string {
		return isset($this->components['user']) ?
			$this->components['user'].((isset($this->components['pass'])) ? ":".$this->components['pass'] : "") :
			"";
	}
	
	/**
	 * Retrieve the host component of the URI.
	 *
	 * If no host is present, this method MUST return an empty string.
	 *
	 * The value returned MUST be normalized to lowercase, per RFC 3986
	 * Section 3.2.2.
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
	 * @return string The URI host.
	 */
	public function getHost() : string {
		return mb_strtolower(($this->components['host'] ?? ""), "utf-8");
	}
	
	/**
	 * Retrieve the port component of the URI.
	 *
	 * If a port is present, and it is non-standard for the current scheme,
	 * this method MUST return it as an integer. If the port is the standard port
	 * used with the current scheme, this method SHOULD return null.
	 *
	 * If no port is present, and no scheme is present, this method MUST return
	 * a null value.
	 *
	 * If no port is present, but a scheme is present, this method MAY return
	 * the standard port for that scheme, but SHOULD return null.
	 *
	 * @return null|int The URI port.
	 */
	public function getPort() : ?int {
		return (isset($this->components['port']) && !$this->isDefaultPortForScheme($this->components['port'], $this->getScheme())) ?
			$this->components['port'] :
			null;
	}
	
	/**
	 * Check the port associated with a given scheme.
	 *
	 * @param int $port The URI port.
	 * @param string $scheme The URI scheme.
	 * @return bool True if specified port is associated with the given scheme.
	 */
	private function isDefaultPortForScheme(int $port, string $scheme) : bool {
		if (empty($scheme)) {
			return false;
		}
		
		return getservbyname($scheme, "tcp") === $port;
	}
	
	/**
	 * Retrieve the path component of the URI.
	 *
	 * The path can either be empty or absolute (starting with a slash) or
	 * rootless (not starting with a slash). Implementations MUST support all
	 * three syntaxes.
	 *
	 * Normally, the empty path "" and absolute path "/" are considered equal as
	 * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
	 * do this normalization because in contexts with a trimmed base path, e.g.
	 * the front controller, this difference becomes significant. It's the task
	 * of the user to handle both "" and "/".
	 *
	 * The value returned MUST be percent-encoded, but MUST NOT double-encode
	 * any characters. To determine what characters to encode, please refer to
	 * RFC 3986, Sections 2 and 3.3.
	 *
	 * As an example, if the value should include a slash ("/") not intended as
	 * delimiter between path segments, that value MUST be passed in encoded
	 * form (e.g., "%2F") to the instance.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.3
	 * @return string The URI path.
	 */
	public function getPath() : string {
		return isset($this->components['path']) ?
			$this->encodeUriComponent($this->components['path']) :
			"";
	}
	
	/**
	 * Retrieve the query string of the URI.
	 *
	 * If no query string is present, this method MUST return an empty string.
	 *
	 * The leading "?" character is not part of the query and MUST NOT be
	 * added.
	 *
	 * The value returned MUST be percent-encoded, but MUST NOT double-encode
	 * any characters. To determine what characters to encode, please refer to
	 * RFC 3986, Sections 2 and 3.4.
	 *
	 * As an example, if a value in a key/value pair of the query string should
	 * include an ampersand ("&") not intended as a delimiter between values,
	 * that value MUST be passed in encoded form (e.g., "%26") to the instance.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.4
	 * @return string The URI query string.
	 */
	public function getQuery() : string {
		return isset($this->components['query']) ?
			$this->encodeUriComponent($this->components['query']) :
			"";
	}
	
	/**
	 * Retrieve the fragment component of the URI.
	 *
	 * If no fragment is present, this method MUST return an empty string.
	 *
	 * The leading "#" character is not part of the fragment and MUST NOT be
	 * added.
	 *
	 * The value returned MUST be percent-encoded, but MUST NOT double-encode
	 * any characters. To determine what characters to encode, please refer to
	 * RFC 3986, Sections 2 and 3.5.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.5
	 * @return string The URI fragment.
	 */
	public function getFragment() : string {
		return isset($this->components['fragment']) ?
			$this->encodeUriComponent($this->components['fragment']) :
			"";
	}
	
	/**
	 * Return encoded uri component based on RFC 3986 unreserved characters & reserved characters list.
	 * This avoid double-encoding issues and keep already existing percent-encoded.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.3
	 * @param string $component The URI component.
	 * @return string The URI component encoded.
	 */
	private function encodeUriComponent(string $component) : string {
		return preg_replace_callback(
			"/(?:[^".preg_quote(self::UNRESERVED_CHARACTERS.self::RESERVED_CHARACTERS, "/")."%:@\/]+|%(?![a-fA-F0-9]{2}))/",
			fn ($match) => rawurlencode($match[0]),
			$component
		);
	}
	
	/**
	 * Create a URI string based on args given.
	 *
	 * @param string $scheme The URI scheme.
	 * @param string $userInfo The URI user information.
	 * @param string $host The URI host.
	 * @param int|null $port The URI port.
	 * @param string $path The URI path.
	 * @param string $query The URI query.
	 * @param string $fragment The URI fragment.
	 * @return string The formatted URI based on components provided.
	 */
	private function createUriFromSubComponents(
		string $scheme,
		string $userInfo,
		string $host,
		?int $port,
		string $path,
		string $query,
		string $fragment
	) {
		$uri = "";
		if ($userInfo !== "" || $host !== "") {
			if ($scheme !== "") $uri = $scheme.":";
			$uri .= "//";
			if ($userInfo !== "") $uri .= $userInfo.'@';
			if ($host !== "") $uri .= $host;
			if ($port !== null) $uri .= ':'.$port;
		}
		
		if ($path !== "") {
			if ($userInfo === "" && $host === "" && str_starts_with($path, "//")) {
				$path = "/".ltrim($path, "/");
			}
			elseif (($userInfo !== "" || $host !== "") && !str_starts_with($path, "/"))
			{
				$path = "/".$path;
			}
			
			$uri .= $path;
		}
		if ($query !== "") $uri .= '?'.$query;
		if ($fragment !== "") $uri .= '#'.$fragment;
		
		return $uri;
	}
	
	/**
	 * Return an instance with the specified scheme.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified scheme.
	 *
	 * Implementations MUST support the schemes "http" and "https" case
	 * insensitively, and MAY accommodate other schemes if required.
	 *
	 * An empty scheme is equivalent to removing the scheme.
	 *
	 * @param string $scheme The scheme to use with the new instance.
	 * @return static A new instance with the specified scheme.
	 * @throws InvalidArgumentException for invalid scheme.
	 */
	public function withScheme($scheme = null) : static {
		if (!(is_string($scheme) || is_null($scheme))) {
			throw new InvalidArgumentException("Argument provided must be a string, an empty string or null.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$scheme ?? "",
			$this->getUserInfo(),
			$this->getHost(),
			$this->getPort(),
			$this->getPath(),
			$this->getQuery(),
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified user information.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified user information.
	 *
	 * Password is optional, but the user information MUST include the
	 * user; an empty string for the user is equivalent to removing user
	 * information.
	 *
	 * @param string $user The user name to use for authority.
	 * @param null|string $password The password associated with $user.
	 * @return static A new instance with the specified user information.
	 */
	public function withUserInfo($user, $password = null) : static {
		$userInfo = "";
		if (is_string($user) && $user !== "") {
			$userInfo = $user;
			if (is_string($password) && $password !== "") $userInfo .= ':'.$password;
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$userInfo,
			$this->getHost(),
			$this->getPort(),
			$this->getPath(),
			$this->getQuery(),
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified host.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified host.
	 *
	 * An empty host value is equivalent to removing the host.
	 *
	 * @param string $host The hostname to use with the new instance.
	 * @return static A new instance with the specified host.
	 * @throws InvalidArgumentException for invalid host.
	 */
	public function withHost($host = null) : static {
		if (!(is_string($host) || is_null($host))) {
			throw new InvalidArgumentException("Argument provided must be a string, an empty string or null.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$host ?? "",
			$this->getPort(),
			$this->getPath(),
			$this->getQuery(),
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified port.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified port.
	 *
	 * Implementations MUST raise an exception for ports outside the
	 * established TCP and UDP port ranges.
	 *
	 * A null value provided for the port is equivalent to removing the port
	 * information.
	 *
	 * @param null|int $port The port to use with the new instance; a null value
	 *     removes the port information.
	 * @return static A new instance with the specified port.
	 * @throws InvalidArgumentException for invalid ports.
	 */
	public function withPort($port = null) : static {
		if ((!is_int($port) && !empty($port)) || ($port < 0 && $port < 65353)) {
			throw new InvalidArgumentException("Invalid port provided. Allowed port range: 0 - 65353.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$this->getHost(),
			$port,
			$this->getPath(),
			$this->getQuery(),
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified path.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified path.
	 *
	 * The path can either be empty or absolute (starting with a slash) or
	 * rootless (not starting with a slash). Implementations MUST support all
	 * three syntaxes.
	 *
	 * If the path is intended to be domain-relative rather than path relative then
	 * it must begin with a slash ("/"). Paths not starting with a slash ("/")
	 * are assumed to be relative to some base path known to the application or
	 * consumer.
	 *
	 * Users can provide both encoded and decoded path characters.
	 * Implementations ensure the correct encoding as outlined in getPath().
	 *
	 * @param string $path The path to use with the new instance.
	 * @return static A new instance with the specified path.
	 * @throws InvalidArgumentException for invalid path.
	 */
	public function withPath($path = null) : static {
		if (!(is_string($path) || is_null($path))) {
			throw new InvalidArgumentException("Argument provided must be a string, an empty string or null.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$this->getHost(),
			$this->getPort(),
			$path ?? "",
			$this->getQuery(),
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified query string.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified query string.
	 *
	 * Users can provide both encoded and decoded query characters.
	 * Implementations ensure the correct encoding as outlined in getQuery().
	 *
	 * An empty query string value is equivalent to removing the query string.
	 *
	 * @param string $query The query string to use with the new instance.
	 * @return static A new instance with the specified query string.
	 * @throws InvalidArgumentException for invalid query string.
	 */
	public function withQuery($query = null) : static {
		if (!(is_string($query) || is_null($query))) {
			throw new InvalidArgumentException("Argument provided must be a string, an empty string or null.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$this->getHost(),
			$this->getPort(),
			$this->getPath(),
			$query ?? "",
			$this->getFragment()
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return an instance with the specified URI fragment.
	 *
	 * This method MUST retain the state of the current instance, and return
	 * an instance that contains the specified URI fragment.
	 *
	 * Users can provide both encoded and decoded fragment characters.
	 * Implementations ensure the correct encoding as outlined in getFragment().
	 *
	 * An empty fragment value is equivalent to removing the fragment.
	 *
	 * @param string $fragment The fragment to use with the new instance.
	 * @return static A new instance with the specified fragment.
	 * @throws InvalidArgumentException for invalid fragment.
	 */
	public function withFragment($fragment = null) : static {
		if (!(is_string($fragment) || is_null($fragment))) {
			throw new InvalidArgumentException("Argument provided must be a string, an empty string or null.");
		}
		
		$newUri = $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$this->getHost(),
			$this->getPort(),
			$this->getPath(),
			$this->getQuery(),
			$fragment ?? ""
		);
		
		return new static($newUri);
	}
	
	/**
	 * Return the string representation as a URI reference.
	 *
	 * Depending on which components of the URI are present, the resulting
	 * string is either a full URI or relative reference according to RFC 3986,
	 * Section 4.1. The method concatenates the various components of the URI,
	 * using the appropriate delimiters:
	 *
	 * - If a scheme is present, it MUST be suffixed by ":".
	 * - If an authority is present, it MUST be prefixed by "//".
	 * - The path can be concatenated without delimiters. But there are two
	 *   cases where the path has to be adjusted to make the URI reference
	 *   valid as PHP does not allow to throw an exception in __toString():
	 *     - If the path is rootless and an authority is present, the path MUST
	 *       be prefixed by "/".
	 *     - If the path is starting with more than one "/" and no authority is
	 *       present, the starting slashes MUST be reduced to one.
	 * - If a query is present, it MUST be prefixed by "?".
	 * - If a fragment is present, it MUST be prefixed by "#".
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-4.1
	 * @return string
	 */
	public function __toString() : string {
		return $this->createUriFromSubComponents(
			$this->getScheme(),
			$this->getUserInfo(),
			$this->getHost(),
			$this->getPort(),
			$this->getPath(),
			$this->getQuery(),
			$this->getFragment()
		);
	}
}
