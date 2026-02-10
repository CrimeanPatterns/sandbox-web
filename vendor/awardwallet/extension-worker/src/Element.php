<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

class Element {

    private const TEXT_NODE = 3;
    private const ATTRIBUTE_NODE = 2;

    /** @internal */
    public string $id;
    private Communicator $communicator;
    private Tab $tab;
    /** @internal */
    public int $frameId;
    /** @internal */
    public int $nodeType;
    private QuerySelectorParams $querySelectorParams;
    private LoggerInterface $logger;

    public function __construct(string $id, int $nodeType, Communicator $communicator, Tab $tab, int $frameId, QuerySelectorParams $querySelectorParams, LoggerInterface $logger) {
        $this->id = $id;
        $this->communicator = $communicator;
        $this->tab = $tab;
        $this->frameId = $frameId;
        $this->nodeType = $nodeType;
        $this->querySelectorParams = $querySelectorParams;
        $this->logger = $logger;
    }

    public function click(): void
    {
        $this->onlyElementNode();
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "method", ["method" => "click", "arguments" => []]))
        );
    }

    public function focus(): void
    {
        $this->onlyElementNode();
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "method", ["method" => "focus", "arguments" => []]))
        );
    }

    public function setValue(string $text): void
    {
        $this->onlyElementNode();
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "method", ["method" => "focus", "arguments" => []]))
        );
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "setProperty", ["property" => "value", "value" => $text]))
        );
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "dispatchEvent", ["event" => "input"]))
        );
        $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "dispatchEvent", ["event" => "change"]))
        );
    }

    public function getNodeName(): string
    {
        if ($this->nodeType === self::TEXT_NODE) {
            return "TEXT";
        }

        if ($this->nodeType === self::ATTRIBUTE_NODE) {
            return "ATTRIBUTE";
        }

        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "getProperty", ["property" => "nodeName"]))
        );
    }

    public function getInnerText(): string
    {
        return $this->retryOnDetachedElement(function() {
            $propertyName = $this->isTextNode() ? "textContent" : "innerText";
            return $this->communicator->sendMessageToExtension(
                new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "getProperty", ["property" => $propertyName]))
            );
        });
    }

    public function getInnerHtml(): string
    {
        $this->onlyElementNode();
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "getProperty", ["property" => "innerHTML"]))
        );
    }

    public function checked(): bool
    {
        $this->onlyElementNode();
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "getProperty", ["property" => "checked"]))
        );
    }

    public function getValue(): string
    {
        $this->onlyElementNode();
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "getProperty", ["property" => "value"]))
        );
    }

    private function onlyElementNode(): void
    {
        if ($this->isTextNode()) {
            throw new \InvalidArgumentException("Only ELEMENT nodes can be used for this operation, this is TEXT node ($this->nodeType)");
        }
    }

    public function getAttribute(string $attributeName): ?string
    {
        $this->onlyElementNode();
        return $this->communicator->sendMessageToExtension(
            new ExtensionRequest("element", new ElementRequest($this->id, $this->tab->getId(), $this->frameId, "method", ["method" => "getAttribute", "arguments" => [$attributeName]]))
        );
    }
    
    public function shadowRoot() : ShadowRoot
    {
        return new ShadowRoot($this->tab, $this);
    }

    private function isTextNode() : bool
    {
        return in_array($this->nodeType, [self::TEXT_NODE, self::ATTRIBUTE_NODE]);
    }

    private function retryOnDetachedElement(Callable $executor)
    {
        $startTime = microtime(true);
        $lastError = null;

        while ($lastError === null || (microtime(true) - $startTime) < 5) {
            try {
                return call_user_func($executor);
            } catch (ExtensionError $error) {
                $lastError = $error;

                if (stripos($error->getMessage(), "REMOVED_FROM_DOM")) {
                    $this->logger->info("element {$this->id} was detached from DOM. Trying to find it again by selector {$this->querySelectorParams->getSelector()}");
                    $elements = $this->tab->querySelectorInternal(
                        $this->querySelectorParams->getSelector(),
                        $this->querySelectorParams->isAll(),
                        $this->querySelectorParams->getMethod(),
                        $this->querySelectorParams->getContextNode(),
                        $this->querySelectorParams->isVisible(),
                        $this->querySelectorParams->isNotEmptyString(),
                        $this->querySelectorParams->isAllFrames(),
                        0
                    );

                    if (isset($elements[$this->querySelectorParams->getPosition()])) {
                        $this->logger->info("replacing element {$this->id} id with {$elements[$this->querySelectorParams->getPosition()]->id} found by selector {$this->querySelectorParams->getSelector()}");
                        $this->id = $elements[$this->querySelectorParams->getPosition()]->id;
                        continue;
                    }

                    sleep(1);
                }

                throw $error;
            }
        }

        throw $lastError;
    }

}