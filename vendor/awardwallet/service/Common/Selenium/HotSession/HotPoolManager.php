<?php

namespace AwardWallet\Common\Selenium\HotSession;

use AwardWallet\Common\Document\HotSession;
use AwardWallet\Common\Document\HotSessionInfo;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

class HotPoolManager implements HotPoolManagerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \SeleniumConnector
     */
    private $connector;

    /**
     * @var DocumentManager
     */
    private $dm;

    /** @var \Doctrine\ODM\MongoDB\DocumentRepository */
    private $rep;

    public function __construct(LoggerInterface $logger, \SeleniumConnector $seleniumConnector, DocumentManager $documentManager)
    {
        $this->logger = $logger;
        $this->connector = $seleniumConnector;
        $this->dm = $documentManager;
        $this->rep = $this->dm->getRepository(HotSession::class);
    }

    public function getConnection(string $prefix, string $provider, ?string $accountKey): ?\SeleniumConnection
    {
        /** @var HotSession $session */
        $session = $this->rep->getHotSession($prefix, $provider, $accountKey);
        if (null === $session) {
            return null;
        }
        $connection = $this->getHotConnection($session);
        if ($connection) {
            $this->logger->info("got hot connection id: {$session->getId()}, sessionId: {$session->getSessionInfo()->getSessionId()} on {$session->getSessionInfo()->getHost()}:{$session->getSessionInfo()->getPort()}",
                $connection->getContext());
        } else {
            $this->logger->info("no hot connections found");
        }

        return $connection;
    }

    /**
     * $accountKey - RaAccount.id which used in session or null if without (will use for better choice of account for parsing)
     *               gets from SeleniumDriver if SeleniumDriver has $this->finderRequest->getHotAccountKey()
     */
    public function saveConnection(\SeleniumConnection $connection, string $prefix, string $provider, ?string $accountKey): void
    {
        $sessionId = $this->rep->findBySessionData($connection->getHost(), $connection->getPort(), $connection->getSessionId());

        if ($sessionId !== null) {
            $this->logger->info("ongoing hot connection: ". $sessionId . " sessionId: " . $connection->getSessionId());
            $this->rep->unlockHotSession($sessionId);
            return;
        }

        $sessionInfo = new HotSessionInfo($connection->getHost(), $connection->getPort(), $connection->getSessionId(),
            $connection->getShare(), $connection->getBrowserFamily(), $connection->getBrowserVersion(),
            $connection->getPath(), $connection->getStartTime(), $connection->getContext());

        $sessionId = $this->rep->createNewRow($prefix, $provider, $accountKey, $sessionInfo);
        $this->logger->info("saved hot connection: ". $sessionId . " sessionId: " . $connection->getSessionId());
    }

    public function deleteConnection(\SeleniumConnection $connection): void
    {
        $sessionId = $this->rep->findBySessionData($connection->getHost(), $connection->getPort(), $connection->getSessionId());

        if ($sessionId === null) {
            return;
        }

        $this->logger->info("deleting hot connection " . $connection->getSessionId());
        $this->rep->deleteHotSession($sessionId);
    }

    /**
     * @internal
     */
    public function getHotConnection(HotSession $hotSession): ?\SeleniumConnection
    {
        $sessionInfo = $hotSession->getSessionInfo();
        $session = new \SeleniumSession(
            $sessionInfo->getSessionId(),
            $sessionInfo->getHost(),
            $sessionInfo->getPort(),
            $sessionInfo->getPath(),
            $sessionInfo->getShare(),
            $sessionInfo->getContext(),
            new \SeleniumOptions()
        );
        $webDriver = $this->connector->restoreSession($session);

        if ($webDriver === null) {
            $this->logger->info("removing connection {$session->getSessionId()} from hot pool");
            $this->rep->deleteHotSession($hotSession->getId());
            return null;
        }
        /** @var \SeleniumConnection $connection */
        $connection = new \SeleniumConnection(
            $webDriver,
            $sessionInfo->getSessionId(),
            $sessionInfo->getHost(),
            $sessionInfo->getPort(),
            $sessionInfo->getPath(),
            $sessionInfo->getShare(),
            $sessionInfo->getBrowserFamily(),
            $sessionInfo->getBrowserVersion(),
            $sessionInfo->getContext(),
            $sessionInfo->getStartTime()
        );

        return $connection;
    }

}