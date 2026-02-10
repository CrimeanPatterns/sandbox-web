<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ExtensionController extends Controller
{
    /**
     * @Route("/engine/{providerCode}/extension.js",
     *      name="aw_extension_js",
     *      requirements={"providerCode" = "\w+"}
     * )
     *
     * @param string $providerCode
     *
     * @return Response
     */
    public function extensionJsAction($providerCode)
    {
        $srcDir = $this->get('kernel')->getRootDir() . '/../src';
        $fileName = $srcDir . '/engine/' . $providerCode . '/extension.js';

        if (!file_exists($fileName)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($fileName);
        $content .= "\n\n// utilities \n\n" . file_get_contents($srcDir . '/extension/util.js');

        /** @var Provider $provider */
        $provider = $this->container->get("aw.repository.provider")->findOneBy(["code" => $providerCode]);

        if (empty($provider)) {
            return new Response("Provider not found", 400);
        }

        $logger = $this->get("logger");

        if (!preg_match('#hosts\s*:\s*(\{[^\}]*\})#ims', $content, $matchesHosts)) {
            $logger->warning("could not parse hosts in " . $fileName);

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        $matchesHosts[1] = preg_replace('#\/\/[^n]*\n#ims', '', $matchesHosts[1]);
        //problem with decoding json, the difference of versions PHP associated with keys in single quotes
        $hosts = json_decode((function () use ($matchesHosts) {
            $regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;

            return preg_replace_callback($regex, function ($matches) {
                return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
            }, $matchesHosts[1]);
        })(), true);

        if (is_empty($hosts)) {
            $logger->warning("empty hosts in $providerCode");

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        if (!preg_match('#(cashbackLink\s*:\s*)(\'\')(,)#ims', $content, $matchesLink)) {
            $logger->warning("could not find cashbackLink in $providerCode");

            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        }

        return new Response($content, 200, ['Content-Type' => 'text/javascript']);
    }

    /**
     * @Route("/extension/extensionStats.php")
     * @Route("/account/extension-stats")
     * @Route("/account/receive-browser-log")
     * @Route("/m/api/account/extension-stats")
     * @Route("/m/api/account/receive-browser-log")
     * @Route("/m/api/account/receive-from-browser")
     * @Route("/extension/version-report")
     *
     * @return JsonResponse|Response
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function extensionStatsAction(Request $request)
    {
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/m/api/account/autologin/{version}",
     *      name="awm_newapp_autologin_autologin",
     *      requirements={
     *			"version" = "mobile|desktop"
     * 		}
     * )
     */
    public function autologinAction(Request $request, string $version)
    {
        $isMobile = ('mobile' === $version);
        $params = $this->formatForExtension(json_decode($request->getContent(), true));
        $content = '
			    var applicationPlatform = \'' . $request->headers->get('x-aw-platform') . '\';
			    var params = ' . json_encode($params) . ";\n\n";
        $srcDir = $this->get('kernel')->getRootDir() . '/../src';
        $providerCode = $params['providerCode'];
        $extensions = [
            'extension' => $srcDir . '/engine/' . $providerCode . '/extension' . ($isMobile ? 'Mobile' : '') . '.js',
            'utilities' => $srcDir . '/extension/util.js',
            'mobile api' => $this->getMobileApi(),
        ];

        $content .= $this->loadExtension($extensions);

        return new Response($content, 200, ['Content-Type' => 'text/javascript']);
    }

    /**
     * @return string filename
     */
    protected function getMobileApi()
    {
        $srcDir = $this->get('kernel')->getRootDir() . '/../src';

        return $srcDir . '/engine/awextension/extensionMobileApi-v3.1.js';
    }

    protected function loadExtension(array $files)
    {
        $extension = '';

        foreach ($files as $comment => $fileName) {
            if (!file_exists($fileName)) {
                throw $this->createNotFoundException();
            }
            $fileContent = file_get_contents($fileName);

            if (false === $fileContent) {
                throw $this->createNotFoundException();
            }
            $extension .= "\n\n//" . $comment . "\n\n" . $fileContent;
        }

        return $extension;
    }

    protected function formatForExtension(array $params): array
    {
        $params['canUpdate'] = true;
        $params['properties'] = $this->parsePropertyLines($params['accountProperties']);
        unset($params['accountProperties']);
        $params['account'] = $params;

        return $params;
    }

    private function getTargetHostForLink($link, LoggerInterface $logger, array $knownHosts)
    {
        $cache = $this->get("aw.memcached");
        $cacheKey = "th_" . sha1($link);
        $host = $cache->get($cacheKey);

        if (!empty($host)) {
            return $host;
        }

        $options = [
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36',
        ];
        $redirectCount = 0;
        $startTime = microtime(true);
        // we will not try to get known host, because sometimes request to target host is forbidden from aws / russia
        // so, we will do redirects one by one, until we reached redirect to known host or error or success
        do {
            $requestInfo = [CURLINFO_EFFECTIVE_URL, CURLINFO_HTTP_CODE, CURLINFO_REDIRECT_URL];
            $status = curlRequest($link, 10, $options, $requestInfo, $curlErrno);

            if ($status === false || $requestInfo[CURLINFO_HTTP_CODE] < 300 || $requestInfo[CURLINFO_HTTP_CODE] >= 400) {
                break;
            }
            $link = $requestInfo[CURLINFO_REDIRECT_URL];

            if (isset($knownHosts[parse_url($link, PHP_URL_HOST)])) {
                break;
            }
        } while ($redirectCount < 20 && (microtime(true) - $startTime) < 30);

        if ($status === false) {
            $logger->warning("Curl request failed (curl errno $curlErrno", $requestInfo);

            return null;
        }

        $host = parse_url($link, PHP_URL_HOST);

        if ($host === false) {
            $logger->warning("Failed to parse host from url $link");

            return null;
        }

        $cache->set($cacheKey, $host, SECONDS_PER_DAY);

        return $host;
    }

    private function parsePropertyLines(string $text): array
    {
        $result = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }
            $keyValue = explode("=", $line);

            if (count($keyValue) !== 2) {
                throw new \Exception("Invalid property line: $line, expected key=value");
            }
            $result[$keyValue[0]] = $keyValue[1];
        }

        return $result;
    }
}
