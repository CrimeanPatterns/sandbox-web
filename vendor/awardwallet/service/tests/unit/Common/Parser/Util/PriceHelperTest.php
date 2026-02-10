<?php

namespace tests\unit\Common\Parser\Util;

use AwardWallet\Common\Parser\Util\PriceHelper;
use PHPUnit\Framework\TestCase;


class PriceHelperTest extends TestCase
{
    public function testDotComma()
    {
        $this->assertEquals(1123.45, PriceHelper::cost('1.123,45', '.', ','), '', 0.0001);
    }

    public function testSpaceDot()
    {
        $this->assertEquals(1123.45, PriceHelper::cost('1 123.45'), '', 0.0001);
    }

    public function testCommaDot()
    {
        $this->assertEquals(1123.45, PriceHelper::cost('1,123.45'), '', 0.0001);
    }

    public function testCommaNone()
    {
        $this->assertEquals(1123, PriceHelper::cost('1,123'), '', 0.0001);
    }

    public function testDotNone()
    {
        $this->assertEquals(1123, PriceHelper::cost('1.123', '.', ','), '', 0.0001);
    }

    public function testDotNoneSameSeparators()
    {
        $this->assertEquals(null, PriceHelper::cost('1.123', '.', '.'), '', 0.0001);
    }

    public function testSpaceNone()
    {
        $this->assertEquals(1123, PriceHelper::cost('1 123'), '', 0.0001);
    }

    public function testQuoteDot()
    {
        $this->assertEquals(1123.45, PriceHelper::cost("1'123.45", "'", '.'), '', 0.0001);
    }

    public function testQuote()
    {
        $this->assertEquals(1123, PriceHelper::cost("1'123", "'", '.'), '', 0.0001);
    }

    public function testEmpty()
    {
        $this->assertEquals(null, PriceHelper::cost(''));
    }

    public function testDotDotMatch()
    {
        $this->assertEquals(11.22, PriceHelper::cost('11.22'), '', 0.0001);
    }

    public function testDotCommaMismatch()
    {
        $this->assertEquals(null, PriceHelper::cost('11.22', '.', ','));
    }

    public function testCommaCommaMatch()
    {
        $this->assertEquals(11.22, PriceHelper::cost('11,22', '.', ','), '', 0.0001);
    }

    public function testCommaDotMismatch()
    {
        $this->assertEquals(null, PriceHelper::cost('11,22', ',', '.'));
    }

    public function testMillion()
    {
        $this->assertEquals(1234567.89, PriceHelper::cost('1,234,567.89', ',', '.'), '', 0.0001);
    }

    public function testStartComma()
    {
        $this->assertEquals(null, PriceHelper::cost(',123'));
    }

    public function testStartDot()
    {
        $this->assertEquals(0.123, PriceHelper::cost('.123'));
    }

    public function testStartDot7()
    {
        $this->assertEquals(0.7, PriceHelper::cost('.7'));
    }

    public function testStartComma7Good()
    {
        $this->assertEquals(0.7, PriceHelper::cost(',7', '.', ','));
    }

}
