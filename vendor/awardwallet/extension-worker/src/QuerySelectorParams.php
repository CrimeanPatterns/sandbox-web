<?php

namespace AwardWallet\ExtensionWorker;

class QuerySelectorParams
{

    private string $selector;
    private bool $all;
    private string $method;
    private ?Element $contextNode;
    private bool $visible;
    private bool $notEmptyString;
    private bool $allFrames;
    private float $timeout;
    private int $position;

    public function __construct(string $selector, bool $all, string $method, ?Element $contextNode, bool $visible, bool $notEmptyString, bool $allFrames, float $timeout, int $position)
    {

        $this->selector = $selector;
        $this->all = $all;
        $this->method = $method;
        $this->contextNode = $contextNode;
        $this->visible = $visible;
        $this->notEmptyString = $notEmptyString;
        $this->allFrames = $allFrames;
        $this->timeout = $timeout;
        $this->position = $position;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getContextNode(): ?Element
    {
        return $this->contextNode;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isNotEmptyString(): bool
    {
        return $this->notEmptyString;
    }

    public function isAllFrames(): bool
    {
        return $this->allFrames;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

}