<?php

namespace tests\unit\Common\Partner;

use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\Common\Partner\CallbackAuth;
use AwardWallet\Common\Partner\CallbackAuthSource;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CallbackAuthSourceTest extends TestCase
{

    /**
     * @dataProvider callbackScenarios
     */
    public function testCallback(array $partnerCallbackRows, string $url, ?CallbackAuth $expectedResult): void
    {
        $connection = DriverManager::getConnection([
            'dbname' => 'wsdlawardwallet',
            'user' => 'wsdlawardwallet',
            'password' => 'wsdlawardwallet',
            'host' => 'mysql',
            'driver' => 'pdo_mysql',
        ]);

        $partner = "par" . bin2hex(random_bytes(4));
        $connection->insert("Partner", [
            "Login" => $partner,
        ]);
        $partnerId = $connection->lastInsertId();

        foreach ($partnerCallbackRows as $row) {
            $row["PartnerID"] = $partnerId;
            $connection->insert("PartnerCallback", $row);
        }

        $authSource = new CallbackAuthSource($connection, new MemcachedMock(), new NullLogger());
        $this->assertEquals($expectedResult, $authSource->getByUrl($partner, $url));
    }

    public function callbackScenarios()
    {
        return [
            'matchByHost' => [
                'partnerCallbackRows' => [
                    ['URL' => 'some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                ],
                'url' => 'https://some.host/url?1',
                'expectedResult' => new CallbackAuth('login1', 'pass1'),
            ],
            'noMatchByHost' => [
                'partnerCallbackRows' => [
                    ['URL' => 'some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                ],
                'url' => 'https://some.other-host/url?1',
                'expectedResult' => null,
            ],
            'matchByWildcard' => [
                'partnerCallbackRows' => [
                    ['URL' => '*.some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                ],
                'url' => 'https://sub1.some.host/url?1',
                'expectedResult' => new CallbackAuth('login1', 'pass1'),
            ],
            'noMatchByWildcard' => [
                'partnerCallbackRows' => [
                    ['URL' => '*.some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                ],
                'url' => 'https://sub1.some2.host/url?1',
                'expectedResult' => null,
            ],
            'noMatchBySubDomain' => [
                'partnerCallbackRows' => [
                    ['URL' => 'sub1.some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                ],
                'url' => 'https://sub2.some.host/url?1',
                'expectedResult' => null,
            ],
            'shortestMatchByWildcard' => [
                'partnerCallbackRows' => [
                    ['URL' => '*.sub1.some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                    ['URL' => '*.some.host', 'Username' => 'login', 'Pass' => 'pass'],
                    ['URL' => '*.sub2.sub1.some.host', 'Username' => 'login2', 'Pass' => 'pass2'],
                ],
                'url' => 'https://sub1.some.host/url?1',
                'expectedResult' => new CallbackAuth('login', 'pass'),
            ],
            'preferNoWildcard' => [
                'partnerCallbackRows' => [
                    ['URL' => '*.sub1.some.host', 'Username' => 'login1', 'Pass' => 'pass1'],
                    ['URL' => 'some.host', 'Username' => 'login', 'Pass' => 'pass'],
                    ['URL' => '*.sub2.sub1.some.host', 'Username' => 'login2', 'Pass' => 'pass2'],
                    ['URL' => '*.sub3.sub3.some.host', 'Username' => 'login3', 'Pass' => 'pass3'],
                ],
                'url' => 'https://some.host/url?1',
                'expectedResult' => new CallbackAuth('login', 'pass'),
            ],
        ];
    }


}