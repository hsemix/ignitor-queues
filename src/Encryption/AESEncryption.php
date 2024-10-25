<?php

namespace Igniter\Queues\Encryption;

class AESEncryption implements EncryptionInterface
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Encrypt the given data.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted data.
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $encryptedData = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        
        // Return the IV along with the encrypted data for decryption
        return base64_encode($iv . '::' . $encryptedData);
    }

    /**
     * Decrypt the given data.
     *
     * @param string $encryptedData The data to decrypt.
     * @return string The decrypted data.
     */
    public function decrypt(string $encryptedData): string
    {
        $encryptedData = base64_decode($encryptedData);
        list($iv, $ciphertext) = explode('::', $encryptedData, 2);
        
        return openssl_decrypt($ciphertext, $this->cipher, $this->key, 0, $iv);
    }
}
