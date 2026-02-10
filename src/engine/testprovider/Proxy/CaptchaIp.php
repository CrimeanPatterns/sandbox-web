<?php

namespace AwardWallet\Engine\testprovider\Proxy;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\testprovider\Success;

class CaptchaIp extends Success
{
    use ProxyList;

    public function Parse()
    {
        $this->http->GetURL("http://ipinfo.io");
        $this->http->SetProxy($this->proxyDOP());
        $postData = array_merge(
            [
                "type"             => "FunCaptchaTask",
                "websiteURL"       => $this->http->currentUrl(),
                "websitePublicKey" => "pubkey",
                "userAgent"        => $this->http->getDefaultHeader("User-Agent"),
            ],
            $this->getCaptchaProxy()
        );

        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $recognizer->RecognizeTimeout = 120;
        $captcha = $this->recognizeAntiCaptcha($recognizer, $postData, false);
    }
}
