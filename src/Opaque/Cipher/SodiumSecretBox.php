<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Opaque;

/**
 * Symmetric authenticated encryption, XSalsa20-Poly1305: one secret key both ciphers and deciphers.
 * A fresh random nonce per value.
 */
final class SodiumSecretBox implements Cipher
{
    private const ALGORITHM = 'sodium-secretbox';

    private readonly Opaque $key;
    private readonly string $keyId;

    public function __construct(#[\SensitiveParameter] string $key)
    {
        if (SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== \strlen($key)) {
            throw new CipherFailure(sprintf('A secret box key is %d bytes long.', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        }
        $this->key = Opaque::fromString($key);
        $this->keyId = KeyId::of($key);
    }

    public function algorithm(): string
    {
        return self::ALGORITHM;
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function encrypt(Opaque $clear): Ciphered
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return new Ciphered(self::ALGORITHM, $this->keyId, $nonce, sodium_crypto_secretbox(disclose($clear), $nonce, disclose($this->key)));
    }

    public function decrypt(Ciphered $ciphered): Opaque
    {
        KeyId::expect($ciphered, self::ALGORITHM, $this->keyId);
        if (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES !== \strlen($ciphered->nonce)) {
            throw new CipherFailure('The secret box nonce has the wrong length.');
        }
        $clear = sodium_crypto_secretbox_open($ciphered->payload, $ciphered->nonce, disclose($this->key));
        if (false === $clear) {
            throw new CipherFailure('The secret box did not open: wrong key, or tampered payload.');
        }

        return Opaque::fromString($clear);
    }
}
