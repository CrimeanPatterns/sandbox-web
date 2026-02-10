<?php

namespace AwardWallet\Common\Selenium;

use AwardWallet\Common\Strings;
use Psr\Log\LoggerInterface;

class BrowserCommunicator
{

    public const ATTR_REQUEST_ELEMENT_ID = 'extension-request-element-id';
    public const ATTR_RESPONSE_ELEMENT_ID = 'extension-response-element-id';

    // https://stackoverflow.com/a/23877974
    private const CHROME_PROXY_AUTH_EXT_ID = 'bkbleiaogfdjcabnmdplplokbebkaikc';
    private const REQUEST_RECORDER_EXT_ID = 'nokelfgfkmnchdohcokmibdplbhpekjk';
    private const REQUEST_RECORDER_FIREFOX_EXT_ID = 'request-recorder@awardwallet.com';

    /**
     * @var RemoteWebDriver
     */
    private $webDriver;
    /**
     * @var string
     */
    private $requestElementId;
    /**
     * @var string
     */
    private $responseElementId;
    /**
     * @var string
     */
    private $browserFamilty;
    /**
     * @var int
     */
    private $browserVersion;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private ?bool $isFreshSeleniumPuppeteerResult = null;

    /**
     * @param \RemoteWebDriver $webDriver
     * @param string $extPageUrl - this should match externally_connectable in extensions/bridge/manifest.json. at least second-level domain
     */
    public function __construct($webDriver, string $requestElementId, string $responseElementId, string $browserFamily, int $browserVersion, LoggerInterface $logger)
    {
        $this->webDriver = $webDriver;
        $this->requestElementId = $requestElementId;
        $this->responseElementId = $responseElementId;
        $this->browserFamily = $browserFamily;
        $this->browserVersion = $browserVersion;
        $this->logger = $logger;
    }

    public function isSupportedBrowser() : bool
    {
        return
            in_array($this->browserFamily, [
                \SeleniumFinderRequest::BROWSER_CHROMIUM,
                \SeleniumFinderRequest::BROWSER_CHROME,
                \SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER,
                \SeleniumFinderRequest::BROWSER_CHROME_EXTENSION,
                \SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT,
            ])
            || ($this->browserFamily === \SeleniumFinderRequest::BROWSER_FIREFOX && $this->browserVersion >= \SeleniumFinderRequest::FIREFOX_59);
    }

    /**
     *
     * if you are using async=true, then you must call function sendResponse to pass result
     * $arguments will be available as javascript variable "arguments"
     *
     * @param string $code - javascript code to execute
     * @param bool $async
     */
    public function executeInBackground(string $code, bool $async, $arguments = null)
    {
        if (!$this->isSupportedBrowser()) {
            throw new BrowserCommunicatorException("Unsupported browser for extension communication: " . $this->browserFamilty . ":" . $this->browserVersion);
        }
        // debug
/*        if ($this->browserFamily === \SeleniumFinderRequest::BROWSER_CHROME && $this->browserVersion == 95) {
            throw new BrowserCommunicatorException("selenium-puppeteer does not support execute_async yet");
        }*/

        $haveLocalhostTab = $this->isFreshSeleniumPuppeteer();

        if ($haveLocalhostTab && stripos($code, 'chrome.runtime.sendMessage') !== false) {
            return $this->webDriver->executeAsyncScript("/* EXECUTE_IN_LOCALHOST */
                if (typeof(sendResponse) === 'undefined') {
                    console.log('got sendResponse from arguments')
                    const sendResponse = arguments[arguments.length - 1]
                }
                " . $code);
        }
        
        if ($haveLocalhostTab) {
            return $this->webDriver->executeAsyncScript(
                /** @lang JavaScript */
                "
        // EXECUTE_IN_LOCALHOST
        if (typeof(sendResponse) === 'undefined') {
            console.log('got sendResponse from arguments')
            sendResponse = arguments[arguments.length - 1]
        }
        chrome.runtime.sendMessage(
            'gdjbffnafgkdpacbfokcffhnaljippll', // bridge
            " . json_encode(['code' => $code, "arguments" => $arguments]) . ",
            function (response) {
                sendResponse(response);
            }
        );
",
                []
            );
        }

        [$success, $response] = $this->webDriver->executeAsyncScript( /** @lang JavaScript */
            "
            console.log('executeInBackground');
            console.log(arguments);
            console.log('creating closure');
            (()  => {
            // EXECUTE_IN_TOP_FRAME - this comment understood by selenium-puppeteer, do not delete it
            console.log('in closure');
            const requestElementId = arguments[0];
            const responseElementId = arguments[1];
            const message = arguments[2];
            const sendResponse = arguments[3];
            
            var tries = 0;
            const exec = function() {
            console.log('in exec');
                console.log('locating elements ' + requestElementId + ', ' + responseElementId);
                const requestElement = document.getElementById(requestElementId);
                const responseElement = document.getElementById(responseElementId);
                if (requestElement === null || responseElement === null) {
                    console.log('could not locate elements', requestElementId, responseElementId);
                    tries++;
                    if (tries >= 10) {
                        sendResponse([false, 'Failed to locate request/response elements']);
                        return;
                    }
                    setTimeout(exec, 500);
                    return;
                }
                
                requestElement.setAttribute('data-request', JSON.stringify(message));
                responseElement.setAttribute('data-response', '');
                responseElement.onclick = function() {
                    const response = JSON.parse(responseElement.getAttribute('data-response'));
                    sendResponse(response);
                }
                requestElement.click();
            }
            
            exec()
            })(...arguments);
            ",
            [
                $this->requestElementId,
                $this->responseElementId,
                [
                    "code" => $code,
                    "async" => $async,
                    "arguments" => $arguments,
                ]
            ]
        );

        if (!$success) {
            $this->logger->notice("error communicating with browser: " . Strings::cutInMiddle($response, 250));
            throw new BrowserCommunicatorException($response);
        }
        
        if (is_array($response) && isset($response['error'])) {
            $exception = new BrowserCommunicatorException("error from browser: " . Strings::cutInMiddle($response['error'], 250));
            $this->logger->notice($exception->getMessage());
            throw $exception;
        }

        return $response;

//        $startTime = microtime(true);
//        $extElement = null;
//        do {
//            try {
//                $extElement = $this->webDriver->findElement(\WebDriverBy::id($this->requestElementId));
//            }
//            catch (\NoSuchElementException $exception) {
//                usleep(random_int(100000, 900000));
//            }
//        } while ((microtime(true) - $startTime) < 5 && $extElement === null);
//
//        if ($extElement === null) {
//            throw new \Exception("failed to find extension control element: {$this->requestElementId}");
//        }
//
//        $this->webDriver->executeScript("document.getElementById('{$this->requestElementId}').setAttribute('data-response', '');");
//        $this->webDriver->executeScript("document.getElementById('{$this->requestElementId}').setAttribute('data-request', " . json_encode($code) . ");");
//        $extElement->click();

//        if ($this->webDriver->getCurrentURL() !== $this->extPageUrl) {
//            $this->logger->info("navigating to {$this->extPageUrl} to communicate with extension");
//            $this->webDriver->get($this->extPageUrl);
//        }
//
//        // https://stackoverflow.com/questions/23873623/obtaining-chrome-extension-id-for-development
//        return $this->webDriver->executeAsyncScript( /** @lang JavaScript */
//            "
//            var sendResponse = arguments[1];
//            chrome.runtime.sendMessage('gdjbffnafgkdpacbfokcffhnaljippll', arguments[0], null, function(response) {
//                console.log(response, chrome.runtime.lastError);
//                if (response === null) {
//                    sendResponse(chrome.runtime.lastError.message);
//                    return;
//                }
//                sendResponse(response);
//            })",
//            [
//                [
//                    "code" => $code,
//                    "async" => $async,
//                    "arguments" => $arguments,
//                ]
//            ]
//        );
    }
    
    private function isFreshSeleniumPuppeteer() : bool
    {
        if (!in_array($this->browserFamily, [\SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER, \SeleniumFinderRequest::BROWSER_CHROME_EXTENSION])) {
            return false;
        }

        if ($this->isFreshSeleniumPuppeteerResult !== null) {
            return $this->isFreshSeleniumPuppeteerResult;
        }

        try {
            $version = $this->webDriver->executeScript("CHECK_SELENIUM_PUPPETEER_VERSION");
        }
        catch (\Exception $exception) {
            $this->isFreshSeleniumPuppeteerResult = false;

            return $this->isFreshSeleniumPuppeteerResult;
        }

        $this->isFreshSeleniumPuppeteerResult = ($version !== null);

        return $this->isFreshSeleniumPuppeteerResult;
    }

    public function resetState()
    {
        $script = /** @lang JavaScript */ <<<EOF
        chrome.browsingData.remove(
            {}, 
            {
                "appcache": true,
                "cache": true,
                "cacheStorage": true,
                "cookies": true,
                "downloads": true,
                "fileSystems": true,
                "formData": true,
                "history": true,
                "indexedDB": true,
                "localStorage": true,
                "pluginData": true,
                "passwords": true,
                "serviceWorkers": true,
                "webSQL": true
            },
            function () {
                sendResponse("ok");
            } 
        );
EOF;
        if (in_array($this->browserFamily, [\SeleniumFinderRequest::BROWSER_CHROMIUM, \SeleniumFinderRequest::BROWSER_CHROME])
        && $this->browserVersion < 72) {
            $script = str_replace('"cacheStorage": true,', '', $script);
        }
        $response = $this->executeInBackground($script, true);
        if ($response !== 'ok') {
            throw new \Exception("failed to clear state: " . $response);
        }
    }

    public function getCookies() : array
    {
        $script = /** @lang JavaScript */ <<<EOF
        chrome.cookies.getAll(
            {}, 
            function (cookies) {
                sendResponse(cookies);
            } 
        );
EOF;
        $cookies = $this->executeInBackground($script, true);
        if (!is_array($cookies)) {
           throw new \Exception("failed to get cookies: " . json_encode($cookies));
        }

        $this->logger->info("got " . count($cookies) . " cookies from browser");

        return $cookies;
    }

    public function setCookies(array $cookies) : void
    {
        $this->logger->info("restoring " . count($cookies) . " cookies to browser");

        $script = /** @lang JavaScript */ <<<EOF
        var cookies = arguments;
        
        var setCookie = function() {
            if (cookies.length === 0) {
                sendResponse("ok");
                return;
            }
            
            var fullCookie = cookies.pop();
            console.log("setting cookie: ", fullCookie);

            var newCookie = {};
            //If no real url is available use: "https://" : "http://" + domain + path
            if (fullCookie.url) {
                newCookie.url = fullCookie.url;
            } else {
                newCookie.url = "http" + ((fullCookie.secure) ? "s" : "") + "://" + fullCookie.domain.replace(/^\./, '') + fullCookie.path;
            }
            newCookie.name = fullCookie.name;
            newCookie.value = fullCookie.value;
            if (!fullCookie.hostOnly)
                newCookie.domain = fullCookie.domain;
            newCookie.path = fullCookie.path;
            newCookie.secure = fullCookie.secure;
            newCookie.httpOnly = fullCookie.httpOnly;
            if (!fullCookie.session)
                newCookie.expirationDate = fullCookie.expirationDate;
            //newCookie.storeId = fullCookie.storeId;

            console.log("new cookie: ", newCookie);

            chrome.cookies.set(
                newCookie, 
                function (cookie) {
                    if (cookie === null) {
                        sendResponse('error setting cookie: ' + JSON.stringify(cookie) + ", " + runtime.lastError);
                        return;
                    }
                    setCookie();
                } 
            );
        }  
        
        setCookie();
EOF;
        try {
            $response = $this->executeInBackground($script, true, $cookies);
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->warning("[setCookies] error executeInBackground: " . $e->getMessage() . "\n");
            $response = null;
        }

        if ($response !== 'ok') {
            $exception = new BrowserCommunicatorException("failed to set cookies, expected 'ok' got: " . $response);
            $this->logger->notice($exception->getMessage());
            throw $exception;
        }
    }

    public function switchProxy(string $host, int $port) : void
    {
        if ($this->browserFamily === \SeleniumFinderRequest::BROWSER_FIREFOX) {
            $script = /** @lang JavaScript */ <<<EOF
            browser.proxy.settings.set({value: {
              proxyType: "manual",
              http: "http://{$host}:{$port}",
              httpProxyAll: true,
              proxyDNS: true
          }});            
          sendResponse('ok');
EOF;
        }
        else {
            $script = /** @lang JavaScript */ <<<EOF
            chrome.proxy.settings.set(
              {
                value: {
                    mode: "fixed_servers",
                    pacScript: {},
                    rules: {
                      singleProxy: {
                        scheme: "http",
                        host: "{$host}",
                        port: {$port}
                      }
                    }
                }, 
                scope: 'regular'},
              function() {
                  sendResponse('ok');
              }
            );
EOF;
        }
        $response = $this->executeInBackground($script, true);
        
        if ($response !== 'ok') {
            $exception = new BrowserCommunicatorException("failed to switch proxy, expected 'ok' got: " . $response);
            $this->logger->notice($exception->getMessage());
            throw $exception;
        }
    }
    
    public function canSwitchProxy() : bool
    {
        return
            in_array($this->browserFamily, [\SeleniumFinderRequest::BROWSER_CHROMIUM, \SeleniumFinderRequest::BROWSER_CHROME])
            || ($this->browserFamily === \SeleniumFinderRequest::BROWSER_FIREFOX && $this->browserVersion >= 60);
    }

    public function switchProxyAuth(string $username, string $password) : void
    {
        $script = /** @lang JavaScript */ <<<EOF
        chrome.runtime.sendMessage('%ext_id%', {credentials: {username: '%username%', password: '%password%'}},
          function(response) {
            console.log('delivered');
            sendResponse('ok');
          }
        );        
EOF;
        $script = str_replace('%ext_id%', self::CHROME_PROXY_AUTH_EXT_ID, $script);
        $script = str_replace('%username%', $username, $script);
        $script = str_replace('%password%', $password, $script);

        $response = $this->executeInBackground($script, true);
    }

    /**
     * @return RecordedXHR[]
     * @throws BrowserCommunicatorException
     */
    public function getRecordedRequests(bool $clearHistory = true) : array
    {
        $clearHistory = json_encode($clearHistory); 
        $script = /** @lang JavaScript */ <<<EOF
        chrome.runtime.sendMessage('%ext_id%', {clear: $clearHistory},
          function(response) {
            console.log('received response from recorder');
            console.log(response);
            sendResponse(response);
          }
        );        
EOF;
        $script = str_replace('%ext_id%', in_array($this->browserFamily, [\SeleniumFinderRequest::BROWSER_FIREFOX, \SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT]) ? self::REQUEST_RECORDER_FIREFOX_EXT_ID : self::REQUEST_RECORDER_EXT_ID, $script);
        $response = $this->executeInBackground($script, true);
        $response = array_map(function(array $record) { return new RecordedXHR($record); }, $response);

        return $response;
    }

    public function blockRequests(array $patterns) : void
    {
        $script = /** @lang JavaScript */ <<<EOF
console.log('blockRequests')
if (chrome.webRequest.onBeforeRequest.hasListener(blockRequest)) {
    console.log('blockRequests removeListener')
    chrome.webRequest.onBeforeRequest.removeListener(blockRequest);
}

const patterns = patterns_placeholder;

var validPatterns = patterns.filter(isValidPattern);
  
if (patterns.length) {
    console.log('blocking patterns', patterns)
    try{
      chrome.webRequest.onBeforeRequest.addListener(blockRequest, {
        urls: validPatterns
      }, ['blocking']);
    } catch (e) {
      console.error(e);
    }
} else {
    console.log('blockRequests no patterns')
}

console.log('blockRequests complete')
sendResponse("ok");
  
EOF;
        $script = str_replace('patterns_placeholder', json_encode($patterns), $script);
        $response = $this->executeInBackground($script, true);

        if ($response !== 'ok') {
            $exception = new BrowserCommunicatorException("failed to blockRequests, expected 'ok' got: " . $response);
            $this->logger->notice($exception->getMessage());
            throw $exception;
        }
    }

}
