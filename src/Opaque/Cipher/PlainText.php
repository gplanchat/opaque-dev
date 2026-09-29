<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;

use function Gplanchat\Opaque\disclose;

use Gplanchat\Opaque\Opaque;

/**
 * UNSECURED: the payload is the clear text. For development and tests, where no key exists; a
 * keyring holding it accepts clear rows.
 */
final class PlainText implements Cipher
{
    private const ALGORITHM = 'plain';

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
        return new Ciphered(self::ALGORITHM, '', '', disclose($clear));
    }

    public function decrypt(Ciphered $ciphered): Opaque
    {
        KeyId::expect($ciphered, self::ALGORITHM, '');

        return Opaque::fromString($ciphered->payload);
    }
}
