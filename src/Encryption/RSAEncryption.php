<?php

namespace Igniter\Queues\Encryption;

class RSAEncryption implements EncryptionInterface
{
    private string $privateKey;
    private string $publicKey;

    public function __construct(string $privateKey, string $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function encrypt(string $data): string
    {
        openssl_public_encrypt($data, $encryptedData, $this->publicKey);
        return base64_encode($encryptedData);
    }

    public function decrypt(string $encryptedData): string
    {
        openssl_private_decrypt(base64_decode($encryptedData), $decryptedData, $this->privateKey);
        return $decryptedData;
    }
}
