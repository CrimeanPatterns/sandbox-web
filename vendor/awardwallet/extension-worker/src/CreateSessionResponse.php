<?php

namespace AwardWallet\ExtensionWorker;

class CreateSessionResponse implements \JsonSerializable
{

    private string $sessionId;
    private string $centrifugoJwtToken;

    public function __construct(string $sessionId, string $centrifugoJwtToken)
    {
        $this->sessionId = $sessionId;
        $this->centrifugoJwtToken = $centrifugoJwtToken;
    }

    public function jsonSerialize()
    {
        return [
            'sessionId' => $this->sessionId,
            'centrifugoJwtToken' => $this->centrifugoJwtToken,
        ];
    }

    public function getCentrifugoJwtToken() : string
    {
        return $this->centrifugoJwtToken;
    }

    public function getSessionId() : string
    {
        return $this->sessionId;
    }
}