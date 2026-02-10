<?php

namespace AwardWallet\Common\Geo;

use Doctrine\DBAL\Connection;

class AddressParser
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function parse($address)
    {
        if (preg_match('#^([^\,]+)\,([^\,]+)\,\s*([A-Z]{2}|[a-z\s]{5,})\s*(\d{5})\s*\,?\s*(US|USA|United States)$#ims',
                $address, $matches) && preg_match('#^\d{5}$#ims', $matches[4])) {
            if (strlen($matches[3]) == 2) {
                $sql = <<<SQL
                  SELECT Name FROM State WHERE Code = :CODE AND CountryID = :COUNTRYID
SQL;
                $result = $this->connection->executeQuery($sql,
                    [':CODE' => addslashes(trim($matches[3])), ':COUNTRYID' => 230])->fetch();
                if (!$result) {
                    return null;
                }
                $stateName = $result['Name'];
            } else {
                $stateName = $matches[3];
            }

            return [
                'AddressLine' => trim($matches[1]),
                'City' => trim($matches[2]),
                'State' => $stateName,
                'PostalCode' => $matches[4],
                'Country' => 'United States',
                'CountryCode' => 'US',
            ];
        } else {
            return null;
        }
    }

    public static function getAddressVariants(string $address) : array
    {
        $arResult = array($address);
        $s = str_ireplace("Arpt", "Airport", $address);
        $s = str_ireplace("People s Republic Of", "", $s);
        if ($s != $address) {
            $arResult[] = $s;
            $address = $s;
        }
        if (preg_match('/^((\w+\s+)+Airport)(\s*\,)?(.+)$/ims', $address, $arMatches)) {
            $arResult[] = $arMatches[1];
            $arResult[] = $arMatches[4];
        }
        $ar = array();
        foreach ($arResult as $s) {
            $s = trim($s);
            if ($s != "") {
                $ar[] = $s;
            }
        }
        return $ar;
    }

    public static function normalizeAddress($address) : string
    {
        return @iconv("UTF-8", "UTF-8//IGNORE", substr(self::normalizeStr($address), 0, 250));
    }

    public static function normalizeStr($s) : string
    {
        $s = preg_replace("/<[^>]*>/ims", " ", $s);
        $s = preg_replace("/[^\w\d\.\,\-]/uims", " ", $s);
        $s = preg_replace("/\s+/ims", " ", $s);
        $s = preg_replace("/ ([.\,\-])/ims", '\1', $s);
        $s = trim($s);
        return $s;
    }

}