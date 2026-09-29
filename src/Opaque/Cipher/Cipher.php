<?php

declare(strict_types=1);

namespace Gplanchat\Opaque\Cipher;

use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\CipherFailure;
use Gplanchat\Opaque\Opaque;

/**
 * Turns an opaque value into a ciphered one and back (OPQ001). The clear text never crosses this
 * interface as a string.
 */
interface Cipher
{
    public function algorithm(): string;

    /**
     * Names the key without giving it away; empty for an unkeyed encoding.
     */
    public function keyId(): string;

    public function encrypt(Opaque $clear): Ciphered;

    /**
     * @throws CipherFailure when the row is not this cipher's, or does not decipher
     */
    public function decrypt(Ciphered $ciphered): Opaque;
}
