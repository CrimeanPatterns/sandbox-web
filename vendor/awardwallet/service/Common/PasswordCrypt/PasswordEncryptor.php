<?php

namespace AwardWallet\Common\PasswordCrypt;

class PasswordEncryptor
{

    private string $privateKey;
    private string $keyPassPhrase;
    private $private;
    private int $chunkSize;

    public function __construct(string $privateKey, string $keyPassPhrase)
    {
        $this->privateKey = $privateKey;
        $this->keyPassPhrase = $keyPassPhrase;
    }

    public function encrypt(?string $password) : ?string
    {
        if ($password !== null) {
            $password = trim($password);
        }

        if ($password === "" || $password === null) {
            return $password;
        }

        if ($this->private === null) {
            $this->private = openssl_pkey_get_private($this->privateKey, $this->keyPassPhrase);
            
            if ($this->private === false) {
                throw new CryptException("failed to load key: " . openssl_error_string());
            }

            $details = openssl_pkey_get_details($this->private);
            if ($details === false) {
                throw new CryptException("failed to get key details: " . openssl_error_string());
            }

            // For a 1024 bit key length => max number of chars (bytes) to encrypt = 1024/8 - 11 (when padding used) = 117 chars (bytes).
            // https://www.php.net/manual/en/function.openssl-private-encrypt.php#119810
            $this->chunkSize = $details['bits'] / 8 - 11;
        }

        $result = '';
        while (strlen($password) > 0) {
            $chunk = substr($password, 0, $this->chunkSize);
            $cryptedChunk = '';

            if (!openssl_private_encrypt($chunk, $cryptedChunk, $this->private)) {
                throw new CryptException("encryption failed: " . openssl_error_string());
            }

            $result .= $cryptedChunk;
            $password = substr($password, $this->chunkSize);
        }


        return base64_encode($result);
    }


    public function __destruct()
    {
        if ($this->private !== null) {
            openssl_free_key($this->private);
        }
    }

}
