<?php

namespace AwardWallet\Common\CurrencyConverter;

use AwardWallet\Common\Memcached\Item;
use AwardWallet\Common\Memcached\Util;
use AwardWallet\Common\Strings;
use Psr\Log\LoggerInterface;

class CurrencyConverter
{
    // https://apilayer.com/
    private const MAIN_CONVERTER_KEY = '790eddb6fa8f0db3bb5a796bd5bb2e68';

    // https://free.currencyconverterapi.com/
    private string $currencyConverterApiKey;

    private const CACHED_TIME = 12 * 60 * 60;

    /** @var \HttpDriverInterface */
    private $httpDriver;

    /** @var Util */
    private $memcachedUtil;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(\HttpDriverInterface $httpDriver, Util $memcachedUtil, LoggerInterface $logger, string $currencyConverterApiKey)
    {
        $this->httpDriver = $httpDriver;
        $this->memcachedUtil = $memcachedUtil;
        $this->logger = $logger;
        $this->currencyConverterApiKey = $currencyConverterApiKey;
    }

    public function convertToUsd(float $amount, string $currencyCode): ?float
    {
        $rate = $this->getExchangeRate($currencyCode, 'USD');
        if (is_float($rate)) {
            return round($amount * $rate, 2);
        }
        return null;
    }

    public function getExchangeRate($from, $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        return $this->memcachedUtil->getThrough('rate_' . $from . ':' . $to, function() use ($from, $to) {
            $result = null;

            try {
                $valueKey = $from . '_' . $to;
                $result = $this->getFromReserveConverter($valueKey);
            } catch (\Exception $e) {
                if (
                    strpos($e->getMessage(), '503 Service Unavailable') !== false
                    || strpos($e->getMessage(), '504 Gateway Time-out') !== false
                ) {
                    $this->logger->info($e->getMessage());
                } else {
                    $this->logger->warning($e->getMessage());
                }
            }

            if ($result === null && ($to === 'USD' || $from==='USD')) {// TODO to del: && ($to === 'USD' || $from==='USD')
                try {
                    if ($to === 'USD') {
                        $valueKey = $to . $from;
                        $convertTo = $to;
                    } else {
                        $valueKey = $from . $to;
                        $convertTo = $from;
                    }
                    $rates = $this->getFromMainConverter($convertTo);
                    if (isset($rates[$valueKey])) {
                        if ($to === 'USD') {
                            $result = 1 / (float)$rates[$valueKey];
                        } else {
                            $result = (float)$rates[$valueKey];
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            return new Item($result, $result === null ? 60 : 86400, true);
        });
    }

    private function getFromMainConverter($to)
    {
        $getter = function () use($to) {
            $source = '';
            if ($to !== 'USD') {
                $source = '&source=' . $to;
            }
            $response = $this->httpDriver->request(
                new \HttpDriverRequest(sprintf('http://apilayer.net/api/live?access_key=%s%s',
                    self::MAIN_CONVERTER_KEY, $source))
            );
            $json = json_decode($response->body, true);

            if (isset($json['success']) && true === $json['success']) {
                return new Item($json['quotes'], self::CACHED_TIME);
            }

            throw new \Exception("Invalid response (master converter): " . $response->body);
        };
        return $this->memcachedUtil->getThrough("currency_exchange_usd_rates", $getter);
    }

    // https://www.currencyconverterapi.com/docs
    private function getFromReserveConverter($valueKey)
    {
        $getter = function () use ($valueKey) {
            $response = $this->httpDriver->request(
                new \HttpDriverRequest("https://api.currconv.com/api/v7/convert?q={$valueKey}&compact=ultra&apiKey=" . $this->currencyConverterApiKey)
            );
            $json = json_decode($response->body, true);

            if (!isset($json[$valueKey])) {
                throw new \Exception("Invalid response (reserve converter), api key " . Strings::cutInMiddle($this->currencyConverterApiKey, 2) . ": " . $response->body);
            }
            return new Item($json[$valueKey], self::CACHED_TIME);
        };
        return $this->memcachedUtil->getThrough('currency_exchange_' . strtolower($valueKey), $getter);
    }
}
