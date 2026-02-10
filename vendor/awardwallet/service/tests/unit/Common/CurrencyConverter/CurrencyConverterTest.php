<?php

namespace tests\unit\Common\CurrencyConverter;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\Common\Memcached\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CurrencyConverterTest extends TestCase
{

    /**
     * @dataProvider dataProvider
     */
    public function testConverter(
        string $scenario,
        string $from,
        string $to,
        float $expectedRate,
        array $httpResponses,
        array $memcachedContents
    ): void
    {
        $httpDriver = $this->createMock(\CurlDriver::class);
        $httpDriver
            ->expects(self::exactly(count($httpResponses)))
            ->method('request')
            ->willReturnOnConsecutiveCalls(...$httpResponses)
        ;

        $memcached = new MemcachedMock();

        foreach ($memcachedContents as $key => $value) {
            $memcached->set($key, $value);
        }

        $memcachedUtil = new Util($memcached);

        $converter = new CurrencyConverter($httpDriver, $memcachedUtil, new NullLogger());
        $rate = $converter->getExchangeRate($from, $to);
        self::assertEquals($expectedRate, $rate);
    }

    public function dataProvider() : array
    {
        return [
            [
                'scenario' => 'get rate from cache',
                'from' => 'RUB',
                'to' => 'USD',
                'expectedRate' => 90,
                'httpResponses' => [],
                'memcachedContents' => [
                    'rate_RUB:USD' => 90,
                ],
            ],
            [
                'scenario' => 'get rate from reserve converter',
                'from' => 'RUB',
                'to' => 'USD',
                'expectedRate' => 50,
                'httpResponses' => [
                    new \HttpDriverResponse('{"RUB_USD":50}', 200)
                ],
                'memcachedContents' => [],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderDuo
     */
    public function testConverterDuo(
        string $scenario,
        string $from,
        string $to,
        float $expectedRate,
        array $httpResponses,
        array $memcachedContents
    ): void {
        $httpDriver = $this->createMock(\CurlDriver::class);
        $httpDriver
            ->expects(self::exactly(count($httpResponses)))
            ->method('request')
            ->willReturnOnConsecutiveCalls(...$httpResponses);

        $memcached = new MemcachedMock();

        foreach ($memcachedContents as $key => $value) {
            $memcached->set($key, $value);
        }

        $memcachedUtil = new Util($memcached);

        $converter = new CurrencyConverter($httpDriver, $memcachedUtil, new NullLogger());
        $amount = 76000;
        $convertedAmount = $converter->convertToUsd($amount, $from);
        self::assertEquals(1000, $convertedAmount);
    }

    public function dataProviderDuo(): array
    {
        return [
            [
                'scenario' => 'reserve converter failed, main in cache',
                'from' => 'RUB',
                'to' => 'USD',
                'expectedRate' => 76.0,
                'httpResponses' => [
                    new \HttpDriverResponse('{}', 200)
                ],
                'memcachedContents' => [
                    'currency_exchange_usd_rates' => json_decode('{"USDAED":3.673197,"USDRUB":76,"USDZMW":22.192269,"USDZWL":322.000195}',
                        true)
                ],
            ],
            [
                'scenario' => 'reserve converter failed, no cache',
                'from' => 'RUB',
                'to' => 'USD',
                'expectedRate' => 76.0,
                'httpResponses' => [
                    new \HttpDriverResponse('{}', 200),
                    new \HttpDriverResponse('{"success":true,"terms":"https:\/\/currencylayer.com\/terms","privacy":"https:\/\/currencylayer.com\/privacy","timestamp":1618884783,"source":"USD","quotes":{"USDAED":3.673197,"USDRUB":76,"USDZMW":22.192269,"USDZWL":322.000195}}',
                        200)
                ],
                'memcachedContents' => [],
            ],
        ];
    }

}