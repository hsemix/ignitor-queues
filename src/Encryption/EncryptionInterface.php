<?php

namespace Igniter\Queues\Encryption;

interface EncryptionInterface
{
    /**
     * Encrypt the given data.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted data.
     */
    public function encrypt(string $data): string;

    /**
     * Decrypt the given data.
     *
     * @param string $encryptedData The data to decrypt.
     * @return string The decrypted data.
     */
    public function decrypt(string $encryptedData): string;
}