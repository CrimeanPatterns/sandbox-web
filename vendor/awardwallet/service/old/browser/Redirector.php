<?php
require_once __DIR__ . '/HttpPluginInterface.php';

class Redirector implements HttpPluginInterface {

	/**
	 * @var HttpDriverInterface
	 */
	private $driver;
	public $maxRedirects = 5;
	public $parseMetaRedirects = true;
	private $redirects = 0;
    // You can increase timeout for very slow sites (exceptional cases)
    public $curlRequestTimeout = null;

	public function onRequest(HttpDriverRequest $request) {
		if(empty($request->attributes['redirected']))
			$this->redirects = 0;
	}

	public function onResponse(HttpDriverResponse $response){
		$result = null;
		if($this->redirects < $this->maxRedirects){
			$headers = [];
			$url = $this->getHttpRedirect($response);
			if (empty($url) && $this->parseMetaRedirects) {
				$url = $this->getMetaRedirect($response);
				// set Referer only on meta-redirects
				$headers['Referer'] = $response->request->url;
			}
			if(!empty($url)){
				$this->redirects++;
				$result = new HttpDriverRequest($url, 'GET', null, array_merge($response->request->headers, $headers), $this->curlRequestTimeout);
				$result->sslVersion = $response->request->sslVersion;
				$result->attributes['redirected'] = true;
			}
		}
		return $result;
	}

    private function getHttpRedirect(HttpDriverResponse $response) {
        if ($response->httpCode >= 300 && $response->httpCode < 400 && !empty($response->headers['location'])) {
            $locations = $response->headers['location'];
            /*
             * ryanair fix
             *
             * Date: Mon, 17 Sep 2018 16:01:41 GMT
             * Location: HTTP/1.1 301 Moved Permanently
             * Location: https://news.ryanair.com/en/ryanair-ops-page/
             *
             */
            if (is_array($locations)) {
                foreach ($locations as $loc)
                    if (!stristr($loc, 'HTTP/1.1'))
                        $location = $loc;
            }
            else
                $location = $locations;
            if (empty($location))
                return null;
			return $this->rel2abs($location, $response->request->url);
        }
		else
			return null;
	}

	// remove noscript tags
	private function removeTag($body, $tag){
		$p = 0;
		do{
			$p = stripos($body, "<".$tag, $p);
			if($p === false)
				$p = strlen($body);
			else{
				$endPos =  stripos($body, "</".$tag, $p);
				if($endPos === false)
					$p++;
				else{
					$closePos = strpos($body, ">", $endPos);
					if($closePos === false)
						$closePos = $endPos + strlen("</".$tag);
					else
						$closePos++;
					$body = substr($body, 0, $p) . substr($body, $closePos);
				}
			}
		}while($p < strlen($body));
		return $body;
	}

	private function getMetaRedirect(HttpDriverResponse $response)
	{
        if (!empty($response->headers['content-type']) && is_array($response->headers['content-type']))
            $contentType = $response->headers['content-type'][0];
        elseif (isset($response->headers['content-type']))
            $contentType = $response->headers['content-type'];
        else
            $contentType = null;

		if (
		strlen($response->body) > 500000 ||
		empty($response->headers['content-type']) ||
		stripos($contentType, 'text/html') === false ||
		stripos($response->body, '<head>') === false)
			return null;

		// filter out no-scripts, and scrips
		$body = $this->removeTag($response->body, "noscript");
		$body = $this->removeTag($body, "script");

		// No <meta http-equiv=Refresh> tag
		if (!preg_match('!<meta\\s+([^>]*http-equiv\\s*=\\s*("Refresh"|\'Refresh\'|Refresh)[^>]*)>!is', $body, $matches)) {
			return null;
		}
		// Just a refresh, no redirect
		if (!preg_match('!content\\s*=\\s*("[^"]+"|\'[^\']+\'|\\S+)!is', $matches[1], $urlMatches)) {
			return null;
		}
		$parts = explode(';', ('\'' == substr($urlMatches[1], 0, 1) || '"' == substr($urlMatches[1], 0, 1)) ?
			substr($urlMatches[1], 1, -1) : $urlMatches[1]);
		if (empty($parts[1]) || !preg_match('/url\\s*=\\s*("[^"]+"|\'[^\']+\'|\\S+)/is', $parts[1], $urlMatches)) {
			return null;
		}
		// no timed-out refreshes
		if (intval($parts[0]) > 5) {
			return null;
		}
		$url = ('\'' == substr($urlMatches[1], 0, 1) || '"' == substr($urlMatches[1], 0, 1)) ?
			substr($urlMatches[1], 1, -1) : $urlMatches[1];
		// We do finally have an url... Now check that it's:
		// a) HTTP, b) not to the same page
		$previousUrl = $response->request->url;
		$redirectUrl = $this->rel2abs(html_entity_decode($url), $previousUrl);
//		$this->Log("found meta redirect: $redirectUrl", LOG_LEVEL_NORMAL);
		return (null === $redirectUrl || $redirectUrl == $previousUrl) ? null : $redirectUrl;
	}

	private function rel2abs($rel, $base)
	{
	    /* return if already absolute URL */
	    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

	    /* queries and anchors */
	    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

	    /* parse base URL and convert to local variables:
	       $scheme, $host, $path */
	    extract(parse_url($base));
		if(!isset($path))
			$path = "/";
		if(!isset($host))
		    $host = "";

	    /* remove non-directory element from path */
	    $path = preg_replace('#/[^/]*$#', '', $path);

	    /* destroy path if relative url points to root */
	    if ($rel[0] == '/') $path = '';

	    /* dirty absolute URL */
	    $abs = "$host$path/$rel";

	    /* replace '//' or '/./' or '/foo/../' with '/' */
	    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
	    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

	    /* absolute URL is ready! */
	    return $scheme.'://'.$abs;
	}
}