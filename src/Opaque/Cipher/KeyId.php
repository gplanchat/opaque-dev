<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;

/**
 * @internal
 */
final class KeyId
{
    /**
     * A truncated, domain-separated hash: enough to tell keys apart on a ring, useless to find one.
     */
    public static function of(#[\SensitiveParameter] string $key): string
    {
        return substr(hash('sha256', 'gplanchat/opaque key id' . "\0" . $key), 0, 16);
    }

    public static function expect(Ciphered $ciphered, string $algorithm, string $keyId): void
    {
        if ($ciphered->algorithm !== $algorithm || $ciphered->keyId !== $keyId) {
            throw new CipherFailure(sprintf(
                'This row was written by "%s" with key "%s", not by "%s" with key "%s".',
                $ciphered->algorithm,
                $ciphered->keyId,
                $algorithm,
                $keyId,
            ));
        }
    }
}
