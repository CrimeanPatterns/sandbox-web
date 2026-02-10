<?php
namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

class Tab
{
    private const DEFAULT_TIMEOUT = 15;

    public const MESSAGE_RECAPTCHA = 'Message from AwardWallet: In order to log in into this account, you need to solve the CAPTCHA below and click the sign in button. Once logged in, sit back and relax, we will do the rest.';

    private $tabId;
    private Communicator $communicator;
    private int $frameId;
    private LoggerInterface $logger;
    private FileLogger $fileLogger;

    public function __construct($tabId, $communicator, int $frameId, LoggerInterface $logger, FileLogger $fileLogger) {
        $this->tabId = $tabId;
        $this->communicator = $communicator;
        $this->frameId = $frameId;
        $this->logger = $logger;
        $this->fileLogger = $fileLogger;
    }

    public function querySelector($selector, ?QuerySelectorOptions $options = null) : Element {
        if ($options === null) {
            $options = new QuerySelectorOptions();
        }

        $elements = $this->querySelectorInternal($selector, false, "querySelector", $options->getShadowRoot(), $options->getVisible(), $options->getNotEmptyString(), false, $options->getTimeout() ?? self::DEFAULT_TIMEOUT);

        if (count($elements) === 0) {
            throw new SelectorException("Could not locate element by selector {$selector}");
        }

        return $elements[0];
    }

    /**
     * @return Element[]
     */
    public function querySelectorAll($selector, ?QuerySelectorOptions $options = null) : array {
        if ($options === null) {
            $options = new QuerySelectorOptions();
        }

        $this->checkTimeoutOnAll($options->getTimeout());

        return $this->querySelectorInternal($selector, true, "querySelector", $options->getShadowRoot(), $options->getVisible(), $options->getNotEmptyString(), false, 0);
    }

    public function evaluate(string $xpath, ?EvaluateOptions $options = null) : ?Element
    {
        if ($options === null) {
            $options = new EvaluateOptions();
        }

        if ($options->getTimeout() === null && $options->getAllowNull()) {
            $options->timeout(0);
        }

        $elements = $this->querySelectorInternal($xpath, false, "evaluate", $options->getContextNode(), $options->getVisible(), $options->getNotEmptyString(), false, $options->getTimeout() ?? self::DEFAULT_TIMEOUT);

        if (count($elements) === 0 && $options->getAllowNull()) {
            $this->logger->info("evaluate: Could not locate element by xpath {$xpath}, returning null");

            return null;
        }

        if (count($elements) === 0) {
            throw new ElementNotFoundException("Could not locate element by xpath {$xpath}");
        }

        return $elements[0];
    }

    public function evaluateAll(string $xpath, ?EvaluateOptions $options = null) : array {
        if ($options === null) {
            $options = new EvaluateOptions();
        }

        $this->checkTimeoutOnAll($options->getTimeout());

        $elements = $this->querySelectorInternal($xpath, true, "evaluate", $options->getContextNode(), $options->getVisible(), $options->getNotEmptyString(), false, 0);

        return $elements;
    }

    public function findText(string $selector, ?FindTextOptions $options = null) : ?string
    {
        if ($options === null) {
            $options = new FindTextOptions();
        }

        if ($options->getTimeout() === null && $options->getAllowNull()) {
            $options->timeout(0);
        }

        $startTime = microtime(true);
        $endTime = $startTime + ($options->getTimeout() ?? self::DEFAULT_TIMEOUT);
        $timeout = fn() => $endTime - microtime(true);
        $lastError = null;
        $pass = 0;
        $sleep = function() use ($timeout) {
            if ($timeout() > 0.1) {
                sleep(1);
            }
        };

        $pregReplaceIfNeeded = function(?string $result) use ($options) {
            if ($options->getPregReplaceRegexp() === null) {
                return $result;
            }

            return preg_replace($options->getPregReplaceRegexp(), $options->getPregReplaceReplacement(), $result);
        };

        while ($timeout() > 0.1 || $pass === 0) {
            $pass++;
            $elements = $this->querySelectorInternal($selector, false, $options->getMethod(), $options->getContextNode(), $options->getVisible(), $options->getNotEmptyString(), false, $timeout());
            if (count($elements) === 0) {
                $sleep();
                $lastError = "Selector '$selector' not found";
                continue;
            }

            $element = $elements[0];
            $text = $element->getInnerText();
            if ($options->getPreg() === null) {
                return $pregReplaceIfNeeded($text);
            }

            $result = preg($options->getPreg(), $text);
            if ($result !== null) {
                return $pregReplaceIfNeeded($result);
            }

            $lastError = "selector '$selector' found, but preg not found: '{$options->getPreg()}' within text '" . $this->cutString($text) . "'";
            $sleep();
        };

        if ($options->getAllowNull()) {
            $this->logger->info("findText: " . $lastError);

            return null;
        }

        throw new SelectorException($lastError);
    }

    public function findTextNullable(string $selector, ?FindTextOptions $options = null) : ?string
    {
        if ($options === null) {
            $options = new FindTextOptions();
        }

        if ($options->getTimeout() === null) {
            $options->timeout(0);
        }

        try {
            return $this->findText($selector, $options);
        } catch (SelectorException $e) {
            $this->logger->info("findTextNullable: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function findTextAll(string $selector, ?FindTextOptions $options = null) : array
    {
        if ($options === null) {
            $options = new FindTextOptions();
        }

        $this->checkTimeoutOnAll($options->getTimeout());

        $elements = $this->querySelectorInternal($selector, true, $options->getMethod(), $options->getContextNode(), $options->getVisible(), $options->getNotEmptyString(), false, 0);
        $result = array_map(fn(Element $element) => $element->getInnerText(), $elements);

        if ($options->getPreg() === null) {
            return $result;
        }

        return array_filter($result, fn(string $text) => preg($options->getPreg(), $text));
    }

    /**
     * find frame containing specified element. do not specify "frame" or "iframe" within selector
     */
    public function selectFrameContainingSelector(string $selector, ?SelectFrameOptions $options = null) : ?Tab
    {
        if ($options === null) {
            $options = new SelectFrameOptions();
        }

        if ($options->getTimeout() === null && $options->getAllowNull()) {
            $options->timeout(0);
        }

        $elements = $this->querySelectorInternal($selector, false, $options->getMethod(), null, $options->getVisible(), $options->getNotEmptyString(), true, $options->getTimeout() ?? self::DEFAULT_TIMEOUT);

        if (count($elements) === 0 && $options->getAllowNull()) {
            $this->logger->info("evaluate: Could not locate element by selector {$selector}, returning null");

            return null;
        }

        if (count($elements) === 0) {
            throw new SelectorException("Could not locate element by selector {$selector}");
        }

        if (count($elements) > 1) {
            throw new SelectorException("Too much frames matching selector {$selector}");
        }

        $element = $elements[0];

        return new Tab($this->tabId, $this->communicator, $element->frameId, $this->logger, $this->fileLogger);
    }

    public function getHtml() : string
    {
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("getHtml", new NoParamsRequest($this->tabId, $this->frameId))
        );
    }

    public function getUrl() : string
    {
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("getUrl", new NoParamsRequest($this->tabId, $this->frameId))
        );
    }

    public function gotoUrl(string $url) : void
    {
        $this->logger->info("gotoUrl: $url");
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("gotoUrl", new GotoUrlRequest($this->tabId, $this->frameId, $url))
        );
    }

    /**
     * @link https://developer.mozilla.org/en-US/docs/Web/API/fetch
     * @param $options array{ body:string, cache:string, headers:array, method:string, redirect:string, referrer:string }
     */
    public function fetch(string $url, array $options = []) : FetchResponse
    {
        $this->logger->info("fetch $url");
        $response = $this->communicator->sendMessageToExtension(
            new ExtensionRequest("fetch", new FetchRequest($this->tabId, $this->frameId, $url, $options))
        );

        $result = new FetchResponse();
        foreach ($response as $property => $value) {
            if (property_exists($result, $property)) {
                $result->$property = $response[$property];
            }
        }

        $extension = ".html";
        if (stripos($result->headers['content-type'] ?? 'text/html', 'application/json') !== false) {
            $extension = ".json";
        }

        $this->fileLogger->logFile($result->body, $extension);

        return $result;
    }

    /**
     * @return string - base64 encoded screenshot
     */
    public function screenshot() : string
    {
        return base64_decode($this->communicator->sendMessageToExtension(
            new ExtensionRequest("screenshot", new NoParamsRequest($this->tabId, $this->frameId))
        ));
    }

    /**
     * returns cookies for the current domain parsed into array ["cookie1" => "value1", "cookie2" => "value2" ...
     * it will return only cookies accessible to javascript (document.cookie)
     *
     * @return array - ["cookie1" => "value1", "cookie2" => "value2" ...
     */
    public function getCookies() : array
    {
        $cookieStr = $this->communicator->sendMessageToExtension(
            new ExtensionRequest("getCookies", new NoParamsRequest($this->tabId, $this->frameId))
        );

        return CookieParser::parseCookieString($cookieStr);
    }

    public function getFromSessionStorage(string $itemName) : ?string
    {
        $result = $this->communicator->sendMessageToExtension(
            new ExtensionRequest("getFromSessionStorage", new ReadStorageRequest($this->tabId, $this->frameId, $itemName))
        );

        $this->logger->info("getFromSessionStorage('$itemName'): " . ( $result === null ? 'null' : ("'" . $this->cutString($result)) . "'"));

        return $result;
    }

    public function getFromLocalStorage(string $itemName) : ?string
    {
        $result = $this->communicator->sendMessageToExtension(
            new ExtensionRequest("getFromLocalStorage", new ReadStorageRequest($this->tabId, $this->frameId, $itemName))
        );

        $this->logger->info("getFromLocalStorage('$itemName'): " . ( $result === null ? 'null' : ("'" . $this->cutString($result)) . "'"));

        return $result;
    }

    public function back() : void
    {
        $this->logger->info("Tab::back()");
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("back", new NoParamsRequest($this->tabId, $this->frameId))
        );
    }

    /**
     * @internal
     */
    public function getId() : string
    {
        return $this->tabId;
    }

    /**
     * @internal
     * @return Element[]
     */
    public function querySelectorInternal(string $selector, bool $all, string $method, ?Element $contextNode, bool $visible, bool $notEmptyString, bool $allFrames, float $timeout = 15) : array {
        if ($all && $timeout >= 0.1) {
            throw new \InvalidArgumentException("Could not use timeout greater than 0 when all is true");
        }

        $elements = [];
        $poll = true;
        $startTime = microtime(true);

        while ($poll) {
            $elements = $this->communicator->sendMessageToExtension(new ExtensionRequest("querySelector", new QuerySelectorRequest($selector, $all, $this->tabId, $method, $contextNode ? $contextNode->id : null, $visible, $notEmptyString, $allFrames, $this->frameId)));
            $poll = !$all && count($elements) === 0 && (microtime(true) - $startTime) < $timeout;

            if ($poll) {
                usleep(1000000); // Sleep for 1 second
            }
        }

        $position = 0;
        return array_map(function($element) use ($selector, $all, $method, $contextNode, $visible, $notEmptyString, $allFrames, $timeout, &$position) {
            return new Element(
                $element["elementId"],
                $element["nodeType"],
                $this->communicator,
                $this,
                $element["frameId"],
                new QuerySelectorParams($selector, $all, $method, $contextNode, $visible, $notEmptyString, $allFrames, $timeout, $position),
                $this->logger
            );

        }, $elements);
    }

    public function showMessage(string $message) : void
    {
        $this->logger->info("showMessage: $message");
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("showMessage", new ShowMessageRequest($this->tabId, $this->frameId, $message))
        );
    }

    public function hideMessage() : void
    {
        $this->logger->info("hideMessage");
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("hideMessage", new NoParamsRequest($this->tabId, $this->frameId))
        );
    }

    private function cutString(string $text) : string
    {
        if (strlen($text) < 80) {
            return $text;
        }

        return substr($text, 0, 80) . "...";
    }

//    not working, chrome sends tab status updates too late
//    public function waitLoaded(int $idleMilliseconds = 1000, int $maxWaitMilliseconds = 20000) : void
//    {
//        $startTime = microtime(true);
//        do {
//            $status = $this->communicator->sendMessageToExtension(
//                new ExtensionRequest("getTabStatus", new NoParamsRequest($this->tabId, $this->frameId))
//            );
//            $waitTime = (microtime(true) - $startTime) / 1000;
//            $timedOut = $waitTime > $maxWaitMilliseconds;
//            $complete = $status['age'] >= $idleMilliseconds && $status['status'] === 'complete';
//            if (!$timedOut && !$complete) {
//                usleep(500000);
//            }
//        } while (!$complete && !$timedOut);
//    }
    private function checkTimeoutOnAll(?int $timeout) : void
    {
        if ($timeout !== null) {
            throw new \Exception("You could not use timeout with ..All methods");
        }
    }

}