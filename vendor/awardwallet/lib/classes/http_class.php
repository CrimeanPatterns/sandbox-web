<?
class http_class {

	var $cookies = array();
	var $url = array();
	var $current_url = "";
	var $connection;
	var $request = "";
	var $response = "";
	var $header = "";
	var $body = "";
	var $referer = "";
	var $redirect = true;
	var $redirect_url = "";
	var $debug = false;
	var $log_file;
	var $p_header = array(
		"User-Agent" => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
		"Accept" => "text/html, application/xml;q=0.9, application/xhtml+xml;q=0.9, image/png, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1",
		"Accept-Language" => "en",
		"Accept-Charset" => "windows-1252, utf-8, utf-16, iso-8859-1;q=0.6, *;q=0.1",
		"Accept-Encoding" => "deflate, identity, *;q=0"
	);
	var $arguments = array();
	var $error = "";
	var $protocol = "";
	var $keep_alive = false;
	var $connected_host;
	var $connected_port;

	//---------------------------------------------------------------------------
	function prepare_arguments() {
		if (!isset($this->arguments["URL"])) {
			$this->error = "URL no set";
			return false;
		}
		if ((substr($this->arguments['URL'], 0, 4) != 'http') && isset($this->url['scheme']) && isset($this->url['host'])) {
			// relative url, add host and schema
			$this->arguments['URL'] = $this->url['scheme'] . "://" . $this->url['host'] . $this->arguments['URL'];
		}
		$this->current_url = $this->arguments["URL"];
		$this->url = parse_url($this->arguments["URL"]);
		if (!isset($this->url["path"])) {
			$this->url["path"] = "/";
		}
		$this->protocol = "";
		if ($this->url["scheme"] == "https") {
			$this->protocol = "ssl://";
			if (!isset($this->url["port"])) {
				$this->url["port"] = 443;
			}
		}
		if (!isset($this->url["port"])) {
			$this->url["port"] = 80;
		}
		if (!isset($this->arguments["timeout"])) {
			$this->arguments["timeout"] = 30;
		}
		return true;
	}

	function NameValue($s) {
		$p = strpos($s, "=");
		if ($p > 0)
			return array("Name" => trim(substr($s, 0, $p)), "Value" => trim(substr($s, $p + 1)));
		else
			return null;
	}

	//---------------------------------------------------------------------------
	function set_cookies($header) {
		$header = explode("\r\n", $header);
		$arCookies = array();
		foreach ($this->cookies as $sCookie) {
			$NameValue = $this->NameValue($sCookie);
			$arCookies[$NameValue["Name"]] = $NameValue["Value"];
		}
		foreach ($header as $key => $value) {
			if (preg_match("/^(.*?):(.*)$/", $value, $value)) {
				if (strtolower(trim($value[1])) == "set-cookie") {
					$foo = explode(";", $value[2]);
					$NameValue = $this->NameValue($foo[0]);
					$arCookies[$NameValue["Name"]] = $NameValue["Value"];
					if (preg_match("/Expires\=(\w{3}, \d{2}\-\w{3}\-\d{4} \d{2}\:\d{2}\:\d{2}) GMT/ims", $header[$key], $arMatches)) {
						$time = strtotime($arMatches[1]);
						$arTime = getdate($time);
						$time = gmmktime($arTime['hours'], $arTime['minutes'], $arTime['seconds'], $arTime['mon'], $arTime['mday'], $arTime['year']);
						if ($time < time()) {
							unset($arCookies[$NameValue["Name"]]);
						}
					}
				}
			}
		}
		$this->cookies = array();
		foreach ($arCookies as $sName => $sValue)
			$this->cookies[] = "$sName=$sValue";
	}

	function get_cookies() {
		$str_cookies = "";
		$arCookies = array();
		foreach ($this->cookies as $h => $v)
			$arCookies[] = $v;
		if (count($arCookies) > 0)
			$str_cookies = "Cookie: " . implode("; ", $arCookies) . "\r\n";
		return $str_cookies;
	}

	//---------------------------------------------------------------------------
	function get_post_data($post_values = array()) {
		$post_string = "";
		foreach ($post_values as $key => $val) {
			$post_string .= urlencode($key) . "=" . urlencode($val) . "&";
		}
		$post_string = substr($post_string, 0, -1);
		return $post_string;
	}

	//---------------------------------------------------------------------------
	function parse_response($response) {
		$this->log("parse response");
		preg_match("/^(.*?)\r\n\r\n(.*)$/ims", $response, $match);
		@$this->header = $match[1];
		@$this->body = $match[2];
		//parse header;
		$redirect = "";
		$foo = explode("\r\n", $this->header);
		foreach ($foo as $key => $value) {
			if (preg_match("/^(.*?):(.*)$/", $value, $value)) {
				if (strtolower(trim($value[1])) == "location") {
					$redirect = trim($value[2]);
					$this->redirect_url = trim($value[2]);
					$this->log("redirect url: {$this->redirect_url}");
				}
			}
		}
		$this->set_cookies($this->header);
		if (strlen($redirect) > 0 && $this->redirect) {
			if (!preg_match("/\w{4,5}\:\/\//", $redirect)) {
				$redirect = $this->url["scheme"] . "://" . $this->url["host"] . ":" . $this->url["port"] . $redirect;
			}
			$this->log("Redirect to: " . $redirect . "<hr>");
			/*$this->referer = $this->url[ "scheme" ] . "://" . $this->url[ "host" ] . $this->url[ "path" ];
if( isset( $this->url[ "query" ] ) )
$this->referer .= "?" . $this->url[ "query" ];*/
			$this->arguments["URL"] = $redirect;
			$this->arguments["RequestMethod"] = "GET";
			$this->open($this->arguments);
		}
	}

	//---------------------------------------------------------------------------
	function connect($target, $port, $errno, $errstr, $timeout) {
		if ($this->keep_alive && $this->connection && ($this->connected_host == $target) && ($this->connected_port == $port))
			return true;
		$this->log("connect: " . $this->protocol . $this->url["host"] . ":" . $this->url["port"]);
		$this->connection = @fsockopen($target, $port, $errno, $errstr, $timeout);
		//stream_set_timeout($this->connection, 5);
		if (!$this->connection) {
			$this->log($errno . " : " . $errstr);
			$this->error = $errstr . " (" . $errno . ")";
			return false;
		}
		$this->connected_host = $target;
		$this->connected_port = $port;
		return true;
	}

	//---------------------------------------------------------------------------
	function re_connect() {
		if (!$this->prepare_arguments()) {
			return false;
		}
		if (!$this->connect($this->protocol . $this->url["host"], $this->url["port"], $errno = "", $errstr = "", $this->arguments["timeout"])) {
			return false;
		}
	}

	//---------------------------------------------------------------------------
	function request($request) {
		$this->log("<pre>" . $this->request . "</pre><hr>");
		//send query
		$this->response = "";
		if (@fputs($this->connection, $request) === false) {
			$this->log(("failed to put request"));
		}
		else {
			//get response
			if ($this->keep_alive) {
				$this->log("reading response, keep-alive");
				$nContentLength = null;
				$bChunked = false;
				while (!feof($this->connection)) {
					$sHeader = @fgets($this->connection);
					$this->response .= $sHeader;
					if (preg_match("/Content\-length\: (\d+)/ims", $sHeader, $arMatches))
						$nContentLength = $arMatches[1];
					if (preg_match("/Transfer\-Encoding\: chunked/ims", $sHeader, $arMatches))
						$bChunked = true;
					if (trim($sHeader) == "")
						break;
				}
				$this->log("response read");
				if ($bChunked)
					$this->readChunked();
				else
					if (!isset($nContentLength)) {
						$this->log("can't find Content-Length/Keep-Alive header, read to end, then break connection");
						$nLine = 1;
						while (!feof($this->connection)) {
							$sLine = @fgets($this->connection);
							$this->response .= $sLine;
							$nLine++;
						}
						$this->close_connection();
					}
					else
						$this->response .= @fread($this->connection, $nContentLength);
			}
			else
				while (!feof($this->connection)) {
					$this->response .= @fgets($this->connection);
				}
		}
		$this->log("<div style='max-height: 200px; overflow-x: auto;'><pre>" . htmlspecialchars($this->response) . "</pre></div><hr>");
		$this->parse_response($this->response);
		$this->referer = $this->arguments["URL"];
	}

	function readChunked() {
		$temp = trim(fgets($this->connection));
		$chunk_size = hexdec(trim($temp));
		while ($chunk_size > 0) {
			$sData = "";
			while (strlen($sData) <= $chunk_size)
				$sData .= fgets($this->connection);
			$this->response .= $sData;
			$temp = trim(fgets($this->connection));
			$chunk_size = hexdec(trim($temp));
		}
		fgets($this->connection);
	}


	//---------------------------------------------------------------------------
	function close_connection() {
		$this->log("closing connection");
		@fclose($this->connection);
		$this->connection = null;
	}

	//---------------------------------------------------------------------------
	function open($arg = array()) {
		if (count($arg) > 0) {
			$this->arguments = $arg;
			if (!$this->prepare_arguments()) {
				return false;
			}
		}
		if (!$this->connect($this->protocol . $this->url["host"], $this->url["port"], $errno = "", $errstr = "", $this->arguments["timeout"])) {
			return false;
		}
		$query = "";
		$sHttpVersion = "1.0";
		$sHttpConnection = "close";
		if ($this->keep_alive) {
			$sHttpVersion = "1.1";
			$sHttpConnection = "keep-alive";
		}
		if (isset($this->url["query"])) {
			$query = "?" . $this->url["query"];
		}
		if ($this->arguments["RequestMethod"] == "GET") {
			$this->request = "GET " . $this->url["path"] . $query . " HTTP/{$sHttpVersion}\r\nHost: " . $this->url["host"] . "\r\n";
		}
		if ($this->arguments["RequestMethod"] == "POST") {
			$this->request = "POST " . $this->url["path"] . $query . " HTTP/{$sHttpVersion}\r\nHost: " . $this->url["host"] . "\r\n";
		}
		//add header
		foreach ($this->p_header as $h => $v) {
			$this->request .= $h . ": " . $v . "\r\n";
		}
		if (@is_array($this->arguments["header"])) {
			foreach ($this->arguments["header"] as $h => $v) {
				$this->request .= $h . ": " . $v . "\r\n";
			}
		}
		if ($this->referer) {
			$this->request .= "Referer: " . $this->referer . "\r\n";
		}
		$this->request .= $this->get_cookies();
		if ($this->arguments["RequestMethod"] == "POST") {
			if (isset($this->arguments["PostData"]))
				$poststring = $this->arguments["PostData"];
			else
				$poststring = $this->get_post_data($this->arguments["PostValues"]);
			$this->request .= "Content-type: application/x-www-form-urlencoded\r\n";
			$this->request .= "Content-length: " . strlen($poststring) . "\r\n";
			if ($this->keep_alive)
				$this->request .= "Keep-Alive: 300\r\n";
			$this->request .= "Connection: {$sHttpConnection}\r\n\r\n";
			$this->request .= $poststring;
		}
		else {
			$this->request .= "Content-length: 0\r\n";
			if ($this->keep_alive)
				$this->request .= "Keep-Alive: 300\r\n";
			$this->request .= "Connection: {$sHttpConnection}\r\n\r\n";
		}
		$this->request($this->request);
		if (!$this->keep_alive)
			$this->close_connection();
		return true;
	}

	function log($s) {
		if (isset($this->log_file)) {
			$f = fopen($this->log_file, "a");
			fwrite($f, $s . "<br>\n");
			fclose($f);
		}
		if ($this->debug)
			echo $s . "<br>\n";
	}
}

?>
