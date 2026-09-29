<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Opaque;

/**
 * Asymmetric, anonymous encryption (X25519, XSalsa20-Poly1305): the public key ciphers, only the
 * keypair deciphers. A process that only writes secrets holds nothing that reads them back.
 */
final class SodiumSealedBox implements Cipher
{
    private const ALGORITHM = 'sodium-sealedbox';

    private readonly string $keyId;

    private function __construct(
        private readonly string $publicKey,
        private readonly ?Opaque $keypair,
    ) {
        if (SODIUM_CRYPTO_BOX_PUBLICKEYBYTES !== \strlen($publicKey)) {
            throw new CipherFailure(sprintf('A sealed box public key is %d bytes long.', SODIUM_CRYPTO_BOX_PUBLICKEYBYTES));
        }
        $this->keyId = KeyId::of($publicKey);
    }

    public static function forPublicKey(string $publicKey): self
    {
        return new self($publicKey, null);
    }

    public static function fromKeypair(#[\SensitiveParameter] string $keypair): self
    {
        if (SODIUM_CRYPTO_BOX_KEYPAIRBYTES !== \strlen($keypair)) {
            throw new CipherFailure(sprintf('A sealed box keypair is %d bytes long.', SODIUM_CRYPTO_BOX_KEYPAIRBYTES));
        }

        return new self(sodium_crypto_box_publickey($keypair), Opaque::fromString($keypair));
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
        return new Ciphered(self::ALGORITHM, $this->keyId, '', sodium_crypto_box_seal(disclose($clear), $this->publicKey));
    }

    public function decrypt(Ciphered $ciphered): Opaque
    {
        KeyId::expect($ciphered, self::ALGORITHM, $this->keyId);
        if (null === $this->keypair) {
            throw new CipherFailure('This sealed box holds only the public key: it cannot decipher.');
        }
        $clear = sodium_crypto_box_seal_open($ciphered->payload, disclose($this->keypair));
        if (false === $clear) {
            throw new CipherFailure('The sealed box did not open: wrong keypair, or tampered payload.');
        }

        return Opaque::fromString($clear);
    }
}
