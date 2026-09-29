<?php

declare(strict_types=1);

namespace Gplanchat\Opaque;

use Gplanchat\Opaque\Cipher\Cipher;

/**
 * The current cipher, and the ones that wrote older rows. Encrypts with the current one; deciphers
 * a row with the cipher whose algorithm and key id it names, and refuses a row none of them wrote.
 */
final class Keyring
{
    /** @var list<Cipher> */
    private readonly array $previous;

    public function __construct(
        private readonly Cipher $current,
        Cipher ...$previous,
    ) {
        $this->previous = array_values($previous);
    }

    public function encrypt(Opaque $clear): Ciphered
    {
        return $this->current->encrypt($clear);
    }

    /**
     * @template T of Opaque
     *
     * @param class-string<T> $as the opaque type to restore
     *
     * @return T
     */
    public function decrypt(Ciphered $ciphered, string $as = Opaque::class): Opaque
    {
        foreach ([$this->current, ...$this->previous] as $cipher) {
            if ($cipher->algorithm() === $ciphered->algorithm && $cipher->keyId() === $ciphered->keyId) {
                $opaque = $cipher->decrypt($ciphered);

                return $opaque instanceof $as ? $opaque : $as::fromString(namespace\disclose($opaque));
            }
        }

        throw new CipherFailure(sprintf('No key on this ring wrote this row ("%s", key "%s").', $ciphered->algorithm, $ciphered->keyId));
    }
}
