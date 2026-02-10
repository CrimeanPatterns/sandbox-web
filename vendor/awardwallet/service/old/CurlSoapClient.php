<?php

class CurlSoapClient extends TExtSoapClient
{

    private $lastError;
    private $lastRequestInfo = [];

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $this->lastRequestInfo = [];
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $location);
        $this->lastRequestInfo['url'] = $location;

        // If you need to handle headers like cookies, session id, etc. you will have
        // to set them here manually
        $headers = array("Content-Type: text/xml", 'SOAPAction: "' . $action . '"');
        if(!empty($this->options['curl_headers']))
            $headers = array_merge($headers, $this->options['curl_headers']);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        $this->lastRequestInfo['request_headers'] = $headers;

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $request);
        $this->lastRequestInfo['request_body'] = $request;
        curl_setopt($handle, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
//        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

        if(isset($this->options['proxy_host']))
            curl_setopt($handle, CURLOPT_PROXY, $this->options['proxy_host'] . ":" . $this->options['proxy_port']);

        if(isset($this->options['curl_ca_file']))
            curl_setopt($handle, CURLOPT_CAINFO, $this->options['curl_ca_file']);
        if(isset($this->options['curl_ssl_certificate'])){
            curl_setopt($handle, CURLOPT_SSLCERT, $this->options['curl_ssl_certificate']);
            if(isset($this->options['curl_ssl_passphrase']))
                curl_setopt($handle, CURLOPT_SSLCERTPASSWD, $this->options['curl_ssl_passphrase']);
        }

        $response = curl_exec($handle);
        $error = curl_errno($handle) . ' ' . curl_error($handle);
        $info = curl_getinfo($handle);
        if (isset($info['http_code'])) {
            $this->lastRequestInfo['http_code'] = $info['http_code'];
        }
        if(curl_errno($handle) != 0)
            $this->lastError = $error . " " . var_export($info, true);
        else
            $this->lastError = null;
        $this->lastRequestInfo['error'] = $this->lastError;
        curl_close($handle);

        while (preg_match("#^HTTP[^\r\n]+\r\n([^\r\n]+\r\n)*\r\nHTTP#ims", $response)) {
            $response = preg_replace("#^HTTP[^\r\n]+\r\n([^\r\n]+\r\n)*\r\nHTTP#ims", 'HTTP', $response);
        }
        $bodyStart = strpos($response, "\r\n\r\n");
        $body = "";
        if ($bodyStart !== false) {
            $this->__lastResponseHeaders = substr($response, 0, $bodyStart);
            $this->lastRequestInfo['response_headers'] = $this->__lastResponseHeaders;
            $body = substr($response, $bodyStart + 4);
            $this->__lastResponse = $body;
            $this->lastRequestInfo['response_body'] = $body;
        }

        // If you need headers for something, it's not too bad to
        // keep them in e.g. $this->headers and then use them as needed

        return $body;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastRequestInfo() : string
    {
        if (empty($this->lastRequestInfo)) {
            return '';
        }

        $result = "POST {$this->lastRequestInfo['url']}\n";
        if (isset($this->lastRequestInfo['request_headers'])) {
            foreach ($this->lastRequestInfo['request_headers'] as $header) {
                $result .= $header . "\n";
            }
        }

        $result .= "\n";

        if (isset($this->lastRequestInfo['request_body'])) {
            $result .= $this->lastRequestInfo['request_body'] . "\n";
        }

        $result .= "\nResponse:\n\n";

        if (isset($this->lastRequestInfo['response_headers'])) {
            $result .= $this->lastRequestInfo['response_headers'] . "\n\n";
        }
        if (isset($this->lastRequestInfo['response_body'])) {
            $result .= $this->lastRequestInfo['response_body'] . "\n";
        }

        return $result;
    }

}