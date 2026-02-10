<?php

namespace AwardWallet\Common\Selenium\HotSession;

use AwardWallet\Common\Document\HotSession;
use AwardWallet\Common\Document\HotSessionInfo;
use AwardWallet\Common\Selenium\SeleniumDriverFactory;
use Aws\S3\S3Client;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

class KeepActiveHotSessionManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KeepHotConfigFactory
     */
    private $keepHotConfigFactory;

    /**
     * @var HotPoolManager
     */
    private $hotPoolManager;

    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var \SeleniumConnector
     */
    private $connector;

    /** @var
     * SeleniumDriverFactory
     */
    private $driverFactory;
    private $bucket;

    public function __construct(
        LoggerInterface $logger,
        KeepHotConfigFactory $keepHotConfigFactory,
        HotPoolManager $hotPoolManager,
        DocumentManager $documentManager,
        \SeleniumConnector $connector,
        SeleniumDriverFactory $driverFactory,
        S3Client $s3Client,
        ?string $bucket
    ) {
        $this->logger = $logger;
        $this->keepHotConfigFactory = $keepHotConfigFactory;
        $this->hotPoolManager = $hotPoolManager;
        $this->dm = $documentManager;
        $this->connector = $connector;
        $this->driverFactory = $driverFactory;
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
    }

    public function runKeepHot(string $providerCode): ?KeepActiveHotSessionResponse
    {
        /** @var KeepActiveHotConfig $config */
        $config = $this->keepHotConfigFactory->load($providerCode);

        $this->logger->pushProcessor(function(array $record) use ($providerCode) {
            $record['extra']['provider'] = $providerCode;
            return $record;
        });

        try {
            if ($config === null) {
                $this->checkAndCloseSessions($providerCode, 'KeepHotConfig is indefined');
                return null;
            }
            if (!$config->isActive()) {
                $this->checkAndCloseSessions($providerCode, 'KeepHotConfig is off');
                return null;
            }

            $this->logger->info("processing provider $providerCode: {$config->getCountToKeep()} with interval {$config->getInterval()}");

            $builder = $this->dm->createQueryBuilder(HotSession::class);
            $builder
                ->group(['prefix' => 1], ['count' => 0])
                ->reduce('
                                function (obj, prev) { 
                                    prev.count++; 
                                }
                            ');
            $prefixs = $builder
                ->field('provider')->equals($providerCode)
                ->sort('lastUseDate', 'desc')
                ->getQuery()
                ->execute()->toArray();
            // TODO for symfony 5
            /*$builder = $this->dm->createAggregationBuilder(HotSession::class);
            $builder
                ->match()
                ->field('provider')->equals($providerCode)
                ->group()
                ->field('id')
                ->expression('$prefix')
                ->field('prefix')
                ->first('$prefix')
                ->field('count')
                ->sum(1)
                ->sort('lastUseDate', 'desc');
            $prefixs = $builder->execute();*/

            $errors = [];
            foreach ($prefixs as $prefix) {
                try {
                    $this->runKeepHotWithPrefix($providerCode, $config, $prefix['prefix']);
                } catch (\Exception $e) {
                    $this->logger->error("exiting, reason: " . $e->getMessage());
                    $errors[] = [
                        'providerCode' => $providerCode,
                        'prefix' => $prefix['prefix'],
                        'error' => $e->getMessage()
                    ];
                }
            }
        } finally {
            $this->logger->popProcessor();
        }
        return new KeepActiveHotSessionResponse($config->getHttpLogDir(), $errors);
    }

    public function stopHotById(string $id): void
    {
        $session = $this->dm->getRepository(HotSession::class)->find($id);
        if (null === $session) {
            return;
        }

        $connection = $this->hotPoolManager->getHotConnection($session);
        if (null == $connection) {
            return;
        }

        $driver = $this->createDriver($session, $connection);
        $driver->stop();

        return;
    }

    private function runKeepHotWithPrefix(string $providerCode, KeepActiveHotConfig $config, string $prefix)
    {
        $this->logger->info('Run keep hot for ' . $providerCode . ' with prefix ' . $prefix);
        $interval = $config->getInterval();

        $lastDate = (new \DateTime())->setTimestamp(strtotime("-$interval minutes"));
        $beforeDateStr = date('Y-m-d H:i', $config->getAfterDateTime());
        $beforeDate = (new \DateTime($beforeDateStr));

        $this->dm->getRepository(HotSession::class)->unlockOld($prefix, $providerCode);

        $builder = $this->dm->createQueryBuilder(HotSession::class);
        $hotSessions = $builder
            ->field('provider')->equals($providerCode)
            ->field('prefix')->equals($prefix)
            ->sort('lastUseDate', 'desc')
            ->getQuery();
        $countFreshHot = 0;
        $this->logger->info("found " . count($hotSessions) . " sessions", ['prefix' => $prefix]);

        /** @var HotSession $hotSession */
        foreach ($hotSessions as $hotSession) {
            $this->logger->pushProcessor(function (array $record) use ($providerCode, $hotSession) {
                $record['extra']['hotSession'] = $hotSession->getId();
                $record['extra']['accountKey'] = $hotSession->getAccountKey();
                $record['extra']['prefix'] = $hotSession->getPrefix();
                $record['extra']['sessionId'] = $hotSession->getSessionInfo()->getSessionId();
                return $record;
            });

            // need refresh before process
            $this->dm->refresh($hotSession);
            try {
                $connection = $this->hotPoolManager->getHotConnection($hotSession);
                if ($connection === null) {
                    $this->logger->info("no hot connections found with id: {$hotSession->getId()}");
                    continue;
                }
                $this->logger->info("got hot connection id: {$hotSession->getId()}, sessionId: {$hotSession->getSessionInfo()->getSessionId()} on {$hotSession->getSessionInfo()->getHost()}:{$hotSession->getSessionInfo()->getPort()}");

                // TODO: if is locked and lifetime limit exceeded don't stop
                if ((!$hotSession->getIsLocked()) && null !== $config->getLimitLifeTime() && $hotSession->getStartDate() < (new \DateTime())->setTimestamp(strtotime("-{$config->getLimitLifeTime()} minutes"))) {
                    $this->stopSession($hotSession, $connection, 'lifetime limit exceeded');
                    continue;
                }

                if ($hotSession->getLastUseDate() > $lastDate || $hotSession->getIsLocked()) {
                    // for debug
                    $this->logger->info("skip connection with id: {$hotSession->getId()}, is active");
                    $countFreshHot++;
                    continue;
                }
                
                $hotSession->setIsLocked(true);
                $this->dm->flush($hotSession);
                
                if (null !== $config->getAfterDateTime() && $hotSession->getStartDate() < $beforeDate) {
                    $this->stopSession($hotSession, $connection, "startDate is earlier than " . $beforeDateStr);
                    continue;
                }
                
                if ($countFreshHot >= $config->getCountToKeep()) {
                    $this->stopSession($hotSession, $connection, 'enough fresh sessions');
                    continue;
                }

                $driver = $this->createDriver($hotSession, $connection);
                $httpLogDir = $config->getHttpLogDir();

                $http = new \HttpBrowser("dir", $driver, $httpLogDir);
                $config->logger = new \CheckerLogger($http);
                $this->logger->pushHandler(new \Monolog\Handler\PsrHandler($config->logger));

                $config->logger->notice("------------------------------");
                $config->logger->notice("run KeepHotSession for sessionId: " . $hotSession->getSessionInfo()->getSessionId() . ", ");
                $config->logger->notice("hotSessionId: " . $hotSession->getId() . " lastUseDate: " . date("Y-m-d H:i",
                        $hotSession->getLastUseDate()->getTimestamp()));
                $config->http = $http;
                $config->Start();
                try {
                    $resRun = $config->run();
                } catch (\Exception $e) {
                    $resRun = false;
                    $config->logger->error("[Error]: " . $e->getMessage());
                    $config->logger->error("[Trace]: " . $e->getTraceAsString());
                }
                if (true == $resRun) {
                    $hotSession
                        ->setIsLocked(false)
                        ->setLastUseDate(new \DateTime());
                    $this->dm->flush($hotSession);
                    if (!$driver->keepSession) {
                        $config->logger->error("KeepActiveHotConfig has error: keepSession!=true and 'run' return true");
                    }
                    $driver->setKeepSession(true);
                    $config->logger->notice("session activated successfully");
                    $countFreshHot++;
                } else {
                    $config->logger->notice("session activation failed");
                    if ($driver->keepSession) {
                        $config->logger->error("KeepActiveHotConfig has error: keepSession==true and 'run' return false");
                    }
                    $driver->setKeepSession(false);
                }
                $driver->stop();
                $config->logger->notice("------------------------------");
                $this->logger->popHandler();

                $this->saveLogs($http, $hotSession->getId(), $hotSession->getProvider());
            } finally {
                $this->logger->popProcessor();
            }
        }
    }

    private function createDriver(HotSession $hotSession, $connection): \SeleniumDriver
    {
        $finderRequest = new \SeleniumFinderRequest();
        $finderRequest->setHotSessionPool($hotSession->getPrefix(), $hotSession->getProvider(),
            $hotSession->getAccountKey());
        $driver = $this->driverFactory->getDriver($finderRequest, new \SeleniumOptions(), $this->logger);
        $driver->startWithConnection($connection);

        return $driver;
    }

    private function saveLogs(\HttpBrowser $http, $sessionId, $providerCode)
    {
        $logDir = $http->LogDir;
        if (!file_exists($logDir . '/log.html')) {
            $this->logger->info('no logs. nothing to save');
            return;
        }
        $now = new \DateTime();
        $name = 'awardwallet_keephotsession_' . $providerCode . '_' . $sessionId . '_' . $now->format('Ymd_His_v');
        $logDirFiles = scandir($logDir);
        $files = [];
        foreach ($logDirFiles as $file) {
            if (in_array($file, ['.', '..']) || is_dir($logDir . '/' . $file)) {
                continue;
            }
            $files[] = $logDir . '/' . $file;
        }

        $zipFilename = \TAccountChecker::ArchiveLogsToZip(file_get_contents($logDir . '/log.html'), $name, $files);

        $result = $this->s3Client->upload($this->bucket, basename($zipFilename), file_get_contents($zipFilename),
            'bucket-owner-full-control');
        $this->logger->info('logs uploaded to ' . basename($zipFilename));

        if ($result && file_exists($zipFilename)) {
            unlink($zipFilename);
        }
    }

    private function checkAndCloseSessions(string $providerCode, string $reason)
    {
        $session = $this->dm->getRepository(HotSession::class)->findOneBy(['provider' => $providerCode]);
        if ($session === null) {
            return;
        }

        $hotSessions = $this->dm->createQueryBuilder(HotSession::class)
            ->field('provider')->equals($providerCode)
            ->getQuery();
        $this->logger->info("found " . count($hotSessions) . " sessions to close");

        /** @var HotSession $hotSession */
        foreach ($hotSessions as $hotSession) {
            $this->logger->pushProcessor(function (array $record) use ($providerCode, $hotSession) {
                $record['extra']['hotSession'] = $hotSession->getId();
                $record['extra']['accountKey'] = $hotSession->getAccountKey();
                $record['extra']['prefix'] = $hotSession->getPrefix();
                $record['extra']['sessionId'] = $hotSession->getSessionInfo()->getSessionId();
                return $record;
            });

            try {
                // need refresh before process
                $this->dm->refresh($hotSession);
                $hotSession->setIsLocked(true);
                $this->dm->flush($hotSession);

                $connection = $this->hotPoolManager->getHotConnection($hotSession);
                if ($connection === null) {
                    $this->logger->info("no hot connections found with id: {$hotSession->getId()}");
                    continue;
                }
                $this->stopSession($hotSession, $connection, $reason);
            } finally {
                $this->logger->popProcessor();
            }
        }
    }

    private function stopSession($hotSession, $connection, $reason)
    {
        $this->logger->info("stop hot connection {$hotSession->getId()}: {$reason}");
        $driver = $this->createDriver($hotSession, $connection);
        $driver->stop();
        $this->dm->remove($hotSession);
    }
}