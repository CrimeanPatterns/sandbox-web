<?php

namespace tests\unit\Common\PasswordCrypt;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use PHPUnit\Framework\TestCase;

class PasswordEncryptorTest extends TestCase {

    // 256 bits
    private const PRIVATE_KEY = <<<EOF
-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,FEA68EE934D1A84C

RjD+HWb57tm8ogATDU34ZizhDw4NVItd63VSSX+GXqssQFigTaghihXyHvk8akvE
/24gW4HNNSiJXIWgmTUfxiSj6fwQmA6NxSykCtgmoKEVcr46H5lbnXNk8uoGEaya
tHQONEpRek6NrN+bd/yy83KqNstJd3khT7sLdiOZ3Y/M84Q9Wb3ksC17nZ+NAqLq
SHoWTg/BqwKrny+WzT3dOkT4pF2FrrkOPA8hq/+L5Hg=
-----END RSA PRIVATE KEY-----
EOF;

    private const PUBLIC_KEY = <<<EOF
-----BEGIN PUBLIC KEY-----
MDwwDQYJKoZIhvcNAQEBBQADKwAwKAIhAMK6cgiBJzslkM9dSEXkaU6JurshxOtg
aZgjRFwBWKGpAgMBAAE=
-----END PUBLIC KEY-----
EOF;

    private const PRIVATE_KEY_PASSWORD = '1234';

    /**
     * @dataProvider dataProvider
     */
    public function testEncryptDecrypt(?string $original)
    {
        $encryptor = new PasswordEncryptor(self::PRIVATE_KEY, self::PRIVATE_KEY_PASSWORD);
        $encrypted = $encryptor->encrypt($original);

        if ($original !== "" && $original !== null) {
            $this->assertNotEquals($original, $encrypted);
        }

        $decryptor = new PasswordDecryptor(self::PUBLIC_KEY);
        $decrypted = $decryptor->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }

    public function dataProvider() : array
    {
        return [
            [''],
            [null],
            ['a'],
            ['123'],
            ['more_than_256_bits_with_русские буквы и пробелы и еще вот это 😱'],
        ];
    }

}