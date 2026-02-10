<?php
namespace AwardWallet\Common\Tests;

use Psr\Log\LoggerInterface;

class HttpDriverMock extends \CurlDriver
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    private $mockPrefixes = ['https://maps.googleapis.com', 'http://api.timezonedb.com'];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();

        $this->logger = $logger;
    }

    public function request(\HttpDriverRequest $request)
    {
        if($request->method === "GET" && $this->isCacheable($request->url)) {
            $index = "curldriver:" . $request->method . " " . $this->filterUrl($request->url);

            if(isset(HttpCache::$mockedResponses[$index])) {
                $this->logger->debug('loading request from cache: ' . $index);
                $data = json_decode(HttpCache::$mockedResponses[$index]["response"], true);
                $response = new \HttpDriverResponse();
                foreach ($data as $key => $value) {
                    $response->$key = $value;
                }
                $response->request = $request;
                return $response;
            }

            $this->logger->debug('passing request to parent: ' . $index);
            $result = parent::request($request);

            $data = get_object_vars($result);
            unset($data['request']);
            unset($data['requestHeaders']);
            HttpCache::$mockedResponses[$index] = ["response" => json_encode($data), "version" => 1, "file" => TestPathExtractor::getFileAndLine()];

            return $result;
        }
        else
            $result = parent::request($request);
        return $result;
    }

    private function isCacheable(string $url) : bool
    {
        foreach ($this->mockPrefixes as $prefix)
            if(strpos($url, $prefix) === 0)
                return true;
        return false;
    }

    private function filterUrl(string $url) : string
    {
        $parts = parse_url($url);
        if(!empty($parts['query'])){
            parse_str($parts['query'], $query);
            unset($query['key']);
            unset($query['timestamp']);
            $url = substr($url, 0, strpos($url, "?")) . "?" . http_build_query($query);
        }
        return $url;
    }
    
}
