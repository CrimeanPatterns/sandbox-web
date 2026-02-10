<?php

namespace AwardWallet\Common\PasswordCrypt;

class PasswordDecryptor
{

    private string $publicKey;
    private $public;
    private int $chunkSize;

    public function __construct(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    public function decrypt(?string $password) : ?string
    {
        if ($password !== null) {
            $password = trim($password);
        }

        if ($password == "" || $password === null) {
            return $password;
        }

        if ($this->public === null) {
            $this->public = openssl_pkey_get_public($this->publicKey);

            if ($this->public === false) {
                throw new CryptException("failed to load key: " . openssl_error_string());
            }

            $details = openssl_pkey_get_details($this->public);
            if ($details === false) {
                throw new CryptException("failed to get key details: " . openssl_error_string());
            }

            $this->chunkSize = $details['bits'] / 8;
        }

        if (strlen($password) < $this->chunkSize) {
            // already decrypted
            return $password;
        }

        $password = base64_decode($password);

        $result = '';
        while (strlen($password) > 0) {
            $chunk = substr($password, 0, $this->chunkSize);
            $decryptedChunk = '';

            if (!openssl_public_decrypt($chunk, $decryptedChunk, $this->public)) {
                throw new CryptException("encryption failed: " . openssl_error_string());
            }

            $result .= $decryptedChunk;
            $password = substr($password, $this->chunkSize);
        }

        return $result;
    }
    
    public function __destruct()
    {
        if ($this->public !== null) {
            openssl_free_key($this->public);
        }
    }

}
