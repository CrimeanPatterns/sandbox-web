<?php

namespace AwardWallet\ExtensionWorker;

class ShadowRoot
{

    private Tab $tab;
    private Element $shadowRootElement;

    public function __construct(Tab $tab, Element $shadowRootElement)
    {
        $this->tab = $tab;
        $this->shadowRootElement = $shadowRootElement;
    }

    public function querySelector($selector, ?QuerySelectorOptions $options = null) : Element {
        return $this->tab->querySelector($selector, $this->createOptions($options));
    }

    /**
     * @return Element[]
     */
    public function querySelectorAll($selector, ?QuerySelectorOptions $options = null) : array {
        return $this->tab->querySelectorAll($selector, $this->createOptions($options));
    }

    private function createOptions(?QuerySelectorOptions $options) : QuerySelectorOptions
    {
        if ($options === null) {
            $options = new QuerySelectorOptions();
        }

        $options->shadowRoot($this->shadowRootElement);

        return $options;
    }

}