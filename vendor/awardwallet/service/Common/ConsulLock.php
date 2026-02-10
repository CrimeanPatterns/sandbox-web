<?php

namespace AwardWallet\Common;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ConsulLock
{

    private $consulHost;
    private $key;
    private $sessionId;
    private $acquired = false;
    private $ttl;
    /**
     * @var int
     */
    private $lockTime;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $lastRequestInfo = [];

    public function __construct($consulHost, $key, $ttl, $lockTime = 0, $logger = null)
    {
        if(!preg_match('#^\w+$#ims', $key))
            throw new \InvalidArgumentException("Key should be valid identifier");
        $this->consulHost = $consulHost;
        $this->key = $key;
        $this->ttl = $ttl;
        $this->lockTime = $lockTime;
        if(!empty($logger))
            $this->logger = $logger;
        else
            $this->logger = new NullLogger();
    }

    public function acquire($pollingInterval = null, $maxWaitTime = null){
        $startTime = time();
        
        do {
            $this->createSession();
            $current = $this->callConsul("GET", "/kv/{$this->key}", null, [404]);
            if(!empty($current[0])){
                $data = json_decode(base64_decode($current[0]['Value']), true);
                $age = time() - $data['time'];
                if($age > $this->ttl){
                    $this->logger->info("forcing release of expired lock", array_merge(["key" => $this->key], is_array($data) ? $data : []));
                    if(!empty($current[0]['Session'])) {
                        $result = $this->callConsul("PUT", "/kv/{$this->key}?release={$current[0]['Session']}", $this->lockData());
                        if($result !== true)
                            $this->logger->info("failed to force release lock", ["key" => $this->key]);
                    }
                    else
                        $this->logger->info("there are no session on this lock");
                }
            }
            $this->acquired = !empty($this->sessionId) && $this->callConsul("PUT", "/kv/{$this->key}?acquire={$this->sessionId}", $this->lockData());
            if($this->acquired)
                $this->logger->debug("acquired lock {$this->key} at consul {$this->consulHost}");
            else {
                $this->logger->info("can't lock {$this->key} at {$this->consulHost}", ["data" => isset($data) ? $data : null, "sessionId" => !empty($current[0]) && isset($current[0]['Session']) ? $current[0]['Session'] : null]);
            }
            if(!empty($pollingInterval) && !$this->acquired) {
                $this->logger->debug("sleeping {$pollingInterval} seconds");
                sleep($pollingInterval);
            }
        }
        while(!$this->acquired && !empty($pollingInterval) && (empty($maxWaitTime) || time() < ($startTime + $maxWaitTime)));

        return $this->acquired;
    }

    public function release(){
        if($this->acquired) {
            $this->callConsul("PUT", "/kv/{$this->key}?release={$this->sessionId}", $this->lockData());
            $this->logger->debug("released lock {$this->key}");
            $this->acquired = false;
        }
    }

    private function createSession(){
        if(!empty($this->sessionId)) {
            $sessions = $this->callConsul("PUT", "/session/renew/{$this->sessionId}");
            if(is_array($sessions) && count($sessions) == 1)
                $session = array_pop($sessions);
        }

        if(empty($session['ID'])){
            $session = $this->callConsul("PUT", "/session/create", [
                "Name" => $this->key . " at " . gethostname() . ", pid " . getmypid(),
                "TTL" => $this->ttl . "s",
                "LockDelay" => $this->lockTime . "s"
            ]);
        }

        if (!empty($session['ID']))
            $this->sessionId = $session['ID'];
        else
            $this->sessionId = null;
    }

    private function callConsul($method, $path, $body = null, $ignoreHttpCodes = []){
        $curl = curl_init("http://" . $this->consulHost . ":8500/v1" . $path);
       	if(empty($curl))
       	    throw new \Exception("failed to init curl");
       	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
       	curl_setopt($curl, CURLOPT_TIMEOUT, 5);
       	curl_setopt($curl, CURLOPT_FAILONERROR, false);
       	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
       	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if(!empty($body))
       	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        $try = 0;
        do{
            $try++;
            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            $errNo = curl_errno($curl);
            $errMessage = curl_error($curl);
            if($errNo != 0){
                $this->logger->warning("curl error {$errNo} {$errMessage}, try $try");
            }
        } while($errNo != 0 && $try <= 3);
        curl_close($curl);
        $this->logger->debug("$method $path", array_merge(["request" => $body, "result" => $result, "errNo" => $errNo, "errMessage" => $errMessage], $info));
        if($errNo != 0 || empty($info['http_code']) || $info['http_code'] != 200) {
            if(empty($info['http_code']) || !in_array($info['http_code'], $ignoreHttpCodes))
                $this->logger->info("$method $path failed", array_merge($info, ["error_number" => $errNo, "error_message" => $errMessage, "result" => $result]));
            return false;
        }
        $result = json_decode($result, true);
        return $result;
    }

    private function lockData()
    {
        return [
            "host" => gethostname(),
            "pid" => getmypid(),
            "time" => time(),
            "formattedTime" => date("Y-m-d H:i:s"),
        ];
    }

}