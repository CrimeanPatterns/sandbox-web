<?php

namespace AwardWallet\ExtensionWorker;

use phpcent\Client;
use Psr\Log\LoggerInterface;

class SessionManager
{

    private Client $centrifuge;
    private LoggerInterface $logger;

    public function __construct(Client $centrifuge, LoggerInterface $logger)
    {
        $this->centrifuge = $centrifuge;
        $this->logger = $logger;
    }
    
    public function create() : CreateSessionResponse
    {
        $sessionId = bin2hex(random_bytes(16));
        $token = $this->centrifuge->generateConnectionToken($sessionId, time() + 15*60);
        $this->logger->info("created session $sessionId");

        return new CreateSessionResponse($sessionId, $token);
    }

}