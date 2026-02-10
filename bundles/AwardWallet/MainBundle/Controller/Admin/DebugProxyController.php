<?php

namespace AwardWallet\MainBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugProxyController extends Controller
{

    /**
     * @Route("/admin/save-debug-proxy-token")
     */
    public function saveTokenAction(Request $request)
    {
        if ($request->query->get("state") === "local") {
            $server = "Local";
        } else {
            $server = "Remote";
        }

        $driver = $this->get("aw.curl_driver");
        $response = $driver->request(new \HttpDriverRequest(($server === 'Local' ? 'http://awardwallet.docker' : 'https://awardwallet.com') . '/api/oauth2/token.php', 'POST', [
            'client_id' => 'parserbox',
            'client_secret' => 'Awdeveloper12',
            'code' => $request->query->get('code'),
            'scope' => 'debugProxy',
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://parserbox-web.awardwallet.docker/admin/save-debug-proxy-token'
        ]));

        $token = json_decode($response->body, true);

        if ($response->httpCode != 200 || !is_array($token) || !isset($token['access_token'])) {
            throw new \Exception("invalid response: " . $response->httpCode . " " . $response->body);
        }

        file_put_contents(__DIR__ . '/../../../../../app/config/debugProxyToken' . $server . '.json', $response->body);

        return new Response('Token saved app/config/debugProxyToken' . $server . '.json. <a href="/admin/debugProxy.php">Return to debugProxy</a>');
    }

};