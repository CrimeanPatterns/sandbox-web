<?php

namespace AwardWallet\ExtensionWorker;

class EvaluateOptions
{

    private bool $notEmptyString = false;
    private ?Element $contextNode = null;
    private bool $visible = true;
    private ?int $timeout = null;
    private bool $allowNull = false;

    public static function new() : self
    {
        return new self();
    }

    /**
     * skip nodes with empty text
     */
    public function nonEmptyString(bool $notEmptyString = true) : self
    {
        $this->notEmptyString = $notEmptyString;
        return $this;
    }

    /**
     * @param Element|null $node - context node for XPath (evaluate)
     */
    public function contextNode(?Element $node)  : self
    {
        $this->contextNode = $node;
        return $this;
    }

    public function visible(bool $visible) : self
    {
        $this->visible = $visible;
        return $this;
    }

    public function timeout(?int $seconds) : self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function getNotEmptyString(): bool
    {
        return $this->notEmptyString;
    }

    public function getContextNode(): ?Element
    {
        return $this->contextNode;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function getAllowNull(): bool
    {
        return $this->allowNull;
    }

    public function allowNull(bool $allowNull): self
    {
        $this->allowNull = $allowNull;

        return $this;
    }

}