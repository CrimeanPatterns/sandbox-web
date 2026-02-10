<?php

namespace AwardWallet\ExtensionWorker;

class FindTextOptions
{

    private ?string $preg = null;
    private bool $notEmptyString = false;
    private ?Element $contextNode = null;
    private bool $visible = true;
    private string $method = 'evaluate';
    private ?int $timeout = null;
    private bool $allowNull = false;
    private ?string $pregReplaceRegexp = null;
    private ?string $pregReplaceReplacement = null;

    public static function new() : self
    {
        return new self();
    }

    /**
     * return text matching pattern. if pattern contains groups, text from first group will be returned
     */
    public function preg(?string $pattern) : self
    {
        $this->preg = $pattern;
        return $this;
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

    /**
     * @param string $method - 'evaluate' or 'querySelector'
     */
    public function method(string $method) : self
    {
        if (!in_array($method, ['evaluate', 'querySelector'])) {
            throw new \InvalidArgumentException("method must be either 'evaluate' or 'querySelector'");
        }

        $this->method = $method;
        return $this;
    }

    public function timeout(?int $seconds) : self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function allowNull(bool $allowNull) : self
    {
        $this->allowNull = $allowNull;

        return $this;
    }

    public function pregReplace(string $regexp, string $replacement) : self
    {
        $this->pregReplaceRegexp = $regexp;
        $this->pregReplaceReplacement = $replacement;

        return $this;
    }

    /**
     * @internal
     */
    public function getPregReplaceRegexp(): ?string
    {
        return $this->pregReplaceRegexp;
    }

    /**
     * @internal
     */
    public function getPregReplaceReplacement(): ?string
    {
        return $this->pregReplaceReplacement;
    }

    public function getPreg() : ?string
    {
        return $this->preg;
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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function getAllowNull(): bool
    {
        return $this->allowNull;
    }

}