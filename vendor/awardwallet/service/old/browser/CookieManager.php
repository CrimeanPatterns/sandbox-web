<?php
require_once __DIR__ . '/HttpPluginInterface.php';

class CookieManager implements HttpPluginInterface
{

	/**
	 * An array containing cookie values
	 */
	private $cookies = array();

	/**
	 * @var string
	 */
	private $lastHost;

	public function getState(){
		return array_filter(get_object_vars($this), function($value){ return !empty($value); });
	}

	public function setState($state){
		foreach (array_keys(get_object_vars($this)) as $property) {
			if(!empty($state[$property]))
				$this->$property = $state[$property];
		}
	}

	/**
	 * Adds cookies to the request
	 */
	public function onRequest(HttpDriverRequest $request)
	{
		$urlParts = parse_url($request->url);
		if(empty($urlParts['path']))
			$urlParts['path'] = '/';
		if(empty($urlParts['host']))
			return true;
		$this->lastHost = $urlParts['host'];
		if (!empty($this->cookies)) {
			// We do not check cookie's "expires" field, as we do not store deleted
			// cookies in the array and our client does not work long enough for other
			// cookies to expire.
			$cookies = array();
			foreach ($this->cookies as $cookie) {
				if ($this->domainMatch($urlParts['host'], $cookie['domain']) && (0 === strpos($urlParts['path'], $cookie['path']))
					&& (empty($cookie['secure']) || strtolower($urlParts['scheme']) == 'https')
				) {
					$cookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
				}
			}
			// cookies with longer paths go first
			$pairs = [];
			foreach ($cookies as $name => $values) {
				krsort($values);
				foreach ($values as $value)
					$pairs[] = $name.'='.$this->quote($value);
			}
			$request->headers['Cookie'] = implode("; ", $pairs);
		}
		return true;
	}

	private function quote($value)
	{
//		if(!empty($value) && substr($value, 0, 1) != '"' && preg_match("#[\x{01}-\x{21}\x{23}-\x{2B}\x{2D}-\x{3A}\x{3C}-\x{5B}\x{5D}-\x{7E}]#ms", $value))
//			return '"' . $value . '"';
//		else
			return $value;
	}


	/**
	 * adds cookie to the list
	 */
	public function setCookie($name, $value, $domain = null, $path = "/", $secure = false, $expires = null)
	{
		if(empty($domain))
			$domain = $this->lastHost;
		$hash = $this->makeHash($name, $domain, $path);
		if($expires === null)
			$expires = time() + SECONDS_PER_DAY * 365 * 3;
		$this->cookies[$hash] = ["name" => $name, "value" => $value, "domain" => $domain, "path" => $path, "secure" => $secure, "expires" => $expires];
	}

	public function getCookie($name, $domain = null, $path = "/"){
		if(empty($domain))
			$domain = $this->lastHost;
		foreach ($this->cookies as $cookie)
			if ($cookie['name'] == $name && $this->domainMatch($domain, $cookie['domain']) && (0 === strpos($path, $cookie['path'])))
				return $cookie['value'];
		return null;
	}

	/**
	 * Parse a Set-Cookie header and return cookies array
	 */
	private function parseSetCookies(HttpDriverResponse $response)
	{
		$result = [];
		$headers = $response->headers;
		if(empty($headers['set-cookie']))
			return [];
		if(!is_array($headers['set-cookie']))
			$headers['set-cookie'] = [$headers['set-cookie']];
		foreach($headers['set-cookie'] as $cookieValue){
			$cookie = array(
				'expires' => null,
				'domain' => null,
				'path' => null,
				'secure' => false
			);
			// Only a name=value pair
			if (!strpos($cookieValue, ';')) {
				$pos = strpos($cookieValue, '=');
				$cookie['name'] = trim(substr($cookieValue, 0, $pos));
				$cookie['value'] = trim(substr($cookieValue, $pos + 1));

				// Some optional parameters are supplied
			} else {
				$elements = explode(';', $cookieValue);
				$pos = strpos($elements[0], '=');
				$cookie['name'] = trim(substr($elements[0], 0, $pos));
				$cookie['value'] = trim(substr($elements[0], $pos + 1));

				for ($i = 1; $i < count($elements); $i++) {
					if (false === strpos($elements[$i], '=')) {
						$elName = trim($elements[$i]);
						$elValue = null;
					} else {
						list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
						$elValue = urldecode($elValue);
					}
					$elName = strtolower($elName);
					if ('secure' == $elName) {
						$cookie['secure'] = true;
					} elseif ('expires' == $elName) {
						$cookie['expires'] = str_replace('"', '', $elValue);
						if (preg_match('/\b(\d{4})\b/ims', $cookie['expires'], $matches))
							if (intval($matches[1]) > 2030)
								$cookie['expires'] = str_replace($matches[1], "2030", $cookie['expires']);
						if (preg_match('/\b(\d{1,2}\-[a-z]{3}\-)(\d{2})\b/ims', $cookie['expires'], $matches))
							if (intval($matches[2]) > 37)
								$cookie['expires'] = str_replace($matches[0], $matches[1]."37", $cookie['expires']);
						$cookie['expires'] = strtotime($cookie['expires']);
					} elseif ('path' == $elName || 'domain' == $elName) {
                        if ($elName == 'domain')
                            $elValue = strtolower($elValue);
						$cookie[$elName] = $elValue;
					} else {
						$cookie[$elName] = $elValue;
					}
				}
			}
			$result[] = $cookie;
		}
		return $result;
	}

	/**
	 * Updates cookie list from HTTP server response
	 */
	public function onResponse(HttpDriverResponse $response)
	{
		if (false !== ($cookies = $this->parseSetCookies($response))) {
			$url = parse_url($response->request->url);
			if(empty($url['path']))
				$url['path'] = '/';
			foreach ($cookies as $cookie) {
				// use the current domain by default
				if (!isset($cookie['domain'])) {
					$cookie['domain'] = $url['host'];
				}
				// use the path to the current page by default
				if (empty($cookie['path'])) {
					$cookie['path'] = DIRECTORY_SEPARATOR == dirname($url['path']) ? '/' : dirname($url['path']);
				}
				// check if the domains match
				if ($this->domainMatch($url['host'], $cookie['domain'])) {
					$hash = $this->makeHash($cookie['name'], $cookie['domain'], $cookie['path']);
					// if value is empty or the time is in the past the cookie is deleted, else added
					if($cookie['value'] == '""')
						$cookie["value"] = '';
					if (strlen($cookie['value'])
						&& (!isset($cookie['expires']) || ($cookie['expires'] > time()))
					) {
						$this->cookies[$hash] = $cookie;
					} elseif (isset($this->cookies[$hash])) {
						unset($this->cookies[$hash]);
					}
				}
			}
		}
	}


	/**
	 * Generates a key for the $cookies array.
	 */
	private function makeHash($name, $domain, $path)
	{
		return md5($name . "\r\n" . $domain . "\r\n" . $path);
	}


	/**
	 * Checks whether a cookie domain matches a request host.
	 */
	public static function domainMatch($requestHost, $cookieDomain)
	{
		if ($requestHost == $cookieDomain) {
			return true;
		}
		// IP address, we require exact match
		if (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $requestHost)) {
			return false;
		}
		if ('.' != substr($cookieDomain, 0, 1)) {
			$cookieDomain = '.' . $cookieDomain;
		}
		// prevents setting cookies for '.com'
		if (substr_count($cookieDomain, '.') < 2) {
			return false;
		}
		return substr('.' . $requestHost, -strlen($cookieDomain)) == $cookieDomain;
	}

	public function reset(){
		$this->cookies = [];
	}


	public function getCookies($host, $path = "/", $secure = false){
		$cookies = [];
		foreach ($this->cookies as $cookie) {
			if ($this->domainMatch($host, $cookie['domain']) && (0 === strpos($path, $cookie['path']))
				&& (empty($cookie['secure']) || $secure)) {
				$cookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
			}
		}
		// cookies with longer paths go first
		$result = array();
		foreach ($cookies as $name => $values) {
			krsort($values);
			foreach ($values as $value) {
				$result[$name] = $value;
			}
		}
		return $result;
	}

} 