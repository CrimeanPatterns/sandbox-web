<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Parsing\Exception\ErrorFormatter;
use AwardWallet\ExtensionWorker\CentrifugeLogHandler;
use AwardWallet\ExtensionWorker\ClientFactory;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\ExtensionResponse;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithConfNoInterface;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\LoginWithIdResult;
use AwardWallet\ExtensionWorker\ParseAllOptions;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\ParserFactory;
use AwardWallet\ExtensionWorker\ParserLogger;
use AwardWallet\ExtensionWorker\ParserRunner;
use AwardWallet\ExtensionWorker\ProviderInfo;
use AwardWallet\ExtensionWorker\ResponseReceiver;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use AwardWallet\ExtensionWorker\SessionManager;
use AwardWallet\Schema\Parser\Component\Master;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use phpcent\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DebugExtensionController extends Controller
{

    /**
     * @Route("/admin/debug-extension", methods={"GET", "POST"})
     */
    public function debugExtensionAction(Request $request, SessionManager $sessionManager, \Memcached $memcached, Client $centrifuge)
    {
        $form = $this->createFormBuilder()
            ->add('providerCode', TextType::class)
            ->add('method', ChoiceType::class, ['choices' => ['autologin' => 'autologin', 'autologin_with_conf_no' => 'autologin_with_conf_no', 'parse' => 'parse']])
            ->add('loglevel', ChoiceType::class, ['choices' => array_combine(array_flip(Logger::getLevels()), array_flip(Logger::getLevels())), 'data' => 'DEBUG'])
            ->add('login', TextType::class)
            ->add('login2', TextType::class, ['required' => false])
            ->add('login3', TextType::class, ['required' => false])
            ->add('password', PasswordType::class, ['always_empty' => false])
            ->add('answers', TextareaType::class, ['attr' => ['rows' => 2], 'required' => false])
            ->add('confNoFields', TextareaType::class, ['attr' => ['rows' => 3], 'required' => false])
            ->add('loginId', TextType::class, ['required' => false])
            ->add('parseItineraries', CheckboxType::class, ['required' => false])
            ->add('parsePastItineraries', CheckboxType::class, ['required' => false])
            ->add('parseHistory', CheckboxType::class, ['required' => false])
            ->add('historyStartDate', DateTimeType::class, ['required' => false])
            ->add('save', SubmitType::class, ['label' => 'Run'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $credentials = new Credentials(
                $formData['login'],
                $formData['login2'],
                $formData['login3'],
                $formData['password'],
                $this->convertAnswers($formData['answers'])
            );
            $session = $sessionManager->create();
            $memcached->set(
                $this->getMemcachedKey($session->getSessionId()),
                [
                    'credentials' => $credentials,
                    'providerCode' => $formData['providerCode'],
                    'method' => $formData['method'],
                    'loginId' => $formData['loginId'],
                    'parseItineraries' => $formData['parseItineraries'],
                    'parsePastItineraries' => $formData['parsePastItineraries'],
                    'parseHistory' => $formData['parseHistory'],
                    'historyStartDate' => $formData['historyStartDate'],
                    'loglevel' => $formData['loglevel'],
                    'confNoFields' => $this->convertAnswers($formData['confNoFields']),
                ],
                60 * 30
            );
            $debugConnectionToken = $centrifuge->generateConnectionToken($session->getSessionId() . '_debug', time() + 15*60);

        }

        return $this->render('@AwardWalletMain/debugExtension.html.twig', [
            'form' => $form->createView(),
            'title' => 'Debug extension v3',
            'session' => $session ?? null,
            'debugConnectionToken' => $debugConnectionToken ?? null,
        ]);
    }

    /**
     * @Route("/admin/run-extension/{sessionId}", name="run_extension", methods={"GET"})
     */
    public function runExtensionAction(
        Request $request,
        string $sessionId,
        ParserRunner $parserRunner,
        Logger $logger,
        \Memcached $memcached,
        Client $centrifuge,
        ParserFactory $parserFactory,
        ClientFactory $clientFactory,
        Connection $connection
    )
    {
        $data = $memcached->get($this->getMemcachedKey($sessionId));
        if ($data === null) {
            throw $this->createNotFoundException('Session not found, refresh the page');
        }

        $logger->pushHandler(new CentrifugeLogHandler($centrifuge, '#' . $sessionId . '_debug', $data['loglevel']));
        $parserLogger = new ParserLogger($logger);
        $providerInfo = new ProviderInfo($connection->fetchOne("select DisplayName from Provider where Code = ?", [$data['providerCode']]));
        $errorFormatter = new ErrorFormatter($providerInfo->getDisplayName());
        try {
            /** @var Credentials $credentials */
            $credentials = $data['credentials'];
            /** @var LoginWithIdInterface $parser */
            $parser = $parserFactory->getParser(
                $data['providerCode'],
                $parserLogger->getFileLogger(),
                $parserLogger->getWarningLogger(),
                new SelectParserRequest($credentials->getLogin2(), $credentials->getLogin3()),
                $providerInfo
            );
            $client = $clientFactory->createClient($sessionId, $parserLogger->getFileLogger());

            if ($data['method'] === 'autologin') {
                $result = $parserRunner->loginWithLoginId($parser, $client, $credentials, true, $data['loginId'] ?? '');
                $result->loginResult->error = $errorFormatter->format($result->loginResult->error);
            } elseif ($data['method'] === 'autologin_with_conf_no') {
                /** @var LoginWithConfNoInterface $parser */
                $result = $parserRunner->loginWithConfNo($parser, $client, $data['confNoFields']);
            } elseif ($data['method'] === 'parse') {
                $result = $this->parse($parserRunner, $parser, $client, $data, $logger, $errorFormatter);
            } else {
                throw new \Exception("Unknown method: {$data['method']}");
            }

            if ($parserLogger->getWarningLogger()->getWarnings()) {
                $result = [
                    'warnings' => $parserLogger->getWarningLogger()->getWarnings(),
                    'master' => $result,
                ];
            }
        } finally {
            $logger->popHandler();
            // commented out to show logs
//            $parserLogger->cleanup();
        }

        return $this->render('@AwardWalletMain/runExtension.html.twig', [
            'title' => 'Extension run result',
            'result' => $result,
            'logDir' => $parserLogger->getLogDir(),
        ]);
    }

    /**
     * @Route("/extension-response", name="extension_response", methods={"POST"})
     */
    public function extensionResponseAction(Request $request, LoggerInterface $logger, ResponseReceiver $responseReceiver) : Response
    {
        $data = json_decode($request->getContent(), true);
        $responseReceiver->receive(new ExtensionResponse($data['sessionId'], $data['result'] ?? null, $data['requestId']));

        return new JsonResponse("ok");
    }

    private function getMemcachedKey(string $extensionSessionId) : string
    {
        return "ext_sess_" . $extensionSessionId;
    }

    private function convertAnswers(?string $answers) : array
    {
        $result = [];
        $lines = explode("\n", $answers);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === "") {
                continue;
            }

            $pair = explode("=", $line);
            if (count($pair) != 2) {
                throw new \Exception("Answers/ConfNo fields expected in format Question=Answer, one per line");
            }

            $result[$pair[0]] = $pair[1];
        }

        return $result;
    }

    public function parse(ParserRunner $parserRunner, LoginWithIdInterface $parser, \AwardWallet\ExtensionWorker\Client $client, $data, Logger $logger, ErrorFormatter $errorFormatter)
    {
        $result = $parserRunner->loginWithLoginId($parser, $client, $data['credentials'], null, $data['loginId'] ?? '');
        $result->loginResult->error = $errorFormatter->format($result->loginResult->error);

        if ($result->loginResult->success) {
            $master = new Master('main');
            $master->addPsrLogger($logger);
            /** @var ParseInterface $parser */
            try {
                $parserRunner->parseAll($parser, $result->tab, $master, new ParseAllOptions(
                    $data['credentials'],
                    $data['parseItineraries'] ? new ParseItinerariesOptions($data['parsePastItineraries']) : null,
                    $data['parseHistory'] ? new ParseHistoryOptions($data['historyStartDate'], [], false) : null,
                ));
            }
            catch (\CheckException $exception) {
                return new LoginWithIdResult(new LoginResult(false, $errorFormatter->format($exception->getMessage()), null, $exception->getCode()), "", $result->tab);
            }

            $result = $master->toArray();
        }

        return $result;
    }

}