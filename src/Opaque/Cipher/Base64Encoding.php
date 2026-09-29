<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Opaque;

/**
 * UNSECURED: an encoding, not a cipher. Anyone who reads the row reads the secret. For development
 * and tests, where no key exists; a keyring holding it accepts clear rows.
 */
final class Base64Encoding implements Cipher
{
    private const ALGORITHM = 'base64';

    public function algorithm(): string
    {
        return self::ALGORITHM;
    }

    public function keyId(): string
    {
        return '';
    }

    public function encrypt(Opaque $clear): Ciphered
    {
        return new Ciphered(self::ALGORITHM, '', '', base64_encode(disclose($clear)));
    }

    public function decrypt(Ciphered $ciphered): Opaque
    {
        KeyId::expect($ciphered, self::ALGORITHM, '');
        $clear = base64_decode($ciphered->payload, true);
        if (false === $clear) {
            throw new CipherFailure('The payload is not base64.');
        }

        return Opaque::fromString($clear);
    }
}
