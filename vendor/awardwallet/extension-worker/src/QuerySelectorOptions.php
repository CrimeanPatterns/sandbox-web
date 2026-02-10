<?php

namespace AwardWallet\ExtensionWorker;

class QuerySelectorOptions
{

    private bool $notEmptyString = false;
    private bool $visible = true;
    private ?int $timeout = null;
    private ?Element $shadowRoot = null;

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

    public function shadowRoot(?Element $shadowRoot): QuerySelectorOptions
    {
        $this->shadowRoot = $shadowRoot;
        return $this;
    }

    /**
     * @internal
     */
    public function getShadowRoot(): ?Element
    {
        return $this->shadowRoot;
    }

    public function getNotEmptyString(): bool
    {
        return $this->notEmptyString;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

}