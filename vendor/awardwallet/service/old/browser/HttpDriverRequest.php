<?php

class HttpDriverRequest implements Serializable {

	public $url;
	public $method = 'GET';
	public $postData;
	public $headers = [];
	public $proxyAddress;
	public $proxyPort;
	public $proxyType = CURLPROXY_HTTP;
	public $proxyLogin;
	public $proxyPassword;
	public $attributes = [];
	public $sslVersion;
    public $timeout;
    public $http2;

	public function __construct($url, $method = 'GET', $postData = null, array $headers = [], $timeout = null, $attributes = [])
    {
		$this->url = $url;
		$this->method = $method;
		$this->postData = $postData;
		$this->headers = $headers;
        $this->timeout = $timeout;
        $this->attributes = $attributes;
	}

    public function serialize()
    {
        $data = get_object_vars($this);
        foreach ($data['attributes'] as $key => $value) {
            if ($value instanceof \Closure) {
                unset($data['attributes'][$key]);
            }
        }
        return json_encode($data);
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        foreach (array_keys(get_object_vars($this)) as $key) {
            if (isset($data[$key])) {
                $this->$key = $data[$key];
            }
        }
    }
}