<?php

namespace AwardWallet\Common\AirLabs;


class Context
{

    /** @var bool */
    private $callWasMade;

    /** @var int */
    private $typeGuessing;

    public function __construct()
    {
        $this->callWasMade = false;
        $this->typeGuessing = -1;
    }

    /**
     * @return bool
     */
    public function wasCallMade(): bool
    {
        return $this->callWasMade;
    }

    /**
     * @param bool $callWasMade
     */
    public function setCallWasMade(bool $callWasMade): void
    {
        $this->callWasMade = $callWasMade;
    }

    /**
     * @param int $guess
     */
    public function setTypeGuessing(int $guess): void
    {
        $this->typeGuessing = $guess;
    }

    /**
     * @return int
     */
    public function getTypeGuessing(): int
    {
        return $this->typeGuessing;
    }

}