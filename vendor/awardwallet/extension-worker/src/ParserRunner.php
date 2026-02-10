<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\Schema\Parser\Component\Master;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\LoggerInterface;

class ParserRunner
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function loginWithLoginId(LoginWithIdInterface $parser, Client $client, Credentials $credentials, ?bool $activeTab, string $loginId) : LoginWithIdResult
    {
        $this->logger->info('Account Check Parameters', ['Header' => 2]);
        $this->logger->info("Provider engine: " . get_class($parser));
        $this->logger->info('Login: ' . $credentials->getLogin());
        $this->logger->info('Login2: ' . $credentials->getLogin2());
        $this->logger->info('Login3: ' . $credentials->getLogin3());
        $this->logger->info("Answers on enter: " . json_encode($credentials->getAnswers()));

        $options = new AccountOptions($credentials->getLogin(), $credentials->getLogin2(), $credentials->getLogin3(), false);
        try {
            $url = $parser->getStartingUrl($options);
        }
        catch (\Throwable $exception) {
            throw new ParserException($exception->getMessage(), 0, $exception);
        }
        $this->logger->info('starting url is: ' . $url);

        if ($activeTab === null) {
            $activeTab = $this->getActiveTab($parser, $options);
        }

        $tab = $client->newTab($url, $activeTab);
        $this->logger->info('IsLoggedIn', ['Header' => 2]);
        try {
            $isLoggedIn = $parser->isLoggedIn($tab);
            $this->logger->info('isLoggedIn: ' . json_encode($isLoggedIn));
            if ($isLoggedIn && $loginId !== '') {
                $this->logger->info('running getLoginId to compare with ' . $loginId);
                $pageLoginId = strtolower(trim($parser->getLoginId($tab)));
                $this->logger->info('page loginId: ' . $pageLoginId);
                if ($loginId === $pageLoginId) {
                    $this->logger->info("already logged in and loginId matches");

                    return new LoginWithIdResult(new LoginResult(true), $loginId, $tab);
                }
            }

            if ($isLoggedIn) {
                $this->logger->info('Logout', ['Header' => 2]);
                $this->logger->info('logging off, because loginId mismatch or empty');
                $parser->logout($tab);
                $tab->gotoUrl($url);

                $this->logger->info('IsLoggedIn', ['Header' => 2]);
                if ($parser->isLoggedIn($tab)) {
                    throw new ParserException("Failed to logoff");
                }
            }

            $this->logger->info('Login', ['Header' => 2]);
            $loginResult = $parser->login($tab, $credentials);
            $this->logger->info('login result: ' . json_encode($loginResult));
            if ($loginResult->success) {
                $this->logger->info('logged in, calling getLoginId');
                $pageLoginId = strtolower(trim($parser->getLoginId($tab)));
                $this->logger->info('loginId: ' . $pageLoginId);

                return new LoginWithIdResult($loginResult, $pageLoginId, $tab);
            }

            return new LoginWithIdResult($loginResult, "", $tab);
        }
        catch (\CheckException $exception) {
            return new LoginWithIdResult(new LoginResult(false, $exception->getMessage(), null, $exception->getCode()), "", $tab);
        }
        catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage() . " at " . $this->getParserErrorLocation(get_class($parser), $exception));

            return new LoginWithIdResult(new LoginResult(false, $exception->getMessage(), null, ACCOUNT_ENGINE_ERROR), "", $tab);
        }
    }

    public function loginWithConfNo(LoginWithConfNoInterface $parser, Client $client, array $confNoFields) : bool
    {
        $this->logger->info('Login With Conf No Parameters', ['Header' => 2]);
        $this->logger->info("Provider engine: " . get_class($parser));
        $this->logger->info("Answers on enter: " . json_encode($confNoFields));

        try {
            $url = $parser->getLoginWithConfNoStartingUrl($confNoFields);
        }
        catch (\Throwable $exception) {
            throw new ParserException($exception->getMessage(), 0, $exception);
        }
        $this->logger->info('starting url is: ' . $url);

        $tab = $client->newTab($url, true);
        try {
            $this->logger->info('Login', ['Header' => 2]);
            $loginResult = $parser->loginWithConfNo($tab, $confNoFields);
            $this->logger->info('login result: ' . json_encode($loginResult));

            return $loginResult;
        }
        catch (\CheckException $exception) {
            return false;
        }
        catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage() . " at " . $this->getParserErrorLocation(get_class($parser), $exception));

            return false;
        }
    }

    /**
     * @param ParseInterface|ParseAllInterface $parser
     */
    public function parseAll($parser, Tab $tab, Master $master, ParseAllOptions $parseOptions)
    {
        if ($parser instanceof ParseAllInterface && $parser instanceof ParseInterface) {
            throw new ParserException("Parser should implement ParseAllInterface or ParseInterface, not both");
        }

        if ($parser instanceof ParseAllInterface && $parser instanceof ParseHistoryInterface) {
            throw new ParserException("Parser should implement ParseAllInterface or ParseHistoryInterface, not both");
        }

        if ($parser instanceof ParseAllInterface && $parser instanceof ParseItinerariesInterface) {
            throw new ParserException("Parser should implement ParseAllInterface or ParseItinerariesInterface, not both");
        }

        $credentials = $parseOptions->getCredentials();
        $accountOptions = new AccountOptions($credentials->getLogin(), $credentials->getLogin2(), $credentials->getLogin3(), false);

        if ($parser instanceof ParseAllInterface) {
            $this->logger->info('Parse All', ['Header' => 2]);
            $parser->parseAll($tab, $master, $accountOptions, $parseOptions->getParseHistoryOptions(), $parseOptions->getParseItinerariesOptions());

            return;
        }

        if (!$parser instanceof ParseInterface) {
            throw new ParserException("Parser should implement ParseAllInterface or ParseInterface");
        }

        $this->logger->info('Parse', ['Header' => 2]);
        $parser->parse($tab, $master, $accountOptions);
        $this->logger->info('Account Check Result', ['Header' => 2]);

        if ($parseOptions->getParseItinerariesOptions() && $parser instanceof ParseItinerariesInterface) {
            $this->logger->info('Parse Itineraries', ['Header' => 2]);
            $parser->parseItineraries($tab, $master, $accountOptions, $parseOptions->getParseItinerariesOptions());
        }

        if ($parseOptions->getParseHistoryOptions() && $parser instanceof ParseHistoryInterface) {
            $this->logger->info('Parse History', ['Header' => 2]);
            $parser->parseHistory($tab, $master, $accountOptions, $parseOptions->getParseHistoryOptions());
        }

        $this->logger->info(json_encode($master->toArray(), JSON_PRETTY_PRINT), ['pre' => true]);
    }

    private function getParserErrorLocation(string $parserClass, \Throwable $exception) : string
    {
         if ($exception->getFile() === $parserClass) {
            return $exception->getFile() . ':' . $exception->getLine();
        }

        $lastFrame = null;
        foreach ($exception->getTrace() as $frame) {
            if (isset($frame['class']) && $frame['class'] === $parserClass && $lastFrame) {
                return $lastFrame['file'] . ':' . $lastFrame['line'];
            }
            $lastFrame = $frame;
        }

        return $exception->getFile() . ':' . $exception->getLine();
    }

    private function getActiveTab(LoginWithIdInterface $parser, AccountOptions $options)
    {
        if (!($parser instanceof ActiveTabInterface)) {
            return false;
        }

        try {
            return $parser->isActiveTab($options);
        } catch (\Throwable $exception) {
            throw new ParserException($exception->getMessage(), 0, $exception);
        }
    }
}