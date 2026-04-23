<?php

namespace App\Services;

class CvEncryptionService
{
    private string $key;

    public function __construct()
    {
        $raw = config('services.cv_encryption.key');

        if (!$raw) {
            throw new \RuntimeException('CV encryption key is missing.');
        }

        if (str_starts_with($raw, 'base64:')) {
            $this->key = base64_decode(substr($raw, 7));
        } else {
            $this->key = $raw;
        }

        if (strlen($this->key) !== 32) {
            throw new \RuntimeException('CV encryption key must be 32 bytes.');
        }
    }

    public function encryptBinary(string $plainBinary): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plainBinary,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Failed to encrypt CV.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decryptBinary(string $encodedPayload): string
    {
        $payload = base64_decode($encodedPayload, true);

        if ($payload === false || strlen($payload) < 28) {
            throw new \RuntimeException('Invalid encrypted payload.');
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $ciphertext = substr($payload, 28);

        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plain === false) {
            throw new \RuntimeException('Failed to decrypt CV.');
        }

        return $plain;
    }
}